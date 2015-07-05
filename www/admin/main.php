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
// $Id: main.php,v 1.15 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.module.ModuleConfig');
import('com.solarix.ampoliros.site.Site');

$hui = new Hui($env['ampdb']);
$hui -> LoadWidget('button');
$hui -> LoadWidget('empty');
$hui -> LoadWidget('grid');
$hui -> LoadWidget('horizframe');
$hui -> LoadWidget('horizgroup');
$hui -> LoadWidget('image');
$hui -> LoadWidget('label');
$hui -> LoadWidget('link');
$hui -> LoadWidget('page');
$hui -> LoadWidget('vertframe');
$hui -> LoadWidget('vertgroup');

$mod_cfg = new ModuleConfig($env['ampdb'], 'ampoliros');

$hui_page = new HuiPage('page', array('title' => 'Ampoliros', 'border' => 'false'));
$hui_vertgroup = new HuiVertGroup('vertgroup', array('align' => 'center', 'groupalign' => 'center', 'groupvalign' => 'middle', 'height' => '100%'));
$hui_buttons_group = new HuiVertGroup('buttons_group', array('align' => 'center', 'groupalign' => 'center', 'groupvalign' => 'middle', 'height' => '0%'));
if ($mod_cfg -> GetKey('ampoliros-biglogo-disabled') != '1') {
    if ($gEnv['core']['edition'] == AMP_EDITION_ASP)
        $edition = '_asp';
    else
        $edition = '_enterprise';

    if (isset($hui_page -> mThemeHandler -> mStyle['bigdot'.$edition]))
        $bigdot_image = $hui_page -> mThemeHandler -> mStyle['bigdot'.$edition];
    else
        $bigdot_image = $hui_page -> mThemeHandler -> mStyle['bigdot'];

    $hui_button = new HuiButton('button', array('action' => 'http://www.ampoliros.com', 'target' => '_top', 'image' => $bigdot_image, 'highlight' => 'false'));
    $hui_buttons_group -> AddChild($hui_button);
}

// OEM personalization
//
$oem_biglogo_filename = $mod_cfg -> GetKey('oem-biglogo-filename');
$oem_url = $mod_cfg -> GetKey('oem-url');

if ($mod_cfg -> GetKey('oem-biglogo-disabled') != '1') {
    if (strlen($oem_biglogo_filename) and file_exists(CGI_PATH.$oem_biglogo_filename)) {
        $oem_button = new HuiButton('oembutton', array('action' => strlen($oem_url) ? $oem_url : 'http://www.ampoliros.com', 'target' => '_top', 'image' => CGI_URL.$oem_biglogo_filename, 'highlight' => 'false'));
        $hui_buttons_group -> AddChild($oem_button);
    }
}

// MOTD
//
$site = new Site($gEnv['root']['db'], $gEnv['site']['id'], $gEnv['site']['db']);
$motd = $site -> GetMotd();

$hui_buttons_group -> AddChild(new HuiLabel('motd', array('nowrap' => 'false', 'bold' => 'true', 'label' => $motd)));

$hui_vertgroup -> AddChild($hui_buttons_group);

$hui_page -> AddChild($hui_vertgroup);
$hui -> AddChild($hui_page);
$hui -> Render();
?>
