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
// $Id: HuiColorsSet.php,v 1.6 2004-07-13 15:20:52 alex Exp $

package('com.solarix.ampoliros.hui.theme');

import('com.solarix.ampoliros.db.DBLayer');

/*!
 @class HuiColorsSet
 @abstract Hui colors set handler.
 @discussion A hui colors set definition file should have .huicolorsset as suffix.
 */
class HuiColorsSet extends Object {
	/*! @var mrAmpDb Dblayer class - Ampoliros database handler. */
	private $mrAmpDb;
	/*! @var mSetName string - Colors set name. */
	private $mSetName;

	/*!
	 @function HuiColorsSet
	 @abstract Class constructor.
	 @discussion Class constructor.
	 @param rampDb Dblayer class - Ampoliros database handler.
	 @param setName string - Colors set name.
	 */
	public function HuiColorsSet($rampDb, $setName) {
		if (!defined('AMPOLIROS_SETUP_PHASE')) {
			if (is_object($rampDb))
				$this -> mrAmpDb = $rampDb;
		}
		$this -> mSetName = $setName;
	}

	/*!
	 @function Install
	 @abstract Installs a new Hui colors set.
	 @discussion Installs a new Hui colors set.
	 @param args array - Element arguments in the structure.
	 @result True if the colors set has been installed.
	 */
	public function install($args) {
		$result = false;
		if ($this -> mrAmpDb) {
			if (strlen($args['name']) and strlen($args['file'])) {
				$result = & $this -> mrAmpDb -> Execute('INSERT INTO huicolorssets '.'VALUES ('.$this -> mrAmpDb -> NextSeqValue('huicolorssets_id_seq').','.$this -> mrAmpDb -> Format_Text($args['name']).','.$this -> mrAmpDb -> Format_Text($args['file']).','.$this -> mrAmpDb -> Format_Text($args['catalog']).')');
			}
		}
		return $result;
	}

	/*!
	 @function Update
	 @abstract Updates a Hui colors set.
	 @discussion Updates a Hui colors set.
	 @param args array - Element arguments in the structure.
	 @result True if the colors set has been updated.
	 */
	public function update($args) {
		$result = false;
		if ($this -> mrAmpDb) {
			if (strlen($this -> mSetName)) {
				$check_query = $this -> mrAmpDb -> Execute('SELECT name '.'FROM huicolorssets '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mSetName));

				if ($check_query -> NumRows()) {
					global $gEnv;
					if ($gEnv['core']['state'] != AMP_STATE_SETUP) {
						$cached_item = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huicolorsset-'.$this -> mSetName);
						$cached_item -> Destroy();
					}
					$result = $this -> mrAmpDb -> Execute('UPDATE huicolorssets '.'SET file='.$this -> mrAmpDb -> Format_Text($args['file']).','.'catalog='.$this -> mrAmpDb -> Format_Text($args['catalog']).' '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mSetName));
				} else
					$result = $this -> Install($args);
			}
		}
		return $result;
	}

	/*!
	 @function Remove
	 @abstract Removes a Hui colors set.
	 @discussion Removes a Hui colors set.
	 @result True if the colors set has been removed.
	 */
	public function remove() {
		$result = false;
		if ($this -> mrAmpDb) {
			if (strlen($this -> mSetName)) {
				global $gEnv;
				if ($gEnv['core']['state'] != AMP_STATE_SETUP) {
					$cached_item = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huicolorsset-'.$this -> mSetName);
					$cached_item -> Destroy();
				}
				$result = $this -> mrAmpDb -> Execute('DELETE FROM huicolorssets '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mSetName));
			}
		}
		return $result;
	}

	public function getColorsSet() {
		$result = array();
        OpenLibrary('configman.library');
		$cfg_file = new ConfigFile(CONFIG_PATH.$this -> mSetName.'.huicolorsset');
		if ($cfg_file -> Opened()) {
			$result['pages']['bgcolor'] = $cfg_file -> Value('COLORSET.PAGES.BGCOLOR');
			$result['pages']['border'] = $cfg_file -> Value('COLORSET.PAGES.BORDER');
			$result['buttons']['text'] = $cfg_file -> Value('COLORSET.BUTTONS.TEXT');
			$result['buttons']['disabledtext'] = $cfg_file -> Value('COLORSET.BUTTONS.DISABLEDTEXT');
			$result['buttons']['selected'] = $cfg_file -> Value('COLORSET.BUTTONS.SELECTED');
			$result['buttons']['notselected'] = $cfg_file -> Value('COLORSET.BUTTONS.NOTSELECTED');
			$result['bars']['color'] = $cfg_file -> Value('COLORSET.BARS.COLOR');
			$result['bars']['shadow'] = $cfg_file -> Value('COLORSET.BARS.SHADOW');
			$result['frames']['border'] = $cfg_file -> Value('COLORSET.FRAMES.BORDER');
			$result['statusbars']['bgcolor'] = $cfg_file -> Value('COLORSET.STATUSBARS.BGCOLOR');
			$result['titlebars']['bgcolor'] = $cfg_file -> Value('COLORSET.TITLEBARS.BGCOLOR');
			$result['titlebars']['textcolor'] = $cfg_file -> Value('COLORSET.TITLEBARS.TEXTCOLOR');
			$result['toolbars']['separator'] = $cfg_file -> Value('COLORSET.TOOLBARS.SEPARATOR');
			$result['tables']['bgcolor'] = $cfg_file -> Value('COLORSET.TABLES.BGCOLOR');
			$result['tables']['headerbgcolor'] = $cfg_file -> Value('COLORSET.TABLES.HEADERBGCOLOR');
			$result['tables']['gridcolor'] = $cfg_file -> Value('COLORSET.TABLES.GRIDCOLOR');
		} else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
			$log -> LogEvent('ampoliros.huithemes_library.huicolorsset_class.getcolorsset', 'Unable to open colors set file '.CONFIG_PATH.$this -> mSetName.'.huicolorsset', LOGGER_ERROR);
        }
		return $result;
	}
}

?>