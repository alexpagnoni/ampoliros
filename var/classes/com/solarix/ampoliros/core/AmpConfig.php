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
// $Id: AmpConfig.php,v 1.5 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.core');

class AmpConfig extends Object {
    private $mConfigFile;
    private $mConfigValues;
    private $mOpened = FALSE;

    /*!
     @param configFile string - Ampoliros configuration file full path. The path is contained into [PRIVATE TREE]/etc/ampconfigpath.php.
     */
    public function AmpConfig($configFile) {
        // Checks to see if the file is there
        if (file_exists($configFile)) {
            $fp = @ fopen($configFile, 'r');
            if ($fp) {
                $this -> mConfigFile = $configFile;
                $this -> mOpened = true;

                while ($fl = @ fgets($fp)) {
                    $trimmed_line = trim($fl);

                    if ((substr($trimmed_line, 0, 1) != '#') and (substr($trimmed_line, 0, 1) != ';') and (strpos($trimmed_line, '='))) {
                        $key = substr($trimmed_line, 0, (strpos($trimmed_line, '=')));
                        $value = substr($trimmed_line, (strpos($trimmed_line, '=') + 1));
                        $this -> mConfigValues[trim($key)] = trim($value);
                    }
                }
                @ fclose($fp);
            } else {
                $carthag = Carthag::instance();
                $carthag->halt('Could not open '.$configFile);
            }
        } else {
            $carthag = Carthag::instance();
            $carthag->halt('Configuration file '.$configFile." doesn't exists");
        }
    }

    /*!
     @function Value
     @abstract Gets a configuration value.
     @discussion Returns the value of a given key from Ampoliros configuration.
     @param keyName string - Configuration key.
     @result The value if the key, if any.
     */
    public function value($keyName) {
        return $this->getKey($keyName);
    }
    
    public function getKey($keyName) {
        return isset($this->mConfigValues[$keyName]) ? trim($this->mConfigValues[$keyName]): '';
    }
    
    public function setVolatileKey($keyName, $value) {
        $this->mConfigValues[$keyName] = $value;
    }
}

?>