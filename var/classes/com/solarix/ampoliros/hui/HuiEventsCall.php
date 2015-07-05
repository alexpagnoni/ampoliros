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
// $Id: HuiEventsCall.php,v 1.5 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

import('com.solarix.ampoliros.hui.HuiEvent');

// HuiEventsCall error codes
//

// HuiEventsCall::AddEvent()
//
define ('HUI_HUIEVENTSCALL_ADDEVENT_NOTANOBJECT', -1);

/*!
 @abstract Hui events call builder.
 */
class HuiEventsCall extends Object {
	/*! @var mCall string - Events call url. */
	private $mCall;
	/*! @var mEvents array - Array of events string. */
	private $mEvents = array();

	/*!
	 @discussion Sets event call. A normal event doesn't need to set eventsCall parameter.
	 @param eventsCall string - Events call url. If not defined, the class defaults it to PHP_SELF constant.
	 */
	public function HuiEventsCall($eventsCall = '') {
		$this -> setCall($eventsCall);
	}

	/*!
	 @discussion Sets event call.
	 @param eventsCall string - Events call url. If not defined, the class defaults it to PHP_SELF constant.
	 @result Always true.
	 */
	public function setCall($eventsCall = '') {
		if (strlen($eventsCall))
			$this -> mCall = $eventsCall;
		else
			$this -> mCall = $_SERVER['PHP_SELF'];
		return true;
	}

	/*!
	 @discussion Adds an event string to the call.
	 @param revent HuiEvent class - Event to be added.
	 @result True if the event is a real object.
	 */
	public function addEvent(& $revent) {
		if (is_object($revent)) {
			$this -> mEvents[] = $revent -> GetEventString();
			return true;
		} else {
			return HUI_HUIEVENTSCALL_ADDEVENT_NOTANOBJECT;
		}
	}

	/*!
	 @discussion Builds and returns the event call string.
	 @result The event call string. At least it will return PHP_SELF constant.
	 */
	public function getEventsCallString() {
		$result = $this -> mCall;
		$items_count = count($this -> mEvents);
		if ($items_count) {
			$result.= '?';
			reset($this -> mEvents);
			$cont = 1;

			while (list ($key, $val) = each($this -> mEvents)) {
				$result.= $val;
				if ($cont < $items_count)
					$result.= '&amp;';
				$cont ++;
			}
		}
		return $result;
	}

	/*!
	 @discussion Resets the event call, removing all events.
	 @result Always true.
	 */
	public function resetEvents() {
		$this -> mEvents = array();
		return true;
	}
}

function build_events_call_string($eventsCallUrl, $eventsArray) {
	$tmp_action = new HuiEventsCall($eventsCallUrl);
	if (is_array($eventsArray)) {
		while (list (, $event) = each($eventsArray)) {
			$tmp_action -> addEvent(new HuiEvent($event[0], $event[1], isset($event[2]) ? $event[2] : ''));
		}
	}
	return $tmp_action -> GetEventsCallString();
}

?>