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
// $Id: ElementElement.php,v 1.4 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.module.elements');

import('com.solarix.ampoliros.module.ModuleElement');

/*!
@class ElementElement

@abstract Element element handler.
*/
class ElementElement extends ModuleElement {
	public $eltype;
	public $type = 'element';
	public $site = false;

	function ElementElement($ampdb, $sitedb, $modname, $name, $file, $basedir) {
		$this -> ModuleElement($ampdb, $sitedb, $modname, $name, $file, $basedir);

		import('com.solarix.ampoliros.module.ModuleElementFactory');
		$this -> eltype = new ModuleElementFactory($ampdb);
	}

	function DoInstallAction($params) {
		$result = false;

        /*
        if (strlen($params['class'])) {
            import('com.solarix.ampoliros.module.elements.ClassElement');
            $class_elem = new ClassElement($this->ampdb,$this->sitedb,$this->modname,$params['class'],$params['class'],$this->basedir);
            $class_params['name'] = $class_params['file'] = $params['class'];
            $class_elem->Install($class_params);
            //$this -> ampdb -> Execute('INSERT INTO elementtypes (id,typename,priority,site,file) VALUES ('.$this -> ampdb -> NextSeqValue('elementtypes_id_seq').','.$this -> ampdb -> Format_Text($params['type']).','.$element['priority'].','.$this -> ampdb -> Format_Text(($element['site'] ? $this -> ampdb -> fmttrue : $this -> ampdb -> fmtfalse)).','.$this -> ampdb -> Format_Text(basename($filepath)).')');
        }
        */
        
        if (strlen($params['file'])) {
			$params['file'] = $this -> basedir.'/var/handlers/'.basename($params['file']);

			if (@ copy($params['file'], HANDLER_PATH.basename($params['file']))) {
				@ chmod(HANDLER_PATH.basename($params['file']), 0644);
                $params['filepath'] = HANDLER_PATH.basename($params['file']);
				if ($this -> eltype -> Install($params)) {
					$result = true;
				}
			} else
				$this -> mLog -> LogEvent('ampoliros.element_element.elementelement_class.doinstallaction', 'In module '.$this -> modname.', element '.$params['name'].': Unable to copy element file ('.$params['file'].') to its destination ('.HANDLER_PATH.basename($params['file']).')', LOGGER_ERROR);
		} else
			$this -> mLog -> LogEvent('ampoliros.element_element.elementelement_class.doinstallaction', 'In module '.$this -> modname.', element '.$params['name'].': Empty element file name', LOGGER_ERROR);

		return $result;
	}

	function DoUninstallAction($params) {
		$result = false;

        $params['filepath'] = HANDLER_PATH.basename($params['file']);
		if ($this -> eltype -> Uninstall($params)) {
			if (@ unlink(HANDLER_PATH.basename($params['file']))) {
				$result = true;
			}
		}

		return $result;
	}

	function DoUpdateAction($params) {
		$result = false;

		if (strlen($params['file'])) {
			$params['file'] = $this -> basedir.'/var/handlers/'.basename($params['file']);

			if (@ copy($params['file'], HANDLER_PATH.basename($params['file']))) {
				@ chmod(HANDLER_PATH.basename($params['file']), 0644);
                $params['filepath'] = HANDLER_PATH.basename($params['file']);
				if ($this -> eltype -> Update($params)) {
					$result = true;
				}
			} else
				$this -> mLog -> LogEvent('ampoliros.element_element.elementelement_class.doupdateaction', 'In module '.$this -> modname.', element '.$params['name'].': Unable to copy element file ('.$params['file'].') to its destination ('.HANDLER_PATH.basename($params['file']).')', LOGGER_ERROR);
		} else
			$this -> mLog -> LogEvent('ampoliros.element_element.elementelement_class.doupdateaction', 'In module '.$this -> modname.', element '.$params['name'].': Empty element file name', LOGGER_ERROR);

		return $result;
	}
}
?>