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
// $Id: ModuleConfig.php,v 1.6 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.module');

/*!
 @abstract Module configuration handling.
 */
class ModuleConfig extends Object {
    /*! @var ampdb dblayer class - Ampoliros database handler. */
    private $ampdb;
    /*! @var modname string - Module name. */
    private $modname;

    /*!
     @param ampdb dblayer class - Ampoliros database handler.
     @param modname string - Module name.
     */
    public function ModuleConfig(DBLayer $ampdb, $modname) {
        $this -> ampdb = $ampdb;
        if ($modname)
            $this -> modname = $modname;
        else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.modules_library.moduleconfig_class.moduleconfig', 'Empty module name', LOGGER_WARNING);
        }
    }

    /*!
     @abstract Sets a key value pair.
     @discussion If a key with the same name already exists, it is updated with the given value.
     @param key string - Key name.
     @param val string - Key value.
     @result True if it's all right.
     */
    public function setKey($key, $val) {
        if ($this -> ampdb and !empty($key)) {
            if ($this -> checkkey($key) != FALSE) {
                // A key with the same name already exists, it will be updated
                //
                return $this -> ampdb -> Execute('UPDATE modconfig SET value='.$this -> ampdb -> Format_Text($val).' WHERE modname='.$this -> ampdb -> Format_Text($this -> modname).' AND keyname='.$this -> ampdb -> Format_Text($key));
            } else {
                // This is a new key
                //
                return $this -> ampdb -> Execute('INSERT INTO modconfig VALUES ( '.$this -> ampdb -> Format_Text($this -> modname).','.$this -> ampdb -> Format_Text($key).','.$this -> ampdb -> Format_Text($val).')');
            }
        }
    }

    /*!
     @discussion The returned string may be an IP address, a port or any other value.
     @param key string - Key name.
     @result String representing the value correspondent to the key.
     */
    public function getKey($key) {
        if ($this -> ampdb and !empty($key)) {
            if ($mcquery = $this -> ampdb -> Execute('SELECT value FROM modconfig WHERE modname='.$this -> ampdb -> Format_Text($this -> modname).' AND keyname='.$this -> ampdb -> Format_Text($key))) {
                if ($mcquery -> NumRows() != 0) {
                    return $mcquery -> Fields('value');
                }
            }
        }
    }

    /*!
     @abstract Removes a key.
     @param key string - Key name.
     @result True if the key has been deleted.
     */
    public function delKey($key) {
        if ($this -> ampdb and !empty($key)) {
            return $this -> ampdb -> Execute('DELETE FROM modconfig WHERE modname='.$this -> ampdb -> Format_Text($this -> modname).' AND keyname='.$this -> ampdb -> Format_Text($key));
        }
    }

    /*!
     @abstract Checks if a certain key has been set and returns id.
     @param key string - Key name.
     @result ID of the key.
     */
    public function checkKey($key) {
        if ($this -> ampdb and !empty($key)) {
            $mcquery = $this -> ampdb -> Execute('SELECT * FROM modconfig WHERE modname='.$this -> ampdb -> Format_Text($this -> modname).' AND keyname='.$this -> ampdb -> Format_Text($key));
            if ($mcquery -> NumRows() > 0)
                return $mcquery;
        }
        return false;
    }
}

?>