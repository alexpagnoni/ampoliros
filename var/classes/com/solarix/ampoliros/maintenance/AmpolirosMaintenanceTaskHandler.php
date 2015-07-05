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
// $Id: AmpolirosMaintenanceTaskHandler.php,v 1.3 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.maintenance');

class AmpolirosMaintenanceTaskHandler extends Object {
	function Install($args, $filePath) {
		$result = false;

		if (strlen($args['name']) and strlen($args['file']) and file_exists($filePath)) {
			if (!isset($args['enabled']))
				$args['enabled'] = 'false';
			if (!isset($args['catalog']))
				$args['catalog'] = '';

			$result = & $GLOBALS['gEnv']['root']['db'] -> Execute('INSERT INTO maintenancetasks '.'VALUES ('.$GLOBALS['gEnv']['root']['db'] -> Format_Text($args['name']).','.$GLOBALS['gEnv']['root']['db'] -> Format_Text(basename($args['file'])).','.$GLOBALS['gEnv']['root']['db'] -> Format_Text($args['catalog']).','.$GLOBALS['gEnv']['root']['db'] -> Format_Text($args['enabled'] == 'true' ? $GLOBALS['gEnv']['root']['db'] -> fmttrue : $GLOBALS['gEnv']['root']['db'] -> fmtfalse).')');

			if ($result) {
				copy($filePath, HANDLER_PATH.basename($args['file']));

				chmod(HANDLER_PATH.basename($args['file']), 0644);
			}
		}

		return $result;
	}

	function Update($args, $filePath) {
		$result = false;

		if (strlen($args['name']) and strlen($args['file']) and file_exists($filePath)) {
			if (!isset($args['catalog']))
				$args['catalog'] = '';

			$result = $GLOBALS['gEnv']['root']['db'] -> Execute('update maintenancetasks '.'SET file='.$GLOBALS['gEnv']['root']['db'] -> Format_Text(basename($args['file'])).','.'catalog='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($args['catalog']).' '.'WHERE name='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($args['name']));

			if ($result) {
				copy($filePath, HANDLER_PATH.basename($args['file']));

				chmod(HANDLER_PATH.basename($args['file']), 0644);
			}
		}

		return $result;
	}

	function Remove($args) {
		$result = false;

		if (strlen($args['name'])) {
			$result = $GLOBALS['gEnv']['root']['db'] -> Execute('DELETE FROM maintenancetasks '.'WHERE name='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($args['name']));

			unlink(HANDLER_PATH.$args['file']);
		}

		return $result;
	}
}

?>