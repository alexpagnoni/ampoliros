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
// $Id: HuiStyle.php,v 1.7 2004-07-13 15:20:52 alex Exp $

package('com.solarix.ampoliros.hui.theme');

import('com.solarix.ampoliros.db.DBLayer');

/*!
 @class HuiStyle
 @abstract Hui style handler.
 @discussion A hui style definition file should have .huistyle as suffix.
 */
class HuiStyle extends Object {
    /*! @var mrAmpDb Dblayer class - Ampoliros database handler. */
    private $mrAmpDb;
    /*! @var mStyleName string - Icons set name. */
    private $mStyleName;

    /*!
     @function HuiStyle
     @abstract Class constructor.
     @discussion Class constructor.
     @param rampDb Dblayer class - Ampoliros database handler.
     @param styleName string - Icons set name.
     */
    public function HuiStyle($rampDb, $styleName) {
        if (!defined('AMPOLIROS_SETUP_PHASE')) {
            $this -> mrAmpDb = $rampDb;
        }
        $this -> mStyleName = $styleName;
    }

    /*!
     @function Install
     @abstract Installs a new Hui style.
     @discussion Installs a new Hui style.
     @param args array - Element arguments in the structure.
     @result True if the style has been installed.
     */
    public function install($args) {
        $result = FALSE;
        if ($this -> mrAmpDb) {
            if (strlen($args['name']) and strlen($args['file'])) {
                $result = & $this -> mrAmpDb -> Execute('INSERT INTO huistyles '.'VALUES ('.$this -> mrAmpDb -> NextSeqValue('huistyles_id_seq').','.$this -> mrAmpDb -> Format_Text($args['name']).','.$this -> mrAmpDb -> Format_Text($args['file']).','.$this -> mrAmpDb -> Format_Text($args['catalog']).')');
            }
        }
        return $result;
    }

    /*!
     @function Update
     @abstract Updates a Hui style.
     @discussion Updates a Hui style.
     @param args array - Element arguments in the structure.
     @result True if the style has been updated.
     */
    public function update($args) {
        $result = FALSE;

        if ($this -> mrAmpDb) {
            if (strlen($this -> mStyleName)) {
                $check_query = & $this -> mrAmpDb -> Execute('SELECT name '.'FROM huistyles '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mStyleName));

                if ($check_query -> NumRows()) {
                    global $gEnv;
                    if ($gEnv['core']['state'] != AMP_STATE_SETUP) {
                        $cached_item = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huistyle-'.$this -> mStyleName);

                        $cached_item -> Destroy();
                    }
                    $result = $this -> mrAmpDb -> Execute('UPDATE huistyles '.'SET file='.$this -> mrAmpDb -> Format_Text($args['file']).','.'catalog='.$this -> mrAmpDb -> Format_Text($args['catalog']).' '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mStyleName));
                } else
                    $result = $this -> Install($args);
            }
        }
        return $result;
    }

    /*!
     @function Remove
     @abstract Removes a Hui style.
     @discussion Removes a Hui style.
     @result True if the style has been removed.
     */
    public function remove() {
        $result = FALSE;

        if ($this -> mrAmpDb) {
            if (strlen($this -> mStyleName)) {
                global $gEnv;
                if ($gEnv['core']['state'] != AMP_STATE_SETUP) {
                    $cached_item = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huistyle-'.$this -> mStyleName);
                    $cached_item -> Destroy();
                }
                $result = $this -> mrAmpDb -> Execute('DELETE FROM huistyles '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mStyleName));
            }
        }
        return $result;
    }

    public function getStyle() {
        $result = array();
OpenLibrary('configman.library');
        $cfg_file = new ConfigFile(CONFIG_PATH.$this -> mStyleName.'.huistyle');
        if ($cfg_file -> Opened()) {
            $values = $cfg_file -> ValuesArray();

            while (list ($key, $val) = each($values)) {
                $key = trim($key);
                $val = trim($val);

                $realkey = strtolower(substr($key, strpos($key, '.') + 1));
                if ($realkey != 'name') {
                    $result[$realkey]['value'] = $val;
                    $result[$realkey]['base'] = $this -> mStyleName;
                }
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.huithemes_library.huistyle_class.getstyle', 'Unable to open style file '.CONFIG_PATH.$this -> mStyleName.'.huistyle', LOGGER_ERROR);
        }
        return $result;
    }
}

?>