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
// $Id: ModuleElement.php,v 1.9 2004-07-14 13:15:37 alex Exp $

package('com.solarix.ampoliros.module');

import('com.solarix.ampoliros.module.Module');
import('com.solarix.ampoliros.module.ModuleRegister');

/*!
 @class ModuleElement
 @abstract Module element class.
 @discussion This class is to be extended for every element type. Extended classes should define DoInstallAction(), DoUninstallAction(),
 DoUpdateAction(), DoEnableSiteAction() and DoDisableSiteAction(), or some of them, for their intended use.
 */
abstract class ModuleElement extends Object {
    /*! @public ampdb dblayer class - Ampoliros database handler. */
    public $ampdb;
    /*! @public sitedb dblayer class - Site database handler. */
    public $sitedb;
    /*! @public modregister modregister class - Module register handler. */
    public $modregister;
    /*! @public modname string - Module name. */
    public $modname;
    /*! @public type string - Element type, defined by the extension class. */
    public $type;
    /*! @public name string - Element name. */
    public $name;
    /*! @public file string - Element file name, if applies. */
    public $file;
    /*! @public basedir string - Module temporary directory path, where the module has been extracted. */
    public $basedir;
    /*! @public setup bool - Setup flag, TRUE when in Ampoliros setup phase. */
    public $setup = false;
    public $site = false;
    public $mLog;

    /*!
     @abstract Class constructor.
     @param ampdb dblayer class - Ampoliros database handler.
     @param sitedb dblayer class - Site database handler. Used when enabling/disabling an element to a site. May be null otherwise.
     @param modname string - Module name.
     @param name string - Element name.
     @param file string - Element file. If it doesn't applies, it should be given the same as name parameter.
     @param basedir string - Module temporary directory path.
     */
    public function ModuleElement(DBLayer $ampdb, $sitedb, $modname, $name, $file, $basedir) {
        // Arguments check and properties initialization
        //
        $this -> ampdb = $ampdb;
        $this -> sitedb = $sitedb;

        if (!empty($modname))
            $this -> modname = $modname;
        if (!empty($name))
            $this -> name = $name;
        if (!empty($file))
            $this -> file = $file;
        if (!empty($basedir))
            $this -> basedir = $basedir;

        $this -> modregister = new ModuleRegister($this -> ampdb);
                        $this->mLog = new Logger(AMP_LOG);
    }

    /*!
     @abstract Installs the element and registers the element in the module register.
     @param params array - Array of the parameters in the element definition structure.
     @result True if succesfully or already installed.
     */
    public function install($params) {
        $result = FALSE;

        if ($this -> modregister -> CheckRegisterElement($this -> type, basename($this -> file)) == FALSE) {
            //if ( isset( $params['donotinstall'] ) or $this->setup ) $result = TRUE;
            if ((isset($params['donotinstall']) or $this -> setup) and (!isset($params['forceinstall'])))
                $result = TRUE;
            else {
                $result = $this -> DoInstallAction($params);
            }

            if ($result == TRUE)
                $this -> modregister -> RegisterElement($this -> modname, $this -> type, $this -> name, basename($this -> file));
        } else
            $this -> modregister -> RegisterElement($this -> modname, $this -> type, $this -> name, basename($this -> file), TRUE);

        return $result;
    }

    /*!
     @abstract Uninstalls the element and unregisters the element from the module register.
     @param params array - Array of the parameters in the element definition structure.
     @result True if successfully uninstalled and unregistered, FALSE if element not found.
     */
    public function uninstall($params) {
        $result = FALSE;
        if ($this -> modregister -> CheckRegisterElement($this -> type, basename($this -> file), $this -> modname) != FALSE) {
            if ($this -> modregister -> CheckRegisterElement($this -> type, basename($this -> file), $this -> modname, TRUE) == FALSE) {
                $result = $this -> DoUninstallAction($params);
                $this -> modregister -> UnregisterElement($this -> modname, $this -> type, basename($this -> file));
            } else
                $result = $this -> modregister -> UnregisterElement($this -> modname, $this -> type, basename($this -> file));
        }
        return $result;
    }

    /*!
     @abstract Updates the element.
     @discussion $this->site controls if the element may be used by a site, and there isn't an error of the function if it isn't usable.
     @param updatemode int - update mode (defined).
     @param params array - Array of the parameters in the element definition structure.
     @result True if successfully updated.
     */
    public function update($updatemode, $params, $siteprescript = '', $sitepostscript = '') {
        $result = FALSE;

        if ($this -> site) {
            $sitesquery = $this -> ampdb -> Execute('SELECT * FROM sites');
            $modquery = $this -> ampdb -> Execute('SELECT id FROM modules WHERE modid='.$this -> ampdb -> Format_Text($this -> modname));
            $modid = $modquery -> Fields('id');
        }

        switch ($updatemode) {
            case Module::UPDATE_MODE_ADD :
                if ($this -> DoInstallAction($params)) {
                    $result = TRUE;

                    if ($this -> site) {
                        if ($sitesquery -> NumRows() > 0) {
                            while (!$sitesquery -> eof) {
                                $sitedata = $sitesquery -> Fields();

                                $actquery = $this -> ampdb -> Execute('SELECT * FROM activemodules WHERE siteid='. (int) $sitedata['id'].' AND moduleid='. (int) $modid);
                                if ($actquery -> NumRows()) {
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

                                    if (!$this -> Enable($sitesquery -> Fields('id'), $params))
                                        $result = FALSE;
                                }

                                $actquery -> Free();

                                $sitesquery -> MoveNext();
                            }
                        }
                    }
                }
                break;

            case Module::UPDATE_MODE_REMOVE :
                if ($this -> DoUninstallAction($params)) {
                    $result = TRUE;

                    if ($this -> site) {
                        if ($sitesquery -> NumRows() > 0) {
                            while (!$sitesquery -> eof) {
                                $sitedata = $sitesquery -> Fields();

                                $actquery = $this -> ampdb -> Execute('SELECT * FROM activemodules WHERE siteid='. (int) $sitedata['id'].' AND moduleid='. (int) $modid);
                                if ($actquery -> NumRows()) {
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

                                    if (!$this -> Disable($sitesquery -> Fields('id'), $params))
                                        $result = FALSE;
                                }

                                $actquery -> Free();

                                $sitesquery -> MoveNext();
                            }
                        }
                    }
                }
                break;

            case Module::UPDATE_MODE_CHANGE :
                if ($this -> DoUpdateAction($params)) {
                    $result = TRUE;

                    if ($this -> site) {
                        if ($sitesquery -> NumRows() > 0) {
                            while (!$sitesquery -> eof) {
                                $sitedata = $sitesquery -> Fields();

                                $actquery = $this -> ampdb -> Execute('SELECT * FROM activemodules WHERE siteid='. (int) $sitedata['id'].' AND moduleid='. (int) $modid);
                                if ($actquery -> NumRows()) {
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

                                    if (strlen($siteprescript) and file_exists($siteprescript)) {
                                        include ($siteprescript);
                                    }

                                    if (!$this -> DoUpdateSiteAction($sitesquery -> Fields('id'), $params))
                                        $result = FALSE;

                                    if (strlen($sitepostscript) and file_exists($sitepostscript)) {
                                        include ($sitepostscript);
                                    }

                                }

                                $actquery -> Free();

                                $sitesquery -> MoveNext();
                            }
                        }
                    }
                }
                break;

            default :
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moduleelement_class.update', 'Invalid update mode', LOGGER_ERROR);
                break;
        }

        if ($this -> site) {
            $sitesquery -> Free();
            $modquery -> Free();
        }

        return $result;
    }

    /*!
     @abstract Enables the element to a site.
     @param siteid string - id name of the site to enable.
     @param params array - Array of the parameters in the element definition structure.
     @result True if successfully enabled, registered or not usable.
     */
    public function enable($siteid, $params) {
        $result = FALSE;
        if ($this -> site) {
            if ($this -> modregister -> CheckRegisterElement($this -> type, $siteid.basename($this -> file)) == FALSE) {
                if ($this -> DoEnableSiteAction($siteid, $params)) {
                    $this -> modregister -> RegisterElement($this -> modname, $this -> type, $siteid.$this -> name, $siteid.basename($this -> file));
                    $result = TRUE;
                }
            } else
                $result = $this -> modregister -> RegisterElement($this -> modname, $this -> type, $siteid.$this -> name, $siteid.basename($this -> file), TRUE);
        } else
            $result = TRUE;
        return $result;
    }

    /*!
     @abstract Disables the element to a site.
     @param siteid string - id name of the site to disable.
     @param params array - Array of the parameters in the element definition structure.
     @result True if successfully disabled, unregistered or not usable.
     */
    public function disable($siteid, $params) {
        $result = FALSE;
        if ($this -> site) {
            if ($this -> modregister -> CheckRegisterElement($this -> type, $siteid.basename($this -> file), $this -> modname) != FALSE) {
                if ($this -> modregister -> CheckRegisterElement($this -> type, $siteid.basename($this -> file), $this -> modname, TRUE) == FALSE) {
                    $result = $this -> DoDisableSiteAction($siteid, $params);
                    $this -> modregister -> UnregisterElement($this -> modname, $this -> type, $siteid.basename($this -> file));
                } else
                    $result = $this -> modregister -> UnregisterElement($this -> modname, $this -> type, $siteid.basename($this -> file));
            }
        } else
            $result = TRUE;
        return $result;
    }

    /*!
     @abstract Executes element install action.
     @discussion It should be called by Install() member only. It should be redefined by the extended class.
     @param params array - Array of the parameters in the element definition structure
     @result True if not redefined.
     */
    public function doInstallAction($params) {
        return TRUE;
    }

    /*!
     @abstract Executes element uninstall action.
     @discussion It should be called by Uninstall() member only. It should be redefined by the extended class.
     @param params array - Array of the parameters in the element definition structure.
     @result True if not redefined.
     */
    public function doUninstallAction($params) {
        return TRUE;
    }

    /*!
     @abstract Executes element update action.
     @discussion It should be called by Update() member only. It should be redefined by the extended class.
     @param params array - Array of the parameters in the element definition structure.
     @result True if not redefined.
     */
    public function doUpdateAction($params) {
        return TRUE;
    }

    /*!
     @abstract Executes enable site action.
     @discussion It should be called by Enable() member only. It should be redefined by the extended class.
     @param siteid string - Site name.
     @param params array - Array of the parameters in the element definition structure.
     @result True if not redefined.
     */
    public function doEnableSiteAction($siteid, $params) {
        return TRUE;
    }

    /*!
     @abstract Executes disable site action.
     @discussion It should be called by Disable() member only. It should be redefined by the extended class.
     @param siteid string - Site name.
     @param params array - Array of the parameters in the element definition structure.
     @result True if not redefined.
     */
    public function doDisableSiteAction($siteid, $params) {
        return TRUE;
    }

    /*!
     @abstract Executes site element update action.
     @discussion It should be called by Update() member only. It should be redefined by the extended class.
     @param siteid string - Site name.
     @param params array - Array of the parameters in the element definition structure.
     @result True if not redefined.
     */
    public function doUpdateSiteAction($siteid, $params) {
        return TRUE;
    }
}

?>