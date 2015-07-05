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
// $Id: DBLayer.php,v 1.8 2004-07-14 13:15:37 alex Exp $

package('com.solarix.ampoliros.db');

import('com.solarix.ampoliros.db.RecordSet');

/*!
 @class DBLayer
 @abstract Database layer abstraction
 */
abstract class DBLayer extends Object {
    // Internal properties
    //
    public $layer;
    public $dbhandler = false;
    public $opened = false;
    public $persistent = false;
    public $autocommit = true;
    public $mLastError;
    private $amp;

    // Database parameters
    //
    /*! @var dbname string - Database name */
    public $dbname;
    /*! @var dbuser string - Database user name */
    public $dbuser;
    /*! @var dbpass string - Database password */
    public $dbpass;
    /*! @var dbhost string - Database server host name */
    public $dbhost;
    /*! @var dbport int - Database server port */
    public $dbport;
    /*! @var dblog string - Database log file full path */
    public $dblog;
    /*! @var dbextra string - Extra parameters */
    public $dbextra;

    // Format types
    //
    /*! @var fmtconcat string - SQL concatenation string */
    public $fmtconcat = '+';
    /*! @var fmtdate string - Date formatting string, in PHP syntax */
    public $fmtdate = "'Y-m-d'";
    /*! @var fmttime string - Time formatting string, in PHP syntax */
    public $fmttime = "'H:i:s'";
    /*! @var fmttimestamp string - Time stamp formatting string, in PHP syntax */
    public $fmttimestamp = "'Y-m-d H:i:s'";
    /*! @var fmttrue string - True value */
    public $fmttrue = '1';
    /*! @var fmtfalse string - False value */
    public $fmtfalse = '0';
    /*! @var fmtquote string - Text quote string */
    public $fmtquote = "\\'";

    // Supported features
    //
    public $supp = array();

    // ----------------------------------------------------
    // Internal methods
    // ----------------------------------------------------

    /*!
     @function DBLayer
     @abstract Class constructor
     @discussion It should be called through NewDBLayer function
     @param params array - Database parameters
     */
    function DBLayer($params) {
        $this -> dblog = $params['dblog'];
        import('com.solarix.ampoliros.core.Ampoliros');
        $this->amp = Ampoliros :: instance('Ampoliros');
        
    }

    // ----------------------------------------------------
    // Database methods
    // ----------------------------------------------------

    /*!
     @function ListDatabases
     @abstract Lists all the databases in the database server
     @result Array of the databases or false if none
     */
    function ListDatabases() {
        return $this -> _ListDatabases();
    }

    function _ListDatabases() {
        return FALSE;
    }

    // Lists all tables
    //
    function ListTables() {
        return $this -> _ListTables();
    }

    function _ListTables() {
        return FALSE;
    }

    // List all columns of a table
    //
    function ListColumns($table) {
        return $this -> _ListColumns($table);
    }

    function _ListColumns($table) {
        return FALSE;
    }

    // Creates a new database
    //
    function CreateDB($params) {
        return $this -> _CreateDB($params);
    }

    function _CreateDB($params) {
        return FALSE;
    }

    // Drops a database
    //
    function DropDB($params) {
        return $this -> _DropDB($params);
    }

    function _DropDB($params) {
        return FALSE;
    }

    // Opens connection to the database
    //
    function Connect($params) {
        $result = FALSE;

        if ($this -> opened == FALSE) {
            if (isset($params['dbname']))
                $this -> dbname = $params['dbname'];
            if (isset($params['dbuser']))
                $this -> dbuser = $params['dbuser'];
            if (isset($params['dbpass']))
                $this -> dbpass = $params['dbpass'];
            if (isset($params['dbhost']))
                $this -> dbhost = $params['dbhost'];
            if (isset($params['dbport']))
                $this -> dbport = $params['dbport'];
            if (isset($params['dbextra']))
                $this -> dbextra = $params['dbextra'];

            if (isset($params['persistent']) and $params['persistent'] == TRUE)
                $result = $this -> _pconnect();
            else
                $result = $this -> _connect();

            if ($result != FALSE) {
                $this -> opened = TRUE;
                $this -> dbhandler = $result;
                if (isset($params['persistent']) and $params['persistent'] == TRUE)
                    $this -> persistent = TRUE;
            }
        } else {
            $this -> mLastError = 'Unable to connect to database '.$this -> dbname;
            import('com.solarix.ampoliros.io.log.Logger');
            $this -> log = new Logger($this -> dblog);
            $this -> log -> logevent('ampoliros.dblayer_library.dblayer_class.connect', $this -> mLastError, LOGGER_ERROR);
        }

        return $result;
    }

    function _Connect() {
        return FALSE;
    }

    function _PConnect() {
        return FALSE;
    }

    // Dumps an entire database
    //
    function DumpDB($params) {
        return $this -> _DumpDB($params);
    }

    function _DumpDB($params) {
        return FALSE;
    }

    // Closes connection to the database
    //
    function Close() {
        $result = FALSE;

        if ($this -> opened == TRUE) {
            if ($this -> _close() == TRUE) {
                $this -> opened = FALSE;
                $result = TRUE;
            } else {
                $this -> mLastError = 'Unable to close database';
                import('com.solarix.ampoliros.io.log.Logger');
                $this -> log = new Logger($this -> dblog);
                $this -> log -> logevent('ampoliros.dblayer_library.dblayer_class.close', $this -> mLastError, LOGGER_ERROR);
            }
        } else {
            $this -> mLastError = 'Tried to close an already closed database';
            import('com.solarix.ampoliros.io.log.Logger');
            $this -> log = new Logger($this -> dblog);
            $this -> log -> logevent('ampoliros.dblayer_library.dblayer_class.close', $this -> mLastError, LOGGER_ERROR);
            $result = TRUE;
        }

        return $result;
    }

    function _Close() {
        return FALSE;
    }

    function ErrorMsg() {
        return $this -> mLastError;
    }

    // ----------------------------------------------------
    // Tables methods
    // ----------------------------------------------------

    // Creates a table
    //
    function CreateTable($params) {
        return $this -> _CreateTable($params);
    }

    function _CreateTable($params) {
        return FALSE;
    }

    // Drops a table
    //
    function DropTable($params) {
        return $this -> _DropTable($params);
    }

    function _DropTable($params) {
        return FALSE;
    }

    /*!
     @function AddColumn
     @abstract Adds a column to a table.
     @param params array - Array of paratemers.
     */
    function AddColumn($params) {
        return $this -> _AddColumn($params);
    }

    function _AddColumn($params) {
        return FALSE;
    }

    /*!
     @function RemoveColumn
     @abstract Removes a column to a table.
     @param params array - Array of paratemers.
     */
    function RemoveColumn($params) {
        return $this -> _RemoveColumn($params);
    }

    function _RemoveColumn($params) {
        return FALSE;
    }

    // Alters a table
    //
    function AlterTable($params) {
        return $this -> _AlterTable($params);
    }

    function _AlterTable($params) {
        return FALSE;
    }

    // ----------------------------------------------------
    // Sequences methods
    // ----------------------------------------------------

    // Creates a sequence
    //
    function CreateSeq($params) {
        return $this -> _CreateSeq($params);
    }

    function _CreateSeq($params) {
        return FALSE;
    }

    function CreateSeqQuery($params) {
        return $this -> _CreateSeqQuery($params);
    }

    function _CreateSeqQuery($params) {
        return FALSE;
    }

    // Drops a sequence
    //
    function DropSeq($params) {
        return $this -> _DropSeq($params);
    }

    function _DropSeq($params) {
        return FALSE;
    }

    function DropSeqQuery($params) {
        return $this -> _DropSeqQuery($params);
    }

    function _DropSeqQuery($params) {
        return FALSE;
    }

    // Gets current sequence value
    //
    function CurrSeqValue($params) {
        return $this -> _CurrSeqValue($params);
    }

    function _CurrSeqValue($params) {
        return FALSE;
    }

    function CurrSeqValueQuery($params) {
        return $this -> _CurrSeqValueQuery($params);
    }

    function _CurrSeqValueQuery($params) {
        return FALSE;
    }

    // Advances sequence to the next value and returns it
    //
    function NextSeqValue($params) {
        return $this -> _NextSeqValue($params);
    }

    function _NextSeqValue($params) {
        return FALSE;
    }

    function NextSeqValueQuery($params) {
        return $this -> _NextSeqValueQuery($params);
    }

    function _NextSeqValueQuery($params) {
        return FALSE;
    }

    // ----------------------------------------------------
    // Query methods
    // ----------------------------------------------------

    // Splits sql queries to single queries. This comes from PHPMyAdmin
    //
    function Split_Sql($sql) {
        $sql = trim($sql);
        $sql_len = strlen($sql);
        $char = '';
        $string_start = '';
        $buffer = array();
        $ret = array();
        $in_string = FALSE;

        for ($i = 0; $i < $sql_len; ++ $i) {
            $char = $sql[$i];

            // We are in a string, check for not escaped end of strings except for
            // backquotes that can't be escaped
            if ($in_string) {
                for (;;) {
                    $i = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if (!$i) {
                        $ret[] = $sql;
                        return $ret;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else
                        if ($string_start == '`' || $sql[$i -1] != '\\') {
                            $string_start = '';
                            $in_string = FALSE;
                            break;
                        }
                    // one or more Backslashes before the presumed end of string...
                    else {
                        // ... first checks for escaped backslashes
                        $j = 2;
                        $escaped_backslash = FALSE;
                        while ($i - $j > 0 && $sql[$i - $j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j ++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash) {
                            $string_start = '';
                            $in_string = FALSE;
                            break;
                        }
                        // ... else loop
                        else {
                            $i ++;
                        }
                    } // end if...elseif...else
                } // end for
            } // end if (in string)

            // We are not in a string, first check for delimiter...
            else
                if ($char == ';') {
                    // if delimiter found, add the parsed part to the returned array
                    $ret[] = substr($sql, 0, $i);
                    $sql = ltrim(substr($sql, min($i +1, $sql_len)));
                    $sql_len = strlen($sql);
                    if ($sql_len) {
                        $i = -1;
                    } else {
                        // The submited statement(s) end(s) here
                        return $ret;
                    }
                } // end else if (is delimiter)

            // ... then check for start of a string,...
            else
                if (($char == '"') || ($char == '\'') || ($char == '`')) {
                    $in_string = TRUE;
                    $string_start = $char;
                } // end else if (is start of string)

            // ... for start of a comment (and remove this comment if found)...
            else
                if ($char == '#' || ($char == ' ' && $i > 1 && $sql[$i -2].$sql[$i -1] == '--')) {
                    // starting position of the comment depends on the comment type
                    $start_of_comment = (($sql[$i] == '#') ? $i : $i -2);
                    // if no "\n" exits in the remaining string, checks for "\r"
                    // (Mac eol style)
                    $end_of_comment = (strpos(' '.$sql, "\012", $i +2)) ? strpos(' '.$sql, "\012", $i +2) : strpos(' '.$sql, "\015", $i +2);
                    if (!$end_of_comment) {
                        // no eol found after '#', add the parsed part to the returned
                        // array and exit
                        $ret[] = trim(substr($sql, 0, $i -1));
                        return $ret;
                    } else {
                        $sql = substr($sql, 0, $start_of_comment).ltrim(substr($sql, $end_of_comment));
                        $sql_len = strlen($sql);
                        $i --;
                    } // end if...else
                } // end else if (is comment)
        }

        // add any rest to the returned array
        if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
            $ret[] = $sql;
        }

        return $ret;
    }

    // Executes a query and returns a recordset
    //
    function Execute($query) {
        $result = false;
        if ($this -> opened) {
            $pieces = $this -> split_sql($query);
            for ($i = 0; $i < count($pieces); $i ++) {
                if ($this->amp -> getState() == Ampoliros :: STATE_DEBUG) {
                    $GLOBALS['gEnv']['runtime']['debug']['queries'][] = $pieces[$i];
                    $debug_counter = $GLOBALS['gEnv']['runtime']['debug']['dbloadtime'] -> AdvanceCounter();
                    $GLOBALS['gEnv']['runtime']['debug']['dbloadtime'] -> Start($debug_counter.': '.$pieces[$i]);

                    $resid = $this -> _query($pieces[$i]);

                    $GLOBALS['gEnv']['runtime']['debug']['dbloadtime'] -> Stop($debug_counter.': '.$pieces[$i]);

                    if (defined('AMP_DBDEBUG')) {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $this -> log = new Logger($this -> dblog);
                        $this -> log -> logevent('ampoliros.dblayer_library.dblayer_class.execute', 'Executed query '.$pieces[$i], LOGGER_DEBUG);
                    }
                }
                else $resid = $this -> _query($pieces[$i]);

                if ($resid == false) {
                    $this -> mLastError = 'Unable to execute query '.$pieces[$i];
                    import('com.solarix.ampoliros.io.log.Logger');
                    $this -> log = new Logger($this -> dblog);
                    $this -> log -> logevent('ampoliros.dblayer_library.dblayer_class.execute', $this -> mLastError, LOGGER_ERROR);
                    $result = false;
                } else
                    if (($i == count($pieces) - 1) and ($resid != 1)) {
                        $rsname = 'recordset_'.$this -> layer;
                        $result = new $rsname ($resid);
                    } else {
                        $result = true;
                    }
            }
        } else {
            $this -> mLastError = 'Database not connected';
            import('com.solarix.ampoliros.io.log.Logger');
            $this -> log = new Logger($this -> dblog);
            $this -> log -> logevent('ampoliros.dblayer_library.dblayer_class.execute', $this -> mLastError, LOGGER_ERROR);
        }

        return $result;
    }

    // Returns number of affected rows
    //
    function AffectedRows() {
        $result = FALSE;

        if ($this -> supp['affrows'] != FALSE) {
            $result = $this -> _affectedrows();
        }

        return $result;
    }

    function _AffectedRows() {
        return FALSE;
    }

    // ----------------------------------------------------
    // Transaction mehods
    // ----------------------------------------------------

    // Sets autocommit mode
    //
    function TransAutocommit($autocommit) {
        $result = FALSE;

        if ($this -> supp['affrows'] != FALSE and $this -> autocommit != $autocommit) {
            $result = $this -> _transautocommit();
            $this -> autocommit = $autocommit;
        }

        return $result;
    }

    function _TransAutocommit() {
        return FALSE;
    }

    // Commits a transaction
    //
    function TransCommit() {
        $result = FALSE;

        if ($this -> supp['transactions'] != FALSE and $this -> opened and !$this -> autocommit)
            $result = $this -> _transcommit();

        return $result;
    }

    function _TransCommit() {
        return FALSE;
    }

    // Rolls back a transaction
    //
    function TransRollback() {
        $result = FALSE;

        if ($this -> supp['transactions'] != FALSE and $this -> opened and !$this -> autocommit)
            $result = $this -> _transrollback();

        return $result;
    }

    function _TransRollBack() {
        return FALSE;
    }

    // ----------------------------------------------------
    // Fields formatting methods
    // ----------------------------------------------------

    function Format_Text($string) {
        //return "'".str_replace( "'", $this->fmtquote, $string )."'";
        if (get_magic_quotes_gpc() == 1)
            $string = stripslashes($string);
        return "'".str_replace("'", "''", $string)."'";
    }

    function Format_Timestamp($timestamp) {

    }

    function Format_Date($date) {
        return date($this -> fmtdate, $date);
    }

    function Format_Time($time) {
        return date($this -> fmttime, $time);
    }

    function Format_UnixTimestamp($timestamp) {
        return date($this -> fmttimestamp, $timestamp);
    }

    function Concatenate() {
        $first = TRUE;
        $s = '';
        $arr = func_get_args();
        $concat = $this -> fmtconcat;
        foreach ($arr as $a) {
            if ($first) {
                $s = (string) $a;
                $first = FALSE;
            } else
                $s.= $concat.$a;
        }
        return $s;
    }

    function GetTimestampFromDateArray($date) {
        if (!isset($date['year']))
            $date['year'] = '';
        if (!isset($date['mon']))
            $date['mon'] = '';
        if (!isset($date['mday']))
            $date['mday'] = '';
        if (!isset($date['hours']))
            $date['hours'] = '';
        if (!isset($date['minutes']))
            $date['minutes'] = '';
        if (!isset($date['seconds']))
            $date['seconds'] = '';

        switch (strlen($date['year'])) {
            case '0' :
                $date['year'] = '2000';
                break;
            case '1' :
                $date['year'] = '200'.$date['year'];
                break;
            case '2' :
                $date['year'] = '20'.$date['year'];
                break;
            case '3' :
                $date['year'] = '2'.$date['year'];
                break;
        }

        $date['year'] = str_pad($date['year'], 4, '0', STR_PAD_LEFT);
        $date['mon'] = str_pad($date['mon'], 2, '0', STR_PAD_LEFT);
        $date['mday'] = str_pad($date['mday'], 2, '0', STR_PAD_LEFT);
        $date['hours'] = str_pad($date['hours'], 2, '0', STR_PAD_LEFT);
        $date['minutes'] = str_pad($date['minutes'], 2, '0', STR_PAD_LEFT);
        $date['seconds'] = str_pad($date['seconds'], 2, '0', STR_PAD_LEFT);

        return sprintf("%s-%s-%s %s:%s:%s", $date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes'], $date['seconds']);
    }

    function GetDateArrayFromTimestamp($timestamp) {
        $timestamp = str_replace(',', '', $timestamp);
        $date_elements = explode(' ', $timestamp);
        list ($date['year'], $date['mon'], $date['mday']) = explode('-', $date_elements[0]);
        list ($date['hours'], $date['minutes'], $date['seconds']) = explode(':', $date_elements[1]);

        if (isset($date_elements[2])) {
            if ($date_elements[2] == 'PM' and $date['hours'] != 12)
                $date['hours'] += 12;
            if ($date_elements[2] == 'AM' and $date['hours'] == 12)
                $date['hours'] = 0;
        }

        return $date;
    }

    // ----------------------------------------------------
    // Data types abstraction
    // ----------------------------------------------------

    Function GetIntegerFieldTypeDeclaration($name, & $field) {
        return ("$name INT". (IsSet($field['default']) ? ' DEFAULT '.$field['default'] : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetTextFieldTypeDeclaration($name, & $field) {
        return ((IsSet($field['length']) ? "$name CHAR (".$field['length'].')' : "$name TEXT"). (IsSet($field['default']) ? ' DEFAULT '.$this -> GetTextFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetBooleanFieldTypeDeclaration($name, & $field) {
        return ("$name CHAR (1)". (IsSet($field['default']) ? ' DEFAULT '.$this -> GetBooleanFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetUnixTimestampFieldTypeDeclaration($name, & $field) {
        return ("$name INT ". (IsSet($field['default']) ? ' DEFAULT '.$this -> GetTimestampFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetDateFieldTypeDeclaration($name, & $field) {
        return ("$name CHAR (".strlen('YYYY-MM-DD').')'. (IsSet($field['default']) ? ' DEFAULT '.$this -> GetDateFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetTimestampFieldTypeDeclaration($name, & $field) {
        return ("$name CHAR (".strlen('YYYY-MM-DD HH:MM:SS').')'. (IsSet($field['default']) ? ' DEFAULT '.$this -> GetTimestampFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetTimeFieldTypeDeclaration($name, & $field) {
        return ("$name CHAR (".strlen('HH:MM:SS').')'. (IsSet($field['default']) ? ' DEFAULT '.$this -> GetTimeFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetFloatFieldTypeDeclaration($name, & $field) {
        return ("$name TEXT ". (IsSet($field['default']) ? ' DEFAULT '.$this -> GetFloatFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetDecimalFieldTypeDeclaration($name, & $field) {
        return ("$name TEXT ". (IsSet($field['default']) ? ' DEFAULT '.$this -> GetDecimalFieldValue($field['default']) : ''). (IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    Function GetIntegerFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : $value);
    }

    Function GetTextFieldValue($value) {
        return ("'$value'");
    }

    Function GetBooleanFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : ($value == 'true' ? "'".$this -> fmttrue."'" : "'".$this -> fmtfalse."'"));
    }

    Function GetUnixTimestampFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : "'$value'");
    }

    Function GetDateFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : "'$value'");
    }

    Function GetTimestampFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : "'$value'");
    }

    Function GetTimeFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : "'$value'");
    }

    Function GetFloatFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : "'$value'");
    }

    Function GetDecimalFieldValue($value) {
        return (!strcmp($value, 'NULL') ? 'NULL' : "'$value'");
    }

    Function GetFieldTypeDeclaration($name, & $field) {
        switch ($field['type']) {
            case 'integer' :
                return ($this -> GetIntegerFieldTypeDeclaration($name, $field));
            case 'text' :
            case 'string' :
                return ($this -> GetTextFieldTypeDeclaration($name, $field));
            case 'boolean' :
                return ($this -> GetBooleanFieldTypeDeclaration($name, $field));
            case 'date' :
                return ($this -> GetDateFieldTypeDeclaration($name, $field));
            case 'unixtimestamp' :
                return ($this -> GetUnixTimestampFieldTypeDeclaration($name, $field));
            case 'timestamp' :
                return ($this -> GetTimestampFieldTypeDeclaration($name, $field));
            case 'time' :
                return ($this -> GetTimeFieldTypeDeclaration($name, $field));
            case 'float' :
                return ($this -> GetFloatFieldTypeDeclaration($name, $field));
            case 'decimal' :
                return ($this -> GetDecimalFieldTypeDeclaration($name, $field));
        }
        return ('');
    }

    Function GetFieldValue($type, $value) {
        switch ($type) {
            case 'integer' :
                return ($this -> GetIntegerFieldValue($value));
            case 'text' :
            case 'string' :
                return ($this -> GetTextFieldValue($value));
            case 'boolean' :
                return ($this -> GetBooleanFieldValue($value));
            case 'unixtimestamp' :
                return ($this -> GetUnixTimestampFieldValue($value));
            case 'date' :
                return ($this -> GetDateFieldValue($value));
            case 'timestamp' :
                return ($this -> GetTimestampFieldValue($value));
            case 'time' :
                return ($this -> GetTimeFieldValue($value));
            case 'float' :
                return ($this -> GetFloatFieldValue($value));
            case 'decimal' :
                return ($this -> GetDecimalFieldValue($value));
        }
        return ('');
    }
}

?>