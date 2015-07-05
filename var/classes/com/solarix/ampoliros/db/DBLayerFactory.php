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
// $Id: DBLayerFactory.php,v 1.6 2004-07-13 15:20:52 alex Exp $

package('com.solarix.ampoliros.db');

import('com.solarix.ampoliros.db.DBLayer');

global $dbtypes;
$dbtypes = array();

class DBLayerFactory extends Object {
	public function dBLayerFactory() {
		OpenLibrary('configman.library');
		$this -> FillDbLayers();
	}

	public function fillDbLayers() {
		global $dbtypes;
		$dbtypes = array();
		$dbcfgfile = new ConfigFile(CONFIG_PATH.'dblayers.cfg', TRUE);
		$dbtypes = (array) $dbcfgfile -> ValuesArray();
	}

	public function addDbLayer($name, $desc) {
		global $dbtypes;
		if (!isset($dbtypes[$name])) {
			$cfg = new ConfigMan('ampoliros', CONFIG_PATH.'dblayers.cfg', CONFIGMODE_DIRECT);
			$cfg -> AddSegment($name, $name.' = '.$desc."\n");
			$this -> FillDBLayers();
		}
	}

	public function updateDbLayer($name, $desc) {
		global $dbtypes;
		if (isset($dbtypes[$name])) {
			$cfg = new ConfigMan('ampoliros', CONFIG_PATH.'dblayers.cfg', CONFIGMODE_DIRECT);
			$cfg -> ChangeSegment($name, $name.' = '.$desc."\n");
			$this -> FillDBLayers();
		}
	}

	public function removeDbLayer($name) {
		global $dbtypes;
		if (isset($dbtypes[$name])) {
			$cfg = new ConfigMan('ampoliros', CONFIG_PATH.'dblayers.cfg', CONFIGMODE_DIRECT);
			$cfg -> RemoveSegment($name);
			$this -> FillDBLayers();
		}
	}

	/*
	 @function NewDBLayer
	
	 @abstract Creates a new instance of DBLayer class
	
	 @param params array - Array of database parameters
	 */
	public function newDBLayer($params) {
		// Checks for database layer type
		//
		if (!isset($params['dbtype']) or !strlen($params['dbtype'])) {
			global $gEnv;
            import('com.solarix.ampoliros.core.Ampoliros');
            $amp = Ampoliros::instance('Ampoliros');
			if ($amp->getState() != Ampoliros::STATE_SETUP)
				$params['dbtype'] = AMP_DBTYPE;
			else
				return false;
		}

		// Creates a new instance of the specified database layer object
		//
		import('com.solarix.ampoliros.db.drivers.'.$params['dbtype'].'.DBLayer_'.$params['dbtype']);
		import('com.solarix.ampoliros.db.drivers.'.$params['dbtype'].'.RecordSet_'.$params['dbtype']);
		$objname = 'dblayer_'.$params['dbtype'];
		return new $objname ($params);
	}
}

?>