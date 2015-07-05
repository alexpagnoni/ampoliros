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
// $Id: Site.php,v 1.8 2004-07-14 13:15:37 alex Exp $

package('com.solarix.ampoliros.site');

OpenLibrary('misc.library');

import('com.solarix.ampoliros.util.Hook');
import('com.solarix.ampoliros.site.user.User');
import('com.solarix.ampoliros.site.SiteSettings');
import('com.solarix.ampoliros.io.log.Logger');

/*!
 @class Site
 @abstract Site management
 */
class Site extends Object {
    public $ampdb;
    public $sitedb;
    public $siteid;
    public $siteserial;
    public $sitelog;
    public $unmetdeps = array();
    public $unmetsuggs = array();

    public function Site(DBLayer $ampdb, $siteid = '0', $sitedb) {
        $this -> ampdb = $ampdb;
        if (!get_cfg_var('safe_mode'))
            set_time_limit(0);
        if (empty($sitedb) and $siteid != '0') {
            $tmpquery = $this -> ampdb -> Execute('SELECT * FROM sites WHERE siteid='.$this -> ampdb -> Format_Text($siteid));
            if ($tmpquery -> NumRows() == 1) {
                $tmpdata = $tmpquery -> Fields();

                $args['dbtype'] = $tmpdata['sitedbtype'];
                $args['dbname'] = $tmpdata['sitedbname'];
                $args['dbhost'] = $tmpdata['sitedbhost'];
                $args['dbport'] = $tmpdata['sitedbport'];
                $args['dbuser'] = $tmpdata['sitedbuser'];
                $args['dbpass'] = $tmpdata['sitedbpassword'];
                $args['dblog'] = $tmpdata['sitedblog'];

                import('com.solarix.ampoliros.db.DBLayerFactory');
                $db_fact = new DBLayerFactory();
                $this -> sitedb = $db_fact -> NewDBLayer($args);
                $this -> sitedb -> Connect($args);

                $this -> siteserial = $tmpdata['id'];
                $this -> siteid = $tmpdata['siteid'];

                $this -> sitelog = new Logger(SITESTUFF_PATH.$siteid.'/log/site.log');
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogDie('ampoliros.sites_library.site_class.site', 'No site exists with specified site id ('.$siteid.')');
            }
        } else {
            $this -> sitedb = & $sitedb;
            $this -> siteid = $siteid;
            $tmpquery = & $this -> ampdb -> Execute('SELECT * '.'FROM sites '.'WHERE siteid='.$this -> ampdb -> Format_Text($siteid));

            $this -> siteserial = $tmpquery -> Fields('id');
            $this -> sitelog = new Logger(SITESTUFF_PATH.$siteid.'/log/site.log');
        }
    }

    public function create($sitedata, $createDb = true) {
        $result = false;

        $hook = new Hook($this -> ampdb, 'ampoliros', 'site.create');
        if ($hook -> CallHooks('calltime', $this, array('sitedata' => $sitedata)) == HOOK_RESULT_OK) {
            $goon = true;

            if ($GLOBALS['gEnv']['core']['edition'] == AMP_EDITION_ENTERPRISE) {
                $check_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT count(*) AS sites '.'FROM sites');

                if ($check_query -> Fields('sites') > 0)
                    $goon = false;
            }

            if ($goon) {
                // Default settings and settings tuning
                //
                $nextseq = $this -> ampdb -> NextSeqValue('sites_id_seq');

                $sitedata['siteid'] = $this -> defopt(strtolower(str_replace(' ', '', trim($sitedata['siteid']))), $nextseq);
                //$sitedata['sitemd5id']            = md5( $sitedata['siteid'] );
                $sitedata['sitemd5id'] = md5(microtime());
                $sitedata['sitepath'] = $this -> defopt(trim($sitedata['sitepath']), SITES_TREE.$sitedata['siteid']);
                $sitedata['sitename'] = $this -> defopt(trim($sitedata['sitename']), $sitedata['siteid'].' site');
                $sitedata['sitepassword'] = $this -> defopt(trim($sitedata['sitepassword']), $sitedata['siteid']);
                $sitedata['sitedbname'] = $this -> defopt(strtolower(str_replace(' ', '', trim($sitedata['sitedbname']))), 'amp'.$sitedata['siteid'].'site');
                $sitedata['sitedbhost'] = $this -> defopt(trim($sitedata['sitedbhost']), AMP_DBHOST);
                $sitedata['sitedbport'] = $this -> defopt(trim($sitedata['sitedbport']), AMP_DBPORT);
                $sitedata['sitedbuser'] = $this -> defopt(str_replace(' ', '', trim($sitedata['sitedbuser'])), AMP_DBUSER);
                $sitedata['sitedbpassword'] = $this -> defopt(trim($sitedata['sitedbpassword']), AMP_DBPASS);
                $sitedata['sitedblog'] = $this -> defopt(trim($sitedata['sitedblog']), SITESTUFF_PATH.$sitedata['siteid'].'/log/db.log');
                $sitedata['sitedbtype'] = $this -> defopt(trim($sitedata['sitedbtype']), AMP_DBTYPE);
                $sitedata['sitecreationdate'] = isset($sitedata['sitecreationdate']) ? trim($sitedata['sitecreationdate']) : time();
                $sitedata['siteexpirydate'] = isset($sitedata['siteexpirytime']) ? trim($sitedata['siteexpirydate']) : time();
                $sitedata['siteactive'] = isset($sitedata['siteactive']) ? $sitedata['siteactive'] : $this -> ampdb -> fmttrue;
                $sitedata['maxusers'] = isset($sitedata['maxusers']) ? $sitedata['maxusers'] : '0';
                if (!isset($sitedata['sitenotes']))
                    $sitedata['sitenotes'] = '';

                $args['dbtype'] = strlen($sitedata['sitedbtype']) ? $sitedata['sitedbtype'] : AMP_DBTYPE;
                $args['dbname'] = $sitedata['sitedbname'];
                $args['dbhost'] = $sitedata['sitedbhost'];
                $args['dbport'] = $sitedata['sitedbport'];
                $args['dbuser'] = $sitedata['sitedbuser'];
                $args['dbpass'] = $sitedata['sitedbpassword'];
                $args['dblog'] = $sitedata['sitedblog'];

                if ($this -> ampdb -> Execute('INSERT INTO sites VALUES ( '.$nextseq.','.$this -> ampdb -> Format_Text($sitedata['siteid']).','.$this -> ampdb -> Format_Text($sitedata['sitemd5id']).','.$this -> ampdb -> Format_Text($sitedata['sitepath']).','.$this -> ampdb -> Format_Text($sitedata['sitename']).','.$this -> ampdb -> Format_Text(md5($sitedata['sitepassword'])).','.$this -> ampdb -> Format_Text($sitedata['siteurl']).','.$this -> ampdb -> Format_Text($sitedata['sitedbname']).','.$this -> ampdb -> Format_Text($sitedata['sitedbhost']).','.$this -> ampdb -> Format_Text($sitedata['sitedbport']).','.$this -> ampdb -> Format_Text($sitedata['sitedbuser']).','.$this -> ampdb -> Format_Text($sitedata['sitedbpassword']).','.$this -> ampdb -> Format_Text($sitedata['sitedblog']).','.$this -> ampdb -> Format_Text($sitedata['sitedbtype']).','.$this -> ampdb -> Format_Date($sitedata['sitecreationdate']).','.$this -> ampdb -> Format_Date($sitedata['siteexpirydate']).','.$this -> ampdb -> Format_Text($sitedata['siteactive']).','.$this -> ampdb -> Format_Text($sitedata['sitenotes']).','.$sitedata['maxusers'].')')) {
                    $this -> siteid = $sitedata['siteid'];
                    $this -> siteserial = $nextseq;
                    $this -> sitelog = new Logger(SITESTUFF_PATH.$sitedata['siteid'].'/log/site.log');

                    // Directory tree creation
                    $this -> makedir($sitedata['sitepath']);
                    $this -> makedir($sitedata['sitepath'].'/media');
                    $this -> makedir(SITESTUFF_PATH.$sitedata['siteid']);
                    $this -> makedir(SITESTUFF_PATH.$sitedata['siteid'].'/log');
                    $this -> makedir(SITESTUFF_PATH.$sitedata['siteid'].'/templates');
                    $this -> makedir(SITESTUFF_PATH.$sitedata['siteid'].'/etc');

                    // Site database population
                    //if ( strcmp( $sitedata['sitedbhost'], AMP_DBHOST ) == 0 )
                    //{
                    // Site database host is the same of the mall one
                    // Database creation
                    $args['name'] = $sitedata['sitedbname'];
                    //$this->ampdb->createdb( $args );

                    import('com.solarix.ampoliros.db.DBLayerFactory');
                    $db_fact = new DBLayerFactory();
                    $tmpdb = $db_fact -> NewDBLayer($args);

                    if ($createDb) {
                        if ($tmpdb -> Connect($args)) {
                            $tmpdb -> DropDB($args);
                            $tmpdb -> Close();
                        }
                    }

                    if (!$createDb or $created = $tmpdb -> CreateDB($args)) {
                        if (isset($created) and $created == true)
                            $this -> sitelog -> LogEvent($sitedata['siteid'], 'Database '.$args['dbname'].' created', LOGGER_NOTICE);
                        if ($tmpdb -> Connect($args)) {
                            $this -> sitedb = & $tmpdb;

                            //$xmldb = new XmlDb( $tmpdb, DBLAYER_PARSER_SQL_CREATE );

                            $tmpquery = $this -> ampdb -> Execute('SELECT id '.'FROM modules '.'WHERE modid='.$this -> ampdb -> Format_Text('ampoliros'));

                            if ($this -> EnableModule($tmpquery -> Fields('id'))) {
                                //$tmpsets = new SiteSettings( $tmpdb );
                                //$tmpsets->EditKey( 'sitelocale', AMP_LANG );

                                $tmpuser = new User($this -> ampdb, $nextseq);
                                $tmpuser -> CreateAdminUser($sitedata['siteid'], $sitedata['sitepassword']);

                                import('com.solarix.ampoliros.io.log.Logger');
                                $log = new Logger(AMP_LOG);

                                $log -> LogEvent($sitedata['siteid'], 'Created new site '.$sitedata['siteid'], LOGGER_NOTICE);

                                $this -> sitelog -> LogEvent($sitedata['siteid'], 'Created site '.$sitedata['siteid'], LOGGER_NOTICE);

                                if ($hook -> CallHooks('sitecreated', $this, array('sitedata' => $sitedata)) != HOOK_RESULT_ABORT)
                                    $result = true;

                                global $gEnv;

                                if ($gEnv['core']['config'] -> Value('ALERT_ON_SITE_OPERATION') == '1') {
                                    Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                                    $amp_security = new SecurityLayer();
                                    $amp_security -> SendAlert('A site has been created with id '.$sitedata['siteid']);
                                    unset($amp_security);
                                }
                            } else {
                                import('com.solarix.ampoliros.io.log.Logger');
                                $log = new Logger(AMP_LOG);
                                $log -> LogEvent('ampoliros.sites_library.site_class.create', 'Unable to enable Ampoliros to the site', LOGGER_ERROR);
                            }
                        } else {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger(AMP_LOG);
                            $log -> LogEvent('ampoliros.sites_library.site_class.create', 'Unable to connect to site database', LOGGER_ERROR);
                        }
                    } else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('ampoliros.sites_library.site_class.create', 'Unable to create site database', LOGGER_ERROR);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.sites_library.site_class.create', 'Unable to insert site row in sites table', LOGGER_ERROR);
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.sites_library.site_class.create', 'Tried to create another site in Enterprise edition', LOGGER_WARNING);
            }
        }

        return $result;
    }

    private function makeDir($dirname) {
        if (!file_exists($dirname))
            return @ mkdir($dirname, 0755);
        else
            return TRUE;
    }

    private function defOpt($option, $defaultopt) {
        if (strlen($option) == 0)
            return $defaultopt;
        else
            return $option;
    }

    public function edit($sitedata) {
        $result = false;

        $hook = new Hook($this -> ampdb, 'ampoliros', 'site.edit');
        if ($hook -> CallHooks('calltime', $this, array('sitedata' => $sitedata)) == HOOK_RESULT_OK) {
            if (!empty($sitedata['siteserial'])) {
                $updatestr = 'UPDATE sites SET sitepath='.$this -> ampdb -> Format_Text($sitedata['sitepath']).',sitename='.$this -> ampdb -> Format_Text($sitedata['sitename']).',siteurl='.$this -> ampdb -> Format_Text($sitedata['siteurl']).',sitedbname='.$this -> ampdb -> Format_Text($sitedata['sitedbname']).',sitedbhost='.$this -> ampdb -> Format_Text($sitedata['sitedbhost']).',sitedbport='.$this -> ampdb -> Format_Text($sitedata['sitedbport']).',sitedbuser='.$this -> ampdb -> Format_Text($sitedata['sitedbuser']).',sitedbpassword='.$this -> ampdb -> Format_Text($sitedata['sitedbpassword']).',sitedblog='.$this -> ampdb -> Format_Text($sitedata['sitedblog']).' WHERE id='. (int) $sitedata['siteserial'];

                $result = $this -> ampdb -> Execute($updatestr);

                $tmpquery = & $this -> ampdb -> Execute('SELECT siteid '.'FROM sites '.'WHERE id='. (int) $sitedata['siteserial']);
                $tmpdata = $tmpquery -> Fields();

                if (strlen($sitedata['sitepassword']))
                    $this -> ChPasswd($sitedata['sitepassword']);

                $this -> sitelog -> LogEvent($tmpdata['siteid'], 'Changed site settings', LOGGER_NOTICE);

                if ($hook -> CallHooks('siteedited', $this, array('sitedata' => $sitedata)) == HOOK_RESULT_ABORT)
                    $result = false;
            }
        }

        return $result;
    }

    /*!
    @function GetNotes
    
    @abstract Gets site notes.
    
    @result Site notes if any, empty string otherwise.
    */
    public function getNotes() {
        if ($site_query = & $this -> ampdb -> Execute('SELECT notes '.'FROM sites '.'WHERE id='. (int) $this -> siteid)) {
            return $site_query -> Fields('notes');
        }
        return '';
    }

    /*!
    @function SetNotes
    
    @abstract Edits site notes.
    
    @param notes string - Notes text.
    
    @result True if notes were updated.
    */
    public function setNotes($notes) {
        if ($this -> ampdb -> Execute('UPDATE sites '.'SET notes='.$this -> ampdb -> Format_Text($notes).' '.'WHERE siteid='.$this -> ampdb -> Format_Text($this -> siteid)))
            return true;
        return false;
    }

    /*!
    @function GetMaxUsers
    
    @abstract Gets site max users limit.
    
    @result Max users limit.
    */
    public function getMaxUsers() {
        if ($site_query = & $this -> ampdb -> Execute('SELECT maxusers '.'FROM sites '.'WHERE id='. (int) $this -> siteid)) {
            return $site_query -> Fields('maxusers');
        }
        return '';
    }

    /*!
    @function SetMaxUsers
    
    @abstract Sets site max users limit.
    
    @param maxUsers integer - Max users limit.
    
    @result True if max users limit has been updated.
    */
    public function setMaxUsers($maxUsers = 0) {
        if ($maxUsers == '')
            $maxUsers = 0;

        if ($this -> ampdb -> Execute('UPDATE sites '.'SET maxusers='.$maxUsers.' '.'WHERE siteid='.$this -> ampdb -> Format_Text($this -> siteid)))
            return true;

        return false;
    }

    /*!
     @function ChPasswd
    
     @abstract Changes site password
    
     @discussion This function changes site and site superuser password.
    
     @param password string - New site password
     */
    public function chPasswd($password) {
        $hook = new Hook($this -> ampdb, 'ampoliros', 'site.chpasswd');
        if ($hook -> CallHooks('calltime', $this, array('password' => $password)) == HOOK_RESULT_OK) {
            if (strlen($password) and $this -> siteserial) {
                // We may require old password if superuser password cannot be changed
                //
                $sitequery = & $this -> ampdb -> Execute('SELECT sitepassword '.'FROM sites '.'WHERE id='. (int) $this -> siteserial);

                // Changes site password
                //
                if ($this -> ampdb -> Execute('UPDATE sites '.'SET sitepassword='.$this -> ampdb -> Format_Text(md5($password)).' '.'WHERE id='. (int) $this -> siteserial)) {
                    // Changes site superuser password
                    //
                    $tmpuser = new User($this -> ampdb, $this -> siteserial);
                    $tmpuser -> SetUserIDByUserName($this -> siteid);
                    $userdata = $tmpuser -> GetUserData();
                    $qres = $this -> ampdb -> Execute('UPDATE users SET password = '.$this -> ampdb -> Format_Text(md5($password)).' WHERE id='. (int) $userdata[id]);

                    if ($qres) {
                        if ($hook -> CallHooks('passwordchanged', $this, array('password' => $password)) == HOOK_RESULT_OK)
                            return true;
                    } else {
                        // Fallback to old site password
                        //
                        $this -> ampdb -> Execute('UPDATE sites '.'SET sitepassword='.$this -> ampdb -> Format_Text($sitequery -> Fields('sitepassword')).' '.'WHERE id='. (int) $this -> siteserial);
                        $this -> sitelog -> LogEvent($this -> siteid, 'Unable to change password for user '.$this -> siteid.'; restored old site password', LOGGER_ERROR);
                    }
                } else
                    $this -> sitelog -> LogEvent($this -> siteid, 'Unable to change site password', LOGGER_ERROR);
            } else {
                if (!strlen($password))
                    $this -> sitelog -> LogEvent($this -> siteid, 'Empty password', LOGGER_ERROR);
                if (!$this -> siteserial)
                    $this -> sitelog -> LogEvent($this -> siteid, 'Empty site serial', LOGGER_ERROR);
            }

        }
        return false;
    }

    /*!
     @function Enable
    
     @abstract Enables the site
    
     @result True if the site has been enabled
     */
    public function enable() {
        $result = false;

        if ($this -> ampdb) {
            if ($this -> siteserial) {
                $result = & $this -> ampdb -> Execute('UPDATE sites '.'SET siteactive='.$this -> ampdb -> Format_Text($this -> ampdb -> fmttrue).' '.'WHERE id='. (int) $this -> siteserial);
                if ($result) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent($this -> siteid, 'Enabled site '.$this -> siteid, LOGGER_NOTICE);
                    $this -> sitelog -> LogEvent($this -> siteid, 'Enabled site '.$this -> siteid, LOGGER_NOTICE);

                    global $gEnv;

                    if ($gEnv['core']['config'] -> Value('ALERT_ON_SITE_OPERATION') == '1') {
                        Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                        $amp_security = new SecurityLayer();
                        $amp_security -> SendAlert('Site '.$this -> siteid.' has been enabled');
                        unset($amp_security);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.sites_library.site_class.disable', 'Unable to enable the site', LOGGER_ERROR);

                    $this -> sitelog -> LogEvent('ampoliros.sites_library.site_class.disable', 'Unable to enable the site', LOGGER_ERROR);
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.sites_library.site_class.enable', 'Invalid site serial', LOGGER_ERROR);
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.sites_library.site_class.enable', 'Invalid Ampoliros database handler', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @function Disable
    
     @abstract Disables the site
     
     @result True if the site has been disabled
     */
    public function disable() {
        $result = false;

        if ($this -> ampdb) {
            if ($this -> siteserial) {
                $result = & $this -> ampdb -> Execute('UPDATE sites '.'SET siteactive='.$this -> ampdb -> Format_Text($this -> ampdb -> fmtfalse).' '.'WHERE id='. (int) $this -> siteserial);
                if ($result) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent($this -> siteid, 'Disabled site '.$this -> siteid, LOGGER_NOTICE);

                    $this -> sitelog -> LogEvent($this -> siteid, 'Disabled site '.$this -> siteid, LOGGER_NOTICE);
                    global $gEnv;

                    if ($gEnv['core']['config'] -> Value('ALERT_ON_SITE_OPERATION') == '1') {
                        Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                        $amp_security = new SecurityLayer();
                        $amp_security -> SendAlert('Site '.$this -> siteid.' has been disabled');
                        unset($amp_security);
                    }
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.sites_library.site_class.disable', 'Unable to disable the site', LOGGER_ERROR);
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.sites_library.site_class.disable', 'Invalid site serial', LOGGER_ERROR);
            }
        } else {
            $log -> LogEvent('ampoliros.sites_library.site_class.disable', 'Invalid Ampoliros database handler', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @function Remove
    
     @abstract Removes the site
    
     @discussion Before removing the site, this function disables all the modules
     */
    public function remove() {
        $result = false;

        $hook = new Hook($this -> ampdb, 'ampoliros', 'site.remove');
        if ($hook -> CallHooks('calltime', $this, '') == HOOK_RESULT_OK) {
            $query = $this -> ampdb -> Execute('SELECT * '.'FROM sites '.'WHERE id='. (int) $this -> siteserial);
            $data = $query -> Fields();

            // Disables all modules
            //
            $this -> DisableAllModules($this -> siteserial);

            $args['dbname'] = $data['sitedbname'];
            $args['dbhost'] = $data['sitedbhost'];
            $args['dbport'] = $data['sitedbport'];
            $args['dbuser'] = $data['sitedbuser'];
            $args['dbpass'] = $data['sitedbpass'];
            $args['dbtype'] = $data['sitedbtype'];
            $args['dblog'] = $data['sitedblog'];

            $this -> sitedb -> Close();
            $this -> sitedb -> DropDB($args);

            // Remove cached items
            //
            Carthag :: import('com.solarix.ampoliros.datatransfer.cache.CacheGarbageCollector');
            $cache_gc = new CacheGarbageCollector();
            $cache_gc -> RemoveSiteItems((int) $data['id']);

            // Removes site users
            //
            $this -> RemoveAllUsers();

            // Removes site from amp database
            //
            $this -> ampdb -> Execute('DELETE FROM sites '.'WHERE id='. (int) $data['id']);

            $this -> ampdb -> Execute('DELETE FROM disabledsubmodules '.'WHERE siteid='.$this -> siteserial);

            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent($data['siteid'], 'Removed site '.$data['siteid'], LOGGER_NOTICE);

            if (!empty($data['siteid']))
                RecRemoveDir(SITESTUFF_PATH.$data['siteid']);

            if ($hook -> CallHooks('siteremoved', $this, '') == HOOK_RESULT_OK)
                $result = true;

            global $gEnv;

            if ($gEnv['core']['config'] -> Value('ALERT_ON_SITE_OPERATION') == '1') {
                Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                $amp_security = new SecurityLayer();
                $amp_security -> SendAlert('Site '.$data['siteid'].' has been removed');
                unset($amp_security);
            }
        }

        return $result;
    }

    // Removes all sites users
    //
    public function removeAllUsers() {
        $usersquery = $this -> ampdb -> Execute('SELECT id '.'FROM users '.'WHERE siteid='. (int) $this -> siteserial);

        if ($usersquery -> NumRows() > 0) {
            $tmpuser = new user($this -> ampdb, $this -> siteserial);

            while (!$usersquery -> eof) {
                $userdata = $usersquery -> Fields();
                $tmpuser -> SetUserId($userdata['id']);
                $tmpuser -> RemoveUser();

                $usersquery -> MoveNext();
            }
        }
        //$this->ampdb->Execute( "DELETE from users where siteid = '$data['id']'" );
    }

    /*!
     @function EnableModule
    
     @abstract Enables a module to the site
    
     @param modid integer - Module serial
     */
    public function enableModule($modid) {
        $result = false;

        $hook = new Hook($this -> ampdb, 'ampoliros', 'site.module.enable');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $this -> siteserial, 'modid' => $modid)) == HOOK_RESULT_OK) {
            if (!empty($this -> sitedb) and !empty($modid) and !$this -> IsModuleEnabled($modid)) {
                OpenLibrary('modulesbase.library');

                $modquery = & $this -> ampdb -> Execute('SELECT modid '.'FROM modules '.'WHERE id='. (int) $modid);

                $tmpmod = new Module($this -> ampdb, $modid);

                if ($tmpmod -> Enable($this -> siteserial)) {
                    if ($hook -> CallHooks('moduleenabled', $this, array('siteserial' => $this -> siteserial, 'modid' => $modid)) == HOOK_RESULT_OK)
                        $result = true;

                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent($this -> siteid, 'Enabled module '.$modquery -> Fields('modid'), LOGGER_NOTICE);

                    $this -> sitelog -> LogEvent($this -> siteid, 'Enabled module '.$modquery -> Fields('modid'), LOGGER_NOTICE);
                }

                $this -> unmetdeps = $tmpmod -> GetLastActionUnmetDeps();
                $this -> unmetsuggs = $tmpmod -> GetLastActionUnmetSuggs();
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);

                if (empty($this -> sitedb))
                    $log -> LogEvent('ampoliros.sites_library.site_class.enablemodule', 'Invalid site database handler', LOGGER_ERROR);

                if (empty($modid))
                    $log -> LogEvent('ampoliros.sites_library.site_class.enablemodule', 'Empty module id', LOGGER_ERROR);

                if ($this -> IsModuleEnabled($modid))
                    $log -> LogEvent('ampoliros.sites_library.site_class.enablemodule', 'Ampoliros already enabled to the site', LOGGER_ERROR);
            }
        }

        return $result;
    }

    /*!
     @function DisableModule
    
     @abstract Disables a module from the site
    
     @param modid string - Module name
     */
    public function disableModule($modid) {
        $result = false;

        $hook = new Hook($this -> ampdb, 'ampoliros', 'site.module.disable');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $this -> siteserial, 'modid' => $modid)) == HOOK_RESULT_OK) {
            if (!empty($this -> sitedb) and !empty($modid) and $this -> IsModuleEnabled($modid)) {
                OpenLibrary('modulesbase.library');

                $modquery = & $this -> ampdb -> Execute('SELECT modid '.'FROM modules '.'WHERE id='. (int) $modid);

                $tmpmod = new Module($this -> ampdb, $modid);

                if ($tmpmod -> Disable($this -> siteserial)) {
                    if ($hook -> CallHooks('moduledisabled', $this, array('siteserial' => $this -> siteserial, 'modid' => $modid)) == HOOK_RESULT_OK)
                        $result = true;

                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent($this -> siteid, 'Disabled module '.$modquery -> Fields('modid'), LOGGER_NOTICE);

                    $this -> sitelog -> LogEvent($this -> siteid, 'Disabled module '.$modquery -> Fields('modid'), LOGGER_NOTICE);
                }

                $this -> unmetdeps = $tmpmod -> GetLastActionUnmetDeps();
            }
        }

        return $result;
    }

    public function isModuleEnabled($modid) {
        if (!empty($this -> ampdb) and !empty($modid)) {
            $actquery = & $this -> ampdb -> Execute('SELECT * '.'FROM activemodules '.'WHERE siteid = '.$this -> siteserial.' '.'AND moduleid = '.$modid);
            if ($actquery -> NumRows())
                return true;
        }
        return false;
    }

    public function getLastActionUnmetDeps() {
        return (array) $this -> unmetdeps;
    }

    public function getLastActionUnmetSuggs() {
        return (array) $this -> unmetsuggs;
    }

    public function enableAllModules() {
        $result = false;

        $modules_query = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT id '.'FROM modules '.'WHERE onlyextension!='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($GLOBALS['gEnv']['root']['db'] -> fmttrue));
        $modules = array();

        while (!$modules_query -> eof) {
            if (!$this -> IsModuleEnabled($modules_query -> Fields('id'))) {
                $modules[$modules_query -> Fields('id')] = $modules_query -> Fields('id');
            }

            $modules_query -> MoveNext();
        }

        $count = 0;
        $max = _sites_factorial(count($modules));

        while (count($modules)) {
            if ($count > $max)
                break;

            $id = current($modules);

            if ($this -> EnableModule($id)) {
                unset($modules[$id]);
            }

            if (count($modules) and !next($modules))
                reset($modules);

            $count ++;
        }

        if (!count($modules))
            $result = true;

        return $result;
    }

    /*!
     @function DisableAllModules
    
     @abstract Disables all the modules enabled to the site
     */
    public function disableAllModules($ampolirosToo = true) {
        $result = false;

        if ($this -> ampdb) {
            // Checks the enabled modules
            //
            $modsquery = & $this -> ampdb -> Execute('SELECT id '.'FROM activemodules,modules '.'WHERE activemodules.siteid='. (int) $this -> siteserial.' '.'AND activemodules.moduleid=modules.id');

            $modules = array();

            while (!$modsquery -> eof) {
                $modules[$modsquery -> Fields('id')] = $modsquery -> Fields('id');
                $modsquery -> MoveNext();
            }

            $nummodules = $modsquery -> NumRows();

            $ampquery = $this -> ampdb -> Execute('SELECT id '.'FROM modules '.'WHERE modid='.$this -> ampdb -> Format_Text('ampoliros'));

            if (!$ampolirosToo) {
                unset($modules[$ampquery -> Fields('id')]);
                $nummodules --;
            }

            // Tries to disable every module since all modules are disabled, following dependencies
            //
            while (count($modules) > 0) {
                $modid = current($modules);
                if ((count($modules) == 1 and $modid == $ampquery -> Fields('id')) or (count($modules) > 1 and $modid != $ampquery -> Fields('id')) or (!$ampolirosToo)) {
                    OpenLibrary('modulesbase.library');

                    $tmpmod = new Module($this -> ampdb, $modid);
                    if ($tmpmod -> Disable($this -> siteserial)) {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent($this -> siteid, 'Disabled module '.$tmpmod -> modname, LOGGER_NOTICE);

                        $this -> sitelog -> LogEvent($this -> siteid, 'Disabled module '.$tmpmod -> modname, LOGGER_NOTICE);

                        unset($modules[$modid]);
                    }
                }
                if (!next($modules))
                    reset($modules);
            }
            $result = true;
        }
        return $result;
    }

    public function getMotd() {
        if (is_object($this -> sitedb)) {
            $sets = new SiteSettings($this -> sitedb);
            return $sets -> GetKey('SITE_MOTD');
        }
        return false;
    }

    public function setMotd($motd) {
        if (is_object($this -> sitedb)) {
            $sets = new SiteSettings($this -> sitedb);
            return $sets -> SetKey('SITE_MOTD', $motd);
        }
        return false;
    }

    public function cleanMotd() {
        if (is_object($this -> sitedb)) {
            $sets = new SiteSettings($this -> sitedb);
            return $sets -> DeleteKey('SITE_MOTD');
        }
        return false;
    }
}

function _sites_factorial($s) {
    $r = (int) $s;
    for ($i = $r; $i --; $i > 1) {
        if ($i) {
            $r = $r * $i;
        }
    }
    return $r;
}

?>