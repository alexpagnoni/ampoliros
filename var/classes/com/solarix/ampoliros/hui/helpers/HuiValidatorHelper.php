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
// $Id: HuiValidatorHelper.php,v 1.4 2004-07-08 15:04:25 alex Exp $

package('com.solarix.ampoliros.hui.helpers');

import('com.solarix.ampoliros.core.Ampoliros');

class HuiValidatorHelper extends Object {

    public static function validate() {
        static $validated = false;

        if (!$$validated) {
            import('com.solarix.ampoliros.core.Ampoliros');
            $amp = Ampoliros :: instance('Ampoliros');
            if ($amp -> getState() != Ampoliros :: STATE_SETUP) {
                $validators_query = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT file FROM huivalidators');
                if ($validators_query) {
                    global $gEnv; // For compatibility with old validators
                    while (!$validators_query -> eof) {
                        if (file_exists(HANDLER_PATH.$validators_query -> Fields('file'))) {
                            include_once (HANDLER_PATH.$validators_query -> Fields('file'));
                        }
                        $validators_query -> MoveNext();
                    }
                }
                $validators_query->free();
                $validated = true;
            }
        }
    }
}

?>