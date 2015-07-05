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
// $Id: locale.php,v 1.19 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
Carthag :: import('com.solarix.ampoliros.locale.LocaleCountry');
OpenLibrary('sites.library');
OpenLibrary('misc.library');
OpenLibrary('hui.library');

$amp_locale = new Locale('amp_root_locale', $gEnv['user']['locale']['language']);
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('locale_pagetitle')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('locale_title'), 'icon' => 'package_network'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$country_action = new HuiEventsCall();
$country_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_countrybutton = new HuiButton('countrybutton', array('label' => $amp_locale -> GetStr('country_button'), 'themeimage' => 'configure', 'horiz' => 'true', 'action' => $country_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_countrybutton);

$language_action = new HuiEventsCall();
$language_action -> AddEvent(new HuiEvent('main', 'language', ''));
$hui_languagebutton = new HuiButton('languagebutton', array('label' => $amp_locale -> GetStr('language_button'), 'themeimage' => 'configure', 'horiz' => 'true', 'action' => $language_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_languagebutton);

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
$pass_disp -> Dispatch();

$pass_disp -> AddEvent('setlanguage', 'pass_setlanguage');
function pass_setlanguage($eventData) {
    global $hui_mainstatus, $amp_locale, $env, $hui_page;

    $site_sets = new SiteSettings($env['db']);

    $site_sets -> EditKey($env['currentuser'].'-language', $eventData['language']);
    if ($env['currentuser'] == $env['currentsite'])
        $site_sets -> EditKey('sitelanguage', $eventData['language']);

    $hui_page -> mArgs['javascript'] = 'parent.frames.sum.location.reload()';

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('languageset_status');
}

$pass_disp -> AddEvent('setcountry', 'pass_setcountry');
function pass_setcountry($eventData) {
    global $hui_mainstatus, $amp_locale, $env, $hui_page;

    $site_sets = new SiteSettings($env['db']);

    $site_sets -> EditKey($env['currentuser'].'-country', $eventData['country']);
    if ($env['currentuser'] == $env['currentsite'])
        $site_sets -> EditKey('sitecountry', $eventData['country']);

    $hui_page -> mArgs['javascript'] = 'parent.frames.sum.location.reload()';

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('countryset_status');
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $hui_mainframe, $hui_titlebar, $amp_locale, $pass_disp, $hui_mainstatus;

    $country_locale = new Locale('amp_misc_locale', $env[$env['currentuser'].'-country']);

    $selected_country = $pass_disp -> GetEventData();
    $selected_country = $selected_country['country'];

    $hui_vgroup = new HuiVertGroup('vgroup');

    $country_query = & $env['ampdb'] -> Execute('SELECT * '.'FROM countries');

    while (!$country_query -> eof) {
        $countries[$country_query -> Fields('countryname')] = $country_locale -> GetStr($country_query -> Fields('countryname'));
        $country_query -> MoveNext();
    }

    $hui_locale_grid = new HuiGrid('localegrid', array('rows' => '1', 'cols' => '3'));

    $hui_locale_grid -> AddChild(new HuiLabel('countrylabel', array('label' => $amp_locale -> GetStr('country_label'))), 0, 0);
    $hui_locale_grid -> AddChild(new HuiComboBox('country', array('disp' => 'pass', 'elements' => $countries, 'default' => ($selected_country ? $selected_country : $env[$env['currentuser'].'-country']))), 0, 1);
    $hui_locale_grid -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('country_submit'))), 0, 2);

    $hui_vgroup -> AddChild($hui_locale_grid);

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'setcountry', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_form = new HuiForm('countryform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('country_title');
}

$main_disp -> AddEvent('language', 'main_language');
function main_language($eventData) {
    global $env, $hui_mainframe, $hui_titlebar, $amp_locale, $pass_disp, $hui_mainstatus;

    $loc_country = new LocaleCountry($env[$env['currentuser'].'-country']);
    $country_language = $loc_country -> Language();

    $language_locale = new Locale('amp_misc_locale', $env[$env['currentuser'].'-language']);

    $selected_language = $pass_disp -> GetEventData();
    $selected_language = $selected_language['language'];

    $hui_vgroup = new HuiVertGroup('vgroup');

    $language_query = & $env['ampdb'] -> Execute('SELECT * '.'FROM languages');

    while (!$language_query -> eof) {
        $languages[$language_query -> Fields('langshort')] = $language_locale -> GetStr($language_query -> Fields('langname'));
        $language_query -> MoveNext();
    }

    $hui_locale_grid = new HuiGrid('localegrid', array('rows' => '1', 'cols' => '3'));

    $hui_locale_grid -> AddChild(new HuiLabel('languagelabel', array('label' => $amp_locale -> GetStr('language_label'))), 0, 0);
    $hui_locale_grid -> AddChild(new HuiComboBox('language', array('disp' => 'pass', 'elements' => $languages, 'default' => ($selected_language ? $selected_language : $env[$env['currentuser'].'-language']))), 0, 1);
    $hui_locale_grid -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('language_submit'))), 0, 2);

    $hui_vgroup -> AddChild($hui_locale_grid);
    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('deflanglabel', array('label' => sprintf($amp_locale -> GetStr('countrylanguage_label'), $languages[$country_language]))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'setlanguage', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'language', ''));

    $hui_form = new HuiForm('languageform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('language_title');
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('locale_help', array('node' => 'ampoliros.site.locale.'.$eventData['node'], 'language' => $env['sitelocale'])));
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
