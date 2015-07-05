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
// $Id: XMLDefFile.php,v 1.5 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.module');

import('com.solarix.ampoliros.xml.XMLParser');
import('com.solarix.ampoliros.module.ModuleElementFactory');
import('com.solarix.ampoliros.io.log.Logger');

/*!
 @class XMLDefFile

 @abstract Provides XML definition file handling.

 @discussion This class reads a xml definition file and gives the module elements structure.
 */
class XMLDefFile extends xmlparser {
	/*! @public mLog logger class - Log handler. */
	public $mLog;
	/*! @public deffile string - Definition file full path. */
	public $deffile;
	/*! @public data string - The whole content of the definition file. */
	public $data;
	/*! @public modstructure array - Array of the module elements structure. */
	public $modstructure = array();
	/*! @public eltypes moduleelementfactory class - Module element types handler. */
	public $eltypes;
	/*! @public ampdb dblayer class - Ampoliros database handler. */
	public $ampdb;
	/*! @public basedir string - Module base directory. */
	public $basedir;

	/*!
	 @function XMLDefFile
	
	 @abstract Class constructor.
	
	 @param ampdb dblayer class - Ampoliros database handler.
	 @param basedir string - Module base directory.
	 */
	function XMLDefFile($ampdb, $basedir = '') {
		$this -> mLog = new Logger(AMP_LOG);
		$this -> eltypes = new ModuleElementFactory($ampdb);
		$this -> eltypes -> FillTypes();
		$this -> xmlparser();
		$this -> basedir = $basedir;
		//$this->modstructure['generalpreinstall'] = '';
		//$this->modstructure['generalpreuninstall'] = '';
		//$this->modstructure['generalpostinstall'] = '';
		//$this->modstructure['generalpostuninstall'] = '';
		//$this->modstructure['sitepreinstall'] = '';
		//$this->modstructure['sitepreuninstall'] = '';
		//$this->modstructure['sitepostinstall'] = '';
		//$this->modstructure['sitepostuninstall'] = '';
		//$this->modstructure['generalpreupdate'] = '';
		//$this->modstructure['generalpostupdate'] = '';
		//$this->modstructure['sitepreupdate'] = '';
		//$this->modstructure['sitepostupdate'] = '';
	}

	/*!
	 @function load_deffile
	 @abstract Reads the structure file.
	 @param deffile string - Full path of the definition file.
	 */
	function load_deffile($deffile) {
		$this -> deffile = $deffile;
		$this -> get_data(loadfile($this -> deffile));
	}

	/*!
	 @function get_data
	 @abstract Returns the definition file content.
	 @param data string - file path.
	 */
	function get_data($data) {
		$this -> data = $data;
	}

	/*!
	 @function _tag_open
	 @abstract Private member.
	 @param tag sring - open tag.
	 @param attrs array - attributes.
	 */
	function _tag_open($tag, $attrs) {
		global $gEnv;

		switch ($tag) {
			case 'STRUCTURE' :
				break;

				// General cases
				//
			case 'GENERALPREINSTALL' : // Before installing the module
				$this -> modstructure['generalpreinstall'] = $attrs['FILE'];
				break;

			case 'GENERALPREUNINSTALL' : // Before uninstalling the module
				$this -> modstructure['generalpreuninstall'] = $attrs['FILE'];
				break;

			case 'GENERALPOSTINSTALL' : // After installing the module
				$this -> modstructure['generalpostinstall'] = $attrs['FILE'];
				break;

			case 'GENERALPOSTUNINSTALL' : // After uninstalling the module
				$this -> modstructure['generalpostuninstall'] = $attrs['FILE'];
				break;

				// Site cases
				//
			case 'SITEPREINSTALL' : // Before enabling the module to a site
				$this -> modstructure['sitepreinstall'] = $attrs['FILE'];
				break;

			case 'SITEPREUNINSTALL' : // Before disabling the module to a site
				$this -> modstructure['sitepreuninstall'] = $attrs['FILE'];
				break;

			case 'SITEPOSTINSTALL' : // After enabling the module to a site
				$this -> modstructure['sitepostinstall'] = $attrs['FILE'];
				break;

			case 'SITEPOSTUNINSTALL' : // After disabling the module to a site
				$this -> modstructure['sitepostuninstall'] = $attrs['FILE'];
				break;

				// Update cases
				//
			case 'GENERALPREUPDATE' : // Before updating the module
				$this -> modstructure['generalpreupdate'] = $attrs['FILE'];
				break;

			case 'GENERALPOSTUPDATE' : // After updating the module
				$this -> modstructure['generalpostupdate'] = $attrs['FILE'];
				break;

			case 'SITEPREUPDATE' : // Before updating the module, for every enabled site
				$this -> modstructure['sitepreupdate'] = $attrs['FILE'];
				break;

			case 'SITEPOSTUPDATE' : // After updating the module, for every enabled site
				$this -> modstructure['sitepostupdate'] = $attrs['FILE'];
				break;

				// Element case
				//
			default :
				// Checks if it is a known element type
				//
				if (isset($this -> eltypes -> types[strtolower($tag)])) {
					reset($this -> eltypes -> types[strtolower($tag)]);
					$tmp = array();

					// Fills the structure attributes for this element
					//
					while (list ($key, $val) = each($attrs)) {
						$tmp[strtolower($key)] = $val;
					}
					$this -> modstructure[strtolower($tag)][] = $tmp;
					/*
					while ( list( $key, $val ) = each( $this->eltypes->types[strtolower( $tag )]['links'] ) )
					{
					    $tmp[$key] = $attrs[strtoupper( $val )];
					}
					$this->structure[strtolower( $tag )][] = $tmp;
					*/
				} else {
					unset($element);

					if (file_exists($this -> basedir.'/var/handlers/'.strtolower($tag).'.element')) {
						$this -> eltypes -> types[strtolower($tag)] = array();

						//include( $this->basedir.'/var/handlers/'.$attrs['FILE'] );
						$tmp = array();

						// Fills the structure attributes for this element
						//
						while (list ($key, $val) = each($attrs)) {
							$tmp[strtolower($key)] = $val;
						}
						$this -> modstructure[strtolower($tag)][] = $tmp;
					}
				}

				break;
		}
	}

	/*!
	 @function _tag_close
	 @abstract Private member.
	 @param tag string - close tag.
	 */
	function _tag_close($tag) {
	}

	/*!
	 @function get_structure
	 @abstract Returns the module elements structure array.
	 @result array.
	 */
	function get_Structure() {
		$this -> parse($this -> data);
		return $this -> modstructure;
	}
}

?>