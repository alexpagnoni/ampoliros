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
// $Id: HuiElementHandler.php,v 1.6 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

/*!
 @class HuiElementHandler
 @abstract Hui elements handler.
 @discussion A hui element handler should have .hui as suffix.
 */
class HuiElementHandler extends Object {
    /*! @var mrAmpDb Dblayer class - Ampoliros database handler. */
    private $mrAmpDb;
    /*! @var mElementName string - Element name. */
    private $mElementName;

    /*!
     @function HuiElement
     @abstract Class constructor.
     @discussion Class constructor.
     @param rampDb Dblayer class - Ampoliros database handler.
     @param elementName string - Element name.
     */
    public function HuiElementHandler(DBLayer $rampDb, $elementName = '') {
        $this -> mrAmpDb = $rampDb;
        $this -> mElementName = $elementName;
    }

    /*!
     @function Install
     @abstract Installs a new Hui element.
     @discussion Installs a new Hui element.
     @param args array - Element arguments in the structure.
     @result True if the element has been installed.
     */
    public function install($args) {
        if (strlen($args['name']) and strlen($args['file'])) {
            return $this -> mrAmpDb -> Execute('INSERT INTO huielements '.'VALUES ('.$this -> mrAmpDb -> NextSeqValue('huielements_id_seq').','.$this -> mrAmpDb -> Format_Text($args['name']).','.$this -> mrAmpDb -> Format_Text($args['file']).')');
        }
        return false;
    }

    /*!
     @function Update
     @abstract Updates a Hui element.
     @discussion Updates a Hui element.
     @param args array - Element arguments in the structure.
     @result True if the element has been updated.
     */
    public function update($args) {
        if (strlen($this -> mElementName)) {
            $check_query = $this -> mrAmpDb -> Execute('SELECT name '.'FROM huielements '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mElementName));

            if ($check_query -> NumRows()) {
                return $this -> mrAmpDb -> Execute('UPDATE huielements '.'SET file='.$this -> mrAmpDb -> Format_Text($args['file']).' '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mElementName));
            } else
                return $this -> Install($args);
        }
        return false;
    }

    /*!
    @function Remove
    @abstract Removes a Hui element.
    @discussion Removes a Hui element.
    @result True if the element has been removed.
    */
    public function remove() {
        if (strlen($this -> mElementName)) {
            return $this -> mrAmpDb -> Execute('DELETE FROM huielements '.'WHERE name='.$this -> mrAmpDb -> Format_Text($this -> mElementName));
        }
        return false;
    }
}

?>