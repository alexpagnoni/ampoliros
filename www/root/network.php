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
// $Id: network.php,v 1.20 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
Carthag :: import('com.solarix.ampoliros.hui.Hui');
OpenLibrary('misc.library');
OpenLibrary('ampshared.library');

$log = new logger(AMP_LOG);
$amp_locale = new locale("amp_root_network", $gEnv['root']['locale']['language']);

// Initialization
//
$hui = new Hui($env['ampdb']);
$hui -> LoadWidget('button');
$hui -> LoadWidget('checkbox');
$hui -> LoadWidget('combobox');
$hui -> LoadWidget('date');
$hui -> LoadWidget('empty');
$hui -> LoadWidget('file');
$hui -> LoadWidget('formarg');
$hui -> LoadWidget('form');
$hui -> LoadWidget('grid');
$hui -> LoadWidget('helpnode');
$hui -> LoadWidget('horizbar');
$hui -> LoadWidget('horizframe');
$hui -> LoadWidget('horizgroup');
$hui -> LoadWidget('image');
$hui -> LoadWidget('label');
$hui -> LoadWidget('link');
$hui -> LoadWidget('listbox');
$hui -> LoadWidget('menu');
$hui -> LoadWidget('page');
$hui -> LoadWidget('progressbar');
$hui -> LoadWidget('radio');
$hui -> LoadWidget('sessionkey');
$hui -> LoadWidget('statusbar');
$hui -> LoadWidget('string');
$hui -> LoadWidget('submit');
$hui -> LoadWidget('tab');
$hui -> LoadWidget('table');
$hui -> LoadWidget('text');
$hui -> LoadWidget('titlebar');
$hui -> LoadWidget('toolbar');
$hui -> LoadWidget('treemenu');
$hui -> LoadWidget('vertframe');
$hui -> LoadWidget('vertgroup');

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('network')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('network'), 'icon' => 'network'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

$menu_frame = new HuiHorizGroup('menuframe');
$menu_frame -> AddChild(new HuiMenu('magellanmainmenu', array('menu' => get_ampoliros_root_menu_def($env['sitelocale']))));
$hui_mainvertgroup -> AddChild($menu_frame);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$home_action = new HuiEventsCall();
$home_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_homebutton = new HuiButton('homebutton', array('label' => 'Home', 'themeimage' => 'gohome', 'horiz' => 'true', 'action' => $home_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_homebutton);

$edit_action = new HuiEventsCall();
$edit_action -> AddEvent(new HuiEvent('main', 'edit', ''));
$hui_editbutton = new HuiButton('editbutton', array('label' => 'Edit', 'themeimage' => 'edit', 'horiz' => 'true', 'action' => $edit_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_editbutton);

// Help tool bar
//
$hui_helptoolbar = new HuiToolBar('helpbar');

$main_disp = new HuiDispatcher('main');
$event_name = $main_disp -> GetEventName();

if (strcmp($event_name, 'help')) {
    $help_action = new HuiEventsCall();
    $help_action -> AddEvent(new HuiEvent('main', 'help', array('node' => $event_name)));
    $hui_helpbutton = new HuiButton('helpbutton', array('label' => $amp_locale -> GetStr('help_button'), 'themeimage' => 'help', 'horiz' => 'true', 'action' => $help_action -> GetEventsCallString()));

    $hui_helptoolbar -> AddChild($hui_helpbutton);
}

// Toolbar frame
//
$hui_toolbarframe = new HuiHorizGroup('toolbarframe');

$hui_toolbarframe -> AddChild($hui_maintoolbar);
$hui_toolbarframe -> AddChild($hui_helptoolbar);
$hui_mainvertgroup -> AddChild($hui_toolbarframe);

$hui_mainframe = new HuiHorizFrame('mainframe');
$hui_mainstatus = new HuiStatusBar('mainstatusbar');

// Pass dispatcher
//
$pass_disp = new HuiDispatcher('pass');

$pass_disp -> AddEvent('edit', 'pass_edit');
function pass_edit($eventData) {
    global $hui_page;

    $log = new Logger(AMP_LOG);

    $ampcfg = new ConfigFile(AMP_CONFIG);
    $ampcfg -> SetValue('AMP_NAME', $eventData['name']);
    $ampcfg -> SetValue('AMP_DOMAIN', $eventData['domain']);
    $ampcfg -> SetValue('AMP_DNS', $eventData['dns']);

    $log -> LogEvent('Ampoliros', 'Changed Ampoliros network settings', LOGGER_NOTICE);

    $hui_page -> mJavascript = 'parent.frames.header.location.reload()';
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $hui_mainframe, $amp_locale, $pass_disp, $hui_mainstatus;

    if ($pass_disp -> GetEventName() == 'edit') {
        $pd_data = $pass_disp -> GetEventData();

        $amp_name = $pd_data['name'];
        $amp_domain = $pd_data['domain'];
        $amp_dns = $pd_data['dns'];

        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('datachanged');
    } else {
        $amp_name = AMP_NAME;
        $amp_domain = AMP_DOMAIN;
        $amp_dns = AMP_DNS;
    }

    $hui_grid = new HuiGrid('grid', array('rows' => '3', 'cols' => '2'));

    $hui_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('namedesc'))), 0, 0);
    $hui_grid -> AddChild(new HuiString('name', array('value' => $amp_name, 'readonly' => 'true')), 0, 1);

    $hui_grid -> AddChild(new HuiLabel('domainlabel', array('label' => $amp_locale -> GetStr('domaindesc'))), 1, 0);
    $hui_grid -> AddChild(new HuiString('domain', array('value' => $amp_domain, 'readonly' => 'true')), 1, 1);

    $hui_grid -> AddChild(new HuiLabel('dnslabel', array('label' => $amp_locale -> GetStr('dnsdesc'))), 2, 0);
    $hui_grid -> AddChild(new HuiString('dns', array('value' => $amp_dns, 'readonly' => 'true')), 2, 1);

    $hui_mainframe -> AddChild($hui_grid);
    //$hui_mainframe->AddChild( new HuiLabel( 'mainlabel', array( 'label' => 'Main page' ) ) );
}

$main_disp -> AddEvent('edit', 'main_edit');
function main_edit($eventData) {
    global $hui_mainframe, $amp_locale, $hui_titlebar;

    $hui_grid = new HuiGrid('grid', array('rows' => '3', 'cols' => '2'));

    $hui_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('namedesc'))), 0, 0);
    $hui_grid -> AddChild(new HuiString('name', array('value' => AMP_NAME, 'disp' => 'pass')), 0, 1);

    $hui_grid -> AddChild(new HuiLabel('domainlabel', array('label' => $amp_locale -> GetStr('domaindesc'))), 1, 0);
    $hui_grid -> AddChild(new HuiString('domain', array('value' => AMP_DOMAIN, 'disp' => 'pass')), 1, 1);

    $hui_grid -> AddChild(new HuiLabel('dnslabel', array('label' => $amp_locale -> GetStr('dnsdesc'))), 2, 0);
    $hui_grid -> AddChild(new HuiString('dns', array('value' => AMP_DNS, 'disp' => 'pass')), 2, 1);

    $hui_vgroup = new HuiVertGroup('vertgroup', array('align' => 'center'));
    $hui_vgroup -> AddChild($hui_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit', array('caption' => $amp_locale -> GetStr('submit'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));
    $form_events_call -> AddEvent(new HuiEvent('pass', 'edit', ''));

    $hui_form = new HuiForm('form', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);
    $hui_titlebar -> mTitle.= ' - Edit';
    //$hui_mainframe->AddChild( new HuiLabel( 'mainlabel', array( 'label' => 'Main page' ) ) );
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('modules_help', array('node' => 'ampoliros.root.network.'.$eventData['node'], 'language' => AMP_LANG)));
}

$main_disp -> Dispatch();

// Page render
//
$hui_mainvertgroup -> AddChild($hui_mainframe);
$hui_mainvertgroup -> AddChild($hui_mainstatus);
$hui_page -> AddChild($hui_mainvertgroup);
$hui -> AddChild($hui_page);
$hui -> Render();

?>
