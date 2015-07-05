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
// $Id: UserSettings.php,v 1.5 2004-07-08 15:04:23 alex Exp $

package('com.solarix.ampoliros.site.user');

import('com.solarix.ampoliros.db.DBLayer');
class UserSettings extends Object {
    public $mUserId;
    public $mrSiteDb;

    public function UserSettings(DBLayer $siteDb, $userId) {
        $this -> mrSiteDb = $siteDb;
        $this -> mUserId = (int) $userId;
    }

    public function getKey($key, $fallbackToSiteSetting = false) {
        if ($this -> mrSiteDb) {
            $key_query = $this -> mrSiteDb -> Execute('SELECT val '.'FROM usersettings '.'WHERE userid='. (int) $this -> mUserId.' '.'AND keyname='.$this -> mrSiteDb -> Format_Text($key));

            if ($key_query -> NumRows()) {
                return $key_query -> Fields('val');
            } else
                if ($fallbackToSiteSetting == true) {
                    import('com.solarix.ampoliros.site.SiteSettings');
                    $sets = new SiteSettings($this -> mrSiteDb);
                    return $sets -> GetKey($key);
                }
        }

        return '';
    }

    public function setKey($key, $value) {
        if ($this -> mrSiteDb) {
            $key_query = $this -> mrSiteDb -> Execute('SELECT val '.'FROM usersettings '.'WHERE userid='. (int) $this -> mUserId.' '.'AND keyname='.$this -> mrSiteDb -> Format_Text($key));

            if ($key_query -> NumRows()) {
                return $this -> mrSiteDb -> Execute('UPDATE usersettings '.'SET val='.$this -> mrSiteDb -> Format_Text($value).' '.'WHERE userid='. (int) $this -> mUserId.' '.'AND keyname='.$this -> mrSiteDb -> Format_Text($key));
            } else {
                return $this -> mrSiteDb -> Execute('INSERT INTO usersettings VALUES('. (int) $this -> mUserId.','.$this -> mrSiteDb -> Format_Text($key).','.$this -> mrSiteDb -> Format_Text($value).')');
            }
        }
        return false;
    }

    public function checkKey($key) {
        if ($this -> mrSiteDb) {
            $key_query = & $this -> mrSiteDb -> Execute('SELECT val '.'FROM usersettings '.'WHERE userid='. (int) $this -> mUserId.' '.'AND keyname='.$this -> mrSiteDb -> Format_Text($key));

            if ($key_query -> NumRows())
                return true;
        }
        return false;
    }

    public function deleteKey($key) {
        if ($this -> mrSiteDb) {
            return $this -> mrSiteDb -> Execute('DELETE FROM usersettings '.'WHERE userid='. (int) $this -> mUserId.' '.'AND keyname='.$this -> mrSiteDb -> Format_Text($key));
        }
        return false;
    }
}

?>