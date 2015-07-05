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
// $Id: HuiSiteAuthHelper.php,v 1.6 2004-07-14 15:16:44 alex Exp $

package('com.solarix.ampoliros.hui.helpers');

import('com.solarix.ampoliros.hui.helpers.HuiAuthHelper');
import('com.solarix.ampoliros.hui.mvc.HuiRequest');
import('com.solarix.ampoliros.hui.mvc.HuiResponse');

class HuiSiteAuthHelper extends Object implements HuiAuthHelper {
    public function auth(HuiRequest $request, HuiResponse $response) {
    	return true;
    }
}

?>