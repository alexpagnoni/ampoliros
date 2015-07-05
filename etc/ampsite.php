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
// $Id: ampsite.php,v 1.20 2004-07-08 15:04:25 alex Exp $

// Deprecated

if (!defined('AMPSITE_PHP')) {
	define ('AMPSITE_PHP', true);
	require ('ampoliros.php');

	function init_amp_site($siteId, $userId = '') {
        $amp = Ampoliros::instance('Ampoliros');
        return $amp->startSite($siteId, $userId);
	}

	function init_amp_site_by_md5id($md5Id, $userId = '') {
        $amp = Ampoliros::instance('Ampoliros');
        return $amp->startSiteByMd5Id($md5Id, $userId);
	}
}
?>
