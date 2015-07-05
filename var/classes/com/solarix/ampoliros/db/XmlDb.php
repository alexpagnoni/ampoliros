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
// $Id: XmlDb.php,v 1.4 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.db');

import('com.solarix.ampoliros.xml.XMLParser');

define( 'DBLAYER_PARSER_SQL_CREATE', 1 );
define( 'DBLAYER_PARSER_SQL_DROP',   2 );
define( 'DBLAYER_PARSER_SQL_UPDATE_OLD', 3 );
define( 'DBLAYER_PARSER_SQL_UPDATE_NEW', 4 );

/*!
@class XmlDb

@abstract Xml Dblayer class provides parsing of Ampoliros XSQL files.
*/
class XmlDb extends XMLParser
{
    /*! @var mLog log handler */
    var $mLog;
    /*! @var deffile definition file to parse */
    var $deffile;
    var $mData;
    var $sql = array();
    var $temp_sql;
    var $mFields = array();
    var $mFieldsList = array();
    var $mTableStructure = array();
    var $insfields = array();
    var $values = array();
    var $table_options;
    var $db;
    var $mAction;

    /*!
     @function XmlDb

     @abstract Class constructor.
     */
    function XmlDb( DBLayer $db, $act )
    {
        $this->xmlparser();
        $this->mAction = $act;
        $this->db = $db;
    }


    function load_deffile( $deffile )
    {
        $result = false;
        $this->deffile = $deffile;
        $content = loadfile( $this->deffile );
        if ( $content )
        {
            $this->get_data( $content );
            $result = true;
        }

        return $result;
    }

    function get_data( $data )
    {
        $this->mData = $data;
    }

    function _tag_open( $tag, $attrs )
    {
        switch ( $tag )
        {
        case 'TABLE':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
                if ( isset( $attrs['TEMPORARY']   ) and $attrs['TEMPORARY']   == 1  ) $temporary = 'TEMPORARY '; else $temporary = '';
                if ( isset( $attrs['IFNOTEXISTS'] ) and $attrs['IFNOTEXISTS'] == 1  ) $ifnotexists = 'IF NOT EXISTS '; else $ifnotexists = '';
                if ( isset( $attrs['OPTIONS']     ) and $attrs['OPTIONS']     != '' ) $this->table_options = ' '.$attrs['OPTIONS']; else $this->table_options = '';
                $this->temp_sql = 'CREATE '.$temporary.'TABLE '.$ifnotexists.$attrs['NAME'].' ( ';
                break;

            case DBLAYER_PARSER_SQL_DROP:
                $this->sql[] = 'DROP TABLE '.$attrs['NAME'].';';
                break;

            case DBLAYER_PARSER_SQL_UPDATE_NEW:
            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                break;
            }
            break;

        case 'FIELD':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
            case DBLAYER_PARSER_SQL_DROP:
            case DBLAYER_PARSER_SQL_UPDATE_NEW:
                $attrs['name']    = isset( $attrs['NAME'] ) ? $attrs['NAME'] : '';
                $attrs['type']    = isset( $attrs['TYPE'] ) ? $attrs['TYPE'] : '';

                if ( isset( $attrs['DEFAULT'] ) ) $attrs['default'] = isset( $attrs['DEFAULT'] ) ? $attrs['DEFAULT'] : '';
                if ( isset( $attrs['NOTNULL'] ) ) $attrs['notnull'] = isset( $attrs['NOTNULL'] ) ? $attrs['NOTNULL'] : '';
                if ( isset( $attrs['LENGTH'] ) ) $attrs['length']  = isset( $attrs['LENGTH'] ) ? $attrs['LENGTH'] : '';
                $this->mFields[$attrs['name']]   = $this->db->GetFieldTypeDeclaration( $attrs['NAME'], $attrs );
                $this->mFieldsList[$attrs['name']] = $attrs['name'];
                $this->mTableStructure[$attrs['name']] = $attrs;
                break;

            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                $this->mFieldsList[$attrs['NAME']] = $attrs['NAME'];
                break;
            }
            break;

        case 'KEY':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
            case DBLAYER_PARSER_SQL_DROP:
                if ( isset( $attrs['TYPE'] ) and $attrs['TYPE'] == 'primary' ) $this->mFields[] = 'PRIMARY KEY ('.$attrs['FIELD'].')';
                if ( isset( $attrs['TYPE'] ) and $attrs['TYPE'] == 'unique'  ) $this->mFields[] = 'UNIQUE ('.$attrs['FIELD'].')';
                if ( isset( $attrs['TYPE'] ) and $attrs['TYPE'] == 'index'   ) $this->mFields[] = 'INDEX ('.$attrs['FIELD'].')';
                break;

            case DBLAYER_PARSER_SQL_UPDATE_NEW:
            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                break;
            }
            break;

        case 'SEQUENCE':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
            case DBLAYER_PARSER_SQL_DROP:
                $attrs['name']  = $attrs['NAME'];
                $attrs['start'] = isset( $attrs['START'] ) ? $attrs['START'] : '';
                if ( $attrs['start'] == '' ) $attrs['start'] = 1;
                $this->sql[] = $this->mAction == DBLAYER_PARSER_SQL_CREATE ? $this->db->CreateSeqQuery( $attrs ) : $this->db->DropSeqQuery( $attrs );
                break;

            case DBLAYER_PARSER_SQL_UPDATE_NEW:
            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                break;
            }
            break;

        case 'INSERT':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
                $this->temp_sql = 'INSERT INTO '.$attrs['TABLE'].' ';
                break;

            case DBLAYER_PARSER_SQL_DROP:
            case DBLAYER_PARSER_SQL_UPDATE_NEW:
            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                break;
            }
            break;

        case 'DATA':
            $this->insfields[] = $attrs['FIELD'];
            $this->values[]    = $this->db->Format_Text( $attrs['VALUE'] );
            break;
        }
    }

    function _tag_close( $tag )
    {
        switch ( $tag )
        {
        case 'TABLE':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
                $this->sql[] = $this->temp_sql.implode( ', ', $this->mFields ).' )'.$this->table_options.';';
                $this->temp_sql = '';
                $this->mFields = array();
                break;

            case DBLAYER_PARSER_SQL_DROP:
            case DBLAYER_PARSER_SQL_UPDATE_NEW:
            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                break;
            }
            break;

        case 'INSERT':
            switch ( $this->mAction )
            {
            case DBLAYER_PARSER_SQL_CREATE:
                $this->sql[] = $this->temp_sql.' ( '.implode( ', ', $this->insfields ).' ) VALUES ( ';
                $this->sql[] = implode( ', ', $this->values ).' );';
                $this->insfields = array();
                $this->values    = array();
                break;

            case DBLAYER_PARSER_SQL_DROP:
            case DBLAYER_PARSER_SQL_UPDATE_NEW:
            case DBLAYER_PARSER_SQL_UPDATE_OLD:
                break;
            }
            break;
        }

    }

    function get_sql()
    {
        $ret = '';
        $this->parse( $this->mData );

        for ( $i = 0; $i < count( $this->sql ); $i++ ) $ret .= $this->sql[$i];
        return $ret;
    }
}

?>