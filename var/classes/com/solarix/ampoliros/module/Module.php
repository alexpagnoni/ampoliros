<?php
/*
 *
 *                    Ampoliros Application Server
 *
 *                      http://www.ampoliros.com
 *
 *
 *
 *   Copyright (C) 2000-2004 Solarix
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
// $Id: Module.php,v 1.9 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.module');

import('com.solarix.ampoliros.db.DBLayer');
import('com.solarix.ampoliros.module.ModuleElementFactory');
import('carthag.io.archive.Archive');
import('com.solarix.ampoliros.module.ModuleDep');
import('com.solarix.ampoliros.security.SecurityLayer');
import('com.solarix.ampoliros.datatransfer.cache.CacheGarbageCollector');
import('com.solarix.ampoliros.util.Hook');
import('com.solarix.ampoliros.module.XMLDefFile');

OpenLibrary('configman.library');

/*!
 @class Module

 @abstract Module handling.

 @discussion Handles modules.
 */
class Module extends Object {
    /*! @public ampdb dblayer class - Ampoliros database handler. */
    public $ampdb;
    /*! @public sitedb dblayer class - Site database handler. */
    public $sitedb;
    /*! @public modname string - Module id name. */
    public $modname;
    /*! @public unmetdeps array - Array of unmet dependencies. */
    public $unmetdeps = array();
    /*! @public unmetsuggs array - Array of unmet suggestions. */
    public $unmetsuggs = array();
    /*! @public eltypes array - Module element types. */
    public $eltypes;
    /*! @public serial int - Module serial. */
    public $serial;
    /*! @public onlyextension bool - True if the module is an extension only module. */
    public $onlyextension = TRUE;
    public $basedir;

    const INSTALL_MODE_INSTALL = 0;
    const INSTALL_MODE_UNINSTALL = 1;
    const INSTALL_MODE_UPDATE = 2;
    const INSTALL_MODE_ENABLE = 3;
    const INSTALL_MODE_DISABLE = 4;

    const UPDATE_MODE_ADD = 0;
    const UPDATE_MODE_REMOVE = 1;
    const UPDATE_MODE_CHANGE = 2;

    const STRUCTURE_FILE = 'structure.xml';
    const GENERALDEF_FILE = 'general.def';
    const BUNDLEDEF_FILE = 'bundle.def';

    /*!
     @function Module
    
     @abstract Module constructor.
    
     @param ampdb dblayer class - Ampoliros database handler.
     @param modserial int - serial number of the module.
     */
    function Module(DBLayer $ampdb, $modserial = 0) {
        $this -> ampdb = $ampdb;
        $this -> serial = $modserial;
        $this -> eltypes = new ModuleElementFactory($this -> ampdb);
        $this -> eltypes -> FillTypes();
        if (!get_cfg_var('safe_mode'))
            set_time_limit(0);
    }

    /*!
     @function Install
    
     @abstract Install a module.
    
     @discussion If the module has been already installed, it will be updated.
    
     @param tmpfilepath string - Full path of the temporary module file.
    
     @result True if the module has been installed.
    */
    function Install($tmpfilepath, $updateOnce = false) {
        $result = FALSE;

        import('com.solarix.ampoliros.core.Ampoliros');
        $amp = Ampoliros :: instance('Ampoliros');

        if ($amp -> GetState() == Ampoliros :: STATE_DEBUG) {
            $GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> Mark('moduleinstallstart');
        }

        if (file_exists($tmpfilepath)) {
            import('carthag.io.archive.Archive');

            // Moves temp file to modules repository and extracts it
            //
            $fname = MODULE_PATH.basename($tmpfilepath);
            @ copy($tmpfilepath, $fname);
            $basetmpdir = $tmpdir = TMP_PATH.'modinst/'.md5(microtime());
            @ mkdir($tmpdir, 0755);
            $olddir = getcwd();
            @ chdir($tmpdir);
            //@system( escapeshellcmd( 'tar zxf '.$fname ) );

            $archive_format = ARCHIVE_TGZ;

            if (substr($fname, -4) == '.zip')
                $archive_format = ARCHIVE_ZIP;

            $mod_archive = new Archive($fname, $archive_format);
            $mod_archive -> Extract($tmpdir);

            // Checks if the files are into a directory instead of the root
            //
            if (!@ is_dir($tmpdir.'/defs')) {
                $dhandle = opendir($tmpdir);
                while (FALSE != ($file = readdir($dhandle))) {
                    if ($file != '.' && $file != '..' && is_dir($tmpdir.'/'.$file.'/defs')) {
                        $tmpdir = $tmpdir.'/'.$file;
                    }
                }
                closedir($dhandle);
            }

            $this -> basedir = $tmpdir;

            // Checks for definition and structure files
            //
            if (file_exists($tmpdir.'/defs/'.Module :: BUNDLEDEF_FILE)) {
                $modules_array = file($tmpdir.'/defs/'.Module :: BUNDLEDEF_FILE);
                $result = TRUE;

                while (list (, $module) = each($modules_array)) {
                    $module = trim($module);
                    if (strlen($module) and file_exists($tmpdir.'/modules/'.$module)) {
                        $temp_module = new Module($this -> ampdb);
                        if (!$temp_module -> Install($tmpdir.'/modules/'.$module))
                            $result = FALSE;
                    }
                }
            } else
                if (file_exists($tmpdir.'/defs/'.Module :: STRUCTURE_FILE) and file_exists($tmpdir.'/defs/'.Module :: GENERALDEF_FILE)) {
                    $genconfig = new ConfigFile($tmpdir.'/defs/'.Module :: GENERALDEF_FILE);
                    $this -> modname = $genconfig -> Value('MODULEIDNAME');

                    // Checks if the module has been already installed
                    //
                    $tmpquery = $this -> ampdb -> Execute('SELECT id,modfile FROM modules WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));
                    if (!$tmpquery -> NumRows()) {
                        // Module is new, so it will be installed
                        //

                        // Dependencies check
                        //
                        $this -> unmetdeps = array();
                        $this -> unmetsuggs = array();

                        $moddeps = new ModuleDep($this -> ampdb);
                        $deps = $moddeps -> ExplodeDeps($genconfig -> Value('MODULEDEPENDENCIES'));
                        $suggs = $moddeps -> ExplodeDeps($genconfig -> Value('MODULESUGGESTIONS'));

                        if ($deps != FALSE)
                            $this -> unmetdeps = $moddeps -> CheckModuleDeps(0, '', $deps);
                        else
                            $this -> unmetdeps == FALSE;

                        // Suggestions check
                        //
                        if ($suggs != FALSE) {
                            $unmetsuggs = $moddeps -> CheckModuleDeps(0, '', $suggs);
                            if (is_array($unmetsuggs))
                                $this -> unmetsuggs = $unmetsuggs;
                        }

                        // If dependencies are ok, go on
                        //
                        if ($this -> unmetdeps == FALSE) {
                            // Gets serial number for the module
                            //
                            $this -> serial = $this -> ampdb -> NextSeqValue('modules_id_seq');
                            $this -> ampdb -> Execute('INSERT INTO modules VALUES ( '.$this -> serial.','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEIDNAME')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEVERSION')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEDATE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEDESCRIPTION')).','.$this -> ampdb -> Format_Text(basename($tmpfilepath)).','.$this -> ampdb -> Format_Text($this -> ampdb -> fmtfalse).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR_SITE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_SUPPORT_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_BUGS_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_COPYRIGHT')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_LICENSE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_LICENSE_FILE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_MAINTAINER')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_MAINTAINER_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_CATEGORY')).')');

                            // Module dir creation
                            //
                            @ mkdir(MODULE_PATH.$genconfig -> Value('MODULEIDNAME'), 0755);

                            // Defs files
                            //
                            if ($dhandle = @ opendir($tmpdir.'/defs')) {
                                while (FALSE != ($file = readdir($dhandle))) {
                                    if ($file != '.' && $file != '..' && is_file($tmpdir.'/defs/'.$file)) {
                                        @ copy($tmpdir.'/defs/'.$file, MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.$file);
                                    }
                                }
                                closedir($dhandle);
                            }

                            // Adds modules dependencies
                            //
                            $moddeps -> AddDepsArray($genconfig -> Value('MODULEIDNAME'), $deps, DEPTYPE_DEP);
                            $moddeps -> AddDepsArray($genconfig -> Value('MODULEIDNAME'), $suggs, DEPTYPE_SUGG);

                            $this -> SetSubModules(explode(',', trim($genconfig -> Value('MODULE_SUBMODULES'), ' ,')));

                            $this -> HandleStructure($tmpdir.'/defs/'.Module :: STRUCTURE_FILE, Module :: INSTALL_MODE_INSTALL, $tmpdir);

                            if (strlen($genconfig -> Value('MODULE_LICENSE_FILE')) and file_exists($tmpdir.'/'.$genconfig -> Value('MODULE_LICENSE_FILE')))
                                @ copy($tmpdir.'/'.$genconfig -> Value('MODULE_LICENSE_FILE'), MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.$genconfig -> Value('MODULE_LICENSE_FILE'));

                            // Checks if it is an extension module
                            //
                            $genconfig = new ConfigFile($tmpdir.'/defs/'.Module :: GENERALDEF_FILE);

                            $ext = $this -> ampdb -> fmtfalse;

                            if ($genconfig -> Value('ONLYEXTENSION') == 'y') {
                                $ext = $this -> ampdb -> fmttrue;
                                $this -> onlyextension = TRUE;
                            } else
                                if ($genconfig -> Value('ONLYEXTENSION') == 'n') {
                                    $ext = $this -> ampdb -> fmtfalse;
                                    $this -> onlyextension = FALSE;
                                } else
                                    if ($this -> onlyextension) {
                                        $ext = $this -> ampdb -> fmttrue;
                                    }

                            $this -> ampdb -> Execute('UPDATE modules SET onlyextension='.$this -> ampdb -> Format_Text($ext).' WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));
                            $result = TRUE;

                            if ($GLOBALS['gEnv']['core']['config'] -> Value('ALERT_ON_MODULE_OPERATION') == '1') {
                                import('com.solarix.ampoliros.security.SecurityLayer');

                                $amp_security = new SecurityLayer();
                                $amp_security -> SendAlert('Module '.$this -> modname.' has been installed');
                                unset($amp_security);
                            }

                            if ($result == true) {
                                if ($GLOBALS['gEnv']['core']['edition'] == AMP_EDITION_ENTERPRISE and $this -> modname != 'ampoliros' and $ext != $this -> ampdb -> fmttrue) {
                                    $sites_query = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT id FROM sites');
                                    if ($sites_query -> NumRows()) {
                                        $this -> Enable($sites_query -> Fields('id'));
                                    }
                                }
                                import('com.solarix.ampoliros.io.log.Logger');
                                $log = new Logger(AMP_LOG);
                                $log -> LogEvent('Ampoliros', 'Installed module '.$this -> modname, LOGGER_NOTICE);
                            }
                        }
                    } else {
                        $moddata = $tmpquery -> Fields();
                        $this -> serial = $moddata['id'];

                        // Module will be updated
                        //
                        if ($this -> serial) {
                            // Dependencies check
                            //
                            $this -> unmetdeps = array();
                            $this -> unmetsuggs = array();
                            $moddeps = new ModuleDep($this -> ampdb);
                            $deps = $moddeps -> ExplodeDeps($genconfig -> Value('MODULEDEPENDENCIES'));
                            $suggs = $moddeps -> ExplodeDeps($genconfig -> Value('MODULESUGGESTIONS'));

                            if ($deps != FALSE)
                                $this -> unmetdeps = $moddeps -> CheckModuleDeps(0, '', $deps);
                            else
                                $this -> unmetdeps == FALSE;

                            // Suggestions check
                            //
                            if ($suggs != FALSE) {
                                $unmetsuggs = $moddeps -> CheckModuleDeps(0, '', $suggs);
                                if (is_array($unmetsuggs))
                                    $this -> unmetsuggs = $unmetsuggs;
                            }

                            // If dependencies are ok, go on
                            //
                            if ($this -> unmetdeps == FALSE) {
                                // Creates lock file
                                //
                                touch(TMP_PATH.'.upgrading_system');

                                // :WARNING: evil 20020506: possible problems on Windows systems
                                // It has a 'permission denied'.

                                // Removes old module file
                                //
                                if ((basename($fname) != $moddata['modfile']) and (file_exists(MODULE_PATH.$moddata['modfile'])))
                                    @ unlink(MODULE_PATH.$moddata['modfile']);

                                // Updates modules table
                                //
                                $this -> ampdb -> Execute('UPDATE modules SET modversion='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEVERSION')).', moddate='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEDATE')).', moddesc='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEDESCRIPTION')).', modfile='.$this -> ampdb -> Format_Text(basename($tmpfilepath)).', author='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR')).', authoremail='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR_EMAIL')).', authorsite='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR_SITE')).', supportemail='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_SUPPORT_EMAIL')).', bugsemail='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_BUGS_EMAIL')).', copyright='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_COPYRIGHT')).', license='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_LICENSE')).', licensefile='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_LICENSE_FILE')).', maintainer='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_MAINTAINER')).', maintaineremail='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_MAINTAINER_EMAIL')).', category='.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_CATEGORY')).' WHERE id='. (int) $this -> serial);
                                $genconfig = new ConfigFile($tmpdir.'/defs/'.Module :: GENERALDEF_FILE);

                                // Script files - only before handlestructure
                                //
                                if ($dhandle = @ opendir($tmpdir.'/defs')) {
                                    while (FALSE != ($file = readdir($dhandle))) {
                                        if ($file != '.' and $file != '..' and $file != Module :: STRUCTURE_FILE and $file != Module :: GENERALDEF_FILE and is_file($tmpdir.'/defs/'.$file)) {
                                            @ copy($tmpdir.'/defs/'.$file, MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.$file);
                                        }
                                    }
                                    closedir($dhandle);
                                }

                                $this -> HandleStructure($tmpdir.'/defs/'.Module :: STRUCTURE_FILE, Module :: INSTALL_MODE_UPDATE, $tmpdir);

                                if (strlen($genconfig -> Value('MODULE_LICENSE_FILE')) and file_exists($tmpdir.'/'.$genconfig -> Value('MODULE_LICENSE_FILE')))
                                    @ copy($tmpdir.'/'.$genconfig -> Value('MODULE_LICENSE_FILE'), MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.$genconfig -> Value('MODULE_LICENSE_FILE'));

                                // Defs files - only after handlestructure
                                //
                                @ copy($tmpdir.'/defs/'.Module :: STRUCTURE_FILE, MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.Module :: STRUCTURE_FILE);
                                @ copy($tmpdir.'/defs/'.Module :: GENERALDEF_FILE, MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.Module :: GENERALDEF_FILE);

                                // Checks if it is an extension module
                                //
                                $ext = $this -> ampdb -> fmtfalse;

                                if ($genconfig -> Value('ONLYEXTENSION') == 'y') {
                                    $ext = $this -> ampdb -> fmttrue;
                                    $this -> onlyextension = TRUE;
                                } else
                                    if ($genconfig -> Value('ONLYEXTENSION') == 'n') {
                                        $ext = $this -> ampdb -> fmtfalse;
                                        $this -> onlyextension = FALSE;
                                    } else
                                        if ($this -> onlyextension) {
                                            $ext = $this -> ampdb -> fmttrue;
                                        }

                                $this -> ampdb -> Execute('UPDATE modules SET onlyextension='.$this -> ampdb -> Format_Text($ext).' WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));

                                $this -> SetSubModules(explode(',', trim($genconfig -> Value('MODULE_SUBMODULES'), ' ,')));

                                if ($this -> modname != 'ampoliros') {
                                    // Remove old dependencies
                                    //
                                    $moddeps -> RemAllDep($this -> serial);

                                    // Adds new modules dependencies
                                    //
                                    $moddeps -> AddDepsArray($genconfig -> Value('MODULEIDNAME'), $deps, DEPTYPE_DEP);
                                    $moddeps -> AddDepsArray($genconfig -> Value('MODULEIDNAME'), $suggs, DEPTYPE_SUGG);
                                }

                                $result = TRUE;

                                if (function_exists('apc_reset_cache'))
                                    apc_reset_cache();

                                if ($updateOnce == FALSE) {
                                    $this -> Install($tmpfilepath, true);

                                    // Removes lock file
                                    //
                                    unlink(TMP_PATH.'.upgrading_system');

                                    if ($GLOBALS['gEnv']['core']['config'] -> Value('ALERT_ON_MODULE_OPERATION') == '1') {
                                        Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                                        $amp_security = new SecurityLayer();
                                        $amp_security -> SendAlert('Module '.$this -> modname.' has been updated');
                                        unset($amp_security);
                                    }

                                    if ($result == TRUE) {
                                        import('com.solarix.ampoliros.io.log.Logger');
                                        $log = new Logger(AMP_LOG);
                                        $log -> LogEvent('Ampoliros', 'Updated module '.$this -> modname, LOGGER_NOTICE);
                                    }
                                }
                            }
                            /*
                            else $this->mLog->LogEvent( 'ampoliros.modules_library.modules_class.install',
                            'Structure definition file for module '.$this->modname.' does not exists', LOGGER_ERROR );
                            */
                        } else {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger(AMP_LOG);

                            $log -> LogEvent('ampoliros.modules_library.modules_class.install', 'Empty module serial', LOGGER_ERROR);
                        }
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);

                    if (!file_exists($tmpdir.'/defs/'.Module :: STRUCTURE_FILE))
                        $log -> LogEvent('ampoliros.modules_library.modules_class.install', 'Module structure file '.$tmpdir.'/defs/'.Module :: STRUCTURE_FILE.' not found', LOGGER_ERROR);

                    if (!file_exists($tmpdir.'/defs/'.Module :: GENERALDEF_FILE))
                        $log -> LogEvent('ampoliros.modules_library.modules_class.install', 'Module definition file '.$tmpdir.'/defs/'.Module :: GENERALDEF_FILE.' not found', LOGGER_ERROR);
                }

            // Cleans up temp stuff
            //
            @ chdir($olddir);
            RecRemoveDir($basetmpdir);
            if (file_exists($tmpfilepath))
                @ unlink($tmpfilepath);
        } else {
            if (!file_exists($tmpfilepath)) {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.modules_class.install', 'Temporary module file ('.$tmpfilepath.') does not exists', LOGGER_ERROR);
            }
        }

        if ($amp -> getState() == Ampoliros :: STATE_DEBUG) {
            $GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> Mark('moduleinstallend');
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);

            $log -> LogEvent('ampoliros.modules_library.module_class.install', 'Module installation load time: '.$GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> GetSectionLoad('moduleinstallend'), LOGGER_DEBUG);
        }

        return $result;
    }

    /*!
     @function Uninstall
    
     @abstract Uninstall a module.
    
     @result True if successfully uninstalled.
     */
    function Uninstall() {
        $result = FALSE;

        if ($this -> serial) {
            // Checks if the module exists in modules table
            //
            $modquery = & $this -> ampdb -> Execute('SELECT * FROM modules WHERE id='. (int) $this -> serial);

            if ($modquery -> NumRows() == 1) {
                $moddata = $modquery -> Fields();

                // Checks if the module is Ampoliros itself
                //
                if ($moddata['modid'] != 'ampoliros') {
                    // Checks if the structure file still exists
                    //
                    if (file_exists(MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE)) {
                        $this -> modname = $moddata['modid'];

                        // Checks if there are depengind modules
                        //
                        $moddeps = new ModuleDep($this -> ampdb);
                        $pendingdeps = $moddeps -> CheckDependingModules($moddata['modid']);

                        // If dependencies are ok, go on
                        //
                        if ($pendingdeps == FALSE) {
                            if ($moddata['onlyextension'] != $this -> ampdb -> fmttrue)
                                $this -> DisableToAllSites($moddata['modid']);

                            $this -> HandleStructure(MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE, Module :: INSTALL_MODE_UNINSTALL, TMP_PATH.'modinst/');

                            // Removes module archive and directory
                            //
                            if (file_exists(MODULE_PATH.$moddata['modfile']))
                                @ unlink(MODULE_PATH.$moddata['modfile']);
                            RecRemoveDir(MODULE_PATH.$moddata['modid']);

                            // Module rows in modules table
                            //
                            $this -> ampdb -> Execute('DELETE FROM modules WHERE id='. (int) $this -> serial);

                            // Remove cached items
                            //
                            Carthag :: import('com.solarix.ampoliros.datatransfer.cache.CacheGarbageCollector');
                            $cache_gc = new CacheGarbageCollector();
                            $cache_gc -> RemoveModuleItems($moddata['modid']);

                            // Remove dependencies
                            //
                            $moddeps -> RemallDep($this -> serial);
                            $this -> serial = 0;
                            $result = true;

                            if ($GLOBALS['gEnv']['core']['config'] -> Value('ALERT_ON_MODULE_OPERATION') == '1') {
                                Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                                $amp_security = new SecurityLayer();
                                $amp_security -> SendAlert('Module '.$moddata['modid'].' has been removed');
                                unset($amp_security);
                            }
                        } else {
                            $this -> unmetdeps = $pendingdeps;
                        }

                        if ($result == TRUE) {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger(AMP_LOG);
                            $log -> LogEvent('Ampoliros', 'Uninstalled module '.$this -> modname, LOGGER_NOTICE);
                        }
                    } else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('ampoliros.modules_library.modules_class.uninstall', 'Structure file '.MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE.' for module '.$moddata['modid'].' was not found', LOGGER_ERROR);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.modules_library.modules_class.uninstall', 'Cannot uninstall Ampoliros', LOGGER_ERROR);
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);

                $log -> LogEvent('ampoliros.modules_library.modules_class.uninstall', 'A module with serial '.$this -> serial.' was not found in modules table', LOGGER_ERROR);
            }

            $modquery -> Free();
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.modules_class.uninstall', 'Empty module serial', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @function Update
    
     @abstract Updates a module.
    
     @discussion Alias for Module->Install.
    
     @param tmpfilepath string - Full path of the temporary module file.
    
     @result True if successfully updated.
     */
    function Update($tmpfilepath) {
        return $this -> Install($tmpfilepath);
    }

    /*!
     @function Setup
    
     @abstract Setup Ampoliros structure.
    
     @discussion This method is called only once at Ampoliros setup phase.
    
     @param tmpdir string - temporary directory.
    
     @result True if it's all right.
     */
    function Setup($tmpdir) {
        $result = FALSE;

        // Checks for definition and structure files
        //
        if (file_exists($tmpdir.'defs/'.Module :: STRUCTURE_FILE) and file_exists($tmpdir.'defs/'.Module :: GENERALDEF_FILE)) {
            $genconfig = new ConfigFile($tmpdir.'defs/'.Module :: GENERALDEF_FILE);
            $this -> modname = $genconfig -> Value('MODULEIDNAME');

            // Checks if Ampoliros has been already installed
            //
            $tmpquery = $this -> ampdb -> Execute('SELECT id FROM modules WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));

            if (!$tmpquery -> NumRows()) {
                // Gets serial number for the module
                //
                $this -> serial = $this -> ampdb -> NextSeqValue('modules_id_seq');

                if ($this -> ampdb -> Execute('INSERT INTO modules VALUES ( '.$this -> serial.','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEIDNAME')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEVERSION')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEDATE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULEDESCRIPTION')).','.$this -> ampdb -> Format_Text(basename($tmpfilepath)).','.$this -> ampdb -> Format_Text($this -> ampdb -> fmtfalse).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_AUTHOR_SITE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_SUPPORT_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_BUGS_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_COPYRIGHT')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_LICENSE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_LICENSE_FILE')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_MAINTAINER')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_MAINTAINER_EMAIL')).','.$this -> ampdb -> Format_Text($genconfig -> Value('MODULE_CATEGORY')).')')) {
                    // Module dir creation
                    //
                    if (!file_exists(MODULE_PATH.$genconfig -> Value('MODULEIDNAME')))
                        @ mkdir(MODULE_PATH.$genconfig -> Value('MODULEIDNAME'), 0755);

                    // Defs files
                    //
                    if ($dhandle = @ opendir($tmpdir.'/defs')) {
                        while (FALSE != ($file = readdir($dhandle))) {
                            if ($file != '.' && $file != '..' && is_file($tmpdir.'/defs/'.$file)) {
                                @ copy($tmpdir.'/defs/'.$file, MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.$file);
                            }
                        }
                        closedir($dhandle);
                    }

                    $result = $this -> HandleStructure($tmpdir.'defs/'.Module :: STRUCTURE_FILE, Module :: INSTALL_MODE_INSTALL, $tmpdir, 0, TRUE);

                    if (strlen($genconfig -> Value('MODULE_LICENSE_FILE')) and file_exists($tmpdir.'/'.$genconfig -> Value('MODULE_LICENSE_FILE')))
                        @ copy($tmpdir.'/'.$genconfig -> Value('MODULE_LICENSE_FILE'), MODULE_PATH.$genconfig -> Value('MODULEIDNAME').'/'.$genconfig -> Value('MODULE_LICENSE_FILE'));

                    else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('Ampoliros', 'Unable to install Ampoliros', LOGGER_ERROR);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.modules_library.modules_class.setup', 'Unable to insert Ampoliros module row in modules table', LOGGER_ERROR);
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.modules_class.setup', 'Attempted to resetup Ampoliros', LOGGER_ERROR);
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);

            if (!file_exists($tmpdir.'defs/'.Module :: STRUCTURE_FILE))
                $log -> LogEvent('ampoliros.modules_library.modules_class.setup', 'Ampoliros structure file '.$tmpdir.'defs/'.Module :: STRUCTURE_FILE.' not found', LOGGER_ERROR);

            if (!file_exists($tmpdir.'defs/'.Module :: GENERALDEF_FILE))
                $log -> LogEvent('ampoliros.modules_library.modules_class.setup', 'Ampoliros definition file '.$tmpdir.'defs/'.Module :: GENERALDEF_FILE.' not found', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @function Enable
    
     @abstract Enables a module to a site.
    
     @param siteid string - id name of the site.
    
     @result True if successfully enabled.
     */
    function Enable($siteid) {
        $result = FALSE;
        import('com.solarix.ampoliros.util.Hook');
        $hook = new Hook($this -> ampdb, 'ampoliros', 'module.enable');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $siteid, 'modserial' => $this -> serial)) == HOOK_RESULT_OK) {
            if ($this -> serial) {
                // Checks if the module exists in modules table
                //
                $modquery = & $this -> ampdb -> Execute('SELECT * FROM modules WHERE id='. (int) $this -> serial);

                if ($modquery -> NumRows() == 1) {
                    $moddata = $modquery -> Fields();

                    if ($moddata['onlyextension'] != $this -> ampdb -> fmttrue) {
                        // Checks if the structure file still exists
                        //
                        if (file_exists(MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE)) {
                            $this -> modname = $moddata['modid'];

                            $sitequery = & $this -> ampdb -> Execute('SELECT * FROM sites WHERE id='.$this -> ampdb -> Format_Text((int) $siteid));
                            $sitedata = $sitequery -> Fields();

                            $args['dbtype'] = $sitedata['sitedbtype'];
                            $args['dbname'] = $sitedata['sitedbname'];
                            $args['dbhost'] = $sitedata['sitedbhost'];
                            $args['dbport'] = $sitedata['sitedbport'];
                            $args['dbuser'] = $sitedata['sitedbuser'];
                            $args['dbpass'] = $sitedata['sitedbpassword'];
                            $args['dblog'] = $sitedata['sitedblog'];

                            import('com.solarix.ampoliros.db.DBLayerFactory');
                            $db_fact = new DBLayerFactory();
                            $this -> sitedb = $db_fact -> NewDBLayer($args);
                            $this -> sitedb -> Connect($args);

                            // Dependencies check
                            //
                            $this -> unmetdeps = array();
                            $this -> unmetsuggs = array();

                            $moddeps = new ModuleDep($this -> ampdb);
                            $modenabled = $moddeps -> IsEnabled($this -> modname, $sitedata['siteid']);

                            $unmetdeps = $moddeps -> CheckSiteModuleDeps($this -> modname, $sitedata['siteid'], DEPTYPE_DEP);
                            $unmetsuggs = $moddeps -> CheckSiteModuleDeps($this -> modname, $sitedata['siteid'], DEPTYPE_SUGG);

                            // Suggestions check
                            //
                            if (is_array($unmetsuggs))
                                $this -> unmetsuggs = $unmetsuggs;

                            // If dependencies are ok, go on
                            //
                            if ($unmetdeps == FALSE and !$modenabled) {
                                $result = $this -> HandleStructure(MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE, Module :: INSTALL_MODE_ENABLE, MODULE_PATH.$moddata['modid'].'/', $siteid);
                                $modquery = $this -> ampdb -> Execute('SELECT id FROM modules WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));
                                $this -> ampdb -> Execute('INSERT INTO activemodules VALUES ('.$this -> serial.','.$this -> ampdb -> Format_Text($siteid).','.$this -> ampdb -> Format_Date(time()).','.$this -> ampdb -> Format_Date(time()).','.$this -> ampdb -> Format_Text($this -> ampdb -> fmttrue).')');

                                if ($GLOBALS['gEnv']['core']['config'] -> Value('ALERT_ON_MODULE_SITE_OPERATION') == '1') {
                                    Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                                    $amp_security = new SecurityLayer();
                                    $amp_security -> SendAlert('Module '.$moddata['modid'].' has been enabled to site '.$sitedata['siteid']);
                                    unset($amp_security);
                                }

                                if ($hook -> CallHooks('moduleenabled', $this, array('siteserial' => $siteid, 'modserial' => $this -> serial)) != HOOK_RESULT_OK)
                                    $result = false;
                            } else {
                                $this -> unmetdeps = $unmetdeps;
                            }
                            //if ( $result == TRUE ) $this->mLog->LogEvent( 'Ampoliros', 'Uninstalled module '.$this->modname, LOGGER_NOTICE );

                            $sitequery -> Free();
                        } else {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger(AMP_LOG);
                            $log -> LogEvent('ampoliros.modules_library.modules_class.enable', 'Structure file '.MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE.' for module '.$moddata['modid'].' was not found', LOGGER_ERROR);
                        }
                    } else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('ampoliros.modules_library.modules_class.enable', 'Tried to enable module '.$moddata['modid'].', but it is an extension only module', LOGGER_ERROR);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.modules_library.modules_class.enable', 'A module with serial '.$this -> serial.' was not found in modules table', LOGGER_ERROR);
                }
                $modquery -> Free();
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.modules_class.enable', 'Empty module serial', LOGGER_ERROR);
            }
        }
        return $result;
    }

    /*!
     @function EnableToAllSites
    
     @abstract Enables a module for all sites.
    
     @result True if successfully enabled.
     */
    function EnableToAllSites() {
        $result = FALSE;

        $sitesquery = & $this -> ampdb -> Execute('SELECT id FROM sites');

        if ($sitesquery -> NumRows() > 0) {
            while (!$sitesquery -> eof) {
                $this -> Enable($sitesquery -> Fields('id'));
                $sitesquery -> MoveNext();
            }
        }

        $sitesquery -> Free();

        return $result;
    }

    /*!
     @function Disable
    
     @abstract Disables a module for a site.
    
     @param siteid string - id name of the site.
    
     @result True if successfully disabled.
     */
    function Disable($siteid) {
        $result = FALSE;

		import('com.solarix.ampoliros.util.Hook');
        $hook = new Hook($this -> ampdb, 'ampoliros', 'module.disable');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $siteid, 'modserial' => $this -> serial)) == HOOK_RESULT_OK) {
            if ($this -> serial) {
                // Checks if the module exists in modules table
                //
                $modquery = & $this -> ampdb -> Execute('SELECT * FROM modules WHERE id='. (int) $this -> serial);

                if ($modquery -> NumRows() == 1) {
                    $moddata = $modquery -> Fields();

                    if ($moddata['onlyextension'] != $this -> ampdb -> fmttrue) {
                        // Checks if the structure file still exists
                        //
                        if (file_exists(MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE)) {
                            $this -> modname = $moddata['modid'];

                            $sitequery = $this -> ampdb -> Execute('SELECT * FROM sites WHERE id='.$this -> ampdb -> Format_Text((int) $siteid));
                            $sitedata = $sitequery -> Fields();

                            $args['dbtype'] = $sitedata['sitedbtype'];
                            $args['dbname'] = $sitedata['sitedbname'];
                            $args['dbhost'] = $sitedata['sitedbhost'];
                            $args['dbport'] = $sitedata['sitedbport'];
                            $args['dbuser'] = $sitedata['sitedbuser'];
                            $args['dbpass'] = $sitedata['sitedbpassword'];
                            $args['dblog'] = $sitedata['sitedblog'];

                            import('com.solarix.ampoliros.db.DBLayerFactory');
                            $db_fact = new DBLayerFactory();
                            $this -> sitedb = $db_fact -> NewDBLayer($args);
                            $this -> sitedb -> Connect($args);

                            // Dependencies check
                            //
                            $this -> unmetdeps = array();
                            $this -> unmetsuggs = array();
                            $moddeps = new ModuleDep($this -> ampdb);
                            $pendingdeps = $moddeps -> CheckSiteDependingModules($this -> modname, $sitedata['siteid'], FALSE);
                            $modenabled = $moddeps -> IsEnabled($this -> modname, $sitedata['siteid']);

                            // If dependencies are ok, go on
                            //
                            if (($pendingdeps == FALSE) and ($modenabled == TRUE)) {
                                $result = $this -> HandleStructure(MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE, Module :: INSTALL_MODE_DISABLE, MODULE_PATH.$moddata['modid'].'/', $siteid);

                                $modquery = $this -> ampdb -> Execute('SELECT id FROM modules WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));
                                $this -> ampdb -> Execute('DELETE FROM activemodules WHERE moduleid='. (int) $this -> serial.' AND siteid='.$this -> ampdb -> Format_Text($siteid));
                                $this -> ampdb -> Execute('DELETE FROM disabledsubmodules WHERE moduleid='. (int) $this -> serial.' AND siteid='. (int) $siteid);

                                if ($GLOBALS['gEnv']['core']['config'] -> Value('ALERT_ON_MODULE_SITE_OPERATION') == '1') {
                                    Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');
                                    $amp_security = new SecurityLayer();
                                    $amp_security -> SendAlert('Module '.$moddata['modid'].' has been disabled from site '.$sitedata['siteid']);
                                    unset($amp_security);
                                }

                                if ($hook -> CallHooks('moduledisabled', $this, array('siteserial' => $siteid, 'modserial' => $this -> serial)) != HOOK_RESULT_OK)
                                    $result = false;
                            } else
                                if ($modenabled == FALSE) {
                                } else {
                                    $this -> unmetdeps = $pendingdeps;
                                }
                            //if ( $result == TRUE ) $this->mLog->LogEvent( 'Ampoliros', 'Uninstalled module '.$this->modname, LOGGER_NOTICE );

                            $sitequery -> Free();
                        } else {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger(AMP_LOG);
                            $log -> LogEvent('ampoliros.modules_library.modules_class.disable', 'Structure file '.MODULE_PATH.$moddata['modid'].'/'.Module :: STRUCTURE_FILE.' for module '.$moddata['modid'].' was not found', LOGGER_ERROR);
                        }
                    } else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('ampoliros.modules_library.modules_class.disable', 'Tried to disable module '.$moddata['modid'].', but it is an extension only module', LOGGER_ERROR);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.modules_library.modules_class.disable', 'A module with serial '.$this -> serial.' was not found in modules table', LOGGER_ERROR);
                }
                $modquery -> Free();
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.modules_class.disable', 'Empty module serial', LOGGER_ERROR);
            }
        }

        return $result;
    }

    /*!
     @function DisableToAllSites
    
     @abstract Disables a module for all sites.
    
     @result True if successfully disabled.
     */
    function DisableToAllSites() {
        $result = FALSE;

        $sitesquery = & $this -> ampdb -> Execute('SELECT id FROM sites');

        if ($sitesquery -> NumRows() > 0) {
            while (!$sitesquery -> eof) {
                $this -> Disable($sitesquery -> Fields('id'));
                $sitesquery -> MoveNext();
            }
        }

        $sitesquery -> Free();

        return $result;
    }

    function SetSubModules($subModules) {
        $current_sub_modules = $this -> GetSubModules();

        while (list (, $sub_module_name) = each($subModules)) {
            $sub_module_name = trim($sub_module_name);

            if (strlen($sub_module_name)) {
                $key = array_search($sub_module_name, $current_sub_modules);

                if ($key != false)
                    unset($current_sub_modules[$key]);
                else {
                    $this -> ampdb -> Execute('INSERT INTO submodules VALUES ('.$this -> serial.','.$this -> ampdb -> Format_Text($sub_module_name).')');
                }
            }
        }

        while (list (, $old_sub_module_name) = each($current_sub_modules)) {
            $this -> RemoveSubModule($old_sub_module_name);
        }

        return true;
    }

    function GetSubModules() {
        $result = array();

        $sub_query = & $this -> ampdb -> Execute('SELECT name FROM submodules WHERE moduleid='. (int) $this -> serial.' ORDER BY name');

        $row = 1;
        while (!$sub_query -> eof) {
            $result[$row ++] = $sub_query -> Fields('name');
            $sub_query -> MoveNext();
        }

        $sub_query -> Free();

        return $result;
    }

    function RemoveSubModule($subModule) {
        $this -> ampdb -> Execute('DELETE FROM submodules WHERE moduleid='. (int) $this -> serial.' AND name='.$this -> ampdb -> Format_Text($subModule));
        $this -> ampdb -> Execute('DELETE FROM disabledsubmodules WHERE moduleid='. (int) $this -> serial.' AND submodule='.$this -> ampdb -> Format_Text($subModule));
        return true;
    }

    function EnableSubModule($subModule, $siteId) {
        $this -> ampdb -> Execute('DELETE FROM disabledsubmodules WHERE moduleid='. (int) $this -> serial.' AND siteid='. (int) $siteId.' AND submodule='.$this -> ampdb -> Format_Text($subModule));
        return true;
    }

    function DisableSubModule($subModule, $siteId) {
        if ($this -> CheckIfSubModuleEnabled($subModule, $siteId)) {
            $this -> ampdb -> Execute('INSERT INTO disabledsubmodules VALUES ('.$this -> serial.','.$this -> ampdb -> Format_Text($subModule).','.$siteId.')');
        }
        return true;
    }

    function CheckIfSubModuleEnabled($subModule, $siteId) {
        $result = true;

        $sub_check = $this -> ampdb -> Execute('SELECT submodule FROM disabledsubmodules WHERE moduleid='. (int) $this -> serial.' AND siteid='. (int) $siteId.' AND submodule='.$this -> ampdb -> Format_Text($subModule));
        if ($sub_check -> NumRows())
            $result = false;
        $sub_check -> Free();

        return $result;
    }

    /*!
     @function HandleStructure
    
     @abstract Handles a given structure.
    
     @param deffilepath string - file path.
     @param installmode int - install mode (defined).
     @param tmpdir string - temporary directory.
     @param siteid string - id name of the site.
     @param setup boolean - setup flag.
    
     @result True.
     */
    function HandleStructure($deffilepath, $installmode, $tmpdir, $siteid = 0, $setup = FALSE) {
        $result = FALSE;
        $this -> onlyextension = TRUE;

        // Installation mode depending variables initializazion
        //
        switch ($installmode) {
            case Module :: INSTALL_MODE_INSTALL :
                $sortmode = 'cmp';
                $scriptdir = $tmpdir.'/defs/';
                $prescript = 'generalpreinstall';
                $postscript = 'generalpostinstall';
                break;

            case Module :: INSTALL_MODE_UNINSTALL :
                $sortmode = 'rcmp';
                $scriptdir = $tmpdir.'/defs/';
                $prescript = 'generalpreuninstall';
                $postscript = 'generalpostuninstall';
                break;

            case Module :: INSTALL_MODE_UPDATE :
                $sortmode = 'cmp';
                $scriptdir = $tmpdir.'/defs/';
                $prescript = 'generalpreupdate';
                $postscript = 'generalpostupdate';
                $siteprescript = $sitepostscript = '';
                break;

            case Module :: INSTALL_MODE_ENABLE :
                $sortmode = 'cmp';
                $scriptdir = $tmpdir.'/';
                $prescript = 'sitepreinstall';
                $postscript = 'sitepostinstall';
                break;

            case Module :: INSTALL_MODE_DISABLE :
                $sortmode = 'rcmp';
                $scriptdir = $tmpdir.'/';
                $prescript = 'sitepreuninstall';
                $postscript = 'sitepostuninstall';
                break;

            default :
                break;
        }

        // Parse structure file
        //
        switch ($installmode) {
            case Module :: INSTALL_MODE_UPDATE :
                $structure = $this -> MergeStructureFiles($deffilepath, MODULE_PATH.$this -> modname.'/'.Module :: STRUCTURE_FILE, $tmpdir);
                break;

            default :
                $deffile = new XMLDefFile($this -> ampdb, $tmpdir);
                $deffile -> Load_DefFile($deffilepath);
                $structure = $deffile -> Get_Structure();
        }

        // Sort structure elements by priority
        //
        uksort($structure, array($this, $sortmode));

        // Check for site update scripts

        if (isset($structure['sitepreupdate']))
            $siteprescript = $scriptdir.$structure['sitepreupdate'];
        if (isset($structure['sitepostupdate']))
            $sitepostscript = $scriptdir.$structure['sitepostupdate'];

        // Check for preinstallation jobs
        //
        if (isset($structure[$prescript]) and sizeof($structure[$prescript]))
            include ($scriptdir.$structure[$prescript]);

        // Install elements
        //
        while (list ($eltype, $arraycontent) = each($structure)) {
            // Checks if it is an element and skips scripts
            //
            switch ($eltype) {
                case 'generalpreinstall' :
                case 'generalpreuninstall' :
                case 'generalpostinstall' :
                case 'generalpostuninstall' :
                case 'sitepreinstall' :
                case 'sitepreuninstall' :
                case 'sitepostinstall' :
                case 'sitepostuninstall' :
                case 'generalpreupdate' :
                case 'generalpostupdate' :
                case 'sitepreupdate' :
                case 'sitepostupdate' :
                    break;

                default :
                    // Checks if the element type file exists
                    //
                    if (file_exists(HANDLER_PATH.strtolower($eltype).'.element')) {
                        while (list (, $val) = each($arraycontent)) {
                            // If the element type file was not already included, include it
                            //
                            OpenLibrary(strtolower($eltype).'.element', HANDLER_PATH);

                            // Checks for file and name element attributes
                            //
                            if (empty($val['file'])) {
                                $val['file'] = $val['name'];
                                //$this->mLog->LogEvent( 'ampoliros.modules_library.modules_class.handlestructure',
                                //                      'An element of '.$eltype.' type in '.$this->modname.' module has no file property', LOGGER_WARNING );
                            }
                            if (empty($val['name'])) {
                                $val['name'] = $val['file']; // Should never happen
                                //$this->mLog->LogEvent( 'ampoliros.modules_library.modules_class.handlestructure',
                                //                      'An element of '.$eltype.' type in '.$this->modname.' module has no name property', LOGGER_WARNING );
                            }

                            // Creates a new instance of the element type class and installs the element
                            //
                            $tmpclassname = $this -> eltypes -> types[$eltype]['classname'];
                            //if ( !$tmpclassname ) $tmpclassname = $eltype;
                            if ($tmpclassname) {
                                $tmpelement = new $tmpclassname ($this -> ampdb, $this -> sitedb, $this -> modname, $val['name'], $val['file'], $tmpdir);

                                /*
                                 {
                                 unset( $element );
                                 if ( file_exists( HANDLER_PATH.$data['file'] ) )
                                 {
                                 include( HANDLER_PATH.$data['file'] );
                                 }
                                 else $this->mLog->LogEvent( 'ampoliros.modules_library.moduleelementfactory_class.filltypes', 'Element file '.$data['file'].' doesn't exists in handlers directory', LOGGER_WARNING );
                                
                                 $this->types[$element['type']] = $element;
                                 }
                                 */

                                if ($setup)
                                    $tmpelement -> setup = TRUE;
                                if ($tmpelement -> site == true) {
                                    $this -> onlyextension = FALSE;
                                }

                                // Calls appropriate method
                                //
                                switch ($installmode) {
                                    case Module :: INSTALL_MODE_INSTALL :
                                        $tmpelement -> Install($val);
                                        break;

                                    case Module :: INSTALL_MODE_UNINSTALL :
                                        $tmpelement -> UnInstall($val);
                                        break;

                                    case Module :: INSTALL_MODE_UPDATE :
                                        $tmpelement -> Update($val['updatemode'], $val, $siteprescript, $sitepostscript);
                                        break;

                                    case Module :: INSTALL_MODE_ENABLE :
                                        $tmpelement -> Enable($siteid, $val);
                                        break;

                                    case Module :: INSTALL_MODE_DISABLE :
                                        $tmpelement -> Disable($siteid, $val);
                                        break;

                                    default :
                                        import('com.solarix.ampoliros.io.log.Logger');
                                        $log = new Logger(AMP_LOG);
                                        $log -> LogEvent('ampoliros.modules_library.modules_class.handlestructure', 'Invalid installation method for element of '.$eltype.' type in '.$this -> modname.' module', LOGGER_ERROR);
                                        break;
                                }

                                // There may be changes in element types, so we refill eltypes array
                                //
                                if ($eltype == 'element')
                                    $this -> eltypes -> FillTypes();

                                unset($tmpelement);
                            } else {
                                import('com.solarix.ampoliros.io.log.Logger');
                                $log = new Logger(AMP_LOG);
                                $log -> LogEvent('ampoliros.modules_library.modules_class.handlestructure', 'Element class ('.$tmpclassname.') for element '.$eltype.' in '.$this -> modname." module doesn't exists", LOGGER_WARNING);
                            }
                        }
                    } else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('ampoliros.modules_library.modules_class.handlestructure', 'Element handler for element '.$eltype.' in '.$this -> modname." module doesn't exists", LOGGER_WARNING);
                    }
                    break;
            }
        }

        // Checks for postinstallation jobs
        //
        if (isset($structure[$postscript]) and sizeof($structure[$postscript]))
            include ($scriptdir.$structure[$postscript]);

        $result = TRUE;

        return $result;
    }

    /*!
     @function GetLastActionUnmetDeps
    
     @abstract Gets last unmet dependencies.
    
     @result Array of unmet dependencies.
     */
    function GetLastActionUnmetDeps() {
        return (array) $this -> unmetdeps;
    }

    /*!
     @function GetLastActionUnmetSuggs
    
     @abstract Gets last unmet suggestions.
    
     @result Array of unmet suggestions.
     */
    function GetLastActionUnmetSuggs() {
        return (array) $this -> unmetsuggs;
    }

    /*!
     @function MergeStructureFiles
    
     @abstract Merges two structure files into a new one, handling differences between them.
    
     @param filea string - New structure file.
     @param fileb string - Old structure file.
     @param tmpdir string - New module base dir.
    
     @result Merged array.
     */
    function MergeStructureFiles($filea, $fileb, $tmpdir = '') {
        $result = array();

        if (file_exists($filea) and file_exists($fileb)) {
            // Load structure files
            //
            $deffilea = new XMLDefFile($this -> ampdb, $tmpdir);
            $deffilea -> Load_DefFile($filea);
            $structurea = $deffilea -> Get_Structure();

            $deffileb = new XMLDefFile($this -> ampdb);
            $deffileb -> Load_DefFile($fileb);
            $structureb = $deffileb -> Get_Structure();

            // Fill scripts array
            //
            $scripts = array();

            if (isset($structureb['generalpreinstall']))
                $scripts['generalpreinstall'] = $structureb['generalpreinstall'];
            if (isset($structureb['generalpreuninstall']))
                $scripts['generalpreuninstall'] = $structureb['generalpreuninstall'];
            if (isset($structureb['generalpostinstall']))
                $scripts['generalpostinstall'] = $structureb['generalpostinstall'];
            if (isset($structureb['generalpostuninstall']))
                $scripts['generalpostuninstall'] = $structureb['generalpostuninstall'];
            if (isset($structureb['sitepreinstall']))
                $scripts['sitepreinstall'] = $structureb['sitepreinstall'];
            if (isset($structureb['sitepreuninstall']))
                $scripts['sitepreuninstall'] = $structureb['sitepreuninstall'];
            if (isset($structureb['sitepostinstall']))
                $scripts['sitepostinstall'] = $structureb['sitepostinstall'];
            if (isset($structureb['sitepostuninstall']))
                $scripts['sitepostuninstall'] = $structureb['sitepostuninstall'];
            if (isset($structureb['generalpreupdate']))
                $scripts['generalpreupdate'] = $structureb['generalpreupdate'];
            if (isset($structureb['generalpostupdate']))
                $scripts['generalpostupdate'] = $structureb['generalpostupdate'];
            if (isset($structureb['sitepreupdate']))
                $scripts['sitepreupdate'] = $structureb['sitepreupdate'];
            if (isset($structureb['sitepostupdate']))
                $scripts['sitepostupdate'] = $structureb['sitepostupdate'];

            // Remove scripts and odd entries
            //
            while (list ($key, $val) = each($structurea)) {
                if (!is_array($val))
                    unset($structurea[$key]);
            }
            reset($structurea);

            while (list ($key, $val) = each($structureb)) {
                if (!is_array($val))
                    unset($structureb[$key]);
            }
            reset($structureb);

            $tmpstructure = array();

            // Scan structure a
            //
            while (list ($eltypea, $arraycontenta) = each($structurea)) {
                if (isset($structureb[$eltypea])) {
                    // This element type is in both structures
                    //
                    $arraycontentb = $structureb[$eltypea];

                    reset($arraycontenta);

                    // Checks every element in current structure a element type
                    //
                    while (list ($keya, $vala) = each($arraycontenta)) {
                        reset($arraycontentb);
                        $found = FALSE;

                        while (list ($keyb, $valb) = each($arraycontentb)) {
                            if ($valb['file'] == $vala['file']) {
                                $found = TRUE;
                                $tmpkey = $keyb;
                            }
                        }

                        if ($found) {
                            // This element must be updated
                            //
                            $tmparray = array();
                            $tmparray = $vala;
                            $tmparray['updatemode'] = Module :: UPDATE_MODE_CHANGE;

                            $tmpstructure[$eltypea][] = $tmparray;

                            unset($structurea[$eltypea][$keya]);
                            unset($structureb[$eltypea][$tmpkey]);
                        } else {
                            // This element must be added
                            //
                            $tmparray = array();
                            $tmparray = $vala;
                            $tmparray['updatemode'] = Module :: UPDATE_MODE_ADD;

                            $tmpstructure[$eltypea][] = $tmparray;
                        }
                    }
                } else {
                    // It is a completely new element type for structure file b, so add it
                    //
                    array_walk($arraycontenta, array($this, '_elem_add'));

                    $tmpstructure[$eltypea] = $arraycontenta;
                    unset($structurea[$eltypea]);
                }
            }

            reset($structureb);

            // Scan structure b
            //
            while (list ($eltypeb, $arraycontentb) = each($structureb)) {
                if (isset($structurea[$eltypeb])) {
                    // This element type is in both structures
                    //
                    $arraycontenta = $structurea[$eltypeb];

                    reset($arraycontentb);

                    // Check every remaining element in current structure b element type
                    //
                    while (list ($keyb, $valb) = each($arraycontentb)) {
                        reset($arraycontenta);
                        $found = FALSE;

                        // This is just a check
                        //
                        while (list ($keya, $vala) = each($arraycontenta)) {
                            if ($vala['file'] == $valb['file']) {
                                $found = TRUE;
                            }
                        }

                        if ($found) {
                            // Should never happen
                            //
                            $tmparray = array();
                            $tmparray = $valb;
                            $tmparray['updatemode'] = Module :: UPDATE_MODE_CHANGE;

                            $tmpstructure[$eltypeb][] = $tmparray;
                        } else {
                            // This element must be removed
                            //
                            $tmparray = array();
                            $tmparray = $valb;
                            $tmparray['updatemode'] = Module :: UPDATE_MODE_REMOVE;

                            $tmpstructure[$eltypeb][] = $tmparray;
                        }

                        if (isset($structurea[$eltypea][$keya]))
                            unset($structurea[$eltypea][$keya]);
                        if (isset($structureb[$eltypea][$keya]))
                            unset($structureb[$eltypea][$keya]);
                    }
                } else {
                    // It is a completely old element type for structure file b, so remove it
                    //
                    array_walk($arraycontentb, array($this, '_elem_remove'));

                    $tmpstructure[$eltypeb] = $arraycontentb;
                }
            }

            $result = array_merge($tmpstructure, $scripts);
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);

            if (!file_exists($filea))
                $log -> LogEvent('ampoliros.modules_library.module_class.mergestructurefiles', 'Structure file '.$filea.' not found', LOGGER_ERROR);

            if (!file_exists($fileb))
                $log -> LogEvent('ampoliros.modules_library.module_class.mergestructurefiles', 'Structure file '.$fileb.' not found', LOGGER_ERROR);
        }

        return $result;
    }

    /*!
     @function _elem_add
    
     @abstract Sets update mode to 'add'.
    
     @param item array - array containing element informations.
     @param key string - key name.
     */
    function _elem_add(& $item, $key) {
        $item['updatemode'] = Module :: UPDATE_MODE_ADD;
    }

    /*!
     @function _elem_remove
    
     @abstract Sets update mode to 'remove'.
    
     @param item array - array containing element informations.
     @param key string - key name.
     */
    function _elem_remove(& $item, $key) {
        $item['updatemode'] = Module :: UPDATE_MODE_REMOVE;
    }

    /*!
     @function cmp
    
     @abstract Compares priorities between two types.
    
     @param a mixed - type a.
     @param b mixed - type b.
    
     @result 0 if equal, -1 if typea > typeb, 1 if typea < typeb.
     */
    function cmp($a, $b) {
        switch ($a) {
            case 'generalpreinstall' :
            case 'generalpreuninstall' :
            case 'generalpostinstall' :
            case 'generalpostuninstall' :
            case 'sitepreinstall' :
            case 'sitepreuninstall' :
            case 'sitepostinstall' :
            case 'sitepostuninstall' :
            case 'generalpreupdate' :
            case 'generalpostupdate' :
            case 'sitepreupdate' :
            case 'sitepostupdate' :
                return -1;
                break;
        }

        switch ($b) {
            case 'generalpreinstall' :
            case 'generalpreuninstall' :
            case 'generalpostinstall' :
            case 'generalpostuninstall' :
            case 'sitepreinstall' :
            case 'sitepreuninstall' :
            case 'sitepostinstall' :
            case 'sitepostuninstall' :
            case 'generalpreupdate' :
            case 'generalpostupdate' :
            case 'sitepreupdate' :
            case 'sitepostupdate' :
                return 1;
                break;
        }

        if ($this -> eltypes -> types[$a]['priority'] == $this -> eltypes -> types[$b]['priority'])
            return 0;
        return (($this -> eltypes -> types[$a]['priority'] > $this -> eltypes -> types[$b]['priority']) ? -1 : 1);
    }

    /*!
     @function rcmp
    
     @abstract Reverse compares priorities between two types.
    
     @param a mixed - type a.
     @param b mixed - type b.
    
     @result 0 if equal, -1 if typea < typeb, 1 if typea > typeb.
     */
    function rcmp($a, $b) {
        switch ($a) {
            case 'generalpreinstall' :
            case 'generalpreuninstall' :
            case 'generalpostinstall' :
            case 'generalpostuninstall' :
            case 'sitepreinstall' :
            case 'sitepreuninstall' :
            case 'sitepostinstall' :
            case 'sitepostuninstall' :
            case 'generalpreupdate' :
            case 'generalpostupdate' :
            case 'sitepreupdate' :
            case 'sitepostupdate' :
                return -1;
                break;
        }

        switch ($b) {
            case 'generalpreinstall' :
            case 'generalpreuninstall' :
            case 'generalpostinstall' :
            case 'generalpostuninstall' :
            case 'sitepreinstall' :
            case 'sitepreuninstall' :
            case 'sitepostinstall' :
            case 'sitepostuninstall' :
            case 'generalpreupdate' :
            case 'generalpostupdate' :
            case 'sitepreupdate' :
            case 'sitepostupdate' :
                return 1;
                break;
        }

        if ($this -> eltypes -> types[$a]['priority'] == $this -> eltypes -> types[$b]['priority'])
            return 0;
        return (($this -> eltypes -> types[$a]['priority'] < $this -> eltypes -> types[$b]['priority']) ? -1 : 1);
    }
}

?>