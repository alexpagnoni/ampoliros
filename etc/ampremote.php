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
// $Id: ampremote.php,v 1.20 2004-07-08 15:04:25 alex Exp $

if (!defined('AMPREMOTE_PHP')) {
    define ('AMPREMOTE_PHP', true);
    require ('ampoliros.php');
    $amp = Ampoliros :: instance('Ampoliros');
    $amp -> setMode(Ampoliros :: MODE_ROOT);
    $amp -> setInterface(Ampoliros :: INTERFACE_REMOTE);

    OpenLibrary('xmlrpc.library');
    OpenLibrary('misc.library');

    $env['db'] = $GLOBALS['gEnv']['root']['db'];
    $GLOBALS['gEnv']['remote']['methods'] = array();

    $xuser = new XmlRpcUser($GLOBALS['gEnv']['root']['db']);
    if ($xuser -> SetByAccount($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
        $GLOBALS['gEnv']['remote']['user'] = $_SERVER['PHP_AUTH_USER'];
        $GLOBALS['gEnv']['remote']['profile'] = $xuser -> mProfileId;

        if ($xuser -> mSiteId) {
            $site_query = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid FROM sites WHERE id='.$xuser -> mSiteId);
            if ($site_query -> NumRows()) {
                $amp = Ampoliros :: instance('Ampoliros');
                $amp -> startSite($site_query -> Fields('siteid'));
            }
        }

        $xprofile = new XmlRpcProfile($GLOBALS['gEnv']['root']['db'], $GLOBALS['gEnv']['remote']['profile']);
        $GLOBALS['gEnv']['remote']['methods'] = $xprofile -> AvailableMethods();
    } else {
        if ($GLOBALS['gEnv']['core']['config'] -> Value('ALERT_ON_WRONG_REMOTE_LOGIN') == '1') {
            import('com.solarix.ampoliros.security.SecurityLayer');
            $amp_security = new SecurityLayer();
            $amp_security -> SendAlert('Wrong remote login for user '.$_SERVER['PHP_AUTH_USER'].' from remote address '.$_SERVER['REMOTE_ADDR']);
            unset($amp_security);
        }
    }
}
?>
