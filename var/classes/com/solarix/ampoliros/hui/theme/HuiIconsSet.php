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
// $Id: HuiIconsSet.php,v 1.6 2004-07-13 15:20:52 alex Exp $

package('com.solarix.ampoliros.hui.theme');

import('com.solarix.ampoliros.db.DBLayer');

/*!
 @class HuiIconsSet
 @abstract Hui icons set handler.
 @discussion A hui icons set definition file should have .huiiconsset as suffix.
 */
class HuiIconsSet extends Object {
    /*! @var mrAmpDb Dblayer class - Ampoliros database handler. */
    private $mrAmpDb;
    /*! @var mSetName string - Icons set name. */
    private $mSetName;

    /*!
     @function HuiIconsSet
     @abstract Class constructor.
     @discussion Class constructor.
     @param rampDb Dblayer class - Ampoliros database handler.
     @param setName string - Icons set name.
     */
    public function HuiIconsSet($rampDb, $setName) {
        if (!defined('AMPOLIROS_SETUP_PHASE')) {
            if (is_object($rampDb))
                $this -> mrAmpDb = $rampDb;
        }
        $this -> mSetName = $setName;
    }

    /*!
     @function Install
     @abstract Installs a new Hui icons set.
     @discussion Installs a new Hui icons set.
     @param args array - Element arguments in the structure.
     @result True if the icons set has been installed.
     */
    public function install($args) {
        $result = FALSE;
        if ($this -> mrAmpDb) {
            if (strlen($args['name']) and strlen($args['file'])) {
                $result = $this -> mrAmpDb -> Execute('INSERT INTO huiiconssets '.'VALUES ('.$this -> mrAmpDb -> NextSeqValue('huiiconssets_id_seq').','.$this -> mrAmpDb -> Format_Text($args['name']).','.$this -> mrAmpDb -> Format_Text($args['file']).','.$this -> mrAmpDb -> Format_Text($args['catalog']).')');
            }
        }
        return $result;
    }

    /*!
     @function Update
     @abstract Updates a Hui icons set.
     @discussion Updates a Hui icons set.
     @param args array - Element arguments in the structure.
     @result True if the icons set has been updated.
     */
    public function update($args) {
        $result = FALSE;
        if ($this -> mrAmpDb) {
            if (strlen($this -> mSetName)) {
                $check_query = $this -> mrAmpDb -> Execute('SELECT name '.'FROM huiiconssets '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mSetName));

                if ($check_query -> NumRows()) {
                    global $gEnv;
                    if ($gEnv['core']['state'] != AMP_STATE_SETUP) {
                        $cached_item = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huiiconsset-'.$this -> mSetName);
                        $cached_item -> Destroy();
                    }
                    $result = $this -> mrAmpDb -> Execute('UPDATE huiiconssets '.'SET file='.$this -> mrAmpDb -> Format_Text($args['file']).','.'catalog='.$this -> mrAmpDb -> Format_Text($args['catalog']).' '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mSetName));
                } else
                    $result = $this -> Install($args);
            }
        }
        return $result;
    }

    /*!
     @function Remove
     @abstract Removes a Hui icons set.
     @discussion Removes a Hui icons set.
     @result True if the icons set has been removed.
     */
    public function remove() {
        $result = FALSE;
        if ($this -> mrAmpDb) {
            if (strlen($this -> mSetName)) {
                global $gEnv;
                if ($gEnv['core']['state'] != AMP_STATE_SETUP) {
                    $cached_item = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huiiconsset-'.$this -> mSetName);
                    $cached_item -> Destroy();
                }
                $result = $this -> mrAmpDb -> Execute('DELETE FROM huiiconssets '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mSetName));
            }
        }
        return $result;
    }

    public function getIconsSet() {
        $result = array();
        $cfg_file = new ConfigFile(CONFIG_PATH.$this -> mSetName.'.huiiconsset');
        if ($cfg_file -> Opened()) {
            $values = $cfg_file -> ValuesArray();
            while (list ($key, $val) = each($values)) {
                $key = trim($key);
                $val = trim($val);

                if (substr_count($key, '.') == 2) {
                    $tmpkey = strtolower(substr($key, strpos($key, '.') + 1));
                    $type = substr($tmpkey, 0, strpos($tmpkey, '.'));
                    $realkey = substr($tmpkey, strpos($tmpkey, '.') + 1);

                    $result[$type][$realkey]['file'] = $val;
                    $result[$type][$realkey]['base'] = $this -> mSetName;
                }
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.huithemes_library.huistyle_class.getstyle', 'Unable to open icons set file '.CONFIG_PATH.$this -> mSetName.'.huiiconsset', LOGGER_ERROR);
        }

        return $result;
    }
}

?>