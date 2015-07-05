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
// $Id: DBLayer_mysql.php,v 1.7 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.db.drivers.mysql');

import('com.solarix.ampoliros.db.DBLayer');
import('carthag.db.DataAccessFactory');

/*!
@class dblayer_mysql

@abstract DbLayer for MySql.
*/
class DBLayer_mysql extends DBLayer {
	public $layer = 'mysql';
	public $fmtquote = "''";
	public $fmttrue = 't';
	public $fmtfalse = 'f';

	//public $suppautoinc      = true;
	//public $suppblob         = true;

	private $lastquery = false;

	public function dblayer_mysql($params) {
		$this -> support['affrows'] = true;
		$this -> support['transactions'] = false;
		return $this -> dblayer($params);
	}

	function _CreateDB($params) {
		$result = false;

		if (!empty($params['dbname'])) {
			$tmplink = @ mysql_connect($params['dbhost'], $params['dbuser'], $params['dbpass']);
			if ($tmplink) {
				if (mysql_query('CREATE DATABASE '.$params['dbname'], $tmplink))
					$result = true;
				else
					$this -> mLastError = @ mysql_error($tmplink);
			}
			//@mysql_close( $tmplink );
		}

		return $result;
	}

	function _DropDB($params) {
		$result = false;

		if (!empty($params['dbname']))
			$result = @ mysql_query('DROP DATABASE '.$params['dbname'], $this -> dbhandler);

		return $result;
	}

	function _Connect() {
		$result = @ mysql_connect($this -> dbhost, $this -> dbuser, $this -> dbpass);

		if ($result != false) {
			$this -> dbhandler = $result;
			if (!@ mysql_select_db($this -> dbname, $this -> dbhandler))
				$result = false;
		}

		return $result;
	}

	function _PConnect($params) {
		$result = @ mysql_pconnect($this -> dbhost, $this -> dbuser, $this -> dbpass);

		if ($result != false) {
			$this -> dbhandler = $result;
			if (!@ mysql_select_db($this -> dbname, $this -> dbhandler))
				$result = false;
		}

		return $result;
	}

	function _Close() {
		return true;
		//return @mysql_close( $this->dbhandler );
	}

	function _Query($query) {
		@ mysql_select_db($this -> dbname, $this -> dbhandler);
		$this -> lastquery = @ mysql_query($query, $this -> dbhandler);
		//if ( defined( 'DEBUG' ) and !$this->lastquery ) echo mysql_error();
		if (@ mysql_error($this -> dbhandler)) {
            import('com.solarix.ampoliros.io.log.Logger');
        $this -> log = new Logger($this -> dblog);
			$this -> log -> logevent('ampoliros.dblayer_mysql_library.dblayer_mysql_class._query', 'Error: '.@ mysql_error($this -> dbhandler), LOGGER_ERROR);
        }
		return $this -> lastquery;
	}

	function _AffectedRows($params) {
		$result = false;

		if ($this -> lastquery != false) {
			$result = @ mysql_affected_rows($this -> dbhandler);
		}

		return $result;
	}

	function _DropTable($params) {
		$result = false;

		if (!empty($params['tablename']) and $this -> opened)
			$result = $this -> _query('DROP TABLE '.$params['tablename']);

		return $result;
	}

	function _AddColumn($params) {
		$result = FALSE;

		if (!empty($params['tablename']) and !empty($params['columnformat']) and $this -> opened)
			$result = $this -> _query('ALTER TABLE '.$params['tablename'].' ADD COLUMN '.$params['columnformat']);

		return $result;
	}

	function _RemoveColumn($params) {
		$result = FALSE;

		if (!empty($params['tablename']) and !empty($params['column']) and $this -> opened)
			$result = $this -> _query('ALTER TABLE '.$params['tablename'].' DROP COLUMN '.$params['column']);

		return $result;
	}

	function _CreateSeq($params) {
		$result = false;

		if (!empty($params['name']) and !empty($params['start']) and $this -> opened) {
			$result = $this -> Execute('CREATE TABLE _sequence_'.$params['name'].' (sequence INT DEFAULT 0 NOT NULL AUTO_INCREMENT, PRIMARY KEY (sequence))');
			if ($result and ($params['start'] > 0))
				$this -> Execute('INSERT INTO _sequence_'.$params['name'].' (sequence) VALUES ('. ($params['start'] - 1).')');
		}

		return $result;
	}

	function _CreateSeqQuery($params) {
		$result = false;

		if (!empty($params['name']) and !empty($params['start'])) {
			$query = 'CREATE TABLE _sequence_'.$params['name'].' (sequence INT DEFAULT 0 NOT NULL AUTO_INCREMENT, PRIMARY KEY (sequence));';
			if ($params['start'] > 0)
				$query.= 'INSERT INTO _sequence_'.$params['name'].' (sequence) VALUES ('. ($params['start'] - 1).');';
			return $query;
		}

		return $result;
	}

	function _DropSeq($params) {
		if (!empty($params['name']))
			return $this -> _query('DROP TABLE _sequence_'.$params['name']);
		else
			return false;
	}

	function _DropSeqQuery($params) {
		if (!empty($params['name']))
			return 'DROP TABLE _sequence_'.$params['name'].';';
		else
			return false;
	}

	function _CurrSeqValue($name) {
		if (!empty($name)) {
			$result = $this -> _query('SELECT MAX(sequence) FROM _sequence_'.$name);
			return @ mysql_result($result, 0, 0);
		} else
			return false;
	}

	function _CurrSeqValueQuery($name) {
		$result = false;

		if (!empty($name)) {
			$result = 'SELECT MAX(sequence) FROM _sequence_'.$name.';';
		}

		return $result;
	}

	function _NextSeqValue($name) {
		if (!empty($name)) {
			if ($this -> _query('INSERT INTO _sequence_'.$name.' (sequence) VALUES (NULL)')) {
				$value = intval(mysql_insert_id($this -> dbhandler));
				$this -> _query('DELETE FROM _sequence_'.$name.' WHERE sequence<'.$value);
			}
			return $value;
		} else
			return false;
	}

	// ----------------------------------------------------
	//
	// ----------------------------------------------------

	Function GetTextFieldTypeDeclaration($name, & $field) {
		return (((IsSet($field['length']) and ($field['length'] <= 255)) ? "$name VARCHAR (".$field["length"].")" : "$name TEXT"). (IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTextFieldValue($value) {
		return ("'".AddSlashes($value)."'");
	}

	Function GetDateFieldTypeDeclaration($name, & $field) {
		return ($name." DATE". (IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimeFieldTypeDeclaration($name, & $field) {
		return ($name." TIME". (IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldTypeDeclaration($name, & $field) {
		return ("$name FLOAT8 ". (IsSet($field["default"]) ? " DEFAULT ".$this -> GetFloatFieldValue($field["default"]) : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDecimalFieldTypeDeclaration($name, & $field) {
		return ("$name DECIMAL ". (IsSet($field["length"]) ? " (".$field["length"].") " : ""). (IsSet($field["default"]) ? " DEFAULT ".$this -> GetDecimalFieldValue($field["default"]) : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldValue($value) {
		return (!strcmp($value, "NULL") ? "NULL" : "$value");
	}

	Function GetDecimalFieldValue($value) {
		return (!strcmp($value, "NULL") ? "NULL" : strval(intval($value * $this -> decimal_factor)));
	}
}

?>