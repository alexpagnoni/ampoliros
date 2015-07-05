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
// $Id: Hui.php,v 1.12 2004-07-13 15:20:52 alex Exp $

package('com.solarix.ampoliros.hui');

import('com.solarix.ampoliros.db.DBLayer');
import('com.solarix.ampoliros.hui.HuiDispatcher');

/**
 * Html User Interface.
 *
 * Gestore dell'interfaccia utente standard di Ampoliros.
 * Per ogni pagina dovrebbe essere generata un'apposita istanza della classe.
 * @package Ampoliros
 */
class Hui extends Object {
    /*! @var mrAmpDb Dblayer class - Ampoliros database handler. */
    private $mrAmpDb;
    /*! @var mChilds array - Array of the structure main childs. */
    private $mChilds = array();
    /*! @var mDisp HuiDispatcher class - Hui internal dispatcher, called "hui". */
    private $mDisp;
    /*! @var mLayout string - Structure layout. Filled by Hui->Build member. */
    public $mLayout;
    /*! @var mBuilt bool - True if the structure has been built. */
    public $mBuilt;
    /*! @var mLoadedWidgets array - Array of the loaded widgets. */
    public $mLoadedWidgets = array();
    /*! @var mLastError integer - Last error id. */
    public $mLastError;
    /*! @var mForceSetup boolean - TRUE if the check for setup phase must be skipped. Useful only for Ampoliros. */
    private $mForceSetup;

    const LOADWIDGET_FILE_NOT_EXISTS = '-1';
    const LOADWIDGET_WIDGET_ALREADY_LOADED = '-2';
    const LOADALLWIDGETS_UNABLE_TO_SELECT_ELEMENTS = '-1';
    const LOADALLWIDGETS_INVALID_AMPOLIROS_DB = '-2';
    const LOADALLWIDGETS_A_WIDGET_FILE_NOT_EXISTS = '-3';
    const LOADALLWIDGETS_FUNCTION_UNAVAILABLE = '-4';
    const RENDER_UNABLE_TO_RENDER = '-1';

    /*!
     @param rampDb Dblayer class - Ampoliros database handler.
     @param forceSetup boolean - TRUE if the check for setup phase must be skipped. Useful only for Ampoliros.
     */
    public function Hui($rampDb, $forceSetup = FALSE) {
        $this -> mBuilt = false;
        $this -> mDisp = new HuiDispatcher('hui');
        $this -> mForceSetup = $forceSetup;
        if (!defined('AMPOLIROS_SETUP_PHASE') or ($this -> mForceSetup and defined('AMPOLIROS_SETUP_PHASE'))) {
            if (is_object($rampDb))
                $this -> mrAmpDb = $rampDb;
        }
    }

    /*!
     @discussion Loads the handler for a widget class.
     @param widgetName string - widget class name to load.
     @result True if the widget has been loaded. May return false if the widget handler file doesn't exists.
     */
    public function loadWidget($widgetName) {
        $result = FALSE;
        if (!defined(strtoupper($widgetName.'_HUI')) and file_exists(HANDLER_PATH.$widgetName.'.hui') and !isset($this -> mLoadedWidgets[$widgetName])) {
            $result = include (HANDLER_PATH.$widgetName.'.hui');
            $this -> mLoadedWidgets[$widgetName] = $widgetName;
        } else {
            if (!file_exists(HANDLER_PATH.$widgetName.'.hui')) {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.hui_library.hui_class.loadwidget', 'Unable to load widget handler file '.HANDLER_PATH.$widgetName.'.hui', LOGGER_ERROR);
                $this -> mLastError = Hui :: LOADWIDGET_FILE_NOT_EXISTS;
            } else {
                $this -> mLastError = Hui :: LOADWIDGET_WIDGET_ALREADY_LOADED;
            }
        }
        return $result;
    }

    /*!
     @abstract Loads all the widgets.
     @discussion Loads all the widgets in the huielements table.
     Not functional during Ampoliros setup phase.
     @result True if the widgets have been loaded.
     */
    public function loadAllWidgets() {
        $result = false;
        import('com.solarix.ampoliros.core.Ampoliros');
        $amp = Ampoliros :: instance('Ampoliros');

        if ($amp -> getState() == Ampoliros :: STATE_DEBUG)
            $GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> Mark('start - Hui::LoadAllWidgets()');
        if ($amp -> getState() != Ampoliros :: STATE_SETUP or ($amp -> getState() == Ampoliros :: STATE_SETUP and $this -> mForceSetup)) {
            if (is_object($this -> mrAmpDb)) {
                $query = $this -> mrAmpDb -> Execute('SELECT name FROM huielements');

                if ($query) {
                    $result = true;

                    // Load every widget
                    //
                    while (!$query -> eof) {
                        // Load the widget and check if the widget file exists
                        //
                        if (!$this -> LoadWidget($query -> Fields('name'))) {
                            $result = false;
                            if ($this -> mLastError == Hui :: LOADWIDGET_FILE_NOT_EXISTS)
                                $this -> mLastError = Hui :: LOADALLWIDGETS_A_WIDGET_FILE_NOT_EXISTS;
                        }
                        $query -> MoveNext();
                    }

                    if (!$result and strcmp($this -> mLastError, Hui :: LOADALLWIDGETS_A_WIDGET_FILE_NOT_EXISTS) == 0) {
                        import('com.solarix.ampoliros.io.log.Logger');
                        $log = new Logger(AMP_LOG);
                        $log -> LogEvent('ampoliros.hui_library.hui_class.loadallwidgets', 'Unable to load at least one widget handler file', LOGGER_ERROR);
                    }
                } else {
                    $this -> mLastError = Hui :: LOADALLWIDGETS_UNABLE_TO_SELECT_ELEMENTS;
                }
            } else {
                $this -> mLastError = Hui :: LOADALLWIDGETS_INVALID_AMPOLIROS_DB;
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.hui_library.hui_class.loadallwidgets', 'Function unavailable during Ampoliros setup phase', LOGGER_WARNING);
            $this -> mLastError = Hui :: LOADALLWIDGETS_FUNCTION_UNAVAILABLE;
        }
        if ($amp -> getState() == Ampoliros :: STATE_DEBUG)
            $GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> Mark('end - Hui::LoadAllWidgets()');
        return $result;
    }

    /*!
     @discussion Adds a child widget to the structure.
     @param rchildWidget class HuiWidgetElement - Adds a child widget to the structure.
     @result Always true.
     */
    public function addChild(HuiWidgetElement $rchildWidget) {
        $this -> mChilds[] = $rchildWidget;
        return true;
    }

    /*!
     @discussion Builds the structure.
     @result True if the structure has been built by the member.
     */
    public function build() {
        $result = false;

        if (!$this -> mBuilt) {
            import('com.solarix.ampoliros.core.Ampoliros');
            $amp = Ampoliros :: instance('Ampoliros');
            if ($amp -> getState() == Ampoliros :: STATE_DEBUG)
                $GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> Mark('start - Hui::Build()');

            $children_count = count($this -> mChilds);
            if ($children_count) {
                // Builds the structure
                //
                for ($i = 0; $i < $children_count; $i ++) {
                    if ($this -> mChilds[$i] -> Build($this -> mDisp))
                        $this -> mLayout.= $this -> mChilds[$i] -> Render();
                    $this -> mChilds[$i] -> Destroy();
                }
                $this -> mBuilt = true;
                $result = true;
            }

            // Call the internal dispatcher, if not alread called
            //
            $this -> mDisp -> Dispatch();

            if ($amp -> getState() == Ampoliros :: STATE_DEBUG)
                $GLOBALS['gEnv']['runtime']['debug']['loadtime'] -> Mark('stop - Hui::Build()');
        }
        return $result;
    }

    /*!
     @abstract Renders the structure.
     @discussion If the structure has not been built, it will call the Hui->Build() member.
     @result True if the structure has been rendered
     */
    public function render() {
        if (!$this -> mBuilt)
            $this -> Build();

        if ($this -> mBuilt) {
            @ header('P3P: CP="CUR ADM OUR NOR STA NID"');
            $carthag = Carthag :: instance();
            $carthag -> out -> println($this -> mLayout);
            return true;
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.hui_library.hui_class.render', 'Unable to render hui', LOGGER_ERROR);
            $this -> mLastError = Hui :: RENDER_UNABLE_TO_RENDER;
        }
        return false;
    }

    public function getDispatcher() {
        return $this -> mDisp;
    }
}

?>