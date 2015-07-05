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
// $Id: password.php,v 1.19 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

OpenLibrary('hui.library');
import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.io.log.Logger');
OpenLibrary('users.library');

$log = new Logger(AMP_LOG);
$amp_locale = new Locale('amp_site_password', $gEnv['user']['locale']['language']);

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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('password_title')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('password_title'), 'icon' => 'password'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$home_action = new HuiEventsCall();
$home_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_homebutton = new HuiButton('homebutton', array('label' => $amp_locale -> GetStr('chpasswd_button'), 'themeimage' => 'edit', 'horiz' => 'true', 'action' => $home_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_homebutton);

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
    global $env, $hui_mainstatus, $amp_locale;

    if ($eventData['newpassworda'] == $eventData['newpasswordb']) {
        if (strlen($eventData['newpassworda'])) {
            $temp_user = new User($env['ampdb'], $env['currentsiteserial']);
            $temp_user -> SetUserIdByUsername($env['currentuser']);

            $user_data = $temp_user -> GetUserData();

            if (md5($eventData['oldpassword']) == $user_data['password']) {
                $temp_user -> ChPasswd($eventData['newpassworda']);
                $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('passwordchanged_status');
            } else
                $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('wrongoldpassword_status');
        } else
            $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('newpasswordisempty_status');
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('newpasswordnomatch_status');
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $hui_mainframe, $amp_locale, $hui_titlebar;

    $hui_grid = new HuiGrid('grid', array('rows' => '3', 'cols' => '2'));

    $hui_grid -> AddChild(new HuiLabel('oldpasswordlabel', array('label' => $amp_locale -> GetStr('amppasswordold_label'))), 0, 0);
    $hui_grid -> AddChild(new HuiString('oldpassword', array('disp' => 'pass', 'password' => 'true')), 0, 1);

    $hui_grid -> AddChild(new HuiLabel('newpasswordalabel', array('label' => $amp_locale -> GetStr('amppassworda_label'))), 1, 0);
    $hui_grid -> AddChild(new HuiString('newpassworda', array('disp' => 'pass', 'password' => 'true')), 1, 1);

    $hui_grid -> AddChild(new HuiLabel('newpasswordblabel', array('label' => $amp_locale -> GetStr('amppasswordb_label'))), 2, 0);
    $hui_grid -> AddChild(new HuiString('newpasswordb', array('disp' => 'pass', 'password' => 'true')), 2, 1);

    $hui_vgroup = new HuiVertGroup('vertgroup', array('align' => 'center'));
    $hui_vgroup -> AddChild($hui_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit', array('caption' => $amp_locale -> GetStr('amppasschange_submit'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));
    $form_events_call -> AddEvent(new HuiEvent('pass', 'edit', ''));

    $hui_form = new HuiForm('form', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('password_help', array('node' => 'ampoliros.site.password.'.$eventData['node'], 'language' => $env['sitelocale'])));
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
