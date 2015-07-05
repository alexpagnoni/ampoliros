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
// $Id: RecordSet.php,v 1.5 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.db');

/*!
 @class RecordSet

 @abstract Class returned by Execute member of DBLayer class with the result records
 */
abstract class RecordSet extends Object {
	/*! @var resultid int - Result number */
	protected $resultid = -1;
	/*! @var resultrows int - Number of rows in the record set */
	public $resultrows = -1;
	/*! @var resultfields int - Number of fields in a row */
	public $resultfields = -1;
	/*! @var currfields array - Array of the fields in the current row */
	public $currfields;
	/*! @var opened bool - False if the recordset has been flushed by RecordSet->Free */
	public $opened = TRUE;
	/*! @var currentrow int - Current row pointer */
	public $currentrow = -1;
	/*! @var eof bool - True if the row pointer is at the end of record set */
	public $eof = FALSE;
	/*! @var supp array - Array of the supported functions */
	public $supp = array();

	/*!
	 @function RecordSet
	
	 @abstract Class constructor
	
	 @param resultid integer - DBLayer->Execute return value
	 */
	public function RecordSet(& $resultid) {
		$this -> resultid = & $resultid;

		if ($this -> resultid) {
			$this -> _init();
		} else {
			$this -> resultrows = 0;
			$this -> resultfields = 0;
		}

		if ($this -> resultrows != 0 && $this -> resultfields != 0 && $this -> currentrow == -1) {
			$this -> currentrow = 0;
			$this -> eof = ($this -> _fetch() === FALSE);
		} else
			$this -> eof = TRUE;
	}

	/*!
	 @abstract Frees the record set from memory
	 */
	public function free() {
		if ($this -> opened) {
			if ( $this -> _free() ) {
			$this -> opened = FALSE;
            return true;
            }
		}

		return false;
	}

	public function close() {
		return $this -> Free();
	}

	// ----------------------------------------------------
	// Records navigation methods
	// ----------------------------------------------------

	/*!
	 @abstract Moves the record pointer to the first entry
	 */
	public function moveFirst() {
		if ($this -> opened) {
			if ($this -> currentrow == 0)
				return true;
			return $this -> move(0);
		} else
			return false;
	}

	/*!
	 @abstract Moves the record pointer to the next entry, if any
	 */
	public function moveNext() {
		if ($this -> opened) {
			if ($this -> resultrows != 0) {
				$this -> currentrow++;
				if ($this -> currentrow < $this -> resultrows) {
					if ($this -> _fetch()) {
						return true;
					}
				}
			}

			$this -> eof = true;
			return false;
		} else
			return false;
	}

	/*!
	 @abstract Moves the record pointer to an absolute row
	 @param row integer - Row number
	 */
	public function move($row = 0) {
		if ($this -> opened) {
			// Checks if it is already positioned in the requested row
			//
			if ($row == $this -> currentrow)
				return true;

			// Checks if it is asked to position beyond the number of rows
			//
			if ($row > $this -> resultrows)
				if ($this -> resultrows != -1)
					$row = $this -> resultrows - 1;

			if ($this -> supp['seek']) {
				if ($this -> _seek($row)) {
					$this -> currentrow = $row;
					if ($this -> _fetch()) {
						$this -> eof = FALSE;
						return TRUE;
					}
				} else
					return FALSE;
			} else {
				if ($row < $this -> currentrow)
					return FALSE;

				while (!$this -> eof && $this -> currentrow < $row) {
					$this -> currentrow++;

					if (!$this -> _fetch())
						$this -> eof = TRUE;
				}
				if ($this -> eof)
					return FALSE;
				return TRUE;
			}
			$this -> currfields = null;
			$this -> eof = TRUE;
			return FALSE;
		} else
			return FALSE;
	}

	/*!
	 @abstract Moves the record pointer to the last entry
	 */
	public function moveLast() {
		if ($this -> opened) {
			if ($this -> resultrows >= 0)
				return $this -> move($this -> resultrows - 1);

			while (!$this -> eof)
				$this -> movenext();
			return TRUE;
		} else
			return FALSE;
	}

	// ----------------------------------------------------
	// Data fetch methods
	// ----------------------------------------------------

	/*!
	 @abstract Returns the number of rows in the record set
	 */
	public function NumRows() {
		return $this -> opened ? $this -> resultrows : FALSE;
	}

	public function RecordCount() {
		return $this -> NumRows();
	}

	/*!
	 @abstract Returns the number of a row fields
	 */
	public function NumFields() {
		return $this -> opened ? $this -> resultfields : FALSE;
	}

	/*!
	 @abstract Returns the current row
	 */
	public function CurrentRow() {
		return $this -> opened ? $this -> currentrow : FALSE;
	}

	/*!
	 @abstract Returns the current row columns
	 @param column string - Optional column name. Defaults to FALSE
	 @result An array of the fields. If the column argument is given, only the field with that name is returned
	 */
	public function fields($column = FALSE) {
		if ($this -> opened) {
			if ($column !== false and strlen($column))
				return $this -> currfields[$column];
			else
				return $this -> currfields;
		}

		return false;
	}

	// ----------------------------------------------------
	// Stub functions
	// ---------------------------------------------------

	protected function _init() {
		return FALSE;
	}

	protected function _fetch() {
		return FALSE;
	}

	protected function _free() {
		return FALSE;
	}

	protected function _seek($row) {
		return FALSE;
	}
}

?>