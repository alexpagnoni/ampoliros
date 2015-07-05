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
// $Id: sites.php,v 1.39 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
Carthag :: import('com.solarix.ampoliros.hui.Hui');
Carthag :: import('com.solarix.ampoliros.site.Site');
OpenLibrary('configman.library');
OpenLibrary('modules.library');
OpenLibrary('users.library');
OpenLibrary('ampshared.library');

$log = new Logger(AMP_LOG);
$amp_locale = new Locale('amp_root_sites', $gEnv['root']['locale']['language']);
$hui = new Hui($env['ampdb']);
$hui -> LoadWidget('amptoolbar');
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('sites_title')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('sites_title'), 'icon' => 'package'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

$menu_frame = new HuiHorizGroup('menuframe');
$menu_frame -> AddChild(new HuiMenu('magellanmainmenu', array('menu' => get_ampoliros_root_menu_def($env['sitelocale']))));
$hui_mainvertgroup -> AddChild($menu_frame);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$home_action = new HuiEventsCall();
$home_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_homebutton = new HuiButton('homebutton', array('label' => $amp_locale -> GetStr('sites_button'), 'themeimage' => 'view_detailed', 'horiz' => 'true', 'action' => $home_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_homebutton);

if ($gEnv['core']['edition'] == AMP_EDITION_ENTERPRISE) {
    $site_query = & $gEnv['root']['db'] -> Execute('SELECT count(*) AS sites '.'FROM sites');
}

if ($gEnv['core']['edition'] == AMP_EDITION_ASP or !$site_query -> Fields('sites') > 0) {
    $new_action = new HuiEventsCall();
    $new_action -> AddEvent(new HuiEvent('main', 'newsite', ''));
    $hui_newbutton = new HuiButton('newbutton', array('label' => $amp_locale -> GetStr('newsite_button'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => $new_action -> GetEventsCallString()));
    $hui_maintoolbar -> AddChild($hui_newbutton);
}

// Situation tool bar
//
$hui_sittoolbar = new HuiToolBar('situation');

$hui_sitbutton = new HuiButton('sitbutton', array('label' => $amp_locale -> GetStr('situation.button'), 'themeimage' => 'view_detailed', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'situation')))));

$hui_sittoolbar -> AddChild($hui_sitbutton);

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
$hui_toolbarframe -> AddChild($hui_sittoolbar);
$hui_toolbarframe -> AddChild($hui_helptoolbar);
$hui_mainvertgroup -> AddChild($hui_toolbarframe);

$hui_mainframe = new HuiHorizFrame('mainframe');
$hui_mainstatus = new HuiStatusBar('mainstatusbar');

// Pass dispatcher
//
$pass_disp = new HuiDispatcher('pass');

$pass_disp -> AddEvent('createsite', 'pass_createsite');
function pass_createsite($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site = new Site($env['db'], 0, $null);

    $sitedata['siteid'] = $eventData['siteid'];
    $sitedata['sitepath'] = $eventData['sitepath'];
    $sitedata['sitename'] = $eventData['sitename'];
    $sitedata['sitepassword'] = $eventData['sitepassword'];
    $sitedata['siteurl'] = $eventData['siteurl'];
    $sitedata['sitedbname'] = $eventData['sitedbname'];
    $sitedata['sitedbhost'] = $eventData['sitedbhost'];
    $sitedata['sitedbport'] = $eventData['sitedbport'];
    $sitedata['sitedbuser'] = $eventData['sitedbuser'];
    $sitedata['sitedbpassword'] = $eventData['sitedbpassword'];
    $sitedata['sitedblog'] = $eventData['sitedblog'];
    $sitedata['sitedbtype'] = $eventData['sitedbtype'];

    if ($site -> Create($sitedata, $eventData['createsitedb'] == 'on' ? true : false))
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitecreated_status');
    else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitenotcreated_status');
}

$pass_disp -> AddEvent('updatesite', 'pass_updatesite');
function pass_updatesite($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id='.$eventData['siteserial']);

    $site = new Site($env['db'], $site_query -> Fields('siteid'), $null);

    $sitedata['siteserial'] = $eventData['siteserial'];
    $sitedata['sitepath'] = $eventData['sitepath'];
    $sitedata['sitename'] = $eventData['sitename'];
    $sitedata['sitepassword'] = $eventData['sitepassword'];
    $sitedata['siteurl'] = $eventData['siteurl'];
    $sitedata['sitedbtype'] = $eventData['sitedbtype'];
    $sitedata['sitedbname'] = $eventData['sitedbname'];
    $sitedata['sitedbhost'] = $eventData['sitedbhost'];
    $sitedata['sitedbport'] = $eventData['sitedbport'];
    $sitedata['sitedbuser'] = $eventData['sitedbuser'];
    $sitedata['sitedbpassword'] = $eventData['sitedbpassword'];
    $sitedata['sitedbport'] = $eventData['sitedbport'];
    $sitedata['sitedblog'] = $eventData['sitedblog'];
    $sitedata['sitedbtype'] = $eventData['sitedbtype'];

    if ($site -> Edit($sitedata)) {
        $site -> SetMaxUsers($eventData['maxusers']);
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('siteupdated_status');
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitenotupdated_status');
}

$pass_disp -> AddEvent('editsitenotes', 'pass_editsitenotes');
function pass_editsitenotes($eventData) {
    global $gEnv, $hui_mainstatus, $amp_locale;

    $site = new Site($gEnv['root']['db'], $eventData['siteid'], $null);

    $site -> SetNotes($eventData['notes']);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('notes_set.status');
}

$pass_disp -> AddEvent('removesite', 'pass_removesite');
function pass_removesite($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site = new Site($env['ampdb'], $eventData['siteid'], $null);

    if ($site -> Remove())
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('siteremoved_status');
    else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitenotremoved_status');
}

$pass_disp -> AddEvent('enablesite', 'pass_enablesite');
function pass_enablesite($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site = new Site($env['ampdb'], $eventData['siteid'], $null);

    if ($site -> Enable())
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('siteenabled_status');
    else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitenotenabled_status');
}

$pass_disp -> AddEvent('disablesite', 'pass_disablesite');
function pass_disablesite($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site = new Site($env[db], $eventData['siteid'], $null);

    if ($site -> Disable())
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitedisabled_status');
    else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitenotdisabled_status');
}

$pass_disp -> AddEvent('activatemodule', 'pass_activatemodule');
function pass_activatemodule($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id = '.$eventData['siteid']);

    if ($site_query) {
        $site_data = $site_query -> Fields();

        $site = new Site($env['ampdb'], $site_data['siteid'], $null);
        if (!$site -> EnableModule($eventData['modid'])) {
            $unmet_deps = $site -> GetLastActionUnmetDeps();

            if (count($unmet_deps)) {
                while (list (, $dep) = each($unmet_deps))
                    $unmet_deps_str.= ' '.$dep;

                $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('modnotenabled_status').' ';
                $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('unmetdeps_status').$unmet_deps_str.'.';
            }

            $unmet_suggs = $site -> GetLastActionUnmetSuggs();

            if (count($unmet_suggs)) {
                while (list (, $sugg) = each($unmet_suggs))
                    $unmet_suggs_str.= ' '.$sugg.$hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('unmetsuggs_status').$unmet_suggs_str.'.';
            }
        } else
            $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('modenabled_status');
    }
}

$pass_disp -> AddEvent('activateallmodules', 'pass_activateallmodules');
function pass_activateallmodules($eventData) {
    global $hui_mainstatus, $amp_locale;

    $site_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id = '.$eventData['siteid']);

    if ($site_query) {
        $site_data = $site_query -> Fields();

        $site = new Site($GLOBALS['gEnv']['root']['db'], $site_data['siteid'], $null);
        if ($site -> EnableAllModules())
            $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('modules_enabled.status');
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('modules_not_enabled.status');
}

$pass_disp -> AddEvent('deactivateallmodules', 'pass_deactivateallmodules');
function pass_deactivateallmodules($eventData) {
    global $hui_mainstatus, $amp_locale;

    $site_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id = '.$eventData['siteid']);

    if ($site_query) {
        $site_data = $site_query -> Fields();

        $site = new Site($GLOBALS['gEnv']['root']['db'], $site_data['siteid'], $null);
        if ($site -> DisableAllModules(false))
            $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('modules_disabled.status');
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('modules_not_disabled.status');
}

$pass_disp -> AddEvent('enablesubmodule', 'pass_enablesubmodule');
function pass_enablesubmodule($eventData) {
    global $gEnv, $hui_mainstatus, $amp_locale;
    OpenLibrary('modulesbase.library');

    $module = new Module($gEnv['root']['db'], $eventData['moduleid']);

    $module -> EnableSubModule($eventData['submodule'], $eventData['siteid']);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('submodule_enabled.status');
}

$pass_disp -> AddEvent('disablesubmodule', 'pass_disablesubmodule');
function pass_disablesubmodule($eventData) {
    global $gEnv, $hui_mainstatus, $amp_locale;
    OpenLibrary('modulesbase.library');

    $module = new Module($gEnv['root']['db'], $eventData['moduleid']);

    $module -> DisableSubModule($eventData['submodule'], $eventData['siteid']);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('submodule_disabled.status');
}

$pass_disp -> AddEvent('deactivatemodule', 'pass_deactivatemodule');
function pass_deactivatemodule($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id = '.$eventData['siteid']);

    if ($site_query) {
        $site_data = $site_query -> Fields();

        $site = new Site($env['ampdb'], $site_data['siteid'], $null);
        if (!$site -> DisableModule($eventData['modid'])) {
            $unmet_deps = $site -> GetLastActionUnmetDeps();

            if (count($unmet_deps)) {
                while (list (, $dep) = each($unmet_deps))
                    $unmet_deps_str.= ' '.$dep;

                $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('modnotdisabled_status').' ';
                $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('disunmetdeps_status').$unmet_deps_str.'.';
            }
        } else
            $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('moddisabled_status');
    }
}

$pass_disp -> AddEvent('cleansitelog', 'pass_cleansitelog');
function pass_cleansitelog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $temp_log = new Logger(SITESTUFF_PATH.$eventData['siteid'].'/log/site.log');

    if ($temp_log -> CleanLog()) {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('logcleaned_status');
    } else {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('lognotcleaned_status');
    }
}

$pass_disp -> AddEvent('cleansitedblog', 'pass_cleansitedblog');
function pass_cleansitedblog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $query = & $env['ampdb'] -> execute('SELECT sitedblog '.'FROM sites '.'WHERE id='.$eventData['siteid']);

    $temp_log = new Logger($query -> Fields('sitedblog'));

    if ($temp_log -> CleanLog()) {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('logcleaned_status');
    } else {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('lognotcleaned_status');
    }
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

function sites_list_action_builder($pageNumber) {
    return build_events_call_string('', array(array('main', 'default', array('sitespage' => $pageNumber))));
}

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $hui_mainframe, $amp_locale, $pass_disp, $hui_mainstatus;

    $query = & $env['ampdb'] -> execute('SELECT * '.'FROM sites '.'ORDER BY sitename');

    $modules_query = & $env['ampdb'] -> execute('SELECT id '.'FROM modules '.'WHERE onlyextension <> '.$env['ampdb'] -> Format_Text($env['ampdb'] -> fmttrue));

    if ($query -> NumRows() > 0) {
        $headers[0]['label'] = $amp_locale -> GetStr('status_header');
        $headers[1]['label'] = $amp_locale -> GetStr('siteid_header');
        $headers[2]['label'] = $amp_locale -> GetStr('sitename_header');
        $headers[3]['label'] = $amp_locale -> GetStr('sitecreationdate_header');

        $row = 0;

        $hui_sites_table = new HuiTable('sitestable', array('headers' => $headers, 'rowsperpage' => '10', 'pagesactionfunction' => 'sites_list_action_builder', 'pagenumber' => (is_array($eventData) and isset($eventData['sitespage'])) ? $eventData['sitespage'] : ''));

        while (!$query -> eof) {
            $data = $query -> fields();
            if ($data['siteactive'] == $env['db'] -> fmttrue)
                $hui_sites_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['greenball'])), $row, 0);
            else
                $hui_sites_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['redball'])), $row, 0);

            $hui_sites_table -> AddChild(new HuiLabel('siteidlabel'.$row, array('label' => $data['siteid'])), $row, 1);
            $hui_sites_table -> AddChild(new HuiLabel('sitenamelabel'.$row, array('label' => $data['sitename'])), $row, 2);
            $hui_sites_table -> AddChild(new HuiLabel('sitecreationdate'.$row, array('label' => $data['sitecreationdate'])), $row, 3);
            //$hui_sites_table->AddChild( new HuiLabel( 'sitelabel'.$row, array( 'label' => $data['siteid'] ) ), $row, 4 );

            //$hui_buttons = new HuiHorizGroup( 'buttons'.$row );

            $hui_site_toolbar[$row] = new HuiToolBar('sitetoolbar'.$row);

            $show_action[$row] = new HuiEventsCall();
            $show_action[$row] -> AddEvent(new HuiEvent('main', 'showsiteconfig', array('siteid' => $data['id'])));
            $hui_show_button[$row] = new HuiButton('showbutton'.$row, array('label' => $amp_locale -> GetStr('showconfig_label'), 'themeimage' => 'viewmag', 'action' => $show_action[$row] -> GetEventsCallString()));
            $hui_site_toolbar[$row] -> AddChild($hui_show_button[$row]);

            $edit_action[$row] = new HuiEventsCall();
            $edit_action[$row] -> AddEvent(new HuiEvent('main', 'editsiteconfig', array('siteid' => $data['id'])));
            $hui_edit_button[$row] = new HuiButton('editbutton'.$row, array('label' => $amp_locale -> GetStr('editconfig_label'), 'themeimage' => 'edit', 'action' => $edit_action[$row] -> GetEventsCallString()));
            $hui_site_toolbar[$row] -> AddChild($hui_edit_button[$row]);

            $notes_action[$row] = new HuiEventsCall();
            $notes_action[$row] -> AddEvent(new HuiEvent('main', 'editsitenotes', array('siteid' => $data['id'])));
            $hui_notes_button[$row] = new HuiButton('notesbutton'.$row, array('label' => $amp_locale -> GetStr('sitenotes_label'), 'themeimage' => 'attach', 'action' => $notes_action[$row] -> GetEventsCallString()));
            $hui_site_toolbar[$row] -> AddChild($hui_notes_button[$row]);

            $access_action[$row] = new HuiEventsCall();
            $access_action[$row] -> AddEvent(new HuiEvent('main', 'accesssite', array('siteid' => $data['id'])));
            $hui_access_button[$row] = new HuiButton('accessbutton'.$row, array('label' => $amp_locale -> GetStr('accesssite_label'), 'themeimage' => 'gohome', 'action' => $access_action[$row] -> GetEventsCallString(), 'target' => '_top'));
            $hui_site_toolbar[$row] -> AddChild($hui_access_button[$row]);

            if ($data['siteactive'] == $env['ampdb'] -> fmttrue) {
                $disable_action[$row] = new HuiEventsCall();
                $disable_action[$row] -> AddEvent(new HuiEvent('main', 'default', ''));
                $disable_action[$row] -> AddEvent(new HuiEvent('pass', 'disablesite', array('siteid' => $data['siteid'])));
                $hui_disable_button[$row] = new HuiButton('disablebutton'.$row, array('label' => $amp_locale -> GetStr('disablesite_label'), 'themeimage' => 'lock', 'action' => $disable_action[$row] -> GetEventsCallString()));
                $hui_site_toolbar[$row] -> AddChild($hui_disable_button[$row]);
            } else {
                $enable_action[$row] = new HuiEventsCall();
                $enable_action[$row] -> AddEvent(new HuiEvent('main', 'default', ''));
                $enable_action[$row] -> AddEvent(new HuiEvent('pass', 'enablesite', array('siteid' => $data['siteid'])));
                $hui_enable_button[$row] = new HuiButton('enablebutton'.$row, array('label' => $amp_locale -> GetStr('enablesite_label'), 'themeimage' => 'unlock', 'action' => $enable_action[$row] -> GetEventsCallString()));
                $hui_site_toolbar[$row] -> AddChild($hui_enable_button[$row]);
            }

            $remove_action[$row] = new HuiEventsCall();
            $remove_action[$row] -> AddEvent(new HuiEvent('main', 'default', ''));
            $remove_action[$row] -> AddEvent(new HuiEvent('pass', 'removesite', array('siteid' => $data['siteid'])));
            $hui_remove_button[$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removesite_label'), 'themeimage' => 'edittrash', 'action' => $remove_action[$row] -> GetEventsCallString(), 'needconfirm' => 'true', 'confirmmessage' => sprintf($amp_locale -> GetStr('removesitequestion_label'), $data['siteid'].' ('.$data['sitename'].')')));
            $hui_site_toolbar[$row] -> AddChild($hui_remove_button[$row]);

            if ($modules_query -> NumRows()) {
                $modules_action[$row] = new HuiEventsCall();
                $modules_action[$row] -> AddEvent(new HuiEvent('main', 'sitemodules', array('siteid' => $data['id'])));
                $hui_modules_button[$row] = new HuiButton('modulesbutton'.$row, array('label' => $amp_locale -> GetStr('sitemodules_label'), 'themeimage' => 'view_icon', 'action' => $modules_action[$row] -> GetEventsCallString()));
                $hui_site_toolbar[$row] -> AddChild($hui_modules_button[$row]);
            }

            if (file_exists(SITESTUFF_PATH.$data['siteid'].'/log/site.log')) {
                $log_action[$row] = new HuiEventsCall();
                $log_action[$row] -> AddEvent(new HuiEvent('main', 'showsitelog', array('siteid' => $data['id'])));
                $hui_log_button[$row] = new HuiButton('logbutton'.$row, array('label' => $amp_locale -> GetStr('sitelog_label'), 'themeimage' => 'toggle_log', 'action' => $log_action[$row] -> GetEventsCallString()));
                $hui_site_toolbar[$row] -> AddChild($hui_log_button[$row]);
            }

            if (file_exists($data['sitedblog'])) {
                $dblog_action[$row] = new HuiEventsCall();
                $dblog_action[$row] -> AddEvent(new HuiEvent('main', 'showsitedblog', array('siteid' => $data['id'])));
                $hui_dblog_button[$row] = new HuiButton('dblogbutton'.$row, array('label' => $amp_locale -> GetStr('sitedblog_label'), 'themeimage' => 'toggle_log', 'action' => $dblog_action[$row] -> GetEventsCallString()));
                $hui_site_toolbar[$row] -> AddChild($hui_dblog_button[$row]);
            }

            $hui_sites_table -> AddChild($hui_site_toolbar[$row], $row, 4);

            $row ++;
            $query -> MoveNext();
        }

        $hui_mainframe -> AddChild($hui_sites_table);
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('no_available_sites_status');
}

$main_disp -> AddEvent('newsite', 'main_newsite');
function main_newsite($eventData) {
    global $env, $dbtypes, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_site_grid = new HuiGrid('newsitegrid');

    $tab_index = 1;

    // Site fields
    //
    $hui_site_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('sitename_label').' (*)')), 0, 0);
    $hui_site_grid -> AddChild(new HuiString('sitename', array('disp' => 'pass', 'checkmessage' => $amp_locale -> GetStr('sitename_label'), 'required' => 'true', 'tabindex' => $tab_index ++)), 0, 1);

    $hui_site_grid -> AddChild(new HuiLabel('idlabel', array('label' => $amp_locale -> GetStr('siteid_label').' (*)')), 1, 0);
    $hui_site_grid -> AddChild(new HuiString('siteid', array('disp' => 'pass', 'checkmessage' => $amp_locale -> GetStr('siteid_label'), 'required' => 'true', 'tabindex' => $tab_index ++)), 1, 1);

    $hui_site_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('sitepassword_label').' (*)')), 2, 0);
    $hui_site_grid -> AddChild(new HuiString('sitepassword', array('disp' => 'pass', 'checkmessage' => $amp_locale -> GetStr('sitepassword_label'), 'required' => 'true', 'tabindex' => $tab_index ++, 'password' => 'true')), 2, 1);

    $hui_site_grid -> AddChild(new HuiLabel('pathlabel', array('label' => $amp_locale -> GetStr('sitepath_label').' (**)')), 3, 0);
    $hui_site_grid -> AddChild(new HuiString('sitepath', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 3, 1);

    $hui_site_grid -> AddChild(new HuiLabel('urllabel', array('label' => $amp_locale -> GetStr('siteurl_label'))), 4, 0);
    $hui_site_grid -> AddChild(new HuiString('siteurl', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 4, 1);

    $hui_site_grid -> AddChild(new HuiLabel('maxuserslabel', array('label' => $amp_locale -> GetStr('maxusers_label'))), 5, 0);
    $hui_site_grid -> AddChild(new HuiString('maxusers', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 5, 1);

    // Database fields
    //
    $hui_site_grid -> AddChild(new HuiLabel('dbtypelabel', array('label' => $amp_locale -> GetStr('sitedbtype_label'))), 0, 2);
    $hui_site_grid -> AddChild(new HuiComboBox('sitedbtype', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'elements' => $dbtypes, 'default' => AMP_DBTYPE)), 0, 3);

    $hui_site_grid -> AddChild(new HuiLabel('dbnamelabel', array('label' => $amp_locale -> GetStr('sitedbname_label').' (**)')), 1, 2);
    $hui_site_grid -> AddChild(new HuiString('sitedbname', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 1, 3);

    $hui_site_grid -> AddChild(new HuiLabel('dbhostlabel', array('label' => $amp_locale -> GetStr('sitedbhost_label').' (**)')), 2, 2);
    $hui_site_grid -> AddChild(new HuiString('sitedbhost', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 2, 3);

    $hui_site_grid -> AddChild(new HuiLabel('dbportlabel', array('label' => $amp_locale -> GetStr('sitedbport_label').' (**)')), 3, 2);
    $hui_site_grid -> AddChild(new HuiString('sitedbport', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 3, 3);

    $hui_site_grid -> AddChild(new HuiLabel('dbuserlabel', array('label' => $amp_locale -> GetStr('sitedbuser_label').' (**)')), 4, 2);
    $hui_site_grid -> AddChild(new HuiString('sitedbuser', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 4, 3);

    $hui_site_grid -> AddChild(new HuiLabel('dbpasswordlabel', array('label' => $amp_locale -> GetStr('sitedbpassword_label').' (**)')), 5, 2);
    $hui_site_grid -> AddChild(new HuiString('sitedbpassword', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 5, 3);

    $hui_site_grid -> AddChild(new HuiLabel('dbloglabel', array('label' => $amp_locale -> GetStr('sitedblog_label').' (**)')), 6, 2);
    $hui_site_grid -> AddChild(new HuiString('sitedblog', array('disp' => 'pass', 'tabindex' => $tab_index ++)), 6, 3);

    $hui_site_grid -> AddChild(new HuiLabel('createdblabel', array('label' => $amp_locale -> GetStr('createdb_label').' (***)')), 7, 2);
    $hui_site_grid -> AddChild(new HuiCheckBox('createsitedb', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'checked' => 'true')), 7, 3);

    $hui_vgroup -> AddChild($hui_site_grid);

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'createsite', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    //$hui_vgroup->AddChild( new HuiSubmit( 'submit1', array( 'caption' => $amp_locale->GetStr( 'createsite_submit' ), 'tabindex' => $tab_index++ ) ) );
    $hui_vgroup -> AddChild(new HuiButton('submit1', array('label' => $amp_locale -> GetStr('createsite_submit'), 'themeimage' => 'button_ok', 'horiz' => 'true', 'formsubmit' => 'newsiteform', 'formcheckmessage' => $amp_locale -> GetStr('newsite_formcheck.message'), 'action' => $form_events_call -> GetEventsCallString())));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));
    $hui_vgroup -> AddChild(new HuiLabel('dbparamsnotelabel', array('label' => $amp_locale -> GetStr('dbparamsnote_label'))));
    $hui_vgroup -> AddChild(new HuiLabel('dbcreatenotelabel', array('label' => $amp_locale -> GetStr('createdbnote_label'))));

    $hui_form = new HuiForm('newsiteform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('newsite_title');
}

$main_disp -> AddEvent('editsiteconfig', 'main_editsiteconfig');
function main_editsiteconfig($eventData) {
    global $env, $dbtypes, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $query = & $env['ampdb'] -> execute('SELECT * '.'FROM sites '.'WHERE id='.$eventData['siteid'].' '.'ORDER BY sitename');

    if ($query -> NumRows()) {
        $site_data = $query -> Fields();

        $hui_vgroup = new HuiVertGroup('vgroup');

        $hui_site_grid = new HuiGrid('newsitegrid', array('rows' => '7', 'cols' => '4'));

        $tab_index = 1;

        // Site fields
        //
        $hui_site_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('sitename_label').' (*)')), 0, 0);
        $hui_site_grid -> AddChild(new HuiString('sitename', array('disp' => 'pass', 'checkmessage' => $amp_locale -> GetStr('sitename_label'), 'required' => 'true', 'tabindex' => $tab_index ++, 'value' => $site_data['sitename'])), 0, 1);

        $hui_site_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('sitepassword_label').' (*)')), 2, 0);
        $hui_site_grid -> AddChild(new HuiString('sitepassword', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'password' => 'true')), 2, 1);

        $hui_site_grid -> AddChild(new HuiLabel('idlabel', array('label' => $amp_locale -> GetStr('siteid_label'))), 1, 0);
        $hui_site_grid -> AddChild(new HuiString('siteid', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'readonly' => 'true', 'value' => $site_data['siteid'])), 1, 1);

        $hui_site_grid -> AddChild(new HuiLabel('pathlabel', array('label' => $amp_locale -> GetStr('sitepath_label'))), 3, 0);
        $hui_site_grid -> AddChild(new HuiString('sitepath', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitepath'])), 3, 1);

        $hui_site_grid -> AddChild(new HuiLabel('urllabel', array('label' => $amp_locale -> GetStr('siteurl_label'))), 4, 0);
        $hui_site_grid -> AddChild(new HuiString('siteurl', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['siteurl'])), 4, 1);

        $hui_site_grid -> AddChild(new HuiLabel('maxuserslabel', array('label' => $amp_locale -> GetStr('maxusers_label'))), 5, 0);
        $hui_site_grid -> AddChild(new HuiString('maxusers', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['maxusers'])), 5, 1);

        // Database fields
        //
        $hui_site_grid -> AddChild(new HuiLabel('dbtypelabel', array('label' => $amp_locale -> GetStr('sitedbtype_label'))), 0, 2);
        $hui_site_grid -> AddChild(new HuiComboBox('sitedbtype', array('disp' => 'pass', 'tabindex' => $tab_index ++, $elements = $dbtypes, 'default' => $dbtypes[$site_data['sitedbtype']])), 0, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbnamelabel', array('label' => $amp_locale -> GetStr('sitedbname_label'))), 1, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbname', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitedbname'])), 1, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbhostlabel', array('label' => $amp_locale -> GetStr('sitedbhost_label'))), 2, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbhost', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitedbhost'])), 2, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbportlabel', array('label' => $amp_locale -> GetStr('sitedbport_label'))), 3, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbport', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitedbport'])), 3, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbuserlabel', array('label' => $amp_locale -> GetStr('sitedbuser_label'))), 4, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbuser', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitedbuser'])), 4, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbpasswordlabel', array('label' => $amp_locale -> GetStr('sitedbpassword_label'))), 5, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbpassword', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitedbpassword'])), 5, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbloglabel', array('label' => $amp_locale -> GetStr('sitedblog_label'))), 6, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedblog', array('disp' => 'pass', 'tabindex' => $tab_index ++, 'value' => $site_data['sitedblog'])), 6, 3);

        $hui_vgroup -> AddChild($hui_site_grid);

        $form_events_call = new HuiEventsCall();
        $form_events_call -> AddEvent(new HuiEvent('pass', 'updatesite', array('siteserial' => $site_data['id'])));
        $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

        $hui_vgroup -> AddChild(new HuiButton('submit1', array('label' => $amp_locale -> GetStr('editsite_submit'), 'themeimage' => 'button_ok', 'horiz' => 'true', 'formsubmit' => 'editsiteform', 'formcheckmessage' => $amp_locale -> GetStr('editsite_formcheck.message'), 'action' => $form_events_call -> GetEventsCallString())));

        $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
        $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

        $hui_form = new HuiForm('editsiteform', array('action' => $form_events_call -> GetEventsCallString()));
        $hui_form -> AddChild($hui_vgroup);

        $hui_mainframe -> AddChild($hui_form);
    }

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('editsiteconfig_title');
}

$main_disp -> AddEvent('editsitenotes', 'main_editsitenotes');
function main_editsitenotes($eventData) {
    global $gEnv, $amp_locale, $hui_mainframe, $hui_titlebar;

    $site_query = & $gEnv['root']['db'] -> execute('SELECT siteid,sitename,notes '.'FROM sites '.'WHERE id='.$eventData['siteid']);

    $site_data = $site_query -> Fields();

    $xml_def = '<vertgroup>
      <name>notes</name>
      <children>
        <label>
          <name>notes</name>
          <args>
            <bold>true</bold>
            <label type="encoded">'.urlencode($amp_locale -> GetStr('sitenotes_text.label')).'</label>
          </args>
        </label>
        <form>
          <name>notes</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default', ''), array('pass', 'editsitenotes', array('siteid' => $site_data['siteid']))))).'</action>
          </args>
          <children>
            <text>
              <name>notes</name>
              <args>
                <disp>pass</disp>
                <cols>80</cols>
                <rows>10</rows>
                <value type="encoded">'.urlencode($site_data['notes']).'</value>
              </args>
            </text>
          </children>
        </form>
        <horizbar>
          <name>hb</name>
        </horizbar>
        <button>
          <name>apply</name>
          <args>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>notes</formsubmit>
            <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default', ''), array('pass', 'editsitenotes', array('siteid' => $site_data['siteid']))))).'</action>
            <label type="encoded">'.urlencode($amp_locale -> GetStr('notes_apply.submit')).'</label>
            <themeimage>button_ok</themeimage>
          </args>
        </button>
      </children>
    </vertgroup>';

    $hui_mainframe -> AddChild(new HuiXml('page', array('definition' => $xml_def)));

    $hui_titlebar -> mTitle.= ' - '.$site_data['siteid'].' ('.$site_data['sitename'].') - '.$amp_locale -> GetStr('sitenotes.title');
}

$main_disp -> AddEvent('showsiteconfig', 'main_showsiteconfig');
function main_showsiteconfig($eventData) {
    global $env, $dbtypes, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $query = & $env['ampdb'] -> execute('SELECT * '.'FROM sites '.'WHERE id='.$eventData['siteid']);

    if ($query -> NumRows()) {
        $site_data = $query -> Fields();

        $hui_vgroup = new HuiVertGroup('vgroup');

        $hui_site_grid = new HuiGrid('showsitegrid', array('rows' => '7', 'cols' => '4'));

        // Site fields
        //
        $hui_site_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('sitename_label'))), 0, 0);
        $hui_site_grid -> AddChild(new HuiString('sitename', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitename'])), 0, 1);

        $hui_site_grid -> AddChild(new HuiLabel('idlabel', array('label' => $amp_locale -> GetStr('siteid_label'))), 1, 0);
        $hui_site_grid -> AddChild(new HuiString('siteid', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['siteid'])), 1, 1);

        $hui_site_grid -> AddChild(new HuiLabel('pathlabel', array('label' => $amp_locale -> GetStr('sitepath_label'))), 3, 0);
        $hui_site_grid -> AddChild(new HuiString('sitepath', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitepath'])), 3, 1);

        $hui_site_grid -> AddChild(new HuiLabel('urllabel', array('label' => $amp_locale -> GetStr('siteurl_label'))), 4, 0);
        $hui_site_grid -> AddChild(new HuiString('siteurl', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['siteurl'])), 4, 1);

        $hui_site_grid -> AddChild(new HuiLabel('maxuserslabel', array('label' => $amp_locale -> GetStr('maxusers_label'))), 5, 0);
        $hui_site_grid -> AddChild(new HuiString('maxusers', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['maxusers'])), 5, 1);

        // Database fields
        //
        $hui_site_grid -> AddChild(new HuiLabel('dbtypelabel', array('label' => $amp_locale -> GetStr('sitedbtype_label'))), 0, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbtype', array('disp' => 'pass', 'readonly' => 'true', 'value' => $dbtypes[$site_data['sitedbtype']])), 0, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbnamelabel', array('label' => $amp_locale -> GetStr('sitedbname_label'))), 1, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbname', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitedbname'])), 1, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbhostlabel', array('label' => $amp_locale -> GetStr('sitedbhost_label'))), 2, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbhost', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitedbhost'])), 2, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbportlabel', array('label' => $amp_locale -> GetStr('sitedbport_label'))), 3, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbport', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitedbport'])), 3, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbuserlabel', array('label' => $amp_locale -> GetStr('sitedbuser_label'))), 4, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbuser', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitedbuser'])), 4, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbpasswordlabel', array('label' => $amp_locale -> GetStr('sitedbpassword_label'))), 5, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedbpassword', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitedbpassword'])), 5, 3);

        $hui_site_grid -> AddChild(new HuiLabel('dbloglabel', array('label' => $amp_locale -> GetStr('sitedblog_label'))), 6, 2);
        $hui_site_grid -> AddChild(new HuiString('sitedblog', array('disp' => 'pass', 'readonly' => 'true', 'value' => $site_data['sitedblog'])), 6, 3);

        $hui_vgroup -> AddChild($hui_site_grid);

        $hui_mainframe -> AddChild($hui_vgroup);
    }

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('showsiteconfig_title');
}

$main_disp -> AddEvent('showsitelog', 'main_showsitelog');
function main_showsitelog($eventData) {
    global $env, $dbtypes, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $query = & $env['ampdb'] -> execute('SELECT siteid,sitename '.'FROM sites '.'WHERE id='.$eventData['siteid']);

    if ($query -> NumRows()) {
        $site_data = $query -> Fields();

        $hui_vgroup = new HuiVertGroup('vgroup');

        $site_log_content = '';

        if (file_exists(SITESTUFF_PATH.$site_data['siteid'].'/log/site.log')) {
                $log_toolbar = new HuiToolBar('logbar');

                $cleanlog_action = new HuiEventsCall();
                $cleanlog_action -> AddEvent(new HuiEvent('main', 'showsitelog', array('siteid' => $eventData['siteid'])));
                $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleansitelog', array('siteid' => $site_data['siteid'])));
                $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

                $log_toolbar -> AddChild($cleanlog_button);
                $log_frame = new HuiHorizFrame('logframe');
                $log_frame -> AddChild($log_toolbar);
                $hui_mainvertgroup -> AddChild($log_frame);

                $site_log_content = file_get_contents(SITESTUFF_PATH.$site_data['siteid'].'/log/site.log');
        }

        $hui_vgroup -> AddChild(new HuiText('sitelog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($site_log_content), 'rows' => '20', 'cols' => '120')), 0, 1);
        $hui_mainframe -> AddChild($hui_vgroup);
    }

    $hui_titlebar -> mTitle.= ' - '.$site_data['siteid'].' ('.$site_data['sitename'].') - '.$amp_locale -> GetStr('showsitelog_title');
}

$main_disp -> AddEvent('showsitedblog', 'main_showsitedblog');
function main_showsitedblog($eventData) {
    global $env, $dbtypes, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $query = & $env['ampdb'] -> execute('SELECT * '.'FROM sites '.'WHERE id='.$eventData['siteid']);

    if ($query -> NumRows()) {
        $site_data = $query -> Fields();

        $hui_vgroup = new HuiVertGroup('vgroup');

        $db_log_content = '';

        if (file_exists($site_data['sitedblog'])) {
                $log_toolbar = new HuiToolBar('logbar');

                $cleanlog_action = new HuiEventsCall();
                $cleanlog_action -> AddEvent(new HuiEvent('main', 'showsitedblog', array('siteid' => $eventData['siteid'])));
                $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleansitedblog', array('siteid' => $eventData['siteid'])));
                $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

                $log_toolbar -> AddChild($cleanlog_button);
                $log_frame = new HuiHorizFrame('logframe');
                $log_frame -> AddChild($log_toolbar);
                $hui_mainvertgroup -> AddChild($log_frame);

                $db_log_content = file_get_contents($site_data['sitedblog']);
        }

        $hui_vgroup -> AddChild(new HuiText('sitedblog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($db_log_content), 'rows' => '20', 'cols' => '120')), 0, 1);

        $hui_mainframe -> AddChild($hui_vgroup);
    }

    $hui_titlebar -> mTitle.= ' - '.$site_data['siteid'].' ('.$site_data['sitename'].') - '.$amp_locale -> GetStr('showsitedblog_title');
}

$main_disp -> AddEvent('accesssite', 'main_accesssite');
function main_accesssite($eventData) {
    global $env;

    $sitequery = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id='.$eventData['siteid']);

    session_start();
    $GLOBALS['AMP_AUTH_USER'] = $sitequery -> Fields('siteid');
    ;
    session_register('AMP_AUTH_USER');

    header('Location: '.ADMIN_URL);
}

$main_disp -> AddEvent('sitemodules', 'main_sitemodules');
function main_sitemodules($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $site_query = & $env['ampdb'] -> execute('SELECT * FROM sites WHERE id='.$eventData['siteid']);

    $site_data = $site_query -> Fields();

    $modules_query = & $env['ampdb'] -> Execute('SELECT * FROM modules WHERE onlyextension <> '.$env['ampdb'] -> Format_Text($env['ampdb'] -> fmttrue).' ORDER BY modid');

    if ($modules_query) {
        if ($modules_query -> NumRows()) {
            $headers[0]['label'] = $amp_locale -> GetStr('status_header');
            $headers[1]['label'] = $amp_locale -> GetStr('moduleid_header');
            $headers[2]['label'] = $amp_locale -> GetStr('modactivationdate_header');
            $headers[4]['label'] = $amp_locale -> GetStr('moddeps_header');

            $row = 0;

            $hui_sitemodules_table = new HuiTable('sitemodulestable', array('headers' => $headers));

            OpenLibrary('modulesbase.library');

            while (!$modules_query -> eof) {
                $modules_data = $modules_query -> Fields();

                if ($modules_data['modid'] != 'ampoliros') {
                    $act_query = & $env['ampdb'] -> Execute('SELECT * FROM activemodules WHERE siteid = '.$eventData['siteid'].' AND moduleid = '.$modules_data['id']);

                    $hui_en_group[$row] = new HuiVertGroup('enable');
                    $hui_sitemodules_toolbar[$row] = new HuiToolBar('sitemodulestoolbar'.$row);
                    $mod_dep = new ModuleDep($env['ampdb']);

                    if ($act_query -> NumRows()) {
                        // Module is enabled
                        //
                        $act_data = $act_query -> Fields();
                        $hui_sitemodules_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['greenball'])), $row, 0);
                        $hui_sitemodules_table -> AddChild(new HuiLabel('modid'.$row, array('label' => $modules_data['modid'], 'compact' => 'true')), $row, 1);
                        $hui_sitemodules_table -> AddChild(new HuiLabel('actdate'.$row, array('label' => $act_data['activationdate'], 'compact' => 'true')), $row, 2);

                        $site_depending_modules = $mod_dep -> CheckSiteDependingModules($modules_data['modid'], $site_data['siteid']);

                        $module = new Module($env['ampdb'], $modules_data['id']);

                        $sub_modules = $module -> GetSubModules();

                        if (!$site_depending_modules) {
                            // No modules depends on this one
                            //
                            $disable_action[$row] = new HuiEventsCall();
                            $disable_action[$row] -> AddEvent(new HuiEvent('main', 'sitemodules', array('siteid' => $eventData['siteid'])));
                            $disable_action[$row] -> AddEvent(new HuiEvent('pass', 'deactivatemodule', array('siteid' => $eventData['siteid'], 'modid' => $modules_data['id'])));
                            $hui_disable_button[$row] = new HuiButton('disablebutton'.$row, array('label' => $amp_locale -> GetStr('deactivatemodule_label'), 'compact' => 'true', 'horiz' => 'true', 'themeimage' => 'stop', 'themeimagetype' => 'mini', 'action' => $disable_action[$row] -> GetEventsCallString()));

                            $hui_sitemodules_toolbar[$row] -> AddChild($hui_disable_button[$row]);

                            $hui_en_group[$row] -> AddChild($hui_sitemodules_toolbar[$row]);
                        } else {
                            // At least one module depends on this one
                            //
                            $mod_dep_list_str = '';
                            while (list (, $dep) = each($site_depending_modules))
                                $mod_dep_list_str.= $dep.'<br>';
                            $hui_sitemodules_table -> AddChild(new HuiLabel('moddeps'.$row, array('label' => $mod_dep_list_str)), $row, 4);
                        }

                        if (count($sub_modules)) {
                            $toolbar = array();

                            while (list (, $name) = each($sub_modules)) {
                                $enabled = $module -> CheckIfSubModuleEnabled($name, $eventData['siteid']);

                                $toolbar['main']['enable'] = array('label' => sprintf($amp_locale -> GetStr(($enabled ? 'disable' : 'enable').'_submodule.button'), ucfirst($name)), 'themeimage' => $enabled ? 'stop' : 'reload', 'compact' => 'true', 'themeimagetype' => 'mini', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'sitemodules', array('siteid' => $eventData['siteid'])), array('pass', $enabled ? 'disablesubmodule' : 'enablesubmodule', array('moduleid' => $modules_data['id'], 'siteid' => $eventData['siteid'], 'submodule' => $name)))));

                                $hui_en_group[$row] -> AddChild(new HuiAmpToolBar('main', array('frame' => 'false', 'toolbars' => $toolbar)));
                            }
                        }

                        $hui_sitemodules_table -> AddChild($hui_en_group[$row], $row, 3);
                    } else {
                        // Module is not enabled
                        //
                        $hui_sitemodules_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['redball'])), $row, 0);
                        $hui_sitemodules_table -> AddChild(new HuiLabel('modid'.$row, array('label' => $modules_data['modid'])), $row, 1);

                        $site_module_deps = $mod_dep -> CheckSiteModuleDeps($modules_data['modid'], $site_data['siteid'], DEPTYPE_DEP);

                        if (!is_array($site_module_deps)) {
                            // All module dependecies are met
                            //
                            $enable_action[$row] = new HuiEventsCall();
                            $enable_action[$row] -> AddEvent(new HuiEvent('main', 'sitemodules', array('siteid' => $eventData['siteid'])));
                            $enable_action[$row] -> AddEvent(new HuiEvent('pass', 'activatemodule', array('siteid' => $eventData['siteid'], 'modid' => $modules_data['id'])));
                            $hui_enable_button[$row] = new HuiButton('enablebutton'.$row, array('label' => $amp_locale -> GetStr('activatemodule_label'), 'compact' => 'true', 'horiz' => 'true', 'themeimage' => 'reload', 'themeimagetype' => 'mini', 'action' => $enable_action[$row] -> GetEventsCallString()));
                            $hui_sitemodules_toolbar[$row] -> AddChild($hui_enable_button[$row]);
                        } else {
                            // At least one module dependency is not met
                            //
                            $mod_dep_list_str = '';
                            while (list (, $dep) = each($site_module_deps))
                                $mod_dep_list_str.= $dep.'<br>';
                            $hui_sitemodules_table -> AddChild(new HuiLabel('moddeps'.$row, array('label' => $mod_dep_list_str)), $row, 4);
                        }
                        $hui_sitemodules_table -> AddChild($hui_sitemodules_toolbar[$row], $row, 3);
                    }
                    $row ++;
                }

                $modules_query -> MoveNext();
            }

            $xml_def = '<toolbar>
              <children>
            
                <button>
                  <args>
                    <themeimage>reload</themeimage>
                    <label type="encoded">'.urlencode($amp_locale -> GetStr('enable_all_modules.button')).'</label>
                    <horiz>true</horiz>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'sitemodules', array('siteid' => $eventData['siteid'])), array('pass', 'activateallmodules', array('siteid' => $eventData['siteid']))))).'</action>
                  </args>
                </button>
            
                <button>
                  <args>
                    <themeimage>stop</themeimage>
                    <label type="encoded">'.urlencode($amp_locale -> GetStr('disable_all_modules.button')).'</label>
                    <horiz>true</horiz>
                    <needconfirm>true</needconfirm>
                    <confirmmessage type="encoded">'.urlencode($amp_locale -> GetStr('disable_all_modules.confirm')).'</confirmmessage>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'sitemodules', array('siteid' => $eventData['siteid'])), array('pass', 'deactivateallmodules', array('siteid' => $eventData['siteid']))))).'</action>
                  </args>
                </button>
            
              </children>
            </toolbar>';

            $hui_mainvgroup = new HuiVertGroup();

            $hui_mainvgroup -> AddChild($hui_sitemodules_table);
            $hui_mainvgroup -> AddChild(new HuiHorizBar());
            $hui_mainvgroup -> AddChild(new HuiXml('', array(definition => $xml_def)));

            $hui_mainframe -> AddChild($hui_mainvgroup);
        }
    }

    $hui_titlebar -> mTitle.= ' - '.$site_data['siteid'].' ('.$site_data['sitename'].') - '.$amp_locale -> GetStr('sitemodules_title');
}

$main_disp -> AddEvent('situation', 'main_situation');
function main_situation($eventData) {
    global $hui_mainframe, $amp_locale, $hui_titlebar;

    OpenLibrary('modules.library');

    $sites_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid '.'FROM sites '.'ORDER BY siteid');

    $modules_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT modid '.'FROM modules '.'WHERE onlyextension='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($GLOBALS['gEnv']['root']['db'] -> fmtfalse).' '.'ORDER BY modid');

    $headers = array();
    $cont = 1;
    while (!$modules_query -> eof) {
        $orig_label = $modules_query -> Fields('modid');
        $label = '';

        for ($i = 0; $i < strlen($orig_label); $i ++) {
            if ($i)
                $label.= '<br>';
            $label.= $orig_label {
                $i};
        }

        $headers[$cont ++]['label'] = $label;
        $modules_query -> MoveNext();
    }

    $xml_def = '<table><name>situation</name>
      <args>
        <headers type="array">'.huixml_encode($headers).'</headers>
      </args>
      <children>';

    $row = 0;

    $mod_deps = new ModuleDep($GLOBALS['gEnv']['root']['db']);

    while (!$sites_query -> eof) {
        $xml_def.= '<label row="'.$row.'" col="0">
          <args>
            <label type="encoded">'.urlencode($sites_query -> Fields('siteid')).'</label>
            <compact>true</compact>
          </args>
        </label>';

        $col = 1;

        $modules_query -> MoveFirst();

        while (!$modules_query -> eof) {
            $enabled = $mod_deps -> IsEnabled($modules_query -> Fields('modid'), $sites_query -> Fields('siteid'));

            $xml_def.= '<image row="'.$row.'" col="'.$col.'" halign="center" valign="middle">
              <args>
                <imageurl>'. ($enabled ? $hui_mainframe -> mThemeHandler -> mStyle['greenball'] : $hui_mainframe -> mThemeHandler -> mStyle['redball']).'</imageurl>
              </args>
            </image>';
            $col ++;

            $modules_query -> MoveNext();
        }

        $row ++;

        $sites_query -> MoveNext();
    }

    $xml_def.= '  </children>
    </table>';

    $hui_mainframe -> AddChild(new HuiXml('', array(definition => $xml_def)));
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('sites_help', array('node' => 'ampoliros.root.sites.'.$eventData['node'], 'language' => AMP_LANG)));
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
