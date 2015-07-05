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
// $Id: auth.php,v 1.11 2004-07-08 15:04:26 alex Exp $

if (!defined('BASE_AUTH_PHP')) {
    define ('BASE_AUTH_PHP', true);

    require ('ampoliros.php');
    $amp = Ampoliros :: instance('Ampoliros');
    $amp -> setInterface(Ampoliros :: INTERFACE_WEB);
    $amp -> startRoot();

    if (!defined('AMPOLIROS_SETUP_PHASE')) {
        import('com.solarix.ampoliros.module.ModuleConfig');
        $mod_cfg = new ModuleConfig($gEnv['root']['db'], 'ampoliros');
        if (strlen($mod_cfg -> GetKey('hui-root-theme'))) {
            $gEnv['hui']['theme']['name'] = $mod_cfg -> GetKey('hui-root-theme');
        }
        unset($mod_cfg);
    }

    import('com.solarix.ampoliros.hui.theme.HuiTheme');
}
?>
