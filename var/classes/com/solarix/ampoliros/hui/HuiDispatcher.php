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
// $Id: HuiDispatcher.php,v 1.10 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

/*!
 @class HuiDispatcher
 @abstract Hui events dispatcher.
 @discussion Hui events dispatcher.
 */
class HuiDispatcher extends Object {
    /*! @var mName string - Dispatcher name. */
    private $mName;
    /*! @var mEvents array - Array of the event functions. */
    public $mEvents = array();
    /*! @var mDispatched bool - True when the events have been dispatched. */
    private $mDispatched;

    /*!
     @function HuiDispatcher
     @abstract Class constructor.
     @discussion Class constructor.
     @param dispName string - Dispatcher name.
     */
    public function HuiDispatcher($dispName) {
        if (strlen($dispName))
            $this -> mName = $dispName;
        else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.hui_library.huidispatcher_class.huidispatcher', 'Empty dispatcher name', LOGGER_ERROR);
        }
        $this -> mDispatched = false;

        import('com.solarix.ampoliros.hui.helpers.HuiValidatorHelper');
        HuiValidatorHelper::validate();
    }

    /*!
     @function AddEvent
     @abstract Adds an event to the dispatcher list.
     @discussion Adds an event to the dispatcher list. The event is not added if already exists an event with that name.
     @param eventName string - Event to be added to the dispatcher list.
     @param functionName string - Function that handles the eventName event.
     @result True if the event has been added. May return false if the event already exists.
     */
    public function addEvent($eventName, $functionName) {
        if (strlen($eventName) and strlen($functionName) and !isset($this -> mEvents[$eventName])) {
            $this -> mEvents[$eventName] = $functionName;
            return true;
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            if (!strlen($eventName))
                $log -> LogEvent('ampoliros.hui_library.huidispatcher_class.addevent', 'Empty event name', LOGGER_ERROR);
            if (!strlen($functionName))
                $log -> LogEvent('ampoliros.hui_library.huidispatcher_class.addevent', 'Empty function name', LOGGER_ERROR);
            if (isset($this -> mEvents[$eventName]) and strlen($this -> mEvents[$eventName]))
                $log -> LogEvent('ampoliros.hui_library.huidispatcher_class.addevent', 'Event '.$eventName.' already exists', LOGGER_ERROR);
            return false;
        }
    }

    /*!
     @function Dispatch
     @abstract Dispatches the incoming event.
     @discussion Distpatches the incoming event to the event assigned function. It first checks if the function
     exists.
     @result True if the event has been dispatched. Returns false if the function for the event doesn't exists.
     */
    function dispatch() {
        if (!$this -> mDispatched) {
            if (count($this -> mEvents) and isset($GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname']) and isset($this -> mEvents[$GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname']])) {
                if (function_exists($this -> mEvents[$GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname']])) {
                    $func = $this -> mEvents[$GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname']];
                    $func (isset($GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventdata']) ? $GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventdata'] : array());
                    return true;
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.hui_library.huidispatcher_class.dispatch', 'Function '.$this -> mEvents[$GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname']]." doesn't exists", LOGGER_ERROR);
                    return false;
                }
            }
        }
    }

    /*!
     @function GetEventName
     @abstract Gets current event name for this dispatcher.
     @discussion Gets current event name for this dispatcher.
     @result Current event name.
     */
    public function getEventName() {
        return isset($GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname']) ? $GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventname'] : '';
    }

    /*!
     @function GetEventData
     @abstract Gets current event data for this dispatcher.
     @discussion Gets current event data for this dispatcher.
     @result Current event data.
     */
    public function getEventData() {
        return $GLOBALS['gEnv']['runtime']['disp']['hui'][$this -> mName]['eventdata'];
    }
}

function dispatchers_list() {
    $result = array();
    if (isset($GLOBALS['gEnv']['runtime']['disp']['hui']) and is_array($GLOBALS['gEnv']['runtime']['disp']['hui'])) {
        reset($GLOBALS['gEnv']['runtime']['disp']['hui']);
        while (list ($dispatcher_name) = each($GLOBALS['gEnv']['runtime']['disp']['hui'])) {
            $result[] = $dispatcher_name;
        }
        reset($GLOBALS['gEnv']['runtime']['disp']['hui']);
    }
    return $result;
}

?>