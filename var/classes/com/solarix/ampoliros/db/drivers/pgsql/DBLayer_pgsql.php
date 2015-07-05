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
// $Id: DBLayer_pgsql.php,v 1.3 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.db.drivers.pgsql');

import('com.solarix.ampoliros.db.DBLayer');

define ('DBLAYER_DEFAULT_HOST', 'localhost');
define ('DBLAYER_DEFAULT_PORT', '5432');
define ('DBLAYER_DEFAULT_USER', 'nobody');
define ('DBLAYER_DEFAULT_PASS', '');

/*!
@class dblayer_pgsql

@abstract DbLayer for PostgreSQL.
*/
class dblayer_pgsql extends dblayer {
	var $layer = 'pgsql';
	var $fmtquote = "''";
	var $fmttrue = 't';
	var $fmtfalse = 'f';

	//var $suppautoinc      = true;
	//var $suppblob         = true;

	var $lastquery = false;

	function dblayer_pgsql($params) {
		$this -> support['affrows'] = true;
		$this -> support['transactions'] = true;

		return $this -> dblayer($params);
	}

	function StandaloneQuery($params, $query) {
		$result = false;

		if (strlen($params['dbhost']) > 0)
			$options = 'host='.$params['dbhost'];
		if (strlen($params['dbport']) > 0)
			$options.= ' port='.$params['dbport'];
		$options.= ' dbname=template1';
		if (strlen($params['dbuser']) > 0)
			$options.= ' user='.$params['dbuser'];
		if (strlen($params['dbpass']) > 0)
			$options.= ' password='.$params['dbpass'];

		$conn = @ pg_connect($options);

		if ($conn != false) {
			$result = @ pg_exec($conn, $query);
			@ pg_close($conn);
		}

		return $result;
	}

	function _CreateDB($params) {
		$result = false;

		if (!empty($params['dbname']))
			$result = $this -> StandaloneQuery($params, 'CREATE DATABASE '.$params['dbname']);

		return $result;
	}

	function _DropDB($params) {
		$result = false;

		if (!empty($params['dbname'])) {
			@ pg_close($this -> dbhandler);
			$result = $this -> standalonequery($params, 'DROP DATABASE '.$params['dbname']);
		}

		return $result;
	}

	function _Connect() {
		$result = false;

		if (strlen($this -> dbhost) > 0)
			$options = 'host='.$this -> dbhost;
		if (strlen($this -> dbport) > 0)
			$options.= ' port='.$this -> dbport;
		if (strlen($this -> dbname) > 0)
			$options.= ' dbname='.$this -> dbname;
		if (strlen($this -> dbuser) > 0)
			$options.= ' user='.$this -> dbuser;
		if (strlen($this -> dbpass) > 0)
			$options.= ' password='.$this -> dbpass;

		$result = @ pg_connect($options);

		if ($result != false) {
			$this -> dbhandler = $result;
			if (!$this -> autocommit)
				$this -> _query('BEGIN');
		}

		return $result;
	}

	function _PConnect($params) {
		$result = false;

		if (strlen($this -> dbhost) > 0)
			$options = 'host='.$this -> dbhost;
		if (strlen($this -> dbport) > 0)
			$options.= ' port='.$this -> dbport;
		if (strlen($this -> dbname) > 0)
			$options.= ' dbname='.$this -> dbname;
		if (strlen($this -> dbuser) > 0)
			$options.= ' user='.$this -> dbuser;
		if (strlen($this -> dbpass) > 0)
			$options.= ' password='.$this -> dbpass;

		$result = @ pg_pconnect($options);

		if ($result != false) {
			$this -> dbhandler = $result;
			if (!$this -> autocommit)
				$this -> _query('BEGIN');
		}
	}

	function _Close() {
		if (!$this -> autocommit)
			$this -> _query('END');

		return ($this -> dbhandler ? @ pg_close($this -> dbhandler) : true);
	}

	function _Query($query) {
		$this -> lastquery = @ pg_exec($this -> dbhandler, $query);
		return $this -> lastquery;
	}

	function _AffectedRows($params) {
		$result = false;

		if ($this -> lastquery != false) {
			$result = @ pg_affected_rows($this -> lastquery);
		}

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

	function _TransAutocommit($autocommit) {
		if ($this -> opened)
			$this -> _query($autocommit ? 'END' : 'BEGIN');
	}

	function _TransCommit() {
		return ($this -> _query('COMMIT') && $this -> _query('BEGIN'));
	}

	function _TransRollback() {
		return ($this -> _query('ROLLBACK') && $this -> _query('BEGIN'));
	}

	function _DropTable($params) {
		$result = false;

		if (!empty($params['tablename']) and $this -> opened)
			$result = $this -> _query('DROP TABLE '.$params['tablename']);

		return $result;
	}

	// ----------------------------------------------------
	// Sequences
	// ----------------------------------------------------

	function _CreateSeq($params) {
		if (!empty($params['name']) and !empty($params['start']) and $this -> opened) {

			return $this -> _query($this -> CreateSeqQuery($params));
		} else
			return false;
	}

	function _CreateSeqQuery($params) {
		if (!empty($params['name']) and !empty($params['start'])) {
			return 'CREATE SEQUENCE '.$params['name'].' INCREMENT 1'. ($params['start'] < 1 ? ' MINVALUE '.$params['start'] : '').' START '.$params['start'].';';
		} else
			return false;
	}

	function _DropSeq($params) {
		if (!empty($params['name']) and $this -> opened)
			return $this -> _query($this -> DropSeqQuery($params));
		else
			return false;
	}

	function _DropSeqQuery($params) {
		if (!empty($params['name']))
			return 'DROP SEQUENCE '.$params['name'].';';
		else
			return false;
	}

	function _CurrSeqValue($name) {
		if (!empty($name) and $this -> opened) {
			$result = $this -> _query($this -> CurrSeqValueQuery($name));
			return @ pg_result($result, 0, 0);
		} else
			return false;
	}

	function _CurrSeqValueQuery($name) {
		if (!empty($name)) {
			return 'SELECT last_value from '.$name;
		} else
			return false;
	}

	function _NextSeqValue($name) {
		if (!empty($name) and $this -> opened) {
			$result = $this -> _query($this -> NextSeqValueQuery($name));
			return @ pg_result($result, 0, 0);
		} else
			return false;
	}

	function _NextSeqValueQuery($name) {
		if (!empty($name)) {
			return "SELECT NEXTVAL ( '".$name."' )";
		} else
			return false;
	}

	// ----------------------------------------------------
	// SQL fields abstraction
	// ----------------------------------------------------

	Function GetTextFieldTypeDeclaration($name, & $field) {
		return ((IsSet($field['length']) ? "$name VARCHAR (".$field['length'].')' : $name.' TEXT'). (IsSet($field['default']) ? " DEFAULT '".$field['default']."'" : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
	}

	Function GetTextFieldValue($value) {
		return ("'".AddSlashes($value)."'");
	}

	Function GetDateFieldTypeDeclaration($name, & $field) {
		return ($name." DATE". (IsSet($field['default']) ? " DEFAULT '".$field['default']."'" : ''). (IsSet($field['notnull']) ? " NOT NULL" : ""));
	}

	Function GetTimeFieldTypeDeclaration($name, & $field) {
		return ($name." TIME". (IsSet($field['default']) ? " DEFAULT '".$field['default']."'" : ""). (IsSet($field['notnull']) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldTypeDeclaration($name, & $field) {
		return ("$name FLOAT8 ". (IsSet($field['default']) ? " DEFAULT ".$this -> GetFloatFieldValue($field["default"]) : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	/*Function GetDecimalFieldTypeDeclaration( $name, &$field )
	{
	    return( "$name INT8 ".( IsSet( $field["default"] ) ? " DEFAULT ".$this->GetDecimalFieldValue( $field["default"] ) : "" ).( IsSet( $field["notnull"] ) ? " NOT NULL" : "" ) );
	}*/

	Function GetDecimalFieldTypeDeclaration($name, & $field) {
		return ("$name DECIMAL ". (IsSet($field["length"]) ? " (".$field["length"].") " : ""). (IsSet($field["default"]) ? " DEFAULT ".$this -> GetDecimalFieldValue($field["default"]) : ""). (IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldValue($value) {
		return (!strcmp($value, "NULL") ? "NULL" : "$value");
	}

	/*Function GetDecimalFieldValue( $value )
	{
	    return( !strcmp( $value,"NULL" ) ? "NULL" : strval( intval( $value * $this->decimal_factor ) ) );
	}*/
}

?>