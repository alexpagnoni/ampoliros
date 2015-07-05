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
// $Id: ModuleDep.php,v 1.8 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.module');

define ('DEPTYPE_ALL', 0); // Both dependency or suggestion
define ('DEPTYPE_DEP', 1); // Dependency
define ('DEPTYPE_SUGG', 2); // Suggestion

/*!
 @class ModuleDep

 @abstract Module dependencies handling.

 @discussion ModuleDep class handles.
 */
class ModuleDep extends Object {
    /*! @public mrAmpDb dblayer class - Ampoliros database handler. */
    public $mrAmpDb;

    /*!
     @param rampDb DbLayer class - Ampoliros database handler.
     */
    public function ModuleDep(DBLayer $rampDb) {
        $this -> mrAmpDb = $rampDb;
    }

    public function explodeSingleDep($modId) {
        $result = array();
        if (strstr($modId, '[') and strstr($modId, ']')) {
            $result['modid'] = substr($modId, 0, strpos($modId, '['));
            $result['modversion'] = substr($modId, strpos($modId, '[') + 1, -1);
        } else {
            $result['modid'] = $modId;
            $result['modversion'] = '';
        }
        return $result;
    }

    /*!
     @abstract Explodes a string containing module dependencies.
     @param depstring string - String containing the module dependencies in the module1,module2,... format.
     @result An array of the dependencies.
     */
    public function explodeDeps($depstring) {
        if (!empty($depstring)) {
            $strings = explode(',', trim($depstring, ' ,'));
            $result = array();
            while (list (, $dep) = each($strings)) {
                $exploded_module_string = $this -> ExplodeSingleDep(trim($dep, ' ,'));
                $result[$exploded_module_string['modid']] = $exploded_module_string['modversion'];
            }
            return $result;
        }
        return false;
    }

    /*!
     @abstract Adds dependencies for a module, using the array returned by $this->explodedeps().
     @param modid int - id name of the module.
     @param modsarray array - array of the modules to be added as dependencies.
     @param deptype int - type of dependency (defined).
     @result True if the dependencies have been added.
     */
    public function addDepsArray($modid, $modsarray, $deptype) {
        if (!empty($modid) and !empty($modsarray) and !empty($deptype)) {
            $modquery = $this -> mrAmpDb -> Execute('SELECT id FROM modules WHERE modid='.$this -> mrAmpDb -> Format_Text($modid));
            if ($modquery -> NumRows() != 0) {
                $moddata = $modquery -> Fields();
                while (list ($key, $val) = each($modsarray)) {
                    $this -> AddDep($moddata['id'], $key.'['.$val.']', $deptype);
                }
                return true;
            }
        } else {
            if (empty($modid)) {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moddeps_class.adddepsarray', 'Empty module id', LOGGER_ERROR);
            }
        }
        return false;
    }

    /*!
     @abstract Adds a dependency for a module.
     @param modSerial int - serial number of the module.
     @param modId string - id name of the module.
     @param depType int - type of dependency (defined).
     @result True if the dependency has been added.
     */

    // :KLUDGE: evil 20020507: strange modid type
    // It should be an int, but it's used as string

    public function addDep($modSerial, $modId, $depType) {
        if (!empty($modSerial) and !empty($modId) and !empty($depType)) {
            $exploded_module_string = $this -> ExplodeSingleDep($modId);
            $mod_id = $exploded_module_string['modid'];
            $mod_version = $exploded_module_string['modversion'];
            return $this -> mrAmpDb -> Execute('INSERT INTO moddeps VALUES ('.$this -> mrAmpDb -> Format_Text($modSerial).','.$this -> mrAmpDb -> Format_Text($mod_id).','.$this -> mrAmpDb -> Format_Text($depType).','.$this -> mrAmpDb -> Format_Text($mod_version).')');
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.adddep', 'Empty module serial ('.$modSerial.') or module id ('.$modId.') or dependency type ('.$depType.')', LOGGER_ERROR);
            return false;
        }
    }

    /*!
     @abstract Removes a certain dependency of the given module.
     @param modserial int - serial number of the module.
     @param modid string - id name of the module.
     @param deptype int - type of dependency (defined).
     @result True if the dependency has been removed.
     */

    // :KLUDGE: evil 20020507: strange modid type
    // It should be an int, but it's used as string

    public function remDep($modserial, $modid, $deptype) {
        if (!empty($modserial) and !empty($modid) and !empty($deptype)) {
            return $this -> mrAmpDb -> Execute('DELETE FROM moddeps WHERE modid='.$this -> mrAmpDb -> Format_Text($modserial).' AND moddep='.$this -> mrAmpDb -> Format_Text($modid).' AND deptype='.$this -> mrAmpDb -> Format_Text($deptype));
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.remdep', 'Empty module serial ('.$modserial.') or module id ('.$modid.') or dependency type', LOGGER_ERROR);
            return false;
        }
    }

    /*!
     @abstract Removes all dependencies of the given module.
     @param modserial int - serial number of the module.
     @result True if the dependencies have been removed.
     */
    public function remAllDep($modserial) {
        if (!empty($modserial)) {
            return $this -> mrAmpDb -> Execute('DELETE FROM moddeps WHERE modid='.$this -> mrAmpDb -> Format_Text($modserial));
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.remalldep', 'Empty module serial', LOGGER_ERROR);
            return false;
        }
    }

    /*!
     @abstract Checks if a module has been installed.
     @param modId string - id of the module to be checked for existance.
     @result The query index if the module has been installed.
     */
    public function isInstalled($modId) {
        if (!empty($modId)) {
            $exploded_module_string = $this -> ExplodeSingleDep($modId);
            $mod_id = $exploded_module_string['modid'];
            $mod_version = $exploded_module_string['modversion'];
            $module_check = $this -> mrAmpDb -> Execute('SELECT id,modversion,onlyextension FROM modules WHERE modid='.$this -> mrAmpDb -> Format_Text($mod_id));

            if ($mod_id == 'php') {
                $module_check -> resultrows = 1;
                $module_check -> currfields['id'] = '0';
                $module_check -> currfields['modversion'] = PHP_VERSION;
                $module_check -> currfields['onlyextension'] = $this -> mrAmpDb -> fmtfalse;
            } else
                if (strpos($mod_id, '.extension')) {
                    $mod_id = substr($mod_id, 0, strpos($mod_id, '.extension'));
                    if (extension_loaded($mod_id)) {
                        $module_check -> resultrows = 1;
                        $module_check -> currfields['id'] = '0';
                        $module_check -> currfields['modversion'] = PHP_VERSION;
                        $module_check -> currfields['onlyextension'] = $this -> mrAmpDb -> fmtfalse;
                    }
                }

            if ($module_check -> NumRows()) {
                if (CompareVersionNumbers($module_check -> Fields('modversion'), $mod_version) != AMPOLIROS_VERSIONCOMPARE_LESS)
                    return $module_check;
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.isinstalled', 'Empty module id', LOGGER_ERROR);
        }
        return false;
    }

    /*!
     @abstract Lists the modules a certain module depends on.
     @param modid string - id name of the module.
     @result Array of the modules the module depends on or FALSE if it does not have dependencies.
     */
    public function dependsOn($modid) {
        $result = FALSE;

        if (!empty($modid)) {
            $mquery = $this -> IsInstalled($modid);
            if ($mquery != FALSE) {
                $mdata = $mquery -> Fields();

                $mdquery = $this -> mrAmpDb -> Execute('SELECT * FROM moddeps WHERE modid='.$this -> mrAmpDb -> Format_Text($mdata['id']));
                $nummd = $mdquery -> NumRows();

                if ($nummd > 0) {
                    $depmods = array();
                    $m = 0;

                    while (!$mdquery -> eof) {
                        $mddata = $mdquery -> Fields();

                        $depmods[$m]['moddep'] = $mddata['moddep'];
                        $depmods[$m]['deptype'] = $mddata['deptype'];
                        $depmods[$m]['version'] = $mddata['version'];
                        $mdquery -> MoveNext();
                        $m ++;
                    }
                    $result = $depmods;
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moddeps_class.dependson', 'Module $modid is not installed', LOGGER_ERROR);
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.dependson', 'Empty module id', LOGGER_ERROR);
        }
        return $result;
    }

    /*
     * Modules installation/disinstallation dependencies routines
     *
     */

    /*!
    @function CheckModuleDeps
    
    @abstract Checks if all dependencies or suggestions are met.
    
    @param modid string - id name of the module to check.
    @param deptype int - type of dep: dependency, suggestion or both (defined). Not meaningful when using $depsarray argument.
    @param depsarray string - array of the deps. Used when checking deps before installing. Defaults to nothing.
    If used, it takes precedence over $deptype. It doesn't understand difference between dep and suggestion,
    since it is passed an array of modules with no information about if they are suggestions or deps.
    
    @result False if the dependencies are met, an array of the unmet deps if them are not all met or TRUE if something went wrong.
    */
    function CheckModuleDeps($modid, $deptype = '', $depsarray = '') {
        $result = TRUE;

        if (!empty($depsarray) or (!(empty($modid) and empty($deptype)))) {
            if (empty($depsarray)) {
                $moddeps = $this -> DependsOn($modid);
                if ($moddeps == FALSE) {
                    $result = FALSE;
                }
            } else
                $moddeps = $depsarray;

            //else $moddeps = $this->dependson( $modid );

            // If there are no dependencies, automatically these are
            // assumed to be met
            //
            if ($result != FALSE) {
                // We must set this to be TRUE in case all deps are instead
                // only suggestions, or viceversa. useful when $deftype is not
                // DEFTYPE_ALL
                //
                $inst = TRUE;
                $unmetdeps = array();

                foreach ($moddeps as $mod_id => $mod_version) {
                    if (!empty($depsarray) or $deptype == DEPTYPE_ALL or (isset($mod_version['deptype']) and $mod_version['deptype'] == $deptype)) {
                        if (!empty($depsarray)) {
                            $inst = $this -> IsInstalled($mod_id.'['.$mod_version.']');
                            if ($inst == false)
                                array_push($unmetdeps, $mod_id.'['.$mod_version.']');
                        } else {
                            $inst = $this -> IsInstalled($mod_version['moddep']);
                            if ($inst == false)
                                array_push($unmetdeps, $mod_version['moddep'].'['.$mod_version['version'].']');
                        }
                    }
                }

                // All modules are installed
                if ($result)
                    $result = $unmetdeps;
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.checkmoduledeps', 'Empty module id ('.$modid.') and dependency type or dependencies array', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @function CheckDependingModules
     @abstract Checks if installed modules depends on the given module.
     @param modid string - id name of the module to check.
     @param deptype int - type of dependency (defined).
     @result False if no module depends on this one, the array of the modules which depends on this one if some module depends on this one or TRUE if something is not ok.
     */
    function CheckDependingModules($modid, $deptype = DEPTYPE_DEP) {
        $result = TRUE;

        if (!empty($modid)) {
            $modquery = $this -> IsInstalled($modid);
            if ($modquery != FALSE) {
                $dquery = $this -> mrAmpDb -> Execute('SELECT * '.'FROM moddeps '.'WHERE moddep='.$this -> mrAmpDb -> Format_Text($modid).' '.'AND deptype='.$this -> mrAmpDb -> Format_Text($deptype));

                if ($dquery -> NumRows() == 0) {
                    // No dependencies
                    //
                    $result = FALSE;
                } else {
                    $pendingdeps = array();
                    $d = 0;

                    while (!$dquery -> eof) {
                        $modquery = $this -> mrAmpDb -> Execute('SELECT modid '.'FROM modules '.'WHERE id='. (int) $this -> mrAmpDb -> Format_Text($dquery -> Fields('modid')));
                        $pendingdeps[$d ++] = $modquery -> Fields('modid');
                        $dquery -> MoveNext();
                    }
                    $result = $pendingdeps;
                }
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.checkdependingmodules', 'Empty module id', LOGGER_ERROR);
        }
        return $result;
    }

    /*
     * Modules abilitation/disabilitation dependencies routines
     *
     */

    /*!
     @abstract Checks if a module has been enabled to a certain site.
     @param modid string - id name of the module to be checked.
     @param siteid string - id name of the site to be checked.
     @result True if the module has been enabled to the given site.
     */
    public function isEnabled($modid, $siteid, $considerExtensions = TRUE) {
        $result = FALSE;

        if (!empty($modid) and !empty($siteid)) {
            // Looks if the given module has been installed
            //
            $modquery = $this -> IsInstalled($modid);
            if ($modquery != FALSE) {
                $moddata = $modquery -> Fields();

                // If the module is a global extension, we can be sure
                // it is automatically enabled for all sites
                //
                if (strcmp($moddata['onlyextension'], $this -> mrAmpDb -> fmttrue) == 0) {
                    $result = $considerExtensions;
                } else {
                    // Checks if the given site id exists
                    //
                    $stquery = $this -> mrAmpDb -> Execute('SELECT id '.'FROM sites '.'WHERE siteid='.$this -> mrAmpDb -> Format_Text($siteid));

                    if ($stquery -> NumRows() != 0) {
                        // Checks if the module has been enabled
                        //
                        $amquery = $this -> mrAmpDb -> Execute('SELECT moduleid '.'FROM activemodules '.'WHERE moduleid='.$this -> mrAmpDb -> Format_Text($moddata['id']).' '.'AND siteid='.$this -> mrAmpDb -> Format_Text($stquery -> Fields('id')));

                        if ($amquery -> NumRows() != 0)
                            $result = true;
                    }
                }
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.isenabled', 'Empty module id ('.$modid.') or site id ('.$siteid.')', LOGGER_ERROR);
        }
        return $result;
    }

    public function isSubModuleEnabled($moduleId, $subModule, $siteId) {
        $sub_check = $this -> mrAmpDb -> Execute('SELECT submodule '.'FROM disabledsubmodules,modules '.'WHERE modules.modid='.$this -> mrAmpDb -> Format_Text($moduleId).' '.'AND modules.id=disabledsubmodules.moduleid '.'AND siteid='. (int) $siteId.' '.'AND submodule='.$this -> mrAmpDb -> Format_Text($subModule));
        if ($sub_check -> NumRows())
            return false;
        return true;
    }

    /*!
     @abstract Checks if all dependencies or suggestions for the site are met.
     @param modid string - id name of the module to check.
     @param siteid string - id name of the site to check.
     @param deptype int - type of dep: dependency, suggestion or both (defined).
     @result False if dependencies are met, an array of the unmet deps if them are not all met or TRUE if something went wrong.
     */
    public function checkSiteModuleDeps($modid, $siteid, $deptype) {
        $result = TRUE;

        if (!empty($modid) and !empty($siteid) and !empty($deptype)) {
            $moddeps = $this -> DependsOn($modid);

            if ($moddeps == FALSE)
                $result = FALSE;

            if ($result != FALSE) {
                $inst = TRUE;
                $unmetdeps = array();

                while (list (, $deps) = each($moddeps)) {
                    if (($deps['deptype'] == $deptype) or ($deptype == DEPTYPE_ALL)) {
                        $tmp_inst = $this -> IsEnabled($deps['moddep'], $siteid);
                        if ($tmp_inst == FALSE) {
                            $inst = FALSE;
                            $unmetdeps[] = $deps['moddep'];
                        }
                    }
                }

                // All modules are installed
                //
                if ($inst != FALSE)
                    $result = FALSE;
                else
                    $result = $unmetdeps;
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.checksitemoduledeps', 'Empty module id ('.$modid.') or site id ('.$siteid.') or dependency type', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @abstract Checks which modules enabled on this site depends on specified module.
     @param modid string - id name of the module to check.
     @param siteid string - id name of the site to check.
     @result Array of depending modules, FALSE if not enabled or no dependency found or TRUE if wrong modid.
     */
    public function checkSiteDependingModules($modid, $siteid, $considerExtensions = TRUE) {
            // :KLUDGE: evil 20020507: strange modid type
        // It should be an int, but it's used as string
	    $result = TRUE;

        if (!empty($modid)) {
            $modquery = $this -> IsEnabled($modid, $siteid);
            if ($modquery != FALSE) {
                $dquery = $this -> mrAmpDb -> Execute('SELECT * FROM moddeps WHERE moddep='.$this -> mrAmpDb -> Format_Text($modid).' AND deptype='.$this -> mrAmpDb -> Format_Text(DEPTYPE_DEP));

                if ($dquery -> NumRows() == 0) {
                    // No dependencies
                    //
                    $result = FALSE;
                } else {
                    $pendingdeps = array();
                    $d = 0;

                    while (!$dquery -> eof) {
                        $modquery = $this -> mrAmpDb -> Execute('SELECT modid FROM modules WHERE id='.$this -> mrAmpDb -> Format_Text((int) $dquery -> Fields('modid')));
                        $moddata = $modquery -> Fields();

                        if ($this -> IsEnabled($moddata['modid'], $siteid, $considerExtensions)) {
                            $pendingdeps[$d ++] = $moddata['modid'];
                        }

                        $dquery -> MoveNext();
                    }

                    if (count($pendingdeps) == 0)
                        $result = FALSE;
                    else
                        $result = $pendingdeps;
                }
            } else
                $result = FALSE;
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.checksitedependingmodules', 'Empty module id ('.$modid.') or site id ('.$siteid.')', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @abstract Checks the sites having a certain module enabled.
     @param modserial integer - serial id of the module to check.
     @result An array of the enabled sites if any, FALSE if there aren't sites with that modules enabled.
     */
    public function checkEnabledSites($modserial) {
        $result = FALSE;

        if (!empty($modserial)) {
            $ensites = array();
            $query = $this -> mrAmpDb -> Execute('SELECT sites.siteid FROM sites,activemodules WHERE '.'activemodules.siteid=sites.id AND activemodules.moduleid='. (int) $modserial);

            if ($query -> NumRows()) {
                while (!$query -> eof) {
                    $ensites[] = $query -> Fields('siteid');
                    $query -> MoveNext();
                }
                $result = $ensites;
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moddeps_class.checkenabledsites', 'Empty module serial', LOGGER_ERROR);
        }
        return $result;
    }
}

?>