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
// $Id: HuiAuthHelperFactory.php,v 1.5 2004-07-14 15:16:44 alex Exp $

package('com.solarix.ampoliros.hui.helpers');

import('com.solarix.ampoliros.core.Ampoliros');

class HuiAuthHelperFactory extends Object {
    public static function getAuthHelper() {
        $amp = Ampoliros :: instance('Ampoliros');
        switch ($amp -> getMode()) {
            case Ampoliros :: MODE_ROOT :
                import('com.solarix.ampoliros.hui.helpers.HuiRootAuthHelper');
                return new HuiRootAuthHelper();
                break;
            case Ampoliros :: MODE_SITE :
                import('com.solarix.ampoliros.hui.helpers.HuiSiteAuthHelper');
                return new HuiSiteAuthHelper();
                break;
            default:
            	return null;
        }
    }
}

?>