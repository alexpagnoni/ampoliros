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
// $Id: HuiEventRawData.php,v 1.5 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

/*!
 @class HuiEventRawData

 @abstract Event raw data handler.
 */
class HuiEventRawData extends Object {
	private $mDispatcherName;
	private $mKey;
	private $mType;

	/*!
	 @function HuiEventRawData
	 */
	public function HuiEventRawData($dispName, $key, $type = '') {
		$this -> SetDispatcherName($dispName);
		$this -> SetKey($key);
		$this -> SetType($type);
	}

	/*!
	 @function SetDispatcherName
	 @abstract Sets event dispatcher name.
	 @discussion Sets event dispatcher name.
	 @param eventDispatcherName string - Name of the dispatcher that handles this event.
	 @result Always true.
	 */
	public function setDispatcherName($eventDispatcherName) {
		$this -> mDispatcherName = $eventDispatcherName;
		return true;
	}

	/*!
	 @function SetKey
	 @abstract Sets event dispatcher key name.
	 @discussion Sets event dispatcher key name.
	 @param key string - Name of the event key.
	 @result Always true.
	 */
	public function setKey($key) {
		$this -> mKey = $key;
		return true;
	}

	/*!
	 @function SetType
	 @abstract Sets event dispatcher type.
	 @discussion Sets event dispatcher type.
	 @param type string - Type.
	 @result Always true.
	 */
	public function setType($type) {
		$this -> mType = $type;
		return true;
	}

	public function getDataString() {
		$result = 'hui';
		if ($this -> mType == 'file')
			$result.= 'files';
		$result.= '['.$this -> mDispatcherName.'][eventdata]['.$this -> mKey.']';
		return $result;
	}
}

?>