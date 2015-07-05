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
// $Id: Locale.php,v 1.6 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.locale');

define ('AMPOLIROS_LOCALE_SAFE_TIMESTAMP', 'Y-m-d, h:i:s A');

/*!
 @class Locale

 @abstract Language abstraction through strings catalogs. Contained into locale.library.

 @discussion Locale class provides a language abstraction, through strings
 catalogs. A catalog is a collection of a certain language strings.

 Example:

 <b>code</b>:

 $myloc = new Locale( "amp", "en" );<br>
 echo $myloc->GetStr( "teststring" );

 <b>amp_en.catalog file</b>:

 teststring = Test

 This will open a catalog file named amp_en.catalog. If the english translation
 for that catalog doesn't exists, the class fallbacks to other languages in this order:
 Ampoliros default language; English language; default catalog (the one named amp.catalog).

 Catalog files are ASCII files containing the locale strings in the key = value format.

 Naming convention: [catalogname]_[lowcase two letters format language].catalog

 e.g.

 Catalog: test<br>
 Language: Engligh

 becomes: test_en.catalog
 */
class Locale extends Object {
	/*! @var catalog string - Catalog name */
	private $catalog;
	/*! @var lang string - Language */
	private $lang;
	/*! @var locales array - Array of the catalog strings */
	private $locales;

	/*!
	 @function Locale
	
	 @abstract Class constructor.
	
	 @param catalog string - catalog name
	 @param lang string - language id
	 */
	public function Locale($catalog, $lang = '') {
		$this -> catalog = null;
		$this -> lang = null;
		if (empty($lang))
			$lang = AMP_LANG;
		$this -> SetLocaleCT($catalog);
		$this -> SetLocaleLang($lang);
		$this -> OpenCatalog();
	}

	/*!
	 @function SetLocaleCT
	
	 @abstract Sets catalog file for the locale.
	 @param catalog string - Catalog name.
	
	 @result Always true.
	 */
	public function setLocaleCT($catalog) {
		$this -> catalog = $catalog;
		return true;
	}

	/*!
	 @function GetLocaleCT
	 @abstract Gets catalog file for this locale.
	 @result Locale catalog file name.
	 */
	public function getLocaleCT() {
		return $this -> catalog;
	}

	/*!
	 @function SetLocaleLang
	 @abstract Sets language for this locale.
	 @param lang string - Language id.
	 @result True if the catalog file was specified.
	 */
	public function setLocaleLang($lang) {
		$result = false;
		if ($this -> catalog != null) {
			$this -> lang = $lang;
			$result = true;
		}
		return $result;
	}

	/*
	 @function GetLocaleLang
	
	 @abstract Gets locale language.
	
	 @result int language id.
	 */
	public function getLocaleLang() {
		$result = false;
		if ($this -> catalog != null) {
			$result = $this -> lang;
		}
		return $result;
	}

	/*!
	 @function OpenCatalog
	 @abstract Opens the catalog and read the locale strings.
	 @discussion If it cannot find the given language locale catalog, it tries to
	 fallback to Ampoliros language one, then english one, and default one
	 (that is the catalog with no language specification at all).
	 @result True if it is able to open and read the catalog file.
	 */
	public function openCatalog() {
		$result = false;
        
        import('carthag.core.Registry');
        $reg = Registry :: instance();

		if (($this -> catalog != null) and ($this -> lang != null)) {
			// Tries specified language catalog
			//
			if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'_'.$this -> lang.'.catalog')) {
				$catfile = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'_'.$this -> lang.'.catalog';
			}
			// Tries default catalog
			//
			else
				if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'/var/locale/'.$this -> catalog.'.catalog')) {
					$catfile = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'.catalog';
				}
			// Tries Ampoliros language catalog
			//
			else
				if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'_'.AMP_LANG.'.catalog')) {
					$catfile = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'_'.AMP_LANG.'.catalog';
				}
			// Tries English catalog
			//
			else
				if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'_en.catalog')) {
					$catfile = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> catalog.'_en.catalog';
				} else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
					$log -> LogEvent('ampoliros.locale_library.locale_class.opencatalog', 'Unable to find a catalog file for the specified catalog ('.$this -> catalog.') and language ('.$this -> lang.') or fallback to another language', LOGGER_ERROR);
                }

			if (!empty($catfile)) {
				// New way to read locale catalogs
				//
                OpenLibrary('configman.library');
				$loc = new configfile($catfile);
				$this -> locales = $loc -> valuesarray();

				$result = true;

				/*
				if ( sizeof( $this->locales ) == 0 )
				{
				    if ( $fh = @fopen( $catfile, 'r' ) )
				    {
				        fclose( $fh );
				
				        include( $catfile );
				        $this->catversion = $catversion;
				        $this->catdate    = $catdate;
				        $this->locales    = $locale;
				    }
				}
				*/
			}
		}
		return $result;
	}

	/*!
	 @function GetStr
	 @abstract Returns locale string of a certain key.
	 @param id string - Locale string key.
	 @result The string if the key was found, nothing otherwise.
	 */
	public function getStr($id) {
		return isset($this -> locales[$id]) ? $this -> locales[$id] : '';
	}

	/*!
	 @function PrintStr
	 @abstract Writes a string to the stdout and returns it.
	 @discussion This function should be avoided in normal interfaces, since it would break OOPHTML.
	 @param id string - Locale string key.
	 @result The string if the key was found, nothing otherwise.
	 */
	public function printStr($id) {
		echo $this -> locales[$id];
		return $this -> locales[$id];
	}
}

?>