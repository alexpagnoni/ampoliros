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
// $Id: AmpolirosMaintenanceHandler.php,v 1.5 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.maintenance');

OpenLibrary('configman.library');

class AmpolirosMaintenanceHandler extends Object {
	public $mModuleConfig;
	public $mMaintenanceInterval;

	function AmpolirosMaintenanceHandler() {
		OpenLibrary('modules.library');

		$this -> mModuleConfig = new ModuleConfig($GLOBALS['gEnv']['root']['db'], 'ampoliros');

		$cfg = new ConfigFile(AMP_CONFIG);
		$result = $cfg -> Value('MAINTENANCE_INTERVAL');
		if (!strlen($result))
			$result = 0;
		$this -> mMaintenanceInterval = $result;
	}

	// ----- Settings -----

	function SetMaintenanceInterval($interval) {
		$cfg = new ConfigFile(AMP_CONFIG);

		$result = $cfg -> SetValue('MAINTENANCE_INTERVAL', (int) $interval);

		$this -> mMaintenanceInterval = (int) $interval;

		return $result;
	}

	function GetMaintenanceInterval() {
		return $this -> mMaintenanceInterval;
	}

	function GetLastMaintenanceTime() {
		$cfg = new ConfigFile(AMP_CONFIG);

		return $cfg -> Value('LAST_MAINTENANCE');
	}

	function GetTasksList() {
		$result = array();

		Carthag :: import('com.solarix.ampoliros.locale.Locale');

		$tasks_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT * '.'FROM maintenancetasks');

		while (!$tasks_query -> eof) {
			if (strlen($tasks_query -> Fields('catalog'))) {
				$locale = new Locale($tasks_query -> Fields('catalog'), $GLOBALS['gEnv']['root']['locale']['language']);

				$desc = $locale -> GetStr($tasks_query -> Fields('name'));
				unset($locale);
			} else
				$desc = $tasks_query -> Fields('name');

			$result[$tasks_query -> Fields('name')] = array('name' => $tasks_query -> Fields('name'), 'description' => $desc, 'enabled' => $tasks_query -> Fields('enabled') == $GLOBALS['gEnv']['root']['db'] -> fmttrue ? true : false);

			$tasks_query -> MoveNext();
		}

		$tasks_query -> Free();

		return $result;
	}

	function EnableTask($taskName) {
		return $GLOBALS['gEnv']['root']['db'] -> Execute('UPDATE maintenancetasks '.'SET enabled='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($GLOBALS['gEnv']['root']['db'] -> fmttrue).' '.'WHERE name='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($taskName));
	}

	function DisableTask($taskName) {
		return $GLOBALS['gEnv']['root']['db'] -> Execute('UPDATE maintenancetasks '.'SET enabled='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($GLOBALS['gEnv']['root']['db'] -> fmtfalse).' '.'WHERE name='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($taskName));
	}

	// ----- Facilities -----

	function DoMaintenance() {
		$result = array();

		$tasks_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT * FROM maintenancetasks '.'WHERE enabled='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($GLOBALS['gEnv']['root']['db'] -> fmttrue));
		while (!$tasks_query -> eof) {
			if (OpenLibrary($tasks_query -> Fields('file'), HANDLER_PATH)) {
				$function = $tasks_query -> Fields('name').'_maintenance_handler';
				$result[$tasks_query -> Fields('name')] = $function ();
			}
			$tasks_query -> MoveNext();
		}

		$tasks_query -> Free();
		$cfg = new ConfigFile(AMP_CONFIG);
		$cfg -> SetValue('LAST_MAINTENANCE', time());
		return $result;
	}

	function EnableReports() {
		$cfg = new ConfigFile(AMP_CONFIG);
		return $cfg -> SetValue('MAINTENANCE_REPORTS_ENABLED', '1');
	}

	function DisableReports() {
		$cfg = new ConfigFile(AMP_CONFIG);
		return $cfg -> SetValue('MAINTENANCE_REPORTS_ENABLED', '0');
	}

	function GetReportsEnableStatus() {
		$cfg = new ConfigFile(AMP_CONFIG);
		if ($cfg -> Value('MAINTENANCE_REPORTS_ENABLED') == '1') {
			return true;
		}
		return false;
	}

	function SetReportsEmail($email) {
		$cfg = new ConfigFile(AMP_CONFIG);
		return $cfg -> SetValue('MAINTENANCE_REPORTS_EMAIL', $email);
	}

	function GetReportsEmail() {
		$cfg = new ConfigFile(AMP_CONFIG);

		return $cfg -> Value('MAINTENANCE_REPORTS_EMAIL');
	}

	function SendReport($maintenanceResult) {
		$result = false;

		$cfg = new ConfigFile(AMP_CONFIG);
		$email = $cfg -> Value('MAINTENANCE_REPORTS_EMAIL');

		if ($cfg -> Value('MAINTENANCE_REPORTS_ENABLED') == '1' and strlen($email) and is_array($maintenanceResult)) {
			$result_text = '';

			Carthag :: import('com.solarix.ampoliros.locale.Locale');
			$locale = new Locale('amp_root_maintenance', $GLOBALS['gEnv']['root']['locale']['language']);

			$tasks_list = $this -> GetTasksList();

			foreach ($maintenanceResult as $task => $result) {
				$result_text.= "\n".'--> '.$tasks_list[$task]['description']."\n". ($result ? $locale -> GetStr('report_task_ok.label') : $locale -> GetStr('report_task_failed.label'))."\n";
			}

			$result = mail($email, '[AMPOLIROS MAINTENANCE REPORT] - Scheduled maintenance report about '.AMP_NAME.'.'.AMP_DOMAIN, 'This is the scheduled maintenance report about '.AMP_NAME.'.'.AMP_DOMAIN."\n\n".'== MAINTENANCE RESULTS =='."\n".$result_text);
		}

		return $result;
	}
}

?>