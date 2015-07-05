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
// $Id: RecordSet_pgsql.php,v 1.4 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.db.drivers.pgsql');

import('com.solarix.ampoliros.db.RecordSet');

class RecordSet_pgsql extends RecordSet {
	var $suppseek = true;
	var $_start = false;

	publis function recordset_pgsql(& $resultid) {
		$this -> supp['seek'] = true;
		$this -> recordset($resultid);
	}

	protected function _init() {
		$this -> resultrows = @ pg_num_rows($this -> resultid);
		$this -> resultfields = @ pg_num_fields($this -> resultid);
	}

	protected function _seek($row) {
		$this -> currentrow = $row;
		return true;
	}

	protected function _fetch() {
		$this -> currfields = @ pg_fetch_array($this -> resultid, $this -> currentrow);
		return ($this -> currfields == true);
	}

	protected function _free() {
		return @ pg_free_result($this -> resultid);
	}
}

?>