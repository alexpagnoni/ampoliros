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
// $Id: LogCenter.php,v 1.6 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.io.log');

import('com.solarix.ampoliros.io.log.Logger');

/*!
@class LogCenter
@abstract Automatic logging of events in multiple logs.
*/
class LogCenter extends Object {
	/*! @public mModule string - Module id name. */
	private $mModule;

	/*!
	@function LogCenter
	@abstract Class constructor.
	*/
	public function LogCenter($module = '') {
		$this -> mModule = $module;
	}

	/*!
	@function LogEvent
	@abstract Logs an event
	@param destinations array - Array of the destination logs. Available keys: root, rootdb,
	remote, php, module, site, sitedb.
	@param context string - Event context.
	@param eventString string - String to be logged.
	@param eventType integer - Type of log event.
	@param die boolean - True if Ampoliros must die after logging the event.
	@result Always true
	*/
	public function logEvent($destinations, $context, $eventString, $eventType = LOGGER_GENERIC, $die = false) {
		global $gEnv;
		// Root
		//
		if (isset($destinations['root'])) {
			$tmp_log = new Logger($gEnv['root']['log']);
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		// Root db
		//
		if (isset($destinations['rootdb'])) {
			$tmp_log = new Logger($gEnv['root']['dblog']);
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		// Remote
		//
		if (isset($destinations['remote'])) {
			$tmp_log = new Logger($gEnv['remote']['log']);
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		// PHP
		//
		if (isset($destinations['php'])) {
			$tmp_log = new Logger($gEnv['core']['error']['log']);
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		// Module
		//
		if (isset($destinations['module']) and is_dir(MODULE_PATH.$this -> mModule)) {
			$tmp_log = new Logger(MODULE_PATH.$this -> mModule.'/module.log');
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		// Site
		//
		if (isset($destinations['site'])) {
			$tmp_log = new Logger($gEnv['site']['log']);
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		// Site db
		//
		if (isset($destinations['sitedb'])) {
			$tmp_log = new Logger($gEnv['sit']['dblog']);
			$tmp_log -> LogEvent($context, $eventString, $eventType);
			unset($tmp_log);
		}

		if ($die)
			AmpDie($eventString);
		return true;
	}
}

?>