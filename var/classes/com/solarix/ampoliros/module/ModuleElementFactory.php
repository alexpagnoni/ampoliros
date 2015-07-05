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
// $Id: ModuleElementFactory.php,v 1.8 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.module');

/*!
 @class ModuleElementFactory
 @abstract Module element types handling.
 */
class ModuleElementFactory extends Object {
    /*! @public types array - Array of the element types. */
    public $types = array();
    /*! @public ampdb dblayer class - Ampoliros database handler. */
    public $ampdb;

    /*!
     @function ModuleElementFactory
    
     @abstract Class constructor.
    
     @param ampdb dblayer class - Ampoliros database handler.
    */
    public function ModuleElementFactory(DBLayer $ampdb) {
        $this -> ampdb = $ampdb;
    }

    /*!
     @function FillTypes
    
     @abstract Fill the types property with the element types.
    
     @result True if no problem encountered.
     */
    public function fillTypes() {
        $result = TRUE;

        // Flushes current types
        //
        unset($this -> types);
        $this -> types = array();

        if ($this -> ampdb) {
            $query = & $this -> ampdb -> Execute('SELECT * FROM elementtypes');

            if ($query) {
                if ($query -> NumRows()) {
                    // Fills types
                    //
                    while (!$query -> eof) {
                        $data = $query -> Fields();

                        unset($element);
                        if (file_exists(HANDLER_PATH.$data['file'])) {
                            include (HANDLER_PATH.$data['file']);
                        } else {
                            import('com.solarix.ampoliros.io.log.Logger');
                            $log = new Logger(AMP_LOG);
                            $log -> LogEvent('ampoliros.modules_library.moduleelementtypes_class.filltypes', 'Element file '.$data['file']." doesn't exists in handlers directory", LOGGER_WARNING);
                            $result = FALSE;
                        }

                        $this -> types[$element['type']] = $element;
                        $query -> MoveNext();
                    }
                }
	            $query -> Free();
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moduleelementtypes_class.filltypes', 'Unable to select element types from table', LOGGER_ERROR);
                $result = FALSE;
            }
            $result = FALSE;
        }
        return $result;
    }
    /*
    function tmpFillTypes() {
       $result = true;
    
       // Flushes current types
       //
       unset($this -> types);
       $this -> types = array();
    
       if ($this -> ampdb) {
           $query = $this -> ampdb -> Execute('SELECT * FROM elementtypes');
           if (is_object($query)) {
               while (!$query -> eof) {
                   $this -> types[$query -> Fields('typename')] = $query -> Fields();
                   $query -> MoveNext();
               }
               $result = true;
           } else {
               $this -> mLog -> LogEvent('ampoliros.modules_library.moduleelementfactory_class.filltypes', 'Unable to select element types from table', LOGGER_ERROR);
               $result = FALSE;
           }
           $query -> Free();
       } else {
           if (!$this -> ampdb)
               $this -> mLog -> LogEvent('ampoliros.modules_library.moduleelementfactory_class.filltypes', 'Invalid Ampoliros database handler', LOGGER_ERROR);
           $result = FALSE;
       }
    
       return $result;
    }
    */

    /*!
     @abstract Installs a new element type handler.
     @discussion Element type handler must have .element suffix.
     @param filepath string - Complete path of the element type handler.
     @result True if successfully installed.
     */
    public function install($elementData) {
        $result = FALSE;
        $filepath = $elementData['filepath'];

        if ($this -> ampdb and file_exists($filepath)) {
            include ($filepath);

            if ($element['type'] and $element['classname']) {
                if (!isset($element['priority']))
                    $element['priority'] = 0;
                if (!isset($element['links']))
                    $element['links'] = array();

                $result = & $this -> ampdb -> Execute('INSERT INTO elementtypes (id,typename,priority,site,file) VALUES ('.$this -> ampdb -> NextSeqValue('elementtypes_id_seq').','.$this -> ampdb -> Format_Text($element['type']).','.$element['priority'].','.$this -> ampdb -> Format_Text(($element['site'] ? $this -> ampdb -> fmttrue : $this -> ampdb -> fmtfalse)).','.$this -> ampdb -> Format_Text(basename($filepath)).')');
            }
        } else {
            if (!file_exists($filepath)) {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moduleelementfactory_class.install', 'Given file ('.$filepath.') does not exists', LOGGER_ERROR);
            }
        }
        return $result;
    }

    /*!
     @abstract Updates an element type handler.
     @discussion Element type handler must have .element suffix.
     @param filepath string - Complete path of the element type handler.
     @result True if successfully updated.
     */
    public function update($elementData) {
        $result = FALSE;
        $filepath = $elementData['filepath'];

        if ($this -> ampdb and file_exists($filepath)) {
            include ($filepath);

            if ($element['type'] and $element['classname']) {
                if (!isset($element['priority']))
                    $element['priority'] = 0;
                if (!isset($element['links']))
                    $element['links'] = array();

                $result = $this -> ampdb -> Execute('UPDATE elementtypes SET priority='.$element['priority'].',site='.$this -> ampdb -> Format_Text(($element['site'] ? $this -> ampdb -> fmttrue : $this -> ampdb -> fmtfalse)).',file='.$this -> ampdb -> Format_Text(basename($filepath)).' WHERE typename='.$this -> ampdb -> Format_Text($element['type']));
            }
        } else {
            if (!file_exists($filepath)) {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moduleelementfactory_class.install', 'Given file ('.$filepath.') does not exists', LOGGER_ERROR);
            }
        }
        return $result;
    }

    /*!
     @abstract Removes an element type handler.
     @discussion An element type handler must be removed only if there are no installed elements of that type.
     @param filepath string - Complete path of the element type handler to be removed.
     @result True if successfully uninstalled.
     */
    public function uninstall($elementData) {
        $result = FALSE;
        $filepath = $elementData['filepath'];
        if ($this -> ampdb and file_exists($filepath)) {
            include ($filepath);

            if ($element['type'] and $element['classname']) {
                $result = $this -> ampdb -> Execute('DELETE FROM elementtypes WHERE typename='.$this -> ampdb -> Format_Text($element['type']));
            }
        } else {
            if (!file_exists($filepath)) {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.modules_library.moduleelementfactory_class.uninstall', 'Given file ('.$filepath.') does not exists', LOGGER_ERROR);
            }
        }
        return $result;
    }
}

?>