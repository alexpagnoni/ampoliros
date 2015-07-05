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
// $Id: DBLayer_odbc.php,v 1.3 2004-07-08 15:04:23 alex Exp $

package('com.solarix.ampoliros.db.drivers.odbc');

import('com.solarix.ampoliros.db.DBLayer');

/*!
@class dblayer_odbc

@abstract DbLayer for ODBC.
*/
class dblayer_odbc extends dblayer {
	var $layer = "odbc";
	var $fmtdate = "'Y-m-d'";
	var $ftmtimestamp = "'Y-m-d, h:i:sA'";
	var $fmtquote = "''";

	//var $suppautoinc      = true;
	var $suppaffrows = true;
	//var $supptransactions = true;
	//var $suppblob         = true;

	var $lastquery = false;

	function dblayer_odbc($params) {
		return $this -> dblayer($params);
	}

	function CreateDB($params) {
		$result = false;

		if (!empty($params[dbname])) {
			$result = $this -> standalonequery($params, "CREATE DATABASE ".$params[dbname]);
		}

		return $result;
	}

	function DropDB($params) {
		$result = false;

		if (!empty($params[dbname]))
			$result = $this -> standalonequery($params, "DROP DATABASE ".$params[dbname]);

		return $result;
	}

	function _Connect() {
		$result = false;

		$result = @ odbc_connect($this -> dbhost, $this -> dbuser, $this -> dbpass);

		if ($result != false) {
			$this -> dbhandler = $result;
			if (!$this -> autocommit)
				$this -> _query("BEGIN");
		}

		return $result;
	}

	function _PConnect($params) {
		$result = false;

		$result = @ odbc_connect($this -> dbhost, $this -> dbuser, $this -> dbpass);

		if ($result != false) {
			$this -> dbhandler = $result;
			if (!$this -> autocommit)
				$this -> _query("BEGIN");
		}

		return $result;
	}

	function _Close() {
		if (!$this -> autocommit)
			$this -> _query("END");

		return @ pg_close($this -> dbhandler);
	}

	function _Query($query) {
		$this -> lastquery = @ pg_exec($this -> dbhandler, $query);
		return $this -> lastquery;
	}

	function _AffectedRows($params) {
		$result = false;

		if ($this -> lastquery != false) {
			$result = @ pg_cmdtuples($this -> lastquery);
		}

		return $result;
	}

	function _TransAutocommit($autocommit) {
		if ($this -> opened)
			$this -> _query($autocommit ? "END" : "BEGIN");
	}

	function _TransCommit() {
		return ($this -> _query("COMMIT") && $this -> _query("BEGIN"));
	}

	function _TransRollback() {
		return ($this -> _query("ROLLBACK") && $this -> _query("BEGIN"));
	}

	function CreateSeq($params) {
		if (!empty($params[name]) and !empty($params[start]) and $this -> opened)
			return $this -> _query("CREATE SEQUENCE ".$params[name]." INCREMENT 1". ($params[start] < 1 ? " MINVALUE ".$params[start] : "")." START ".$params[start]);
		else
			return false;
	}

	function DropSeq($params) {
		if (!empty($params[name]))
			return $this -> _query("DROP SEQUENCE ".$params[name]);
		else
			return false;
	}

	function CurrSeqValue($name) {
		if (!empty($name)) {
			$result = $this -> _query("SELECT last_value from ".$name);
			return @ pg_result($result, 0, 0);
		} else
			return false;
	}

	function NextSeqValue($name) {
		if (!empty($name)) {
			$result = $this -> _query("SELECT NEXTVAL ( '".$name."' )");
			return @ pg_result($result, 0, 0);
		} else
			return false;
	}
}

?>