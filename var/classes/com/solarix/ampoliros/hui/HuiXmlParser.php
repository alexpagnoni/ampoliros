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
// $Id: HuiXmlParser.php,v 1.5 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

import('com.solarix.ampoliros.xml.XMLParser');

/*!
@class HuiXmlParser

@abstract Xml parser for Hui xml resources.

@discussion Not yet finished.
*/
class HuiXmlParser extends XMLParser {
	/*! @var data string - The whole content of the definition file */
	public $mData;
	public $mLayout;

	/*!
	 @function XMLDefFile
	
	 @abstract Class constructor
	
	 @param ampdb dblayer class - Ampoliros database handler
	 */
	function HuiXmlParser() {
		$this -> xmlparser();
	}

	/*!
	 @function load_deffile
	
	 @abstract Reads the structure file
	
	 @param deffile string - Full path of the definition file
	 */
	function load_deffile($deffile) {
		$this -> deffile = $deffile;
		$this -> get_data(loadfile($this -> deffile));
	}

	/*!
	 @function get_data
	
	 @abstract Returns the definition file content
	 */
	function get_data($data) {
		$this -> mData = $data;
	}

	/*!
	 @function _tag_open
	
	 @abstract Private member
	 */
	function _tag_open($tag, $attrs) {
		switch ($tag) {
			case 'STRUCTURE' :
				break;

			default :
				break;
		}
	}

	/*!
	 @function _tag_close
	
	 @abstract Private member
	 */
	function _tag_close($tag) {
	}

	/*!
	@function Build
	
	@abstract Builds the structure.
	*/
	function Build() {
		$this -> parse($this -> mData);
	}

	/*!
	 @function get_structure
	
	 @abstract Returns the module elements structure array
	 */
	function Render() {
		return $this -> mLayout;
	}
}

?>