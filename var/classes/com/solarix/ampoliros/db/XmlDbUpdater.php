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
// $Id: XmlDbUpdater.php,v 1.5 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.db');

import('com.solarix.ampoliros.db.XmlDb');

/*!
 @class XmlDbUpdater

 @abstract A class to obtain differences between two xsql table definition files.

 @discussion For now it only report new and old columns.
 */
class XmlDbUpdater extends Object {
    /*! @var mDb DbLayer class - Database handler. */
    var $mrDb;
    /*! @var mOldTable string - Old table file full path. */
    var $mOldTable;
    /*! @var mNewTable string - New table file full path. */
    var $mNewTable;
    /*! @var mOldTableHandler XmlDb class - XmlDb class for old table. */
    var $mOldTableHandler;
    /*! @var mNewTableHandler XmlDb class - XmlDb class for new table. */
    var $mNewTableHandler;
    /*! @var mDiffNewColumns array - Array of the new columns. The key contains the column name and the value contains the column definition. */
    var $mDiffNewColumns = array();
    /*! @var mDiffOldColumns array - Array of the old columns. */
    var $mDiffOldColumns = array();
    /*! @var mParse boolean - TRUE when the tables have been parsed. */
    var $mParsed = FALSE;

    /*!
     @param rDb DbLayer class - Database handler.
     @param oldTable string - Full path of the old XSQL table file.
     @param newTable string - Full path of the new XSQL table file.
     */
    public function XmlDbUpdater(DBLayer $rDb, $oldTable, $newTable) {
        $this -> mrDb = $rDb;
        if (file_exists($oldTable)) {
            $this -> mOldTable = $oldTable;
            $this -> mOldTableHandler = new XmlDb($this -> mrDb, DBLAYER_PARSER_SQL_UPDATE_OLD);
            $this -> mOldTableHandler -> Load_DefFile($this -> mOldTable);
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.dblayer_parser_library.xmldbupdater_class.xmlupdater', 'Old table file ('.$oldTable.') does not exists', LOGGER_WARNING);
        }

        if (file_exists($newTable)) {
            $this -> mNewTable = $newTable;
            $this -> mNewTableHandler = new XmlDb($this -> mrDb, DBLAYER_PARSER_SQL_UPDATE_NEW);
            $this -> mNewTableHandler -> Load_DefFile($this -> mNewTable);
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.dblayer_parser_library.xmldbupdater_class.xmlupdater', 'New table file ('.$newTable.') does not exists', LOGGER_WARNING);
        }
    }

    /*!
     @discussion Checks the differences between the XSQL tables.
     @result TRUE if the check has been performed.
     */
    public function checkDiffs() {
        $result = FALSE;

        if (strlen($this -> mOldTable) and strlen($this -> mNewTable)) {
            $this -> mOldTableHandler -> Parse($this -> mOldTableHandler -> mData);
            $this -> mNewTableHandler -> Parse($this -> mNewTableHandler -> mData);

            if (is_array($this -> mOldTableHandler -> mFieldsList)) {
                reset($this -> mOldTableHandler -> mFieldsList);
                while (list (, $old_column) = each($this -> mOldTableHandler -> mFieldsList)) {
                    if (!isset($this -> mNewTableHandler -> mFieldsList[$old_column]))
                        $this -> mDiffOldColumns[] = $old_column;
                }
            }

            if (is_array($this -> mNewTableHandler -> mFieldsList)) {
                reset($this -> mNewTableHandler -> mFieldsList);
                while (list (, $new_column) = each($this -> mNewTableHandler -> mFieldsList)) {
                    if (!isset($this -> mOldTableHandler -> mFieldsList[$new_column]))
                        $this -> mDiffNewColumns[$new_column] = $this -> mNewTableHandler -> mFields[$new_column];
                }
            }

            $this -> mParsed = $result = TRUE;
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.dblayer_parser_library.xmldbupdater_class.checkdiffs', 'Old and new table files not specified', LOGGER_ERROR);
        }
        return $result;
    }

    /*!
     @abstract Returns the old columns, if any.
     @result An array of the old columns.
     */
    public function getOldColumns() {
        $result = FALSE;
        if ($this -> mParsed) {
            $result = $this -> mDiffOldColumns;
        }
        return $result;
    }

    /*!
     @abstract Returns the new columns, if any.
     @result An array of the new columns, with the column name in the key and the column definition in the value.
     */
    public function getNewColumns() {
        $result = FALSE;
        if ($this -> mParsed) {
            $result = $this -> mDiffNewColumns;
        }
        return $result;
    }
}

?>