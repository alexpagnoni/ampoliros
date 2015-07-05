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
// $Id: HuiWidgetElement.php,v 1.10 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

import('com.solarix.ampoliros.hui.theme.HuiTheme');
import('com.solarix.ampoliros.hui.HuiDispatcher');

/*!
 @class HuiWidgetElement
 @abstract Base widget class.
 @discussion Base widget class, to be extended by every widget handler.
 */
abstract class HuiWidgetElement extends Object {
	/*! @var mrHuiDisp dispatcher class - Hui internal dispatcher. */
	public $mrHuiDisp;
	/*! @var mLayout string - Element layout. */
	public $mLayout;
	/*! @var mName string - Element unique name. */
	public $mName;
	/*! @var mArgs array - Array of element arguments and attributes. */
	public $mArgs = array();
	/*! @var mTheme string - Theme applied to the element. */
	public $mTheme;
	/*! @var mThemeHandler HuiTheme class - Theme handler. */
	public $mThemeHandler;
	/*! @var mDispEvents array - Dispatcher events. */
	public $mDispEvents = array();
	/*! @var mComments boolean - Set to TRUE if element should contain comment blocks. */
	public $mComments;
	/*! @var mWidgetType string - Type of widget. */
	public $mWidgetType;
	/*! @var mUseSession boolean - TRUE if the widget should use the stored session parameters. */
	public $mUseSession;
	/*! @var mSessionObjectName string - Name of this widget as object in the session. */
	public $mSessionObjectName;
	public $mSessionObjectUserName;
	public $mSessionObjectNoUser;
	public $mSessionObjectNoPage;
	public $mSessionObjectNoType;
	public $mSessionObjectNoName;

	/*!
	 @function HuiWidgetElement
	 @abstract Class constructor.
	 @discussion Class constructor.
	 @param elemName string - Element unique name.
	 @param elemArgs array - Array of element arguments and attributes.
	 @param elemTheme string - Theme to be applied to the element. Currently unuseful.
	 @param dispEvents array - Dispatcher events.
	 */
	public function HuiWidgetElement($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
		$this -> mName = $elemName;
		$this -> mArgs = &$elemArgs;
		$this -> mComments = AMP_HUI_COMMENTS;
		if (is_array($dispEvents))
			$this -> mDispEvents = &$dispEvents;

		if (strlen($elemTheme))
			$this -> mTheme = $elemTheme;
		else
			if (isset($GLOBALS['gEnv']['hui']['theme']['name']) and strlen($GLOBALS['gEnv']['hui']['theme']['name'])) {
				$this -> mTheme = $GLOBALS['gEnv']['hui']['theme']['name'];
			} else {
				$this -> mTheme = $GLOBALS['gEnv']['hui']['theme']['default'];
			}

		if (!isset($GLOBALS['gEnv']['hui']['theme']['name']) or !isset($GLOBALS['gEnv']['hui']['theme']['handler'])) {
			$this -> mThemeHandler = new HuiTheme($GLOBALS['gEnv']['root']['db'], $this -> mTheme);
		} else
			if (isset($GLOBALS['gEnv']['hui']['theme']['handler']) and is_object($GLOBALS['gEnv']['hui']['theme']['handler'])) {
				$this -> mThemeHandler = $GLOBALS['gEnv']['hui']['theme']['handler'];
			}

		if (!isset($GLOBALS['gEnv']['hui']['theme']['handler'])) {
			$GLOBALS['gEnv']['hui']['theme']['handler'] = $this -> mThemeHandler;
		}

		if (isset($this -> mArgs['usesession']) and ($this -> mArgs['usesession'] == 'true' or $this -> mArgs['usesession'] == 'false'))
			$this -> mUseSession = $this -> mArgs['usesession'];
		else
			$this -> mUseSession = 'true';

		if (isset($this -> mArgs['sessionobjectnouser']))
			$this -> mSessionObjectNoUser = $this -> mArgs['sessionobjectnouser'];
		if (isset($this -> mArgs['sessionobjectnopage']))
			$this -> mSessionObjectNoPage = $this -> mArgs['sessionobjectnopage'];
		if (isset($this -> mArgs['sessionobjectnotype']))
			$this -> mSessionObjectNoType = $this -> mArgs['sessionobjectnotype'];
		if (isset($this -> mArgs['sessionobjectnoname']))
			$this -> mSessionObjectNoName = $this -> mArgs['sessionobjectnoname'];

		if (isset($this -> mArgs['sessionobjectusername']))
			$this -> mSessionObjectUserName = $this -> mArgs['sessionobjectusername'];

		$this -> mSessionObjectName = ($this -> mSessionObjectNoUser == 'true' ? '' : $GLOBALS['gEnv']['user']['id'].'_'). ($this -> mSessionObjectNoPage == 'true' ? '' : $_SERVER['PHP_SELF'].'_'). ($this -> mSessionObjectNoType == 'true' ? '' : $this -> mWidgetType.'_'). ($this -> mSessionObjectNoName == 'true' ? '' : $this -> mName). (strlen($this -> mSessionObjectUserName) ? '_'.$this -> mSessionObjectUserName : '');
	}

	/*!
	 @function Build
	 @abstract Builds the structure.
	 @discussion Builds the structure.
	 @param rhuiDisp HuiDispatcher class - Hui internal dispatcher handler.
	 @result True it the structure has been built by the member.
	 */
	public function build(HuiDispatcher $rhuiDisp) {
		$this -> mrHuiDisp = $rhuiDisp;
		return $this -> _Build();
	}

	/*!
	 @function Render
	 @abstract Renders the structure.
	 @discussion If the structure has not been built, it will call the Hui->Build() member.
	 @result True if the structure has been rendered.
	 */
	public function &render() {
		return $this -> mLayout;
	}

	public function destroy() {
		$this -> mLayout = '';
		$this -> mArgs = array();
	}

	/*!
	 @function _Build
	 @abstract Wrapped build function, redefined by extension classes.
	 @discussion Wrapped build function, redefined by extension classes.
	 @result Always true if not extended.
	 */
	protected function _build() {
		$this -> mLayout = '';
		return true;
	}

	/*!
	 @function StoreSession
	 @abstract Stores widget parameters to be saved in the session.
	 @param args array - Array of the parameters to be stored.
	 @result Always true.
	 */
	public function storeSession($args) {
		if ($this -> mUseSession)
			$_SESSION[$this -> mSessionObjectName] = serialize($args);
		return true;
	}

	/*!
	 @function RetrieveSession
	 @abstract Retrieves stored widget parameters.
	 @result The array of the stored parameters, if any.
	 */
	public function retrieveSession() {
		if ($this -> mUseSession == 'true' and isset($_SESSION[$this -> mSessionObjectName]))
			return unserialize($_SESSION[$this -> mSessionObjectName]);
		else
			return false;
	}
}

?>