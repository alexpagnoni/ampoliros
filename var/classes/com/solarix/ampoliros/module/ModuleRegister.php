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
// $Id: ModuleRegister.php,v 1.6 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.module');

/*!
 @class ModuleRegister
 @abstract Module elements register handling.
 */
class ModuleRegister extends Object {
    /*! @public ampdb dblayer class - Ampoliros database handler. */
    public $ampdb;

    /*!
     @abstract Class constructor.
     @param ampdb dblayer class - Ampoliros database handler.
     */
    public function ModuleRegister(DBLayer $ampdb) {
        $this -> ampdb = $ampdb;
    }

    //
    // Module elements registration routines
    //

    /*!
     @abstract Registers a module element in the elements register.
     @param modname string - name if the module.
     @param category string - module element category complete name (not id number).
     @param elementname string - element name.
     @param elementfile string - element file.
     @param override boolean - if you want to override the previous registration.
     @result True if registered.
     */
    public function registerElement($modname, $category, $elementname, $elementfile, $override = FALSE) {
        if (($this -> CheckRegisterElement($category, $elementfile) == FALSE) or $override == TRUE) {
            $tmpquery = $this -> ampdb -> Execute('SELECT id FROM elementtypes WHERE typename='.$this -> ampdb -> Format_Text($category));
            $tmpdata = $tmpquery -> Fields();
            $this -> ampdb -> Execute('INSERT INTO modregister VALUES ( '.$this -> ampdb -> Format_Text($modname).','.$this -> ampdb -> Format_Text($tmpdata['id']).','.$this -> ampdb -> Format_Text($elementname).','.$this -> ampdb -> Format_Text($elementfile).')');
            return true;
        }
        return false;
    }

    /*!
     @abstract Checks if a certain file has been already registered.
     @param category string - module element category complete name (not id number).
     @param elementfile string - element file.
     @param modname string - name if the module.
     @param exclude boolean - if you want to exclude modname (if given) from check.
     @result Modname if registered.
     */
    public function checkRegisterElement($category, $elementfile, $modname = '', $exclude = FALSE) {
        $result = FALSE;

        $catquery = $this -> ampdb -> Execute('SELECT id FROM elementtypes WHERE typename='.$this -> ampdb -> Format_Text($category));
        $catdata = $catquery -> Fields();

        $querystr = 'categoryid='.$this -> ampdb -> Format_Text($catdata['id']).' AND elementfile='.$this -> ampdb -> Format_Text($elementfile);
        if (!empty($modname))
            $querystr.= ' AND modname '. ($exclude ? '!=' : '=').' '.$this -> ampdb -> Format_Text($modname);

        $tmpquery = $this -> ampdb -> Execute('SELECT * FROM modregister WHERE '.$querystr);
        if ($tmpquery -> NumRows()) {
            $result = &$tmpquery -> Fields();
        }

        return $result;
    }

    /*!
     @abstract Unregisters an element.
     @param modname string - name if the module.
     @param category string - module element category complete name (not id number).
     @param elementfile string - element file.
     @result True if unregistered.
     */
    public function unregisterElement($modname, $category, $elementfile) {
        $regdata = $this -> CheckRegisterElement($category, $elementfile, $modname);
        if ($regdata != FALSE) {
            $catquery = & $this -> ampdb -> Execute('SELECT id FROM elementtypes WHERE typename='.$this -> ampdb -> Format_Text($category));
            $catdata = $catquery -> Fields();

            $this -> ampdb -> Execute('DELETE FROM modregister WHERE modname='.$this -> ampdb -> Format_Text($modname).' AND categoryid='.$this -> ampdb -> Format_Text($catdata['id']).' AND elementfile='.$this -> ampdb -> Format_Text($elementfile));
            return true;
        }
        return false;
    }
}

?>