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
// $Id: ampinterface.php,v 1.20 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.io.log.Logger');
import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.site.SiteSettings');

OpenLibrary('misc.library');
OpenLibrary('hui.library');
OpenLibrary('modules.library');

$amp_locale = new Locale('amp_site_interface', $gEnv['user']['locale']['language']);
$amp_log = new Logger(AMP_LOG);
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
$hui -> LoadWidget('xml');

$hui_comments = '';
$compressed_ob = '';

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('interface_pagetitle')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('interface_title'), 'icon' => 'kcontrol'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$default_action = new HuiEventsCall();
$default_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_defaultbutton = new HuiButton('defaultbutton', array('label' => $amp_locale -> GetStr('default_button'), 'themeimage' => 'thumbnail', 'horiz' => 'true', 'action' => $default_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_defaultbutton);

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

$hui_mainframe = new HuiVertFrame('mainframe');
$hui_mainstatus = new HuiStatusBar('mainstatusbar');

// Pass dispatcher
//
$pass_disp = new HuiDispatcher('pass');

$pass_disp -> AddEvent('settheme', 'pass_settheme');
function pass_settheme($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $env;

    $log = new Logger(AMP_LOG);

    if ($env['currentuser'] == $env['currentsite'])
        $user = $key_name = 'sitetheme';
    else
        $key_name = $env['currentuser'].'-theme';

    $site_cfg = new SiteSettings($env['db']);
    $site_cfg -> EditKey($key_name, $eventData['theme']);

    $env['hui']['theme']['name'] = $eventData['theme'];
    $env['hui']['theme']['handler'] = new HuiTheme($env['ampdb'], $eventData['theme']);

    header('Location: '.build_events_call_string('', array(array('main', 'default', ''), array('pass', 'settheme2', ''))));
}

$pass_disp -> AddEvent('settheme2', 'pass_settheme2');
function pass_settheme2($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $env;

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('themeset_status');
    $hui_page -> mArgs['avascript'] = "parent.frames.sum.location.reload();\nparent.frames.header.location.reload()";
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $gEnv, $hui_mainframe, $hui_titlebar, $amp_locale;

    //$mod_cfg = new ModuleConfig( $env['ampdb'], 'ampoliros' );

    $themes_query = & $env['ampdb'] -> Execute('SELECT name,catalog '.'FROM huithemes ');

    while (!$themes_query -> eof) {
        $tmp_locale = new Locale($themes_query -> Fields('catalog'), $gEnv['site']['locale']['language']);
        $elements[$themes_query -> Fields('name')] = $tmp_locale -> GetStr($themes_query -> Fields('name'));

        $themes_query -> MoveNext();
    }

    asort($elements);

    $xml_def = '<vertgroup><name>vgroup</name><args><halign>center</halign></args><children>
      <form><name>theme</name><args><action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default', ''), array('pass', 'settheme', '')))).'</action></args><children>
        <grid><name>themegrid</name><children>
          <label row="0" col="0"><name>themelabel</name><args><label type="encoded">'.urlencode($amp_locale -> GetStr('themes_label')).'</label></args></label>
          <listbox row="1" col="0"><name>theme</name><args><elements type="array">'.huixml_encode($elements).'</elements><default>'. ($env['hui']['theme']['name'] ? $env['hui']['theme']['name'] : $env['hui']['theme']['default']).'</default><disp>pass</disp><size>10</size></args></listbox>
        </children></grid>
        <submit><name>submit</name><args><caption type="encoded">'.urlencode($amp_locale -> GetStr('settheme_submit')).'</caption></args></submit>
      </children></form>
    </children></vertgroup>';

    $hui_mainframe -> AddChild(new HuiXml('page', array('definition' => $xml_def)));

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('themes_title');
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('locale_help', array('node' => 'ampoliros.site.interface.'.$eventData['node'], 'language' => $gEnv['site']['locale']['language'])));
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
