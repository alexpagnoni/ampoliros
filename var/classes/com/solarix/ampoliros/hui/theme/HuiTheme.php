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
// $Id: HuiTheme.php,v 1.8 2004-07-13 15:20:52 alex Exp $

package('com.solarix.ampoliros.hui.theme');

import('com.solarix.ampoliros.datatransfer.cache.CachedItem');
import('com.solarix.ampoliros.db.DBLayer');
import('com.solarix.ampoliros.hui.theme.HuiStyle');
import('com.solarix.ampoliros.hui.theme.HuiIconsSet');
import('com.solarix.ampoliros.hui.theme.HuiColorsSet');

/*!
 @class HuiTheme
 @abstract Hui themes handler.
 @discussion Handles Hui themes.
 */
class HuiTheme extends Object {
	private $mrAmpDb;
	/*! @var mTheme string - Theme name. */
	private $mTheme;
	/*! @var mThemeFile string - Theme file full path. */
	private $mThemeFile;

	private $mUserSettings;

	public $mIconsSetName;
	public $mIconsSetBase;
	public $mIconsSetDir;
	public $mIconsSet = array();
	public $mIconsBase;

	public $mColorsSetName;
	public $mColorsSet = array();

	public $mStyleBase;
	public $mStyleName;
	public $mStyleDir;
	public $mStyle = array();

	public function HuiTheme($rampDb, $themeName = 'default', $userSettings = '') {
				$this -> mrAmpDb = $rampDb;
		if (strlen($themeName)) {
			$this -> mTheme = $themeName;
			$this -> InitTheme();
		}
		$this -> mUserSettings = $userSettings;
	}

	/*!
	 @function Install
	 @abstract Installs a new Hui style.
	 @discussion Installs a new Hui style.
	 @param args array - Element arguments in the structure.
	 @result True if the style has been installed.
	 */
	public function install($args) {
		if ($this -> mrAmpDb) {
			if (strlen($args['name']) and strlen($args['file'])) {
				return $this -> mrAmpDb -> Execute('INSERT INTO huithemes '.'VALUES ('.$this -> mrAmpDb -> NextSeqValue('huithemes_id_seq').','.$this -> mrAmpDb -> Format_Text($args['name']).','.$this -> mrAmpDb -> Format_Text($args['file']).','.$this -> mrAmpDb -> Format_Text($args['catalog']).')');
			}
		}
		return false;
	}

	/*!
	 @function Update
	 @abstract Updates a Hui style.
	 @discussion Updates a Hui style.
	 @param args array - Element arguments in the structure.
	 @result True if the style has been updated.
	 */
	public function update($args) {
		if ($this -> mrAmpDb) {
			if (strlen($args['name'])) {
				$check_query = $this -> mrAmpDb -> Execute('SELECT name '.'FROM huithemes '.'WHERE name='.$this -> mrAmpDb -> Format_Text($args['name']));

				if ($check_query -> NumRows()) {
					return $this -> mrAmpDb -> Execute('UPDATE huithemes '.'SET file='.$this -> mrAmpDb -> Format_Text($args['file']).','.'catalog='.$this -> mrAmpDb -> Format_Text($args['catalog']).' '.'WHERE name='.$this -> mrAmpDb -> Format_Text($args['name']));
				} else
					return $this -> Install($args);
			}
		}

		return false;
	}

	/*!
	 @function Remove
	 @abstract Removes a Hui style.
	 @discussion Removes a Hui style.
	 @result True if the style has been removed.
	 */
	public function remove($args) {
		if ($this -> mrAmpDb) {
			if (strlen($args['name'])) {
				return $this -> mrAmpDb -> Execute('DELETE FROM huithemes '.'WHERE name='.$this -> mrAmpDb -> Format_Text($args['name']));
			}
		}
		return false;
	}

	public function initTheme() {
		$result = false;
    	if (strlen($this -> mTheme)) {
			global $gEnv;
            import('com.solarix.ampoliros.core.Ampoliros');
            $amp = Ampoliros::instance('Ampoliros');

			if ($this -> mTheme == 'default')
				$this -> mTheme = $gEnv['hui']['theme']['default'];
			if ($this -> mTheme != 'userdefined') {
				if (file_exists(CONFIG_PATH.$this -> mTheme.'.huitheme')) {
					$this -> mThemeFile = CONFIG_PATH.$this -> mTheme.'.huitheme';
				} else {
					$this -> mTheme = $gEnv['hui']['theme']['default'];
					$this -> mThemeFile = CONFIG_PATH.$gEnv['hui']['theme']['default'].'.huitheme';
				}
                OpenLibrary('configman.library');
				$cfg_file = new ConfigFile($this -> mThemeFile);

				if ($cfg_file -> opened()) {
					$this -> mIconsSetName = $cfg_file -> Value('THEME.ICONSSET');
					$this -> mColorsSetName = $cfg_file -> Value('THEME.COLORSSET');
					$this -> mStyleName = $cfg_file -> Value('THEME.STYLE');
				} else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
					$log-> LogEvent('ampoliros.huithemes_library.huitheme_class.inittheme', 'Unable to open theme configuration file '.$this -> mThemeFile, LOGGER_ERROR);
                }
			} else {
				$this -> mIconsSetName = $this -> mUserSettings['iconsset'];
				$this -> mColorsSetName = $this -> mUserSettings['colorsset'];
				$this -> mStyleName = $this -> mUserSettings['stylename'];
			}

			$this -> mIconsSetBase = CGI_URL.'icons/'.$this -> mIconsSetName.'/';
			$this -> mIconsBase = CGI_URL.'icons/';
			$this -> mIconsSetDir = CGI_PATH.'icons/'.$this -> mIconsSetName.'/';
			$this -> mStyleBase = CGI_URL.'styles/';
			$this -> mStyleDir = CGI_PATH.'styles/'.$this -> mStyleName.'/';

			$hui_colors = new HuiColorsSet($this -> mrAmpDb, $this -> mColorsSetName);
			$hui_icons = new HuiIconsSet($this -> mrAmpDb, $this -> mIconsSetName);
			$hui_style = new HuiStyle($this -> mrAmpDb, $this -> mStyleName);

			if ($amp->getState() != Ampoliros::STATE_SETUP) {
				$cached_iconsset = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huiiconsset-'.$this -> mIconsSetName);
				$cached_colorsset = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huicolorsset-'.$this -> mColorsSetName);
				$cached_style = new CachedItem($this -> mrAmpDb, 'ampoliros', 'huistyle-'.$this -> mStyleName);

				$this -> mIconsSet = unserialize($cached_iconsset -> Retrieve());
				$this -> mColorsSet = unserialize($cached_colorsset -> Retrieve());
				$this -> mStyle = unserialize($cached_style -> Retrieve());
			}

			if (!$this -> mIconsSet or !$this -> mColorsSet or !$this -> mStyle) {
				if ($gEnv['hui']['theme']['default'] == $this -> mTheme) {
					$this -> mColorsSet = $hui_colors -> GetColorsSet();
					$this -> mIconsSet = $hui_icons -> GetIconsSet();
					$this -> mStyle = $hui_style -> GetStyle();
				} else {
                    OpenLibrary('configman.library');
					$def_cfg_file = new ConfigFile(CONFIG_PATH.$gEnv['hui']['theme']['default'].'.huitheme');

					if ($def_cfg_file -> Opened()) {
						$def_icons_set_name = $def_cfg_file -> Value('THEME.ICONSSET');
						$def_colors_set_name = $def_cfg_file -> Value('THEME.COLORSSET');
						$def_style_name = $def_cfg_file -> Value('THEME.STYLE');
					} else {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
						$log-> LogEvent('ampoliros.huithemes_library.huitheme_class.inittheme', 'Unable to open default theme configuration file '.CONFIG_PATH.$gEnv['hui']['theme']['default'].'.huitheme', LOGGER_ERROR);
                    }

					$hui_def_colors = new HuiColorsSet($this -> mrAmpDb, $def_colors_set_name);
					$hui_def_icons = new HuiIconsSet($this -> mrAmpDb, $def_icons_set_name);
					$hui_def_style = new HuiStyle($this -> mrAmpDb, $def_style_name);

					$this -> mColorsSet = $this -> DefOpts($hui_def_colors -> GetColorsSet(), $hui_colors -> GetColorsSet());
					$this -> mIconsSet = $this -> DefOpts($hui_def_icons -> GetIconsSet(), $hui_icons -> GetIconsSet());

					$this -> mStyle = $this -> DefOpts($hui_def_style -> GetStyle(), $hui_style -> GetStyle());
				}

				while (list ($style_name, $style_item) = each($this -> mStyle)) {
					$this -> mStyle[$style_name] = $this -> mStyleBase.$style_item['base'].'/'.$style_item['value'];
				}
				if ($amp->getState() != Ampoliros::STATE_SETUP) {
					$cached_iconsset -> Store(serialize($this -> mIconsSet));
					$cached_colorsset -> Store(serialize($this -> mColorsSet));
					$cached_style -> Store(serialize($this -> mStyle));
				}
			}
		}
		return $result;
	}

	public function defOpts($defaultSet, $givenSet) {
		$result = array();
		while (list ($key, $val) = each($defaultSet)) {
			if (is_array($val)) {
				$result[$key] = $this -> DefOpts($defaultSet[$key], $givenSet[$key]);
			} else {
				if (isset($givenSet[$key])) {
					$result[$key] = $givenSet[$key];
					unset($givenSet[$key]);
				} else
					$result[$key] = $val;
			}
		}
		$result = array_merge($givenSet, $result);
		return $result;
	}
}

?>