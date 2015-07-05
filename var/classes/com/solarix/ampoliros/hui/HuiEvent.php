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
// $Id: HuiEvent.php,v 1.5 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

/*!
 @class HuiEvent
 @abstract Hui event.
 @discussion Hui event.
 */
class HuiEvent extends Object {
	/*! @var mDispatcherName string - Dispatcher name for this event. */
	private $mDispatcherName;
	/*! @var mName string - Event name. */
	private $mName;
	/*! @var mData array - Event key value pairs array. */
	private $mData = array();

	/*!
	@function HuiEvent
	@abstract Class constructor.
	@discussion Class constructor.
	@param eventDispatcherName string - Name of the dispatcher that handles this event.
	@param eventName string - Name of the event.
	@param eventData array - Event key value pairs array.
	*/
	public function HuiEvent($eventDispatcherName, $eventName, $eventData = '') {
		$this -> SetDispatcherName($eventDispatcherName);
		$this -> SetName($eventName);
		$this -> SetData($eventData);
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
	@function SetName
	@abstract Sets event name.
	@discussion Sets event name.
	@param eventName string - Name of the event.
	@result Always true.
	*/
	public function setName($eventName) {
		$this -> mName = $eventName;
		return true;
	}

	/*!
	@function SetData
	@abstract Sets event data.
	@discussion Sets event data.
	@param eventData array - Event key value pairs array.
	@result Always true.
	*/
	public function setData($eventData) {
		if (is_array($eventData))
			$this -> mData = $eventData;
		return true;
	}

	/*!
	@function GetEventString
	@abstract Gets event string.
	@discussion Gets event string.
	@result Event string.
	*/
	public function getEventString() {
		$result = false;

		if (strlen($this -> mDispatcherName) and strlen($this -> mName)) {
			$items_count = count($this -> mData);
			$result = 'hui['.$this -> mDispatcherName.'][eventname]='.$this -> mName;

			if ($items_count) {
				$result.= '&amp;';
				reset($this -> mData);
				$cont = 1;

				while (list ($key, $val) = each($this -> mData)) {
					$result.= 'hui['.$this -> mDispatcherName.'][eventdata]['.$key.']='.urlencode($val);
					if ($cont < $items_count)
						$result.= '&amp;';
					$cont ++;
				}
			}

		}
		return $result;
	}
}

?>