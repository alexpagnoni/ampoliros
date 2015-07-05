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
// $Id: SiteSettings.php,v 1.6 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.site');

/*!
 @class SiteSettings

 @abstract Site settings management
 */
class SiteSettings extends Object {
    public $sitedb;

    /*!
     @function SiteSettings
    
     @abstract Class constructor
    
     @param sitedb dblayer class - Site database handler
     */
    public function SiteSettings(DBLayer $sitedb) {
        $this -> sitedb = $sitedb;
    }

    // Adds a key
    //
    // string $key:   key name
    // string $value: value of the key
    //
    // Returns: true if ok
    //
    public function addKey($key, $value) {
        if ($this -> CheckKey($key) == false) {
            return $this -> sitedb -> Execute('INSERT INTO sitesettings '.'VALUES ( '.$this -> sitedb -> Format_Text($key).','.$this -> sitedb -> Format_Text($value).')');
        }
        return false;
    }

    // Edits a key value. If the keys does not exists, it will
    // be created
    //
    // string $key:   key name
    // string $value: value of the key
    //
    // Returns: true if the key was changed
    //
    public function editKey($key, $value) {
        if ($this -> CheckKey($key) == true) {
            return $this -> sitedb -> Execute('UPDATE sitesettings '.'SET val = '.$this -> sitedb -> Format_Text($value).' '.'WHERE keyname = '.$this -> sitedb -> Format_Text($key));
        } else {
            $ins = 'INSERT INTO sitesettings VALUES ('.$this -> sitedb -> Format_Text($key).','.$this -> sitedb -> Format_Text($value).')';
            return $this -> sitedb -> Execute($ins);
        }
        return false;
    }

    public function setKey($key, $value) {
        return $this -> editKey($key, $value);
    }

    // Deletes a key
    //
    // string $key:   key name
    //
    // Returns: true if the key was deleted
    //
    public function deleteKey($key) {
        if ($this -> CheckKey($key) == true) {
            return $this -> sitedb -> Execute('DELETE FROM sitesettings '.'WHERE keyname = '.$this -> sitedb -> Format_Text($key));
        }
        return false;
    }

    // Gets a key value
    //
    // string $key:   key name
    //
    // Returns: key value if the key exists
    //
    public function getKey($key) {
        $query = $this -> CheckKey($key);
        if ($query == true) {
            return $query -> Fields('val');
        }
        return '';
    }

    // Checks if a key exists
    //
    // string $key:   key name
    //
    // Returns: query index if the key exists
    //
    public function checkKey($key) {
        if (!empty($key)) {
            $keyquery = $this -> sitedb -> Execute('SELECT val FROM sitesettings WHERE keyname = '.$this -> sitedb -> Format_Text($key));
            if ($keyquery -> NumRows() > 0)
                return $keyquery;
        }
        return false;
    }
}

?>