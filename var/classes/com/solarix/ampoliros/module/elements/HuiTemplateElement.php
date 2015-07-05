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
// $Id: HuiTemplateElement.php,v 1.1 2004-07-14 15:16:44 alex Exp $

package('com.solarix.ampoliros.module.elements');

import('com.solarix.ampoliros.module.ModuleElement');

class HuiTemplateElement extends ModuleElement {
	public $type = 'huitemplate';
	public $site = false;

	function HuiTemplateElement($ampdb, $sitedb, $modname, $name, $file, $basedir) {
		$this -> ModuleElement($ampdb, $sitedb, $modname, $name, $file, $basedir);
	}

	function DoInstallAction($params) {
		$result = false;

		if (strlen($params['file'])) {
			$class_path = str_replace('.', '/', $params['file']).'.php';
			$class_dir = substr($class_path, 0, strrpos($class_path, '/'));
			$class_name = substr($class_path, strrpos($class_path, '/') + 1);

			if (file_exists($this -> basedir.'/var/classes/'.$class_path)) {
				if (!file_exists(PRIVATE_TREE.'var/classes/'.$class_dir)) {
					mkdirs(PRIVATE_TREE.'var/classes/'.$class_dir.'/', 0755);
				}

				$result = copy($this -> basedir.'/var/classes/'.$class_path, PRIVATE_TREE.'var/classes/'.$class_path);
			}
		}

		return $result;
	}

	function DoUninstallAction($params) {
		$result = false;

		if (strlen($params['file'])) {
			$class_path = str_replace('.', '/', $params['file']).'.php';
			$class_dir = substr($class_path, 0, strrpos($class_path, '/'));
			$class_name = substr($class_path, strrpos($class_path, '/') + 1);

			if (file_exists(PRIVATE_TREE.'var/classes/'.$class_path)) {
				$result = unlink(PRIVATE_TREE.'var/classes/'.$class_path);
			}
		}

		return $result;
	}

	function DoUpdateAction($params) {
		$result = $this -> DoInstallAction($params);
	}

	function checkClass($params) {
		if (strlen($params['file'])) {
			$class_path = str_replace('.', '/', $params['file']).'.php';
			$class_dir = substr($class_path, 0, strrpos($class_path, '/'));
			$class_name = substr($class_path, strrpos($class_path, '/') + 1);

			if (file_exists($this -> basedir.'/var/classes/'.$class_path))
				return true;
			else
				return false;
		}
	}
}

?>