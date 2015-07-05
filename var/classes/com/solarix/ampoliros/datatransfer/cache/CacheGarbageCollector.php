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
// $Id: CacheGarbageCollector.php,v 1.5 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.datatransfer.cache');

class CacheGarbageCollector extends Object {
    public function removeSiteItems($siteId) {
        if (strlen($siteId)) {
            return $GLOBALS['gEnv']['root']['db'] -> Execute('DELETE FROM cacheditems '.'WHERE siteid='.$siteId);
        }
        return false;
    }

    public function removeUserItems($userId) {
        $userId = (int) $userId;
        if (strlen($userId)) {
            return $GLOBALS['gEnv']['root']['db'] -> Execute('DELETE FROM cacheditems '.'WHERE userid='.$userId);
        }
        return false;
    }

    public function removeModuleItems($module) {
        if (strlen($module)) {
            return $GLOBALS['gEnv']['root']['db'] -> Execute('DELETE FROM cacheditems '.'WHERE module='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($module));
        }
        return false;
    }

    public function emptyCache() {
        $GLOBALS['gEnv']['root']['db'] -> Execute('DELETE FROM cacheditems');
        if ($dirstream = opendir(TMP_PATH.'ampcache')) {
            while (false !== ($filename = readdir($dirstream))) {
                if ($filename != '.' && $filename != '..') {
                    if (is_file(TMP_PATH.'ampcache'.'/'.$filename))
                        unlink(TMP_PATH.'ampcache'.'/'.$filename);
                }
            }
            closedir($dirstream);
        }
        return true;
    }
}

?>