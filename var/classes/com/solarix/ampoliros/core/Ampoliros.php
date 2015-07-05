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
// $Id: Ampoliros.php,v 1.16 2004-07-08 14:49:15 alex Exp $

package('com.solarix.ampoliros.core');

import('carthag.util.Singleton');

class Ampoliros extends Singleton {
    private $bootstrapped = false;
    private $pid;
    private $state;
    private $mode;
    private $interface;
    private $edition;
    const STATE_SETUP = 1;
    const STATE_DEVELOPMENT = 2;
    const STATE_DEBUG = 3;
    const STATE_PRODUCTION = 4;
    const STATE_UPGRADE = 5;
    const STATE_MAINTENANCE = 6;
    const INTERFACE_UNKNOWN = 1;
    const INTERFACE_CONSOLE = 2;
    const INTERFACE_WEB = 3;
    const INTERFACE_REMOTE = 4;
    const INTERFACE_GUI = 5;
    const INTERFACE_EXTERNAL = 6;
    const MODE_ROOT = 1;
    const MODE_SITE = 2;
    const EDITION_ASP = 1;
    const EDITION_ENTERPRISE = 2;

    public function bootstrap($configuration) {
        if (!$this -> bootstrapped) {
            import('com.solarix.ampoliros.core.AmpConfig');
            import('carthag.core.Registry');
            $registry = Registry :: instance();

            $amp_cfg = new AmpConfig($configuration);
            $registry -> setEntry('amp.config', $amp_cfg);

            // Ampoliros 3000 style environment variable. Usage is deprecated.
            $gEnv = array();
            $GLOBALS['gEnv'] = & $gEnv;
            $GLOBALS['gEnv']['runtime']['bootstrap'] = 0;

            // ****************************************************************************
            // Ampoliros filesystem and urls
            // ****************************************************************************

            // Trees
            define ('PUBLIC_TREE', $amp_cfg -> getKey('PUBLIC_TREE'));
            define ('PRIVATE_TREE', $amp_cfg -> getKey('PRIVATE_TREE'));
            define ('SITES_TREE', $amp_cfg -> getKey('SITES_TREE'));
            define ('ADMIN_PATH', PUBLIC_TREE.'admin/');
            define ('AMP_PATH', PUBLIC_TREE.'root/');
            define ('CGI_PATH', PUBLIC_TREE.'cgi/');
            define ('CONFIG_PATH', PRIVATE_TREE.'etc/');
            define ('HANDLER_PATH', PRIVATE_TREE.'var/handlers/');
            define ('INITDB_PATH', PRIVATE_TREE.'var/db/');
            define ('LIBRARY_PATH', PRIVATE_TREE.'var/lib/');
            define ('MODULE_PATH', PRIVATE_TREE.'var/modules/');
            define ('SITESTUFF_PATH', PRIVATE_TREE.'var/sites/');
            define ('TMP_PATH', PRIVATE_TREE.'tmp/');
            // Urls
            define ('AMP_HOST', $amp_cfg -> Value('AMP_HOST'));
            define ('AMP_URL', $amp_cfg -> Value('AMP_URL'));
            define ('AMP_ROOTURL', $amp_cfg -> Value('AMP_ROOTURL'));
            define ('ADMIN_URL', $amp_cfg -> Value('ADMIN_URL'));
            define ('CGI_URL', $amp_cfg -> Value('CGI_URL'));

            // ****************************************************************************
            // Environment
            // ****************************************************************************

            // PHP
            if (strlen($amp_cfg -> Value('PHP_MEMORY_LIMIT')))
                $gEnv['core']['php']['memorylimit'] = $amp_cfg -> Value('PHP_MEMORY_LIMIT');
            else
                $gEnv['core']['php']['memorylimit'] = '64M';
            ini_set('memory_limit', $gEnv['core']['php']['memorylimit']);
            if (strlen($amp_cfg -> Value('PHP_EXECUTION_TIME_LIMIT')))
                $gEnv['core']['php']['timelimit'] = $amp_cfg -> Value('PHP_EXECUTION_TIME_LIMIT');
            else
                $gEnv['core']['php']['timelimit'] = 0;
            set_time_limit($gEnv['core']['php']['timelimit']);
            ignore_user_abort(TRUE);
            set_magic_quotes_runtime(0);

            // ****************************************************************************
            // Ampoliros state, mode, interface and edition
            // ****************************************************************************

            // Defines
            define ('AMP_SETUP_LOCK', TMP_PATH.'.setup');
            define ('AMP_UPGRADINGSYSTEM_LOCK', TMP_PATH.'.upgrading_system');
            // Wait until system is in upgrade phase
            if (!defined('AMPOLIROS_OVERRIDE_LOCK')) {
                while (file_exists(AMP_UPGRADINGSYSTEM_LOCK)) {
                    $this -> state = Ampoliros :: STATE_UPGRADE;
                    clearstatcache();
                    sleep(1);
                }
            }
            // Check if system is in setup phase and set the state
            if (file_exists(AMP_SETUP_LOCK)) {
                define ('AMPOLIROS_SETUP_PHASE', TRUE);
                $this -> state = Ampoliros :: STATE_SETUP;
                if (extension_loaded('APD'))
                    apd_set_session_trace(35);
            } else {
                switch ($amp_cfg -> Value('AMP_STATE')) {
                    case 'debug' :
                        $this -> state = Ampoliros :: STATE_DEBUG;
                        if (extension_loaded('APD'))
                            apd_set_session_trace(35);
                        break;
                    case 'development' :
                        $this -> state = Ampoliros :: STATE_DEVELOPMENT;
                        break;
                    case 'production' :
                        $this -> state = Ampoliros :: STATE_PRODUCTION;
                        break;
                    default :
                        if ($amp_cfg -> Value('DEBUG') == '1') {
                            $this -> state = Ampoliros :: STATE_DEBUG;
                            define ('DEBUG', true);
                        } else
                            $this -> state = Ampoliros :: STATE_PRODUCTION;
                }
            }
            // Interface
            $this ->interface = Ampoliros :: INTERFACE_UNKNOWN;
            // Mode
            $this -> mode = Ampoliros :: MODE_ROOT;
            // Edition
            if ($amp_cfg -> Value('AMP_EDITION') == 'enterprise')
                $this -> edition = Ampoliros :: EDITION_ENTERPRISE;
            else
                $this -> edition = Ampoliros :: EDITION_ASP;

            // ****************************************************************************
            // Pid and shutdown function
            // ****************************************************************************

            if ($this -> state != Ampoliros :: STATE_SETUP) {
                $this -> pid = md5(microtime());
                touch(TMP_PATH.'pids/'.$this -> pid, time());
                register_shutdown_function(array($this, 'shutdown'));
            }

            // ****************************************************************************
            // Session
            // ****************************************************************************

            // This must be before session_start
            if (strlen($amp_cfg -> Value('SESSION_LIFETIME')))
                $gEnv['core']['session']['lifetime'] = $amp_cfg -> Value('SESSION_LIFETIME') * 60;
            else
                $gEnv['core']['session']['lifetime'] = 1440 * 60 * 365; // A year
            ini_set('session.gc_maxlifetime', $gEnv['core']['session']['lifetime']);
            ini_set('session.cookie_lifetime', $gEnv['core']['session']['lifetime']);

            // Start output buffer handler
            if ($amp_cfg -> Value('AMP_COMPRESSED_OB') == '1')
                define ('AMP_COMPRESSED_OB', TRUE);
            else
                define ('AMP_COMPRESSED_OB', FALSE);

            if (!headers_sent()) {
                if (AMP_COMPRESSED_OB)
                    ob_start('ob_gzhandler');
                if ($this -> state != Ampoliros :: STATE_SETUP)
                    ini_set('session.save_path', TMP_PATH.'phpsessions/');
                session_start();
            }
            $gEnv['runtime']['sessionid'] = session_id();

            // ****************************************************************************
            // Ampoliros network
            // ****************************************************************************

            define ('AMP_NAME', $amp_cfg -> Value('AMP_NAME'));
            $gEnv['core']['network']['name'] = AMP_NAME;
            define ('AMP_DOMAIN', $amp_cfg -> Value('AMP_DOMAIN'));
            $gEnv['core']['network']['domain'] = AMP_DOMAIN;
            define ('AMP_DNS', $amp_cfg -> Value('AMP_DNS'));
            $gEnv['core']['network']['dns'] = AMP_DNS;

            // ****************************************************************************
            // Ampoliros error handler
            // ****************************************************************************

            if ($this -> state != Ampoliros :: STATE_SETUP)
                define ('PHP_LOG', $amp_cfg->getKey('PRIVATE_TREE').'var/log/php.log');
            else
                define ('PHP_LOG', $amp_cfg->getKey('PRIVATE_TREE').'var/log/amp.log');
            $gEnv['core']['error']['log'] = PHP_LOG;
            set_error_handler(array($this, 'errorHandler'));

            // ****************************************************************************
            // Ampoliros root
            // ****************************************************************************

            define ('AMP_COUNTRY', $amp_cfg -> Value('AMP_COUNTRY'));
            $gEnv['root']['locale']['country'] = AMP_COUNTRY;
            define ('AMP_LANG', $amp_cfg -> Value('AMP_LANG'));
            $gEnv['root']['locale']['language'] = AMP_LANG;
            define ('AMP_LOG', $amp_cfg->getKey('PRIVATE_TREE').'var/log/amp.log');
            $gEnv['root']['log'] = AMP_LOG;

            import('com.solarix.ampoliros.db.DBLayerFactory');
            define ('AMP_DBTYPE', $amp_cfg -> Value('AMP_DBTYPE'));
            define ('AMP_DBNAME', $amp_cfg -> Value('AMP_DBNAME'));
            define ('AMP_DBHOST', $amp_cfg -> Value('AMP_DBHOST'));
            define ('AMP_DBPORT', $amp_cfg -> Value('AMP_DBPORT'));
            define ('AMP_DBUSER', $amp_cfg -> Value('AMP_DBUSER'));
            define ('AMP_DBPASS', $amp_cfg -> Value('AMP_DBPASS'));
            define ('AMP_DBLOG', $amp_cfg->getKey('PRIVATE_TREE').'var/log/ampdb.log');
            $gEnv['root']['dblog'] = AMP_DBLOG;
            if ($amp_cfg -> Value('AMP_DBDEBUG') == '1')
                define ('AMP_DBDEBUG', true);

            if ($this -> state != Ampoliros :: STATE_SETUP) {
                // Ampoliros central database
                //
                $amp_db_args = array();
                $amp_db_args['dbtype'] = AMP_DBTYPE;
                $amp_db_args['dbname'] = AMP_DBNAME;
                $amp_db_args['dbhost'] = AMP_DBHOST;
                $amp_db_args['dbport'] = AMP_DBPORT;
                $amp_db_args['dbuser'] = AMP_DBUSER;
                $amp_db_args['dbpass'] = AMP_DBPASS;
                $amp_db_args['dblog'] = AMP_DBLOG;
                $db_fact = new DBLayerFactory();

                $amp_db = $db_fact -> NewDbLayer($amp_db_args);
                if (!$amp_db -> Connect($amp_db_args))
                    $this -> abort('Database not connected', Ampoliros :: INTERFACE_CONSOLE);
                unset($amp_db_args);
                $registry -> setEntry('amp.root.db', $amp_db);
            }

            // ****************************************************************************
            // Ampoliros remote
            // ****************************************************************************

            define ('AMP_REMOTE_LOG', $amp_cfg->getKey('PRIVATE_TREE').'var/log/remote.log');
            $gEnv['remote']['log'] = AMP_REMOTE_LOG;

            // ****************************************************************************
            // Run time state and interface defined data
            // ****************************************************************************

            // Debugger
            if ($this -> state == Ampoliros :: STATE_DEBUG) {
                import('carthag.dev.LoadTime');
                $loadtimer = new LoadTime(LoadTime :: LOADTIME_MODE_CONTINUOUS);
                $registry -> setEntry('amp.loadtime', $loadtimer);
                $loadtimer -> Mark('start');
                $dbloadtimer = new LoadTime(LoadTime :: LOADTIME_MODE_STARTSTOP);
                $registry -> setEntry('amp.dbloadtime', $dbloadtimer);
            }

            $gEnv['runtime']['disp'] = $this -> array_merge_clobber($this -> array_merge_clobber($_GET, $_POST), $_FILES);

            // Interface settings
            if ($amp_cfg -> Value('AMP_HUI_COMMENTS') == '1' or $this -> state == Ampoliros :: STATE_DEBUG)
                define ('AMP_HUI_COMMENTS', TRUE);
            else
                define ('AMP_HUI_COMMENTS', FALSE);
            $gEnv['hui']['theme']['default'] = 'amp4000';
            define ('ROOTCRONTAB', $amp_cfg -> Value('ROOTCRONTAB'));

            // Security
            $security_reports_interval = $amp_cfg -> Value('SECURITY_REPORTS_INTERVAL');
            if ($security_reports_interval > 0) {
                $last_security_report = $amp_cfg -> Value('LAST_SECURITY_REPORT');
                if (!$last_security_report or $last_security_report < (time() - ($security_reports_interval * 3600 * 24))) {
                    import('com.solarix.ampoliros.security.SecurityLayer');
                    $amp_security = new SecurityLayer();
                    $amp_security -> SendReport();
                    unset($amp_security);
                }
            }
            unset($security_reports_interval);

            // Maintenance
            $maintenance_interval = $amp_cfg -> Value('MAINTENANCE_INTERVAL');
            if ($this -> state != Ampoliros :: STATE_MAINTENANCE and $maintenance_interval > 0) {
                $last_maintenance = $amp_cfg -> Value('LAST_MAINTENANCE');
                if (!$last_maintenance or $last_maintenance < (time() - ($maintenance_interval * 3600 * 24))) {
                    import('com.solarix.ampoliros.maintenance.AmpolirosMaintenanceHandler');
                    $amp_maintenance = new AmpolirosMaintenanceHandler();
                    $amp_maintenance -> DoMaintenance();
                    $amp_maintenance -> SendReport();
                    unset($amp_maintenance);
                }
            }
            unset($maintenance_interval);

            // ****************************************************************************
            // Backward compatibility
            // ****************************************************************************

            $gEnv['core']['config'] = $amp_cfg;

            // Web server
            define ('HTTPD_GROUP', $amp_cfg -> Value('HTTPD_GROUP'));
            define ('HTTPD_USER', $amp_cfg -> Value('HTTPD_USER'));
            $gEnv['core']['webserver']['group'] = HTTPD_GROUP;
            $gEnv['core']['webserver']['user'] = HTTPD_USER;

            // Ampoliros 2000 style environment variable. Usage is deprecated.
            global $env;
            $env = array();
            $env['ampcfg'] = $gEnv['core']['config'];

            $env['amplocale'] = AMP_LANG;

            $env['disp'] = & $gEnv['runtime']['disp'];

            // OOPHtml
            $env['defaultcss'] = 'default.css';

            $gEnv['runtime']['pid'] = $this -> pid;

            define ('AMP_STATE_SETUP', Ampoliros :: STATE_SETUP);
            define ('AMP_STATE_DEVELOPMENT', Ampoliros :: STATE_DEVELOPMENT);
            define ('AMP_STATE_DEBUG', Ampoliros :: STATE_DEBUG);
            define ('AMP_STATE_PRODUCTION', Ampoliros :: STATE_PRODUCTION);
            define ('AMP_STATE_UPGRADE', Ampoliros :: STATE_UPGRADE);
            define ('AMP_STATE_MAINTENANCE', Ampoliros :: STATE_MAINTENANCE);
            define ('AMP_INTERFACE_UNKNOWN', Ampoliros :: INTERFACE_UNKNOWN);
            define ('AMP_INTERFACE_CONSOLE', Ampoliros :: INTERFACE_CONSOLE);
            define ('AMP_INTERFACE_WEB', Ampoliros :: INTERFACE_WEB);
            define ('AMP_INTERFACE_REMOTE', Ampoliros :: INTERFACE_REMOTE);
            define ('AMP_INTERFACE_GUI', Ampoliros :: INTERFACE_GUI);
            define ('AMP_INTERFACE_EXTERNAL', Ampoliros :: INTERFACE_EXTERNAL);
            define ('AMP_MODE_ROOT', Ampoliros :: MODE_ROOT);
            define ('AMP_MODE_SITE', Ampoliros :: MODE_SITE);
            define ('AMP_EDITION_ASP', Ampoliros :: EDITION_ASP);
            define ('AMP_EDITION_ENTERPRISE', Ampoliros :: EDITION_ENTERPRISE);
            define ('STORESTUFF_PATH', PRIVATE_TREE.'var/sites/');
            define ('LOG_PATH', PRIVATE_TREE.'var/log/');
            define ('CATALOG_PATH', PRIVATE_TREE.'var/locale/');
            define ('BIN_PATH', PRIVATE_TREE.'var/bin/');
            define ('HELP_PATH', PRIVATE_TREE.'var/help/');

            $gEnv['core']['filesystem']['public'] = PUBLIC_TREE;
            $gEnv['core']['filesystem']['private'] = PRIVATE_TREE;
            $gEnv['core']['filesystem']['sites'] = SITES_TREE;

            $gEnv['core']['state'] = $this -> state;
            $gEnv['core']['mode'] = $this -> mode;
            $gEnv['core']['interface'] = $this->interface;

            $gEnv['root']['db'] = $amp_db;
            $env['ampdb'] = $gEnv['root']['db'];

            $gEnv['runtime']['modules'] = array();

            $gEnv['runtime']['debug']['loadtime'] = $loadtimer;
            $env['debug']['loadtime'] = $gEnv['runtime']['debug']['loadtime'];
            $gEnv['runtime']['debug']['dbloadtime'] = $dbloadtimer;

            $env['hui'] = & $gEnv['hui'];
            $gEnv['core']['edition'] = $this -> edition;

            // ****************************************************************************
            // Auto exec routines
            // ****************************************************************************

            // Module reupdate check
            if (file_exists(TMP_PATH.'modinst/reupdate')) {
                import('com.solarix.ampoliros.module.Module');
                $tmp_mod = new Module($amp_db, '');
                $tmp_mod -> Install(TMP_PATH.'modinst/reupdate');
                clearstatcache();
                if (file_exists(TMP_PATH.'modinst/reupdate'))
                    unlink(TMP_PATH.'modinst/reupdate');
            }

            // Startup hook
            if ($this -> state != Ampoliros :: STATE_SETUP) {
                import('com.solarix.ampoliros.util.Hook');
                $hook = new Hook($amp_db, 'ampoliros', 'instance');
                switch ($hook -> CallHooks('startup', $null, '')) {
                    case Hook :: RESULT_ABORT :
                        $this -> abort('Bootstrap aborted', Ampoliros :: INTERFACE_CONSOLE);
                        break;
                }
            }

            // Bootstrap end
            $this -> bootstrapped = true;
            $GLOBALS['gEnv']['runtime']['bootstrap'] = $this -> bootstrapped;
        }
    }

    public function startRoot($userId = 'amp') {
        $this->setMode(Ampoliros::MODE_ROOT);

        if (isset($GLOBALS['gEnv']['runtime']['root']['init']) and $GLOBALS['gEnv']['runtime']['root']['init'] == true)
            return;

        global $env;

        if ($this->getState() != Ampoliros::STATE_SETUP) {
            import('com.solarix.ampoliros.module.ModuleConfig');
            $mod_cfg = new ModuleConfig($GLOBALS['gEnv']['root']['db'], 'ampoliros');
            if (strlen($mod_cfg -> GetKey('hui-root-theme'))) {
                $GLOBALS['gEnv']['hui']['theme']['name'] = $mod_cfg -> GetKey('hui-root-theme');
                //$env['hui']['theme']['handler'] = new HuiTheme( $env['ampdb'], $env['hui']['theme']['name'] );
            } else
                $GLOBALS['gEnv']['hui']['theme']['name'] = $GLOBALS['gEnv']['hui']['theme']['default'];

            $env['db'] = $GLOBALS['gEnv']['root']['db'];
        } else
            $GLOBALS['gEnv']['hui']['theme']['name'] = $GLOBALS['gEnv']['hui']['theme']['default'];

        $GLOBALS['gEnv']['user']['id'] = $userId;
        $GLOBALS['gEnv']['user']['locale'] = & $GLOBALS['gEnv']['root']['locale'];

        $env['currentuser'] = $userId;
        $env['sitelocale'] = $GLOBALS['gEnv']['root']['locale']['language'];

        $GLOBALS['gEnv']['runtime']['root']['init'] = true;
    }
    
    public function startSite($siteId, $userId = '') {
        $result = false;
    $this->setMode(Ampoliros::MODE_SITE);

        global $env;

        if (isset($GLOBALS['gEnv']['runtime']['site']['init']) and $GLOBALS['gEnv']['runtime']['site']['init'] == true)
            return;

        // Site id
        //
        $GLOBALS['gEnv']['site']['id'] = $siteId;
        $env['currentsite'] = & $GLOBALS['gEnv']['site']['id'];
        $env['currentsiteid'] = & $env['currentsite'];

        // Site data
        //
        $sitesquery = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT * FROM sites WHERE siteid='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($siteId));

        if ($sitesquery -> NumRows()) {
            $GLOBALS['gEnv']['site']['data'] = $sitesquery -> Fields();
            $env['sitedata'] = & $GLOBALS['gEnv']['site']['data'];
            $sitesquery -> Free();

            // Check if site is active
            //
            if ($this->getInterface() != Ampoliros::INTERFACE_WEB and $GLOBALS['gEnv']['site']['data']['siteactive'] == $GLOBALS['gEnv']['root']['db'] -> fmtfalse)
                $this->abort('Site disabled');

            // Site log
            //
            define ('SITE_LOG', SITESTUFF_PATH.$GLOBALS['gEnv']['site']['data']['siteid'].'/log/site.log');
            $GLOBALS['gEnv']['site']['log'] = SITE_LOG;

            // Current site serial number
            //
            $GLOBALS['gEnv']['site']['serial'] = $GLOBALS['gEnv']['site']['data']['id'];
            $env['currentsiteserial'] = $GLOBALS['gEnv']['site']['serial'];

            // Site database
            //
            $dbargs['dbtype'] = $GLOBALS['gEnv']['site']['data']['sitedbtype'];
            $dbargs['dbname'] = $GLOBALS['gEnv']['site']['data']['sitedbname'];
            $dbargs['dbhost'] = $GLOBALS['gEnv']['site']['data']['sitedbhost'];
            $dbargs['dbport'] = $GLOBALS['gEnv']['site']['data']['sitedbport'];
            $dbargs['dbuser'] = $GLOBALS['gEnv']['site']['data']['sitedbuser'];
            $dbargs['dbpass'] = $GLOBALS['gEnv']['site']['data']['sitedbpassword'];
            $dbargs['dblog'] = $GLOBALS['gEnv']['site']['data']['sitedblog'];

            $GLOBALS['gEnv']['site']['dblog'] = & $GLOBALS['gEnv']['site']['data']['sitedblog'];

            $db_fact = new DBLayerFactory();
            $GLOBALS['gEnv']['site']['db'] = $db_fact->NewDBLayer($dbargs);
            $env['db'] = $GLOBALS['gEnv']['site']['db'];
            if (!$GLOBALS['gEnv']['site']['db'] -> Connect($dbargs)) {
                if (!defined('MISC_LIBRARY'))
                    include (LIBRARY_PATH.'misc.library');
                $adloc = new Locale('amp_misc_auth', $GLOBALS['gEnv']['root']['locale']['language']);
                AmpDie($adloc -> GetStr('nodb'));
            }

            // Hui theme
            //
            $tmpquery = $GLOBALS['gEnv']['site']['db'] -> Execute('SELECT val '.'FROM sitesettings '.'WHERE keyname='.$GLOBALS['gEnv']['site']['db'] -> Format_Text('sitetheme'));

            $GLOBALS['gEnv']['site']['theme'] = $tmpquery -> Fields('val');

            // Site cgi
            //
            if (strlen($GLOBALS['gEnv']['site']['data']['siteurl'])) {
                $tmp = parse_url($GLOBALS['gEnv']['site']['data']['siteurl']);
                $GLOBALS['gEnv']['site']['cgi'] = $tmp['scheme'].'://'.$tmp['host'].CGI_URL;
            } else
                $GLOBALS['gEnv']['site']['cgi'] = CGI_URL;
            $env['sitecgi'] = & $GLOBALS['gEnv']['site']['cgi'];
            unset($tmp);

            // Site country
            //
            $tmpquery = $GLOBALS['gEnv']['site']['db'] -> Execute('SELECT val '.'FROM sitesettings '.'WHERE keyname='.$GLOBALS['gEnv']['site']['db'] -> Format_Text('sitecountry'));
            $GLOBALS['gEnv']['site']['locale']['country'] = strlen($tmpquery -> Fields('val')) ? $tmpquery -> Fields('val') : AMP_COUNTRY;
            $env['sitecountry'] = $GLOBALS['gEnv']['site']['locale']['country'];

            // Site language
            //
            $tmpquery = $GLOBALS['gEnv']['site']['db'] -> Execute('SELECT val '.'FROM sitesettings '.'WHERE keyname='.$GLOBALS['gEnv']['site']['db'] -> Format_Text('sitelanguage'));
            $GLOBALS['gEnv']['site']['locale']['language'] = strlen($tmpquery -> Fields('val')) ? $tmpquery -> Fields('val') : AMP_LANG;
            $env['sitelanguage'] = $GLOBALS['gEnv']['site']['locale']['language'];

            // User
            //
            if (!strlen($userId))
                $userId = $siteId;

            // User id
            //
            $GLOBALS['gEnv']['user']['id'] = $userId;
            $env['currentuser'] = & $GLOBALS['gEnv']['user']['id'];

            // Hui theme
            //
            $tmpquery = $GLOBALS['gEnv']['site']['db'] -> Execute('SELECT val '.'FROM sitesettings '.'WHERE keyname='.$GLOBALS['gEnv']['site']['db'] -> Format_Text($GLOBALS['gEnv']['user']['id'].'-theme'));

            $GLOBALS['gEnv']['user']['theme'] = $tmpquery -> Fields('val');

            if (!strlen($GLOBALS['gEnv']['user']['theme'])) {
                if (!strlen($GLOBALS['gEnv']['site']['theme'])) {
                    import('com.solarix.ampoliros.module.ModuleConfig');
                    $mod_cfg = new ModuleConfig($GLOBALS['gEnv']['root']['db'], 'ampoliros');
                    if (strlen($mod_cfg -> GetKey('hui-root-theme'))) {
                        $GLOBALS['gEnv']['site']['theme'] = $mod_cfg -> GetKey('hui-root-theme');
                        if (!strlen($GLOBALS['gEnv']['site']['theme']))
                            $GLOBALS['gEnv']['site']['theme'] = $GLOBALS['gEnv']['hui']['theme']['default'];
                    } else {
                        $GLOBALS['gEnv']['site']['theme'] = $GLOBALS['gEnv']['hui']['theme']['default'];
                    }
                    unset($mod_cfg);
                }

                $GLOBALS['gEnv']['user']['theme'] = $GLOBALS['gEnv']['site']['theme'];
            }
            $GLOBALS['gEnv']['hui']['theme']['name'] = $GLOBALS['gEnv']['user']['theme'];

            // User country
            //
            $tmpquery = $GLOBALS['gEnv']['site']['db'] -> Execute('SELECT val FROM sitesettings WHERE keyname='.$GLOBALS['gEnv']['site']['db'] -> Format_Text($GLOBALS['gEnv']['user']['id'].'-country'));

            $GLOBALS['gEnv']['user']['locale']['country'] = $env[$GLOBALS['gEnv']['user']['id'].'-country'] = strlen($tmpquery -> Fields('val')) ? $tmpquery -> Fields('val') : $GLOBALS['gEnv']['site']['locale']['country'];
            $env['currentuser-country'] = $GLOBALS['gEnv']['user']['locale']['country'];
            // User language
            //
            $tmpquery = $GLOBALS['gEnv']['site']['db'] -> Execute('SELECT val FROM sitesettings WHERE keyname='.$GLOBALS['gEnv']['site']['db'] -> Format_Text($GLOBALS['gEnv']['user']['id'].'-language'));

            $GLOBALS['gEnv']['user']['locale']['language'] = $env[$GLOBALS['gEnv']['user']['id'].'-language'] = strlen($tmpquery -> Fields('val')) ? $tmpquery -> Fields('val') : $GLOBALS['gEnv']['site']['locale']['language'];
            $env['currentuser-language'] = $GLOBALS['gEnv']['user']['locale']['language'];
            $env['sitelocale'] = $GLOBALS['gEnv']['user']['locale']['language']; // Old one

            $tmpquery = $GLOBALS['gEnv']['root']['db']->Execute('SELECT id,fname,lname,email,groupid FROM users WHERE username='.$GLOBALS['gEnv']['root']['db']->Format_Text($GLOBALS['gEnv']['user']['id']));

            // User datas
            //
            $GLOBALS['gEnv']['user']['group'] = $tmpquery->Fields('groupid');
            $GLOBALS['gEnv']['user']['serial'] = $tmpquery -> Fields('id');
            $GLOBALS['gEnv']['user']['data']['lname'] = $tmpquery -> Fields('lname');
            $GLOBALS['gEnv']['user']['data']['fname'] = $tmpquery -> Fields('fname');
            $GLOBALS['gEnv']['user']['data']['email'] = $tmpquery -> Fields('email');
            $env['currentusercname'] = $tmpquery -> Fields('fname').' '.$tmpquery -> Fields('lname');
            $env['currentgroupserial'] = $GLOBALS['gEnv']['user']['group'];
            $env['currentuserserial'] = $GLOBALS['gEnv']['user']['serial'];
            $tmpquery -> Free();

            $result = true;
        }
        $GLOBALS['gEnv']['runtime']['site']['init'] = true;
        return $result;
    }
    
    public function startSiteByMd5($md5Id, $userId = '') {
        $result = false;
        $query = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE sitemd5id='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($md5Id));

        if ($query -> NumRows()) {
            $result = $this->startSite($query -> Fields('siteid'), $userId);
            $query->free();
        }

        return $result;
    }
    
    public function startRemote() {
    }
    
    public function startMaintenance() {
        $this -> setState(Ampoliros :: STATE_MAINTENANCE);
        $this -> setInterface(Ampoliros :: INTERFACE_CONSOLE);

        $this->startRoot();

        import('com.solarix.ampoliros.maintenance.AmpolirosMaintenanceHandler');
        import('com.solarix.ampoliros.util.Hook');

        $hook = new Hook($GLOBALS['gEnv']['root']['db'], 'ampoliros', 'instance');
        switch ($hook -> CallHooks('maintenance', $null, '')) {
            case Hook :: RESULT_ABORT :
                AmpDie('Maintenance aborted');
                break;
        }

        $amp_mnt = new AmpolirosMaintenanceHandler();
        $GLOBALS['gEnv']['runtime']['maintenance']['result'] = $amp_mnt -> DoMaintenance();
        $amp_mnt -> SendReport($GLOBALS['gEnv']['runtime']['maintenance']['result']);
    }
    
    public function shutdown() {
        global $gEnv;

        if ($this -> state != Ampoliros :: STATE_SETUP) {
            import('com.solarix.ampoliros.util.Hook');
            $reg = Registry :: instance();
            $hook = new Hook($reg -> getEntry('amp.root.db'), 'ampoliros', 'instance');
            switch ($hook -> CallHooks('shutdown', $null, '')) {
                case Hook :: RESULT_ABORT :
                    $this -> abort('Shutdown aborted');
                    break;
            }
        }

        switch ($this -> state) {
            case Ampoliros :: STATE_DEBUG :
                if (is_object($gEnv['runtime']['debug']['loadtime'])) {
                    $gEnv['runtime']['debug']['loadtime'] -> Mark('end');
                    if (function_exists('memory_get_usage')) {
                        $gEnv['runtime']['debug']['memoryusage'] = memory_get_usage();
                    }

                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['root']['log']);
                    $log -> LogEvent('ampoliros', 'Profiler total time: '.$gEnv['runtime']['debug']['loadtime'] -> GetTotalTime(), LOGGER_DEBUG);

                    if ($fh = @ fopen(TMP_PATH.'pids/'.$this -> pid, 'w')) {
                        $defined_functions = get_defined_functions();
                        @ fwrite($fh, serialize(array('gEnv' => $gEnv, 'classes' => get_declared_classes(), 'functions' => $defined_functions['user'], 'extensions' => get_loaded_extensions(), 'includedfiles' => get_included_files())));
                        @ fclose($fh);
                    }
                }
                break;
        }

        if ($this -> state != Ampoliros :: STATE_DEBUG and file_exists(TMP_PATH.'pids/'.$this -> pid))
            @ unlink(TMP_PATH.'pids/'.$this -> pid);

        $carthag = Carthag :: instance();
        $carthag -> halt();
    }

    public function abort($text, $forceInterface) {
        global $gEnv;
        OpenLibrary('hui.library');
        if (strlen($forceInterface))
            $interface = $forceInterface;
        else
            $interface = $this->interface;

        if ($interface == Ampoliros :: INTERFACE_EXTERNAL) {
            if (isset($gEnv['runtime']['external_interface_error_handler']) and function_exists($gEnv['runtime']['external_interface_error_handler'])) {
                $func = $gEnv['runtime']['external_interface_error_handler'];
                $func ($text);
            } else {
                $interface = Ampoliros :: INTERFACE_WEB;
                $this->interface = Ampoliros :: INTERFACE_WEB;
            }
        }

        switch ($interface) {
            case Ampoliros :: INTERFACE_GUI :
            case Ampoliros :: INTERFACE_UNKNOWN :
            case Ampoliros :: INTERFACE_REMOTE :
            case Ampoliros :: INTERFACE_EXTERNAL :
                break;
            case Ampoliros :: INTERFACE_CONSOLE :
                echo "\n".$text."\n";
                break;
            case Ampoliros :: INTERFACE_WEB :
                $reg = Registry :: instance();
                $tmp_hui = new Hui($reg -> getEntry('amp.root.db'));
                $tmp_hui -> LoadWidget('empty');
                //$tmp_elem = new HuiEmpty('empty');

                if (is_object($gEnv['hui']['theme']['handler'])) {
                    $die_image = $gEnv['hui']['theme']['handler'] -> mStyle['bigdot'];
                } else
                    $die_image = '';
                ?> 
                                                <html>
                                                <head>
                                                <basefont face="Verdana">
                                                <title>Ampoliros</title>
                                                <link rel="stylesheet" type="text/css" href="<?php echo $gEnv['hui']['theme']['handler'] -> mStyle['css'];
                ?>">
                                                </head>
                                                
                                                <body bgcolor="white">
                                                
                                                <table border="0" cellspacing="0" cellpadding="0" align="center" width="200">
                                                <tr>
                                                    <td align="center"><a href="<?php echo AMP_URL;
                ?>"><img src="<?php echo $die_image;
                ?>" alt="Ampoliros" border="0"></a></td>
                                                </tr>
                                                <tr>
                                                <td>&nbsp; </td>
                                                </tr>
                                                <tr>
                                                <td align="center"><?php echo $text;
                ?></td>
                                                </tr>
                                                </table>
                                                
                                                </body>
                                                </html>
                                                <?php break;
        }

        $carthag = Carthag :: instance();
        $carthag -> halt();
    }

    public function errorHandler(
        $errorType,
        $errorMessage,
        $errorFile,
        $errorLine,
        $errorContext) {
        global $gEnv;

        $log_err[E_ERROR]['log'] = true;
        $log_err[E_ERROR]['die'] = true;

        $log_err[E_WARNING]['log'] = false;
        $log_err[E_WARNING]['die'] = false;

        $log_err[E_PARSE]['log'] = true;
        $log_err[E_PARSE]['die'] = false;

        $log_err[E_NOTICE]['log'] = false;
        $log_err[E_NOTICE]['die'] = false;

        $log_err[E_CORE_ERROR]['log'] = true;
        $log_err[E_CORE_ERROR]['die'] = true;

        $log_err[E_CORE_WARNING]['log'] = false;
        $log_err[E_CORE_WARNING]['die'] = false;

        $log_err[E_COMPILE_ERROR]['log'] = true;
        $log_err[E_COMPILE_ERROR]['die'] = true;

        $log_err[E_COMPILE_WARNING]['log'] = false;
        $log_err[E_COMPILE_WARNING]['die'] = false;

        $log_err[E_USER_ERROR]['log'] = true;
        $log_err[E_USER_ERROR]['die'] = true;

        $log_err[E_USER_WARNING]['log'] = false;
        $log_err[E_USER_WARNING]['die'] = false;

        $log_err[E_USER_NOTICE]['log'] = false;
        $log_err[E_USER_NOTICE]['die'] = false;

        switch ($this -> state) {
            case Ampoliros :: STATE_DEBUG :
                $log_err[E_NOTICE]['log'] = true;
                $log_err[E_USER_NOTICE]['log'] = true;
                $log_err[E_WARNING]['log'] = true;
                $log_err[E_CORE_WARNING]['log'] = true;
                $log_err[E_COMPILE_WARNING]['die'] = true;
                $log_err[E_USER_WARNING]['log'] = true;
                break;

            case Ampoliros :: STATE_SETUP :
            case Ampoliros :: STATE_DEVELOPMENT :
                $log_err[E_WARNING]['log'] = true;
                $log_err[E_CORE_WARNING]['log'] = true;
                $log_err[E_COMPILE_WARNING]['die'] = true;
                $log_err[E_USER_WARNING]['log'] = true;
                break;

            case Ampoliros :: STATE_PRODUCTION :
            case Ampoliros :: STATE_UPGRADE :
                break;
        }

        switch ($errorType) {
            case E_ERROR :
                if ($log_err[E_ERROR]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated an ERROR at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_FAILURE);
                }
                if ($log_err[E_ERROR]['die'])
                    $this -> abort(
                        'A fatal error occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_WARNING :
                if ($log_err[E_WARNING]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a WARNING at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_WARNING);
                }
                if ($log_err[E_WARNING]['die'])
                    $this -> abort(
                        'A warning occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_PARSE :
                if ($log_err[E_PARSE]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a PARSE error at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                if ($log_err[E_PARSE]['die'])
                    $this -> abort(
                        'A parse error occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_NOTICE :
                if ($log_err[E_NOTICE]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a notice at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_NOTICE);
                }
                if ($log_err[E_NOTICE]['die'])
                    $this -> abort(
                        'A notice occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_CORE_ERROR :
                if ($log_err[E_CORE_ERROR]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a CORE ERROR at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                if ($log_err[E_CORE_ERROR]['die'])
                    $this -> abort(
                        'A core error occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_CORE_WARNING :
                if ($log_err[E_CORE_WARNING]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a CORE WARNING at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                if ($log_err[E_CORE_WARNING]['die'])
                    $this -> abort(
                        'A core warning occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_COMPILE_ERROR :
                if ($log_err[E_COMPILE_ERROR]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a COMPILE ERROR at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                if ($log_err[E_COMPILE_ERROR]['die'])
                    $this -> abort(
                        'A compile error occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_COMPILE_WARNING :
                if ($log_err[E_COMPILE_WARNING]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated a COMPILE WARNING at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                if ($log_err[E_COMPILE_WARNING]['die'])
                    $this -> abort(
                        'A compile warning occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_USER_ERROR :
                if ($log_err[E_USER_ERROR]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated an USER ERROR at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                if ($log_err[E_USER_ERROR]['die'])
                    $this -> abort(
                        'An user error occured at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage);
                break;

            case E_USER_WARNING :
                if ($log_err[E_USER_WARNING]['log']) {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger($gEnv['core']['error']['log']);
                    $log -> LogEvent(
                        'Ampoliros error handler',
                        'PHP generated an USER WARNING at line '
                            .$errorLine
                            .' of file '
                            .$errorFile
                            .'. The error message was: '
                            .$errorMessage,
                        LOGGER_ERROR);
                }
                    if ($log_err[E_USER_WARNING]['die'])
                        $this -> abort(
                            'An user warning occured at line '
                                .$errorLine
                                .' of file '
                                .$errorFile
                                .'. The error message was: '
                                .$errorMessage);
                    break;

                    case E_USER_NOTICE :
                        if ($log_err[E_USER_NOTICE]['log']) {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger($gEnv['core']['error']['log']);
                            $log -> LogEvent(
                                'Ampoliros error handler',
                                'PHP generated an USER NOTICE at line '
                                    .$errorLine
                                    .' of file '
                                    .$errorFile
                                    .'. The error message was: '
                                    .$errorMessage,
                                LOGGER_ERROR);
                        }
                        if ($log_err[E_USER_NOTICE]['die'])
                            $this -> abort(
                                'An user notice occured at line '
                                    .$errorLine
                                    .' of file '
                                    .$errorFile
                                    .'. The error message was: '
                                    .$errorMessage);
                        break;

                    default :
                        break;
                }
        }

        private function array_merge_clobber($a1, $a2) {
            if (!is_array($a1) || !is_array($a2))
                return false;
            $newarray = $a1;
            foreach ($a2 as $key => $val) {
                if (is_array($val)
                    & isset($newarray[$key])
                    & is_array($newarray[$key])) {
                    $newarray[$key] =
                        $this -> array_merge_clobber($newarray[$key], $val);
                } else {
                    $newarray[$key] = $val;
                }
            }

            return $newarray;
        }

        // Accessors

        public function getPid() {
            return $this -> pid;
        }

        public function getState() {
            return $this -> state;
        }

        public function setState($state) {
            switch ($state) {
                case Ampoliros :: STATE_SETUP :
                case Ampoliros :: STATE_DEVELOPMENT :
                case Ampoliros :: STATE_DEBUG :
                case Ampoliros :: STATE_PRODUCTION :
                case Ampoliros :: STATE_UPGRADE :
                case Ampoliros :: STATE_MAINTENANCE :
                    $this -> state = $state;
                    $GLOBALS['gEnv']['core']['state'] = $state;
                    break;
            }
        }

        public function getMode() {
            return $this -> mode;
        }

        public function setMode($mode) {
            switch ($mode) {
                case Ampoliros :: MODE_ROOT :
                case Ampoliros :: MODE_SITE :
                    $this -> mode = $mode;
                    $GLOBALS['gEnv']['core']['mode'] = $mode;
                    break;
            }
        }

        public function getInterface() {
            return $this->interface;
        }

        public function setInterface($interface) {
            switch ($interface) {
                case Ampoliros :: INTERFACE_UNKNOWN :
                case Ampoliros :: INTERFACE_CONSOLE :
                case Ampoliros :: INTERFACE_WEB :
                case Ampoliros :: INTERFACE_REMOTE :
                case Ampoliros :: INTERFACE_GUI :
                case Ampoliros :: INTERFACE_EXTERNAL :
                    $this->interface = $interface;
                    $GLOBALS['gEnv']['core']['interface'] = $interface;
                    break;
            }
        }

        public function getEdition() {
            return $this -> edition;
        }
    }

    function OpenLibrary($libraryFile, $libraryPath = LIBRARY_PATH) {
        $result = FALSE;
        global $gEnv;

        $library_define = strtoupper($libraryFile);
        $library_define = strtr($library_define, '.', '_');

        if (substr($libraryPath, strlen($libraryPath) - 1) != '/')
            $libraryPath.= '/';

        if (!defined($library_define)) {
            $amp = Ampoliros :: instance('Ampoliros');
            if ($amp -> getState()
                == Ampoliros :: STATE_DEBUG
                    & isset($gEnv['runtime']['debug']['loadtime']))
                $gEnv['runtime']['debug']['loadtime'] -> Mark(
                    'start - OpenLibrary() for '.$libraryFile);

            //if ( file_exists( $libraryPath.$libraryFile ) )
            if (include ($libraryPath.$libraryFile)) {
                $gEnv['runtime']['libraries'][$library_define] = $libraryFile;

                /*
                 while( list( $key, $val ) = each( $GLOBALS ) )
                 {
                 global $$key;
                 //$$key = &$GLOBALS[$key];
                 }
                 */
                //include( $libraryPath.$libraryFile );

                $result = TRUE;
            } else {
                if (!defined('LOGGER_LIBRARY')) {
                    if (file_exists(LIBRARY_PATH.'logger.library')) {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $tmp_log = new Logger(AMP_LOG);
                        $tmp_log -> LogEvent(
                            'ampoliros.openlibrary',
                            'Library file '
                                .$libraryPath
                                .$libraryFile
                                .' does not exists',
                            LOGGER_ERROR);
                    }
                }
            }

            if ($amp -> getState()
                == Ampoliros :: STATE_DEBUG
                    & isset($gEnv['runtime']['debug']['loadtime']))
                $gEnv['runtime']['debug']['loadtime'] -> Mark(
                    'end - OpenLibrary() for '.$libraryFile);
        } else
            $result = TRUE; // Already opened, but return TRUE.

        return $result;
    }

    ?>