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
// $Id: header.php,v 1.13 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.hui.Hui');

$hui = new Hui($env['ampdb']);
$hui -> LoadWidget('button');
$hui -> LoadWidget('image');
$hui -> LoadWidget('label');
$hui -> LoadWidget('link');
$hui -> LoadWidget('page');
$hui -> LoadWidget('vertgroup');

$hui_page = new HuiPage('page', array('title' => 'Ampoliros - '.$env['sitedata']['sitename'], 'border' => 'false'));
$hui_page -> mArgs['background'] = $hui_page -> mThemeHandler -> mStyle['menuback'];

$hui_mainvertgroup = new HuiVertGroup('mainvertgroup', array('align' => 'center'));

$hui_mainvertgroup -> AddChild(new HuiButton('amplogo', array('action' => AMP_URL, 'target' => '_top', 'image' => $hui_page -> mThemeHandler -> mStyle['middot'], 'highlight' => 'false')));
$hui_mainvertgroup -> AddChild(new HuiLabel('label', array('label' => $env['sitedata']['sitename'], 'nowrap' => 'true', 'align' => 'center')));
if (strlen($env['currentusercname']))
    $hui_mainvertgroup -> AddChild(new HuiLabel('labelname', array('label' => $env['currentusercname'])));

$hui_page -> AddChild($hui_mainvertgroup);
$hui -> AddChild($hui_page);
$hui -> Render();
?>
