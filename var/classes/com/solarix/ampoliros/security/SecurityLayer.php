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
// $Id: SecurityLayer.php,v 1.8 2004-07-14 13:15:37 alex Exp $

package('com.solarix.ampoliros.security');

import('carthag.core.Registry');

define ('AMPOLIROS_SECURITY_PRESET_LOW', 1);
define ('AMPOLIROS_SECURITY_PRESET_NORMAL', 2);
define ('AMPOLIROS_SECURITY_PRESET_HIGH', 3);
define ('AMPOLIROS_SECURITY_PRESET_PARANOID', 4);

OpenLibrary('configman.library');

class SecurityLayer extends Object {
	public $mAlertsEmail;
	public $mReportsEmail;
	public $mSecurityLog;
	public $mAccessLog;

	/*!
	@function SecurityLayer
	@abstract Class constructor.
	*/
	public function SecurityLayer() {
		$reg = Registry :: instance();
		$this -> mSecurityLog = $reg -> getEntry('amp.config') -> getKey('PRIVATE_TREE').'var/log/security.log';
		$this -> mAccessLog = $reg -> getEntry('amp.config') -> getKey('PRIVATE_TREE').'var/log/access.log';
	}

	/*!
	@function SetPredefinedLevel
	@abstract Sets the security profile as a defined preset.
	@param level integer - One of the defined presets.
	@result True if the preset exists.
	*/
	public function setPredefinedLevel($level) {
		$result = true;

		switch ($level) {
			case AMPOLIROS_SECURITY_PRESET_LOW :
				$max_wrong_logins = 1000;
				$wrong_login_delay = 0;
				$session_lifetime = 525600;
				$alerts = array('wronglocalrootlogin' => false, 'wronglocaluserlogin' => false, 'wrongremotelogin' => false, 'moduleoperation' => false, 'modulesiteoperation' => false, 'siteoperation' => false);
				$reports_interval = 0;
				break;

			case AMPOLIROS_SECURITY_PRESET_NORMAL :
				$max_wrong_logins = 3;
				$wrong_login_delay = 1;
				$session_lifetime = 525600;
				$alerts = array('wronglocalrootlogin' => false, 'wronglocaluserlogin' => false, 'wrongremotelogin' => false, 'moduleoperation' => false, 'modulesiteoperation' => false, 'siteoperation' => false);
				$reports_interval = 0;
				break;

			case AMPOLIROS_SECURITY_PRESET_HIGH :
				$max_wrong_logins = 3;
				$wrong_login_delay = 2;
				$session_lifetime = 525600;
				$alerts = array('wronglocalrootlogin' => true, 'wronglocaluserlogin' => false, 'wrongremotelogin' => true, 'moduleoperation' => true, 'modulesiteoperation' => false, 'siteoperation' => true);
				$reports_interval = 7;
				break;

			case AMPOLIROS_SECURITY_PRESET_PARANOID :
				$max_wrong_logins = 1;
				$wrong_login_delay = 3;
				$session_lifetime = 0;
				$alerts = array('wronglocalrootlogin' => true, 'wronglocaluserlogin' => true, 'wrongremotelogin' => true, 'moduleoperation' => true, 'modulesiteoperation' => true, 'siteoperation' => true);
				$reports_interval = 1;
				break;

			default :
				$result = false;
		}

		if ($result) {
			$this -> SetMaxWrongLogins($max_wrong_logins);
			$this -> SetWrongLoginDelay($wrong_login_delay);
			$this -> SetSessionLifetime($session_lifetime);
			$this -> SetAlertEvents($alerts);
			$this -> SetReportsInterval($reports_interval);
		}

		return $result;
	}

	/*!
	@function SetAlertsEmail
	@abstract Sets alerts destination email.
	@param email string - Destination email.
	*/
	public function setAlertsEmail($email) {
		$result = '';
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('SECURITY_ALERTS_EMAIL', $email);
		return $result;
	}

	/*!
	@function GetAlertsEmail
	@abstract Gets alerts destination email.
	@result Destination email.
	*/
	public function getAlertsEmail() {
		$cfg = new ConfigFile(AMP_CONFIG);
		return $cfg -> Value('SECURITY_ALERTS_EMAIL');
	}

	/*!
	@function SetReportsEmail
	@abstract Sets reports destination email.
	@param email string - Destination email.
	*/
	public function setReportsEmail($email) {
		$result = '';
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('SECURITY_REPORTS_EMAIL', $email);
		return $result;
	}

	/*!
	@function GetReportsEmail
	@abstract Gets reports destination email.
	@result Destination email.
	*/
	public function getReportsEmail() {
		$cfg = new ConfigFile(AMP_CONFIG);
		return $cfg -> Value('SECURITY_REPORTS_EMAIL');
	}

	/*!
	@function SetSessionLifetime
	@abstract Sets session lifetime.
	@param lifeTime integer - Session lifetime in seconds.
	*/
	public function setSessionLifetime($lifeTime) {
		$result = '';
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('SESSION_LIFETIME', $lifeTime);
		return $result;
	}

	/*!
	@function GetSessionLifetime
	@abstract Gets session lifetime.
	@result Session lifetime in seconds.
	*/
	public function getSessionLifetime() {
		$cfg = new ConfigFile(AMP_CONFIG);
		return $cfg -> Value('SESSION_LIFETIME');
	}

	/*!
	@function SetMaxWrongLogins
	@abstract Sets the max number of wrong logins.
	@param maxWrongLogins integer - Max number of wrong logins.
	@result True if the setting has been applied.
	*/
	public function setMaxWrongLogins($maxWrongLogins) {
		$result = '';
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('MAX_WRONG_LOGINS', $maxWrongLogins);
		return $result;
	}

	/*!
	@function GetMaxWrongLogins
	@abstract Gets the max number of allowed wrong logins.
	@result The max number of allowed wrong logins.
	*/
	public function getMaxWrongLogins() {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> Value('MAX_WRONG_LOGINS');
		if (!strlen($result))
			$result = 3;

		return $result;
	}

	/*!
	@function SetWrongLoginDelay
	@abstract Sets the delay before a new login when the previous one was wrong.
	@param delay integer - Delay in seconds.
	@result True if the setting has been applied.
	*/
	public function setWrongLoginDelay($delay) {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('WRONG_LOGIN_DELAY', $delay);
		return $result;
	}

	/*!
	@function GetWrongLoginDelay
	@abstract Gets the delay before a new login when the previous one was wrong.
	@result The delay in seconds.
	*/
	public function getWrongLoginDelay() {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> Value('WRONG_LOGIN_DELAY');
		if (!strlen($result))
			$result = 1;
		return $result;
	}

	/*!
	@function LockUnsecureWebServices
	@abstract Locks the unsecure web services, even if enabled.
	@result Always true.
	*/
	public function lockUnsecureWebServices() {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('LOCK_UNSECURE_WEBSERVICES', '1');
		return $result;
	}

	/*!
	@function GetUnsecureWebServicesLock
	@abstract Tells if the unsecure web services are locked.
	@result True if the unsecure web services are locked, false otherwise.
	*/
	public function getUnsecureWebServicesLock() {
		$result = false;
		$cfg = new ConfigFile(AMP_CONFIG);
		if ($cfg -> Value('LOCK_UNSECURE_WEBSERVICES') == '1')
			$result = true;
		return $result;
	}

	/*!
	@function AcceptOnlyHttpsRootAccess
	@result Always true.
	*/
	public function acceptOnlyHttpsRootAccess($accept = true) {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('ONLY_HTTPS_ROOT_ACCESS', $accept ? '1' : '0');
		return $result;
	}

	/*!
	@function GetOnlyHttpsRootAccess
	@abstract Tells if the only https root access is allowed.
	@result True if https is needed.
	*/
	public function getOnlyHttpsRootAccess() {
		$result = false;
		$cfg = new ConfigFile(AMP_CONFIG);
		if ($cfg -> Value('ONLY_HTTPS_ROOT_ACCESS') == '1')
			$result = true;
		return $result;
	}

	/*!
	@function AcceptOnlyHttpsSiteAccess
	@result Always true.
	*/
	public function acceptOnlyHttpsSiteAccess($accept = true) {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('ONLY_HTTPS_SITE_ACCESS', $accept ? '1' : '0');
		return $result;
	}

	/*!
	@function GetOnlyHttpsSiteAccess
	@abstract Tells if the only https site access is allowed.
	@result True if https is needed.
	*/
	public function getOnlyHttpsSiteAccess() {
		$result = false;
		$cfg = new ConfigFile(AMP_CONFIG);
		if ($cfg -> Value('ONLY_HTTPS_SITE_ACCESS') == '1')
			$result = true;
		return $result;
	}

	/*!
	@function UnlockUnsecureWebServices
	@abstract Unlocks the unsecure web services.
	@result Always true.
	*/
	public function unlockUnsecureWebServices() {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('LOCK_UNSECURE_WEBSERVICES', '0');
		return $result;
	}

	/*!
	@function LogAccess
	@abstract Logs an access to the administration area.
	@result Always true.
	*/
	public function logAccess($user = '', $logout = false, $root = false, $ip = '') {
		Carthag :: import('com.solarix.ampoliros.io.log.Logger');
		$log = new Logger($this -> mAccessLog);
		$log -> LogEvent('ampoliros', ($root ? 'Root ' : 'User '.$user.' '). ($logout ? 'logged out' : 'logged in'). (strlen($ip) ? ' from address '.$ip : ''), LOGGER_NOTICE);
		return true;
	}

	/*!
	@function LogFailedAccess
	@abstract Logs a failed access to the administration area.
	@result Always true.
	*/
	public function logFailedAccess($user = '', $root = false, $ip = '') {
		Carthag :: import('com.solarix.ampoliros.io.log.Logger');
		$log = new Logger($this -> mAccessLog);
		$log -> LogEvent('ampoliros', 'Wrong access from '. ($root ? 'root ' : 'user '.$user.' '). (strlen($ip) ? 'from address '.$ip : ''), LOGGER_NOTICE);
		return true;
	}

	/*!
	@function GetAccessLog
	@abstract Returns the access log content.
	@result The access log full content.
	*/
	public function getAccessLog() {
		if (file_exists($this -> mAccessLog)) {
			return file_get_contents($this -> mAccessLog);
		}
		return '';
	}

	/*!
	@function EraseAccessLog
	@abstract Erases the entire access log.
	@result Always true.
	*/
	public function eraseAccessLog() {
		import('com.solarix.ampoliros.io.log.Logger');
		$log = new Logger($this -> mAccessLog);
		$log -> CleanLog();
		return true;
	}

	public function logEvent($event) {
	}

	public function getEventsLog() {
	}

	public function eraseEventsLog() {
	}

	public function logoutSession($session) {
		$result = true;
		if (strlen($session)) {
			if (file_exists(TMP_PATH.'phpsessions/'.$session))
				$result = unlink(TMP_PATH.'phpsessions/'.$session);
		} else
			$result = false;
		return $result;
	}

	// ----- Security check -----

	public function securityCheck() {
		$result = array();
		$result['unsecurewebservicesprofiles'] = $this -> GetUnsecureWebServicesProfiles();
		$result['unsecurelocalaccounts'] = $this -> GetUnsecureLocalAccounts();
		$result['unsecurewebservicesaccounts'] = $this -> GetUnsecureWebServicesAccounts();
		$result['rootpasswordcheck'] = $this -> CheckRootPassword();
		$result['rootdbpasswordcheck'] = $this -> CheckRootDatabasePassword();
		$result['siteswithunsecuredbpassword'] = $this -> GetSitesWithUnsecureDatabasePassword();
		$result['registerglobals'] = $this -> GetRegisterGlobalsSetting();
		return $result;
	}

	/*!
	@function GetUnsecureWebServicesProfiles
	@abstract Gets the list of the web services profiles with unsecure methods enabled.
	@result Array of the profiles id and name.
	*/
	public function getUnsecureWebServicesProfiles() {
		$result = array();
        $reg = Registry::instance();
        $amp_db = $reg->getEntry('amp.root.db');

		$query = $amp_db -> Execute('SELECT xmlrpcpermissions.profileid AS profileid,xmlrpcprofiles.profilename AS profilename FROM xmlrpcpermissions,xmlrpcmethods,xmlrpcprofiles WHERE ((xmlrpcpermissions.method=xmlrpcmethods.name AND xmlrpcmethods.unsecure='.$amp_db -> Format_Text($amp_db -> fmttrue).') OR (xmlrpcpermissions.method=\'\' AND xmlrpcpermissions.module=xmlrpcmethods.module AND xmlrpcmethods.unsecure='.$amp_db -> Format_Text($amp_db -> fmttrue).')) AND xmlrpcprofiles.id=xmlrpcpermissions.profileid GROUP BY xmlrpcpermissions.profileid,xmlrpcprofiles.profilename');
		while (!$query -> eof) {
			$result[$query -> Fields('profileid')] = $query -> Fields('profilename');
			$query -> MoveNext();
		}
		return $result;
	}

	/*!
	@function GetUnsecureLocalAccounts
	@abstract Tells which locale accounts have a too simple password. At this time it only checks
	    if the username is the same as the password.
	@result An array of the accounts with a too simple password.
	*/
	public function getUnsecureLocalAccounts() {
		$result = array();
		$reg = Registry::instance();
        $amp_db = $reg->getEntry('amp.root.db');
		$users_query = $amp_db -> Execute('SELECT username,password '.'FROM users '.'ORDER BY username');
		while (!$users_query -> eof) {
			$complete_username = $users_query -> Fields('username');
			$crypted_password = $users_query -> Fields('password');

			if (strpos($complete_username, '@')) {
				$username = substr($complete_username, 0, strpos($complete_username, '@'));
				$site = substr($complete_username, strpos($complete_username, '@') + 1);
			} else
				$username = $site = $complete_username;

			if (md5($username) == $crypted_password or md5($site) == $crypted_password or md5($complete_username) == $crypted_password)
				$result[] = $complete_username;

			$users_query -> MoveNext();
		}
		return $result;
	}

	/*!
	@function GetUnsecureWebServicesAccounts
	
	@abstract Tells which web services accounts have a too simple password. At this time it only checks
	    if the username is the same as the password.
	
	@result An array of the accounts with a too simple password.
	*/
	public function getUnsecureWebServicesAccounts() {
		$result = array();
		$reg = Registry::instance();
        $amp_db = $reg->getEntry('amp.root.db');
		$users_query = $amp_db -> Execute('SELECT username,password '.'FROM xmlrpcusers '.'ORDER BY username');
		while (!$users_query -> eof) {
			$username = $users_query -> Fields('username');
			$crypted_password = $users_query -> Fields('password');

			if (md5($username) == $crypted_password) {
				$result[] = $username;
			}

			$users_query -> MoveNext();
		}
		return $result;
	}

	public function checkRootPassword() {
		$result = true;
		$fh = @ fopen(CONFIG_PATH.'amprootpwd.cfg', 'r');
		if ($fh) {
			$password = fgets($fh, 4096);
			if (md5('') == $password or md5('amp') == $password)
				$result = false;
			fclose($fh);
		}
		return $result;
	}

	public function checkRootDatabasePassword() {
		$result = true;
		$reg = Registry::instance();
        $cfg = $reg->getEntry('amp.config');
		$username = $cfg -> Value('AMP_DBUSER');
		$password = $cfg -> Value('AMP_DBPASS');

		if ($password == '' or $username == $password)
			$result = false;
		return $result;
	}

	public function getSitesWithUnsecureDatabasePassword() {
		$result = array();
		$reg = Registry::instance();
        $amp_db = $reg->getEntry('amp.root.db');
		$sites_query = $amp_db -> Execute('SELECT id,siteid '.'FROM sites '.'WHERE sitedbuser=sitedbpassword '.'OR sitedbpassword=\'\' '.'ORDER BY siteid');
		while (!$sites_query -> eof) {
			$result[$sites_query -> Fields('id')] = $sites_query -> Fields('siteid');
			$sites_query -> MoveNext();
		}
		return $result;
	}

	/*!
	@function GetRegisterGlobalsSetting
	
	@abstract Gets the PHP register_globals ini setting.
	
	@result True if set to on, false if set to off.
	*/
	public function getRegisterGlobalsSetting() {
		if (ini_get('register_globals'))
			return true;
		else
			return false;
	}

	/*!
	@function GetLoggedSessions
	
	@abstract Gets the list of the sessions with logged root and site users.
	
	@result Array of sessions.
	*/
	public function getLoggedSessions() {
		$result['root'] = $result['sites'] = array();
		$dir = TMP_PATH.'phpsessions/';

		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' and $file != '..') {
						if (filesize($dir.$file)) {
							$content = file($dir.$file);

							$extracted = $this -> _sess_string_to_array($content[0]);

							if (isset($extracted['AMPROOT_AUTH_USER'])) {
								$result['root'][] = $file;
							}

							if (isset($extracted['AMP_AUTH_USER'])) {
								$result['sites'][$extracted['AMP_AUTH_USER']][] = $file;
							}
						}
					}
				}

				closedir($dh);
			}
		}
		return $result;
	}

	private function _sess_string_to_array($sd) {
		$sess_array = Array();
		$vars = explode(';', $sd);
		for ($i = 0; $i < sizeof($vars); $i ++) {
			$parts = explode('|', $vars[$i]);
			$key = $parts[0];
			$val = unserialize($parts[1].';');

			$sess_array[$key] = $val;
		}
		return $sess_array;
	}

	// ----- Reports -----

	public function setReportsInterval($interval) {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> SetValue('SECURITY_REPORTS_INTERVAL', $interval);
		return $result;
	}

	public function getReportsInterval() {
		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> Value('SECURITY_REPORTS_INTERVAL');
		if (!strlen($result))
			$result = 0;
		return $result;
	}

	public function sendReport() {
		$result = false;
		$email = $this -> GetReportsEmail();
		if (strlen($email)) {
			$sec_check = $this -> SecurityCheck();

			if ($sec_check['rootpasswordcheck'] == true)
				$root_password_check = 'Root password should be safe'."\n";
			else
				$root_password_check = 'Root password is NOT safe'."\n";

			if ($sec_check['rootdbpasswordcheck'] == true)
				$root_db_password_check = 'Root database password should be safe'."\n";
			else
				$root_db_password_check = 'Root database password is NOT safe'."\n";

			if (count($sec_check['unsecurewebservicesprofiles'])) {
				$unsecure_web_services_profiles = '';

				while (list (, $profile) = each($sec_check['unsecurewebservicesprofiles'])) {
					$unsecure_web_services_profiles.= $profile."\n";
				}
			} else
				$unsecure_web_services_profiles = 'No unsecure web services profiles.'."\n";

			if (count($sec_check['unsecurewebservicesaccounts'])) {
				$unsecure_web_services_accounts = '';

				while (list (, $account) = each($sec_check['unsecurewebservicesaccounts'])) {
					$unsecure_web_services_accounts.= (strlen($account) ? $account : 'Anonymous user')."\n";
				}
			} else
				$unsecure_web_services_accouns = 'No unsecure web services accounts.'."\n";

			if (count($sec_check['unsecurelocalaccounts'])) {
				$unsecure_local_accounts = '';

				while (list (, $account) = each($sec_check['unsecurelocalaccounts'])) {
					$unsecure_local_accounts.= $account."\n";
				}
			} else
				$unsecure_local_accounts = 'No unsecure local accounts.'."\n";

			if (count($sec_check['siteswithunsecuredbpassword'])) {
				$unsecure_db_sites = '';

				while (list (, $site) = each($sec_check['siteswithunsecuredbpassword'])) {
					$unsecure_db_sites.= $site."\n";
				}
			} else
				$unsecure_db_sites = 'No unsecure site database passwords.'."\n";

			$config = '';
			if (file_exists(AMP_CONFIG)) {
				$config = file_get_contents(AMP_CONFIG);
			}

			$result = mail($email, '[AMPOLIROS SECURITY REPORT] - Scheduled security report about '.AMP_NAME.'.'.AMP_DOMAIN, 'This is the scheduled security report about '.AMP_NAME.'.'.AMP_DOMAIN."\n\n".'== SECURITY CHECK RESULTS =='."\n"."\n".'--> Root password check'."\n".$root_password_check."\n".'--> Root database password check'."\n".$root_db_password_check."\n".'--> Sites with unsecure database password'."\n".$unsecure_db_sites."\n".'--> Unsecure local accounts'."\n".$unsecure_local_accounts."\n".'--> Unsecure web services profiles'."\n".$unsecure_web_services_profiles."\n".'--> Unsecure web services accounts'."\n".$unsecure_web_services_accounts."\n".'== CURRENT AMPOLIROS CONFIGURATION FILE CONTENT =='."\n\n".$config);

			if ($result) {
				$cfg = new ConfigFile(AMP_CONFIG);

				$cfg -> SetValue('LAST_SECURITY_REPORT', time());
			}
		}

		return $result;
	}

	// ----- Alerts -----

	/*!
	@function SetAlertEvents
	@abstract Sets which events have to be notified by email.
	@param events array - Array of the events to be notified. Allowed keys: 
	    wronglocalrootlogin, wronglocaluserlogin, wrongremotelogin, 
	    moduleoperation, modulesiteoperation, siteoperation.
	@result Always true.
	*/
	public function setAlertEvents($events) {
		$cfg = new ConfigFile(AMP_CONFIG);
		$cfg -> SetValue('ALERT_ON_WRONG_LOCAL_ROOT_LOGIN', $events['wronglocalrootlogin'] ? '1' : '0');
		$cfg -> SetValue('ALERT_ON_WRONG_LOCAL_USER_LOGIN', $events['wronglocaluserlogin'] ? '1' : '0');
		$cfg -> SetValue('ALERT_ON_WRONG_REMOTE_LOGIN', $events['wrongremotelogin'] ? '1' : '0');
		$cfg -> SetValue('ALERT_ON_MODULE_OPERATION', $events['moduleoperation'] ? '1' : '0');
		$cfg -> SetValue('ALERT_ON_MODULE_SITE_OPERATION', $events['modulesiteoperation'] ? '1' : '0');
		$cfg -> SetValue('ALERT_ON_SITE_OPERATION', $events['siteoperation'] ? '1' : '0');
		return true;
	}

	/*!
	@function GetAlertEvents
	@abstract Tells which events have to be notified by email.
	@result Array of the events.
	*/
	public function getAlertEvents() {
		$result = array();
		$cfg = new ConfigFile(AMP_CONFIG);
		$result['wronglocalrootlogin'] = $cfg -> Value('ALERT_ON_WRONG_LOCAL_ROOT_LOGIN') == '1' ? true : false;
		$result['wronglocaluserlogin'] = $cfg -> Value('ALERT_ON_WRONG_LOCAL_USER_LOGIN') == '1' ? true : false;
		$result['wrongremotelogin'] = $cfg -> Value('ALERT_ON_WRONG_REMOTE_LOGIN') == '1' ? true : false;
		$result['moduleoperation'] = $cfg -> Value('ALERT_ON_MODULE_OPERATION') == '1' ? true : false;
		$result['modulesiteoperation'] = $cfg -> Value('ALERT_ON_MODULE_SITE_OPERATION') == '1' ? true : false;
		$result['siteoperation'] = $cfg -> Value('ALERT_ON_SITE_OPERATION') == '1' ? true : false;
		return $result;
	}

	/*!
	@function SendAlert
	@abstract Send an event alert by email.
	@param event string - Event description.
	@result True if the email has been sent.
	*/
	public function sendAlert($event) {
		$result = false;
		$email = $this -> GetAlertsEmail();
		if (strlen($email)) {
			$result = mail($email, '[AMPOLIROS SECURITY ALERT] - Security alert on '.AMP_NAME.'.'.AMP_DOMAIN, 'Warning: an event marked to be notified has been issued on '.AMP_NAME.'.'.AMP_DOMAIN."\n\n".'Event was: '.$event);
		}
		return $result;
	}
}

?>