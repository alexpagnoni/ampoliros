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
// $Id: HuiFrontController.php,v 1.7 2004-07-14 15:16:44 alex Exp $

package('com.solarix.ampoliros.hui.mvc');

import('carthag.util.Singleton');
import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.hui.widgets.HuiXml');
import('com.solarix.ampoliros.hui.helpers.HuiAuthHelperFactory');
import('com.solarix.ampoliros.hui.helpers.HuiValidatorHelper');
import('com.solarix.ampoliros.hui.mvc.HuiRequest');
import('com.solarix.ampoliros.hui.mvc.HuiResponse');
import('com.solarix.ampoliros.hui.mvc.HuiSession');

class HuiFrontController extends Singleton {
	public $session;
	public $request;
	public $response;
	private $commands;

	public function HuiFrontController() {
		$this->session = new HuiSession();
		$this->session->start();
		$this -> request = new HuiRequest();
		$this -> response = new HuiResponse();
		$this -> commands = array();
	}

	public function execute() {
		$validator = new HuiValidatorHelper();
		$validator -> validate();
		$auth = HuiAuthHelperFactory :: getAuthHelper();
		if ($auth -> auth($this -> request, $this->response)) {
			foreach ($GLOBALS['gEnv']['runtime']['disp']['hui'] as $disp => $values) {
				if (isset($this -> commands[$disp][$values['eventname']])) {
					import($this -> commands[$disp][$values['eventname']]);
					$classname = substr($this -> commands[$disp][$values['eventname']], strrpos($this -> commands[$disp][$values['eventname']], '.') + 1);
					$command = new $classname ($this);
					$command -> execute();
				}
			}
		}
		$hui = new Hui($GLOBALS['gEnv']['root']['db']);
		$hui->addChild(new HuiXml('def',array('definition' => $this->response->getContent())));
		$hui->render();
	}

	public function addCommand($disp, $command, $class) {
		$this -> commands[$disp][$command] = $class;
	}
}

?>