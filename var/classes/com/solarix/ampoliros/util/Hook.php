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
// $Id: Hook.php,v 1.6 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.util');

import('com.solarix.ampoliros.db.DBLayer');

define ('HOOK_RESULT_OK', '1');
define ('HOOK_RESULT_CANCEL', '2');
define ('HOOK_RESULT_ABORT', '3');

/*!
 @class Hook

 @abstract Provides hook functionality.

 @discussion Provides hook functionality. An hook is a method to automatically call functions not defined
 at the moment of the code writing when a certain function is a called.
 */
class Hook extends Object {
    /*! @var mAmpDb Dblayer class - Ampoliros database handler. */
    private $mAmpDb;
    /*! @var mModule string - Module of the function containing the hook. */
    private $mModule;
    /*! @var mFunction string - Name of the function containing the hook. */
    private $mFunction;
    const RESULT_OK = 1;
    const RESULT_CANCEL = 2;
    const RESULT_ABORT = 3;

    /*!
     @function Hook	
     @abstract Class constructor.	
     @discussion Class constructor.	
     @param &ampDb Dblayer class - Ampoliros database handler.
     @param module string - Module of the function containing the hook.
     @param function string - Name of the function containing the hook.
     */
    function Hook(DBLayer $ampDb, $module, $function) {
        $this -> mAmpDb = $ampDb;
        $this -> mModule = $module;
        $this -> mFunction = $function;
    }

    /*!
     @function CallHooks
     @abstract Call the functions associated to a certain hook event.
     @discussion Call the functions associated to a certain hook event.
     @param event string -.Name of the hook event that occured.
     @param &obj object - If the hook is contained in an object, a reference to the object should be passed.
     @param args array - Array of the arguments of the function containing the hook.
     @result True if the function associated to the hook event have been called.
     */
    function CallHooks($event, & $obj, $args = '') {
        $result = false;
        if ($this -> mAmpDb) {
            $query = $this -> mAmpDb -> Execute('SELECT * FROM hooks WHERE functionmodule='.$this -> mAmpDb -> Format_Text($this -> mModule).' AND function='.$this -> mAmpDb -> Format_Text($this -> mFunction).' AND event='.$this -> mAmpDb -> Format_Text($event));
            if ($query) {
                global $gEnv;
                $result = HOOK_RESULT_OK;
                import('com.solarix.ampoliros.core.Ampoliros');
                $amp = Ampoliros::instance('Ampoliros');
                while (!$query -> eof) {
                    $data = $query -> Fields();
                    if ($amp->getState() == Ampoliros::STATE_DEBUG)
                        $gEnv['runtime']['debug']['hooks'][$this -> mModule.'::'.$this -> mFunction.'::'.$event][] = $data['hookhandler'].' - '.$data['hookfunction'];
                    OpenLibrary($data['hookhandler'], HANDLER_PATH);
                    $func_result = $data['hookfunction'] ($obj, $args);
                    if ($func_result == HOOK_RESULT_ABORT)
                        $result = HOOK_RESULT_ABORT;
                    $query -> MoveNext();
                }
            }
        }

        return $result;
    }

    /*!
     @function Add
     @abstract Adds an event to the hook.
     @discussion Adds an event to the hook.
     @param event string - Name of the event to be added.
     @param hookModule string - Name of the module containing the function with the hook.
     @param hookHandler string - Name of the file containing the handler for the hook event.
     @param hookFunction string - Name of the function that handles the hook event.
     @result True if the hook event has been added.
     */
    function Add($event, $hookModule, $hookHandler, $hookFunction) {
        $result = false;
        if ($event and $hookModule and $hookHandler and $hookFunction) {
            // :TODO: wuh 020114: add check
            // The function should check if the method already exists.

            $result = $this -> mAmpDb -> Execute('INSERT INTO hooks '.'VALUES ('.$this -> mAmpDb -> NextSeqValue('hooks_id_seq').','.$this -> mAmpDb -> Format_Text($this -> mModule).','.$this -> mAmpDb -> Format_Text($this -> mFunction).','.$this -> mAmpDb -> Format_Text($event).','.$this -> mAmpDb -> Format_Text($hookModule).','.$this -> mAmpDb -> Format_Text($hookHandler).','.$this -> mAmpDb -> Format_Text($hookFunction).' )');
        }
        return $result;
    }

    /*!
     @function Remove
     @abstract Removes a hook.
     @discussion Removed a hook.
     @param event string - Name of the event to be removed.
     @param hookModule string - Name of the module containing the function with the hook.
     @param hookHandler string - Name of the file containing the handler for the hook event.
     @param hookFunction string - Name of the function that handles the hook event.
     @result True if the hook has been removed.
     */
    function Remove($event, $hookModule, $hookHandler, $hookFunction) {
        $result = false;
        if ($event) {
            $result = $this -> mAmpDb -> Execute('DELETE FROM hooks '.'WHERE functionmodule='.$this -> mAmpDb -> Format_Text($this -> mModule).' '.'AND function='.$this -> mAmpDb -> Format_Text($this -> mFunction).' '.'AND event='.$this -> mAmpDb -> Format_Text($event).' '.'AND hookmodule='.$this -> mAmpDb -> Format_Text($hookModule).' '.'AND hookhandler='.$this -> mAmpDb -> Format_Text($hookHandler).' '.'AND hookfunction='.$this -> mAmpDb -> Format_Text($hookFunction));
        }
        return $result;
    }

    /*!
     @function Update
     @abstract Updates an existing hook.
     @result True if the hook has been updated.
     */
    function Update($hookModule, $hookHandler, $hookFunction) {
        $result = false;
        if ($this -> mName and $function and $handler) {
            $result = $this -> mAmpDb -> Execute('UPDATE hooks '.'SET hookhandler='.$this -> mAmpDb -> Format_Text($hookHandler).','.'hookfunction='.$this -> mAmpDb -> Format_Text($hookFunction).' '.'WHERE functionmodule='.$this -> mAmpDb -> Format_Text($this -> mModule).' '.'AND event='.$this -> mAmpDb -> Format_Text($event).' '.'AND hookmodule='.$this -> mAmpDb -> Format_Text($hookModule).' '.'AND function='.$this -> mAmpDb -> Format_Text($this -> mFunction));
        }
        return $result;
    }

    /*!
     @function AddEvent
     @abstract Adds an event to the hook events list.
     @discussion Adds an event to the hook events list.
     @param event string - Event name.
     @result True if the event has been added into the list.
     */
    function AddEvent($event) {
        $result = false;
        if ($event) {
            // :TODO: wuh 020114: add check
            // The function should check if the method already exists.
            $result = $this -> mAmpDb -> Execute('INSERT INTO hookevents '.'VALUES ('.$this -> mAmpDb -> NextSeqValue('hookevents_id_seq').','.$this -> mAmpDb -> Format_Text($this -> mModule).','.$this -> mAmpDb -> Format_Text($this -> mFunction).','.$this -> mAmpDb -> Format_Text($event).' )');
        }
        return $result;
    }

    /*!
     @function Remove event
     @abstract Removes a hook event from the list.
     @discussion Removes a hook event from the list.
     @param event string - Event name.
     @result True if the hook event has been removed.
     */
    function RemoveEvent($event) {
        $result = false;
        if ($event) {
            $result = $this -> mAmpDb -> Execute('DELETE FROM hookevents '.'WHERE functionmodule='.$this -> mAmpDb -> Format_Text($this -> mModule).' '.'AND function='.$this -> mAmpDb -> Format_Text($this -> mFunction).' '.'AND event='.$this -> mAmpDb -> Format_Text($event));
        }
        return $result;
    }
}

?>