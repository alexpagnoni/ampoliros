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
// $Id: applications.php,v 1.9 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.io.log.Logger');
import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.module.*');
import('com.solarix.ampoliros.site.*');
OpenLibrary('configman.library');
OpenLibrary('ampshared.library');

$log = new Logger(AMP_LOG);
$amp_locale = new Locale('amp_root_modules', $gEnv['root']['locale']['language']);
$hui = new Hui($GLOBALS['gEnv']['root']['db']);
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('modules_title')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('modules_title'), 'icon' => 'kpackage'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

$menu_frame = new HuiHorizGroup('menuframe');
$menu_frame -> AddChild(new HuiMenu('magellanmainmenu', array('menu' => get_ampoliros_root_menu_def($env['sitelocale']))));
$hui_mainvertgroup -> AddChild($menu_frame);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$home_action = new HuiEventsCall();
$home_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_homebutton = new HuiButton('homebutton', array('label' => $amp_locale -> GetStr('modules_button'), 'themeimage' => 'view_detailed', 'horiz' => 'true', 'action' => $home_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_homebutton);

$new_action = new HuiEventsCall();
$new_action -> AddEvent(new HuiEvent('main', 'newmodule', ''));
$hui_newbutton = new HuiButton('newbutton', array('label' => $amp_locale -> GetStr('newmodule_button'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => $new_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_newbutton);

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

$pass_disp -> AddEvent('install', 'pass_install');
function pass_install($eventData) {
    global $env, $hui_mainstatus, $amp_locale, $hui_page;

    if (strcmp($eventData['modulefile']['tmp_name'], 'none') != 0) {
        $temp_module = new Module($env['ampdb'], '');

        move_uploaded_file($eventData['modulefile']['tmp_name'], TMP_PATH.$eventData['modulefile']['name']);
        if (!$temp_module -> Install(TMP_PATH.$eventData['modulefile']['name'])) {
            $unmet_deps = $temp_module -> GetLastActionUnmetDeps();
            while (list ($key, $val) = each($unmet_deps))
                $unmet_deps_str.= ' '.$val;
            $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('unmetdeps_status').$unmet_deps_str;
        } else {
            $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('modinstalled_status');
        }

        $unmet_suggs = $temp_module -> GetLastActionUnmetSuggs();
        while (list ($key, $val) = each($unmet_suggs))
            $unmet_suggs_str.= ' '.$val;
        if (isset($unmet_suggs_str) and strlen($unmet_suggs_str))
            $hui_mainstatus -> mArgs['status'].= $amp_locale -> GetStr('unmetsuggs_status').$unmet_suggs_str;
    }

    $hui_page -> mArgs['javascript'] = "parent.frames.sum.location.reload()\n".'parent.frames.header.location.reload()';
}

$pass_disp -> AddEvent('uninstall', 'pass_uninstall');
function pass_uninstall($eventData) {
    global $env, $hui_mainstatus, $amp_locale, $hui_page;

    $temp_module = new Module($env['ampdb'], $eventData['modid']);
    if (!$temp_module -> Uninstall()) {
        $unmet_deps = $temp_module -> GetLastActionUnmetDeps();
        while (list ($key, $val) = each($unmet_deps))
            $unmet_deps_str.= ' '.$val;
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('removeunmetdeps_status').$unmet_deps_str;
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('moduninstalled_status');

    $hui_page -> mArgs['javascript'] = "parent.frames.sum.location.reload()\n".'parent.frames.header.location.reload()';
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

$pass_disp -> AddEvent('cleanmodlog', 'pass_cleanmodlog');
function pass_cleanmodlog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $temp_log = new Logger(MODULE_PATH.$eventData['modid'].'/module.log');

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

function modules_list_action_builder($pageNumber) {
    return build_events_call_string('', array(array('main', 'default', array('modulespage' => $pageNumber))));
}

function modules_tab_action_builder($tab) {
    return build_events_call_string('', array(array('main', 'default', array('activetab' => $tab))));
}

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $gEnv, $hui_mainframe, $amp_locale, $hui_mainstatus;

    $modules_query = & $env['ampdb'] -> Execute('SELECT * '.'FROM modules '.'ORDER BY category,modid');

    if ($modules_query -> NumRows() > 0) {
        $headers[0]['label'] = $amp_locale -> GetStr('modid_header');
        $headers[1]['label'] = $amp_locale -> GetStr('modauthor_header');
        $headers[2]['label'] = $amp_locale -> GetStr('modversion_header');
        $headers[3]['label'] = $amp_locale -> GetStr('moddate_header');

        $row = 0;
        $current_category = '';

        while (!$modules_query -> eof) {
            $tmp_data = $modules_query -> Fields();
            if ($tmp_data['category'] == '')
                $tmp_data['category'] = 'various';
            $modules_array[$tmp_data['category']][] = $tmp_data;
            $modules_query -> MoveNext();
        }

        ksort($modules_array);

        $categories = array();

        while (list (, $tmp_data) = each($modules_array)) {
            while (list (, $data) = each($tmp_data)) {
                if ($data['category'] != $current_category) {
                    $hui_modules_table[$data['category']] = new HuiTable('modulestable', array('headers' => $headers, 'rowsperpage' => '10', 'pagesactionfunction' => 'modules_list_action_builder', 'pagenumber' => (isset($eventData['modulespage']) ? $eventData['modulespage'] : ''), 'sessionobjectusername' => $data['category']));
                    $current_category = $data['category'];

                    $categories[] = $data['category'];
                    $row = 0;
                    //$hui_modules_table->AddChild( new HuiLabel( 'modcategory'.$row, array( 'label' => '<strong><font color="red">'.ucfirst( $data['category'] ).'</font></strong>' ) ), $row, 0 );
                    //$row++;
                }

                $hui_modules_table[$data['category']] -> AddChild(new HuiLabel('modidlabel'.$row, array('label' => '<strong>'.$data['modid'].'</strong><br />'.$data['moddesc'])), $row, 0);
                $hui_modules_table[$data['category']] -> AddChild(new HuiLink('modauthorlabel'.$row, array('label' => $data['author'], 'link' => $data['authorsite'])), $row, 1);
                $hui_modules_table[$data['category']] -> AddChild(new HuiLabel('modversionlabel'.$row, array('label' => $data['modversion'])), $row, 2);
                $hui_modules_table[$data['category']] -> AddChild(new HuiLabel('moddatedatelabel'.$row, array('label' => $data['moddate'])), $row, 3);

                $hui_module_toolbar[$data['category']][$row] = new HuiToolBar('moduletoolbar'.$row);

                $details_action[$data['category']][$row] = new HuiEventsCall();
                $details_action[$data['category']][$row] -> AddEvent(new HuiEvent('main', 'details', array('modid' => $data['id'])));
                $hui_details_button[$data['category']][$row] = new HuiButton('detailsbutton'.$row, array('label' => $amp_locale -> GetStr('moddetails_label'), 'themeimage' => 'viewmag', 'action' => $details_action[$data['category']][$row] -> GetEventsCallString(),'horiz'=>'true'));
                $hui_module_toolbar[$data['category']][$row] -> AddChild($hui_details_button[$data['category']][$row]);

                if ($gEnv['core']['state'] == AMP_STATE_DEVELOPMENT or $gEnv['core']['state'] == AMP_STATE_DEBUG) {
                    $show_action[$data['category']][$row] = new HuiEventsCall();
                    $show_action[$data['category']][$row] -> AddEvent(new HuiEvent('main', 'showmodule', array('modid' => $data['id'])));
                    $hui_show_button[$data['category']][$row] = new HuiButton('showbutton'.$row, array('label' => $amp_locale -> GetStr('showmodule_label'), 'themeimage' => 'viewmag+', 'action' => $show_action[$data['category']][$row] -> GetEventsCallString(),'horiz'=>'true'));
                    $hui_module_toolbar[$data['category']][$row] -> AddChild($hui_show_button[$data['category']][$row]);

                    $hooks_action[$data['category']][$row] = new HuiEventsCall();
                    $hooks_action[$data['category']][$row] -> AddEvent(new HuiEvent('main', 'modulehooks', array('modid' => $data['id'])));
                    $hui_hooks_button[$data['category']][$row] = new HuiButton('hooksbutton'.$row, array('label' => $amp_locale -> GetStr('modulehooks.label'), 'themeimage' => 'attach', 'action' => $hooks_action[$data['category']][$row] -> GetEventsCallString(),'horiz'=>'true'));
                    $hui_module_toolbar[$data['category']][$row] -> AddChild($hui_hooks_button[$data['category']][$row]);
                }

                if (strcmp($data['modid'], 'ampoliros')) {
                    $deps_action[$data['category']][$row] = new HuiEventsCall();
                    $deps_action[$data['category']][$row] -> AddEvent(new HuiEvent('main', 'dependencies', array('modid' => $data['id'])));
                    $hui_deps_button[$data['category']][$row] = new HuiButton('depsbutton'.$row, array('label' => $amp_locale -> GetStr('moduledeps_label'), 'themeimage' => 'view_tree', 'action' => $deps_action[$data['category']][$row] -> GetEventsCallString(),'horiz'=>'true'));
                    $hui_module_toolbar[$data['category']][$row] -> AddChild($hui_deps_button[$data['category']][$row]);

                    $remove_action[$data['category']][$row] = new HuiEventsCall();
                    $remove_action[$data['category']][$row] -> AddEvent(new HuiEvent('main', 'default', ''));
                    $remove_action[$data['category']][$row] -> AddEvent(new HuiEvent('pass', 'uninstall', array('modid' => $data['id'])));
                    $hui_remove_button[$data['category']][$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removemodule_label'), 'themeimage' => 'edittrash', 'action' => $remove_action[$data['category']][$row] -> GetEventsCallString(), 'needconfirm' => 'true', 'confirmmessage' => sprintf($amp_locale -> GetStr('removemodulequestion_label'), $data['modid']),'horiz'=>'true'));
                    $hui_module_toolbar[$data['category']][$row] -> AddChild($hui_remove_button[$data['category']][$row]);
                }

                if (file_exists(MODULE_PATH.$data['modid'].'/module.log')) {
                    $log_action[$data['category']][$row] = new HuiEventsCall();
                    $log_action[$data['category']][$row] -> AddEvent(new HuiEvent('main', 'modulelog', array('modid' => $data['id'])));
                    $hui_log_button[$data['category']][$row] = new HuiButton('logbutton'.$row, array('label' => $amp_locale -> GetStr('modlog_label'), 'themeimage' => 'toggle_log', 'action' => $log_action[$data['category']][$row] -> GetEventsCallString(),'horiz'=>'true'));
                    $hui_module_toolbar[$data['category']][$row] -> AddChild($hui_log_button[$data['category']][$row]);
                }

                $hui_modules_table[$data['category']] -> AddChild($hui_module_toolbar[$data['category']][$row], $row, 4);

                $row ++;
            }

            while (list (, $category) = each($categories)) {
                $tabs[]['label'] = ucfirst($category);
            }
            reset($categories);

            $tab = new HuiTab('modulestab', array('tabactionfunction' => 'modules_tab_action_builder', 'tabs' => $tabs, 'activetab' => (isset($eventData['activetab']) ? $eventData['activetab'] : '')));

            while (list (, $category) = each($categories)) {
                $tab -> AddChild($hui_modules_table[$category]);
            }
        }

        $hui_mainframe -> AddChild($tab);
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('no_available_modules_status');
}

$main_disp -> AddEvent('newmodule', 'main_newmodule');
function main_newmodule($eventData) {
    global $env, $dbtypes, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_module_grid = new HuiGrid('newmodulegrid', array('rows' => '2', 'cols' => '2'));

    // Module fields
    //
    $hui_module_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('modulefile_label').' (*)')), 0, 0);
    $hui_module_grid -> AddChild(new HuiFile('modulefile', array('disp' => 'pass')), 0, 1);

    $hui_vgroup -> AddChild($hui_module_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('newmodule_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'install', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_form = new HuiForm('newmoduleform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('newmodule_title');
}

$main_disp -> AddEvent('details', 'main_details');
function main_details($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_titlebar;

    $query = & $env['ampdb'] -> execute('SELECT * '.'FROM modules '.'WHERE id='.$eventData['modid'].' ');

    $module_data = $query -> Fields();

    $details_vgroup = new HuiVertGroup('vgroup');

    $details_grid = new HuiGrid('moduledetailsgrid', array('rows' => '9', 'cols' => '2'));

    $details_grid -> AddChild(new HuiLabel('authorlabel', array('label' => $amp_locale -> GetStr('author_label'))), 0, 0);
    $details_grid -> AddChild(new HuiString('author', array('value' => $module_data['author'], 'readonly' => 'true', 'size' => 40)), 0, 1);

    $details_grid -> AddChild(new HuiLabel('authorsitelabel', array('label' => $amp_locale -> GetStr('authorsite_label'))), 1, 0);
    $details_grid -> AddChild(new HuiLink('authorsite', array('label' => $module_data['authorsite'], 'link' => $module_data['authorsite'])), 1, 1);

    $details_grid -> AddChild(new HuiLabel('authoremaillabel', array('label' => $amp_locale -> GetStr('authoremail_label'))), 2, 0);
    $details_grid -> AddChild(new HuiLink('authoremail', array('label' => $module_data['authoremail'], 'link' => (strlen($module_data['authoremail']) ? 'mailto:'.$module_data['authoremail'] : ''))), 2, 1);

    $details_grid -> AddChild(new HuiLabel('supportemaillabel', array('label' => $amp_locale -> GetStr('supportemail_label'))), 3, 0);
    $details_grid -> AddChild(new HuiLink('supportemail', array('label' => $module_data['supportemail'], 'link' => (strlen($module_data['supportemail']) ? 'mailto:'.$module_data['supportemail'] : ''))), 3, 1);

    $details_grid -> AddChild(new HuiLabel('bugsemaillabel', array('label' => $amp_locale -> GetStr('bugsemail_label'))), 4, 0);
    $details_grid -> AddChild(new HuiLink('bugsemail', array('label' => $module_data['bugsemail'], 'link' => (strlen($module_data['bugsemail']) ? 'mailto:'.$module_data['bugsemail'] : ''))), 4, 1);

    $details_grid -> AddChild(new HuiLabel('maintainerlabel', array('label' => $amp_locale -> GetStr('maintainer_label'))), 5, 0);
    $details_grid -> AddChild(new HuiString('maintainer', array('value' => $module_data['maintainer'], 'readonly' => 'true', 'size' => 40)), 5, 1);

    $details_grid -> AddChild(new HuiLabel('maintaineremaillabel', array('label' => $amp_locale -> GetStr('maintaineremail_label'))), 6, 0);
    $details_grid -> AddChild(new HuiLink('maintaineremail', array('label' => $module_data['maintaineremail'], 'link' => (strlen($module_data['maintaineremail']) ? 'mailto:'.$module_data['maintaineremail'] : ''))), 6, 1);

    $details_grid -> AddChild(new HuiLabel('copyrightlabel', array('label' => $amp_locale -> GetStr('copyright_label'))), 7, 0);
    $details_grid -> AddChild(new HuiString('copyright', array('value' => $module_data['copyright'], 'readonly' => 'true', 'size' => 40)), 7, 1);

    $details_grid -> AddChild(new HuiLabel('licenselabel', array('label' => $amp_locale -> GetStr('license_label'))), 8, 0);
    $details_grid -> AddChild(new HuiString('license', array('value' => $module_data['license'], 'readonly' => 'true', 'size' => 20)), 8, 1);

    if (strlen($module_data['licensefile']) and file_exists(MODULE_PATH.$module_data['modid'].'/'.$module_data['licensefile'])) {
            $license_text = file_get_contents(MODULE_PATH.$module_data['modid'].'/'.$module_data['licensefile']);
            $details_grid -> mRows = 10;
            $details_grid -> AddChild(new HuiText('licensetext', array('label' => $module_data['license'], 'value' => $license_text, 'readonly' => 'true', 'cols' => 90, 'rows' => '20')), 9, 1);
    }

    $details_vgroup -> AddChild($details_grid);
    $hui_mainframe -> AddChild($details_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$module_data['modid'].' - '.$amp_locale -> GetStr('moduledetails_title');
}

function show_module_action_builder($pageNumber) {
    $tmp_main_disp = new HuiDispatcher('main');

    $event_data = $tmp_main_disp -> GetEventData();

    return build_events_call_string('', array(array('main', 'showmodule', array('pagenumber' => $pageNumber, 'modid' => $event_data['modid']))));
}

$main_disp -> AddEvent('showmodule', 'main_showmodule');
function main_showmodule($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar;

    $query = & $env['ampdb'] -> execute('SELECT modid '.'FROM modules '.'WHERE id='.$eventData['modid'].' ');

    $module_data = $query -> Fields();

    $deffile = new XMLDefFile($env['ampdb'], PRIVATE_TREE);
    $deffile -> Load_DefFile(MODULE_PATH.$module_data['modid'].'/structure.xml');
    $structure = $deffile -> Get_Structure();

    ksort($structure);

    $headers[0]['label'] = $amp_locale -> GetStr('elementtype_header');
    $headers[1]['label'] = $amp_locale -> GetStr('elementname_header');
    $headers[2]['label'] = $amp_locale -> GetStr('elementattrs_header');

    $row = 0;

    $hui_modules_table = new HuiTable('modulestable', array('headers' => $headers, 'rowsperpage' => '20', 'pagesactionfunction' => 'show_module_action_builder', 'pagenumber' => (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : ''), 'sessionobjectusername' => $module_data['modid']));

    while (list ($type, $elems) = each($structure)) {
        if (is_array($elems)) {
            asort($elems);

            while (list ($elem, $attrs) = each($elems)) {
                $attrs_string = '';

                while (list ($key, $val) = each($attrs)) {
                    if (strcmp($key, 'name'))
                        $attrs_string.= '<b>'.$key.'</b>: '.$val.'. ';
                }

                $hui_modules_table -> AddChild(new HuiLabel('elementtypelabel'.$row, array('label' => $type)), $row, 0);
                $hui_modules_table -> AddChild(new HuiLabel('elementnamelabel'.$row, array('label' => $attrs['name'])), $row, 1);
                $hui_modules_table -> AddChild(new HuiLabel('elementattrslabel'.$row, array('label' => $attrs_string)), $row, 2);

                $row ++;
            }
        }
    }

    $hui_mainframe -> AddChild($hui_modules_table);

    $hui_titlebar -> mTitle.= ' - '.$module_data['modid'].' - '.$amp_locale -> GetStr('modulestructure_title');
}

$main_disp -> AddEvent('dependencies', 'main_dependencies');
function main_dependencies($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_titlebar;

    $query = & $env['ampdb'] -> execute('SELECT modid '.'FROM modules '.'WHERE id='.$eventData['modid'].' ');

    $module_data = $query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');
    $hui_hgroup1 = new HuiHorizGroup('hgroup1');
    $hui_hgroup2 = new HuiHorizGroup('hgroup2');

    $temp_deps = new ModuleDep($env['ampdb']);

    $module_deps = array();
    $module_suggs = array();
    $depending_mods = array();
    $suggesting_mods = array();
    $enabled_sites = array();

    $module_deps_array = $temp_deps -> DependsOn($module_data['modid']);
    if (is_array($module_deps_array)) {
        while (list ($key, $val) = each($module_deps_array)) {
            if ($val['deptype'] == DEPTYPE_DEP)
                $module_deps[$val['moddep']] = $val['moddep'].' '.$val['version'];
            else
                $module_suggs[$val['moddep']] = $val['moddep'].' '.$val['version'];
        }
    }

    $depending_mods_array = $temp_deps -> CheckDependingModules($module_data['modid'], DEPTYPE_DEP);
    if (is_array($depending_mods_array)) {
        while (list ($key, $val) = each($depending_mods_array)) {
            $depending_mods[$val] = $val;
        }
    }

    $suggesting_mods_array = $temp_deps -> CheckDependingModules($module_data['modid'], DEPTYPE_SUGG);
    if (is_array($suggesting_mods_array)) {
        while (list ($key, $val) = each($suggesting_mods_array)) {
            $suggesting_mods[$val] = $val;
        }
    }

    $enabled_sites_array = $temp_deps -> CheckEnabledSites($eventData['modid']);
    if (is_array($enabled_sites_array)) {
        asort($enabled_sites_array);

        while (list ($key, $val) = each($enabled_sites_array)) {
            $enabled_sites[$val] = $val;
        }
    }

    $xml_def = '<grid><name>deps</name><children>
    
      <vertframe row="0" col="0">
        <name>deps</name>
        <children>
          <label>
            <name>deps</name>
              <args>
                <label>'.$amp_locale -> GetStr('moddeps_label').'</label>
              </args>
            </label>
            <listbox>
              <name>deps</name>
              <args>
                <disp>pass</disp>
                <readonly>true</readonly>
                <elements type="array">'.huixml_encode($module_deps).'</elements>
                <size>5</size>
              </args>
            </listbox>
          </children>
        </vertframe>
    
      <vertframe row="0" col="1"><name>suggs</name><children>
        <label><name>suggs</name><args><label>'.$amp_locale -> GetStr('modsuggs_label').'</label></args></label>
        <listbox><name>suggs</name><args><disp>pass</disp><readonly>true</readonly><elements type="array">'.huixml_encode($module_suggs).'</elements><size>5</size></args></listbox>
      </children></vertframe>';

    if (strcmp($module_data['modid'], 'ampoliros')) {
        $xml_def.= '  <vertframe row="1" col="0"><name>depending</name><children>
            <label><name>depending</name><args><label>'.sprintf($amp_locale -> GetStr('dependingmods_label'), $module_data['modid']).'</label></args></label>
            <listbox><name>depending</name><args><disp>pass</disp><readonly>true</readonly><elements type="array">'.huixml_encode($depending_mods).'</elements><size>5</size></args></listbox>
          </children></vertframe>
        
          <vertframe row="1" col="1"><name>suggesting</name><children>
            <label><name>suggesting</name><args><label>'.sprintf($amp_locale -> GetStr('suggestingmods_label'), $module_data['modid']).'</label></args></label>
            <listbox><name>suggesting</name><args><disp>pass</disp><readonly>true</readonly><elements type="array">'.huixml_encode($suggesting_mods).'</elements><size>5</size></args></listbox>
          </children></vertframe>
        
          <vertframe row="2" col="0"><name>enabled</name><children>
            <label><name>enabled</name><args><label>'.$amp_locale -> GetStr('enabledsites_label').'</label></args></label>
            <listbox><name>enabled</name><args><disp>pass</disp><readonly>true</readonly><elements type="array">'.huixml_encode($enabled_sites).'</elements><size>5</size></args></listbox>
          </children></vertframe>';
    }

    $xml_def.= '</children></grid>';

    $hui_mainframe -> AddChild(new HuiXml('deps', array('definition' => $xml_def)));
    $hui_titlebar -> mTitle.= ' - '.$module_data['modid'].' - '.$amp_locale -> GetStr('moduledeps_title');
}

$main_disp -> AddEvent('modulehooks', 'main_modulehooks');
function main_modulehooks($eventData) {
    global $hui_mainframe, $amp_locale, $hui_titlebar;

    $modules = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT modid '.'FROM modules '.'WHERE id='.$eventData['modid']);

    $hook_events = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT function,event '.'FROM hookevents '.'WHERE functionmodule='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($modules -> Fields('modid')).' '.'ORDER BY function,event');

    $hooks = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT function,event,hookmodule '.'FROM hooks '.'WHERE functionmodule='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($modules -> Fields('modid')).' '.'ORDER BY hookmodule,function,event');

    $headers_events[0]['label'] = $amp_locale -> GetStr('hook_function.header');
    $headers_events[1]['label'] = $amp_locale -> GetStr('hook_event.header');

    $headers_hooks[0]['label'] = $amp_locale -> GetStr('hook_module.header');
    $headers_hooks[1]['label'] = $amp_locale -> GetStr('hook_function.header');
    $headers_hooks[2]['label'] = $amp_locale -> GetStr('hook_event.header');

    $xml_def = '<horizgroup>
      <args>
        <align>top</align>
      </args>
      <children>
    
        <vertgroup>
          <children>
    
            <label>
              <args>
                <label>'.huixml_cdata($amp_locale -> GetStr('hook_events.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
      <table>
      <name>hookevents</name>
      <args>
        <headers type="array">'.huixml_encode($headers_events).'</headers>
      </args>
      <children>';

    $row = 0;

    while (!$hook_events -> eof) {
        $xml_def.= '<label row="'.$row.'" col="0">
          <args>
            <label>'.huixml_cdata($hook_events -> Fields('function')).'</label>
            <compact>true</compact>
          </args>
        </label>
        <label row="'.$row.'" col="1">
          <args>
            <label>'.huixml_cdata($hook_events -> Fields('event')).'</label>
            <compact>true</compact>
          </args>
        </label>';

        $hook_events -> MoveNext();
        $row ++;
    }

    $xml_def.= '  </children>
    </table>
          </children>
        </vertgroup>
        <vertgroup>
          <children>
    
            <label>
              <args>
                <label>'.huixml_cdata($amp_locale -> GetStr('hooks.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
      <table>
      <name>hooks</name>
      <args>
        <headers type="array">'.huixml_encode($headers_hooks).'</headers>
      </args>
      <children>';

    $row = 0;

    while (!$hooks -> eof) {
        $xml_def.= '<label row="'.$row.'" col="0">
          <args>
            <label>'.huixml_cdata($hooks -> Fields('hookmodule')).'</label>
            <compact>true</compact>
          </args>
        </label>
        <label row="'.$row.'" col="1">
          <args>
            <label>'.huixml_cdata($hooks -> Fields('function')).'</label>
            <compact>true</compact>
          </args>
        </label>
        <label row="'.$row.'" col="2">
          <args>
            <label>'.huixml_cdata($hooks -> Fields('event')).'</label>
            <compact>true</compact>
          </args>
        </label>';

        $hooks -> MoveNext();
        $row ++;
    }

    $xml_def.= '  </children>
    </table>
          </children>
        </vertgroup>
      </children>
    </horizgroup>';
    $hui_mainframe -> AddChild(new HuiXml('deps', array('definition' => $xml_def)));
}

$main_disp -> AddEvent('modulelog', 'main_modulelog');
function main_modulelog($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $query = $env['ampdb'] -> execute('SELECT modid '.'FROM modules '.'WHERE id='.$eventData['modid']);

    $module_data = $query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $mod_log_content = '';

    if (file_exists(MODULE_PATH.$module_data['modid'].'/module.log')) {
            $log_toolbar = new HuiToolBar('logbar');

            $cleanlog_action = new HuiEventsCall();
            $cleanlog_action -> AddEvent(new HuiEvent('main', 'default', ''));
            $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleanmodlog', array('modid' => $module_data['modid'])));
            $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

            $log_toolbar -> AddChild($cleanlog_button);
            $log_frame = new HuiHorizFrame('logframe');
            $log_frame -> AddChild($log_toolbar);
            $hui_mainvertgroup -> AddChild($log_frame);

            $mod_log_content = file_get_contentes(MODULE_PATH.$module_data['modid'].'/module.log');
    }

    $hui_vgroup -> AddChild(new HuiText('modlog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($mod_log_content), 'rows' => '20', 'cols' => '120')), 0, 1);
    $hui_mainframe -> AddChild($hui_vgroup);
    $hui_titlebar -> mTitle.= ' - '.$module_data['modid'].' - '.$amp_locale -> GetStr('modlog.title');
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('modules_help', array('node' => 'ampoliros.root.modules.'.$eventData['node'], 'language' => AMP_LANG)));
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
