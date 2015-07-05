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
// $Id: ampinterface.php,v 1.23 2004-07-08 15:04:24 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
OpenLibrary('misc.library');
OpenLibrary('hui.library');
OpenLibrary('modules.library');
OpenLibrary('ampshared.library');

$amp_locale = new Locale('amp_root_interface', $gEnv['root']['locale']['language']);
$amp_log = new Logger(AMP_LOG);
$hui = new Hui($gEnv['root']['db']);
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

$menu_frame = new HuiHorizGroup('menuframe');
$menu_frame -> AddChild(new HuiMenu('mainmenu', array('menu' => get_ampoliros_root_menu_def($env['sitelocale']))));
$hui_mainvertgroup -> AddChild($menu_frame);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$default_action = new HuiEventsCall();
$default_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_defaultbutton = new HuiButton('defaultbutton', array('label' => $amp_locale -> GetStr('default_button'), 'themeimage' => 'thumbnail', 'horiz' => 'true', 'action' => $default_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_defaultbutton);

$themes_action = new HuiEventsCall();
$themes_action -> AddEvent(new HuiEvent('main', 'themes', ''));
$hui_themesbutton = new HuiButton('themesbutton', array('label' => $amp_locale -> GetStr('themes_button'), 'themeimage' => 'thumbnail', 'horiz' => 'true', 'action' => $themes_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_themesbutton);

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

$pass_disp -> AddEvent('setoem', 'pass_setoem');
function pass_setoem($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $gEnv;

    $log = new Logger(AMP_LOG);

    $mod_cfg = new ModuleConfig($gEnv['root']['db'], 'ampoliros');
    $mod_cfg -> SetKey('oem-name', $eventData['oemname']);
    $mod_cfg -> SetKey('oem-url', $eventData['oemurl']);

    if (strcmp($eventData['oembiglogo']['tmp_name'], 'none') != 0) {
        if (is_uploaded_file($eventData['oembiglogo']['tmp_name'])) {
            $extension = substr($eventData['oembiglogo']['name'], strrpos($eventData['oembiglogo']['name'], '.'));
            move_uploaded_file($eventData['oembiglogo']['tmp_name'], CGI_PATH.'oembiglogo'.$extension);
            $mod_cfg -> SetKey('oem-biglogo-filename', 'oembiglogo'.$extension);
        }
    }

    if (strcmp($eventData['oemlinklogo']['tmp_name'], 'none') != 0) {
        if (is_uploaded_file($eventData['oemlinklogo']['tmp_name'])) {
            $extension = substr($eventData['oemlinklogo']['name'], strrpos($eventData['oemlinklogo']['name'], '.'));
            move_uploaded_file($eventData['oemlinklogo']['tmp_name'], CGI_PATH.'oemlinklogo'.$extension);
            $mod_cfg -> SetKey('oem-link-filename', 'oemlinklogo'.$extension);
        }
    }

    $log -> LogEvent('Ampoliros', 'Changed OEM settings', LOGGER_NOTICE);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('iconsset_status');
    $hui_page -> mArgs['javascript'] = 'parent.frames.sum.location.reload()';
}

$pass_disp -> AddEvent('setenabledicons', 'pass_setenabledicons');
function pass_setenabledicons($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $gEnv;

    $log = new Logger(AMP_LOG);

    $mod_cfg = new ModuleConfig($gEnv['root']['db'], 'ampoliros');
    $mod_cfg -> SetKey('ampoliros-link-disabled', $eventData['ampicon'] == 'on' ? 0 : 1);
    $mod_cfg -> SetKey('solarix-link-disabled', $eventData['solarixicon'] == 'on' ? 0 : 1);
    $mod_cfg -> SetKey('oem-link-disabled', $eventData['oemicon'] == 'on' ? 0 : 1);
    $mod_cfg -> SetKey('ampoliros-biglogo-disabled', $eventData['ampbigicon'] == 'on' ? 0 : 1);
    $mod_cfg -> SetKey('oem-biglogo-disabled', $eventData['oembigicon'] == 'on' ? 0 : 1);

    $log -> LogEvent('Ampoliros', 'Changed Ampoliros interface settings', LOGGER_NOTICE);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('iconsset_status');
    $hui_page -> mArgs['javascript'] = 'parent.frames.sum.location.reload()';
}

$pass_disp -> AddEvent('settheme', 'pass_settheme');
function pass_settheme($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $gEnv;

    $log = new Logger(AMP_LOG);

    $mod_cfg = new ModuleConfig($gEnv['root']['db'], 'ampoliros');
    $mod_cfg -> SetKey('hui-root-theme', $eventData['theme']);

    $gEnv['hui']['theme']['name'] = $eventData['theme'];
    $gEnv['hui']['theme']['handler'] = new HuiTheme($gEnv['root']['db'], $eventData['theme']);

    $log -> LogEvent('Ampoliros', 'Changed Ampoliros theme', LOGGER_NOTICE);

    header('Location: '.build_events_call_string('', array(array('main', 'themes', ''), array('pass', 'settheme2', ''))));
}

$pass_disp -> AddEvent('settheme2', 'pass_settheme2');
function pass_settheme2($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $gEnv;

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('themeset_status');
    $hui_page -> mArgs['javascript'] = "parent.frames.sum.location.reload();\nparent.frames.header.location.reload()";
}
$pass_disp -> AddEvent('setadvanced', 'pass_setadvanced');
function pass_setadvanced($eventData) {
    global $hui_mainstatus, $amp_locale, $hui_page, $env, $hui_comments, $compressed_ob;

    $log = new Logger(AMP_LOG);

    $amp_cfg = new ConfigFile(AMP_CONFIG);
    $amp_cfg -> SetValue('AMP_HUI_COMMENTS', $eventData['hui-comments'] == 'on' ? '1' : '0');
    $amp_cfg -> SetValue('AMP_COMPRESSED_OB', $eventData['compressed-ob'] == 'on' ? '1' : '0');

    $hui_comments = 'false';
    $compressed_ob = 'false';

    if ($eventData['hui-comments'] == 'on')
        $hui_comments = 'true';
    if ($eventData['compressed-ob'] == 'on')
        $compressed_ob = 'true';

    $log -> LogEvent('Ampoliros', 'Changed Ampoliros advanced interface settings', LOGGER_NOTICE);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('advancedset_status');
    $hui_page -> mArgs['javascript'] = 'parent.frames.sum.location.reload()';
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

function interface_tab_action_builder($tab) {
    return build_events_call_string('', array(array('main', 'default', array('activetab' => $tab))));
}

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $hui_mainframe, $hui_titlebar, $amp_locale, $pass_disp, $hui_mainstatus;

    $mod_cfg = new ModuleConfig($env['ampdb'], 'ampoliros');

    // OEM
    //
    $oem_frame = new HuiVertFrame('oemframe');

    $oem_vgroup = new HuiVertGroup('oemvgroup', array('width' => '100%'));

    $oem_vgroup -> AddChild(new HuiLabel('oemlabel', array('label' => $amp_locale -> GetStr('oemframe_label'), 'bold' => 'true')));

    $oem_grid = new HuiGrid('oemgrid', array('rows' => '4', 'cols' => '2'));

    // OEM name
    //
    $oem_grid -> AddChild(new HuiLabel('oemname_label', array('label' => $amp_locale -> GetStr('oemname_label'))), 0, 0);

    $oem_grid -> AddChild(new HuiString('oemname', array('disp' => 'pass', 'size' => '30', 'value' => $mod_cfg -> GetKey('oem-name'))), 0, 1);

    // OEM url
    //
    $oem_grid -> AddChild(new HuiLabel('oemurl_label', array('label' => $amp_locale -> GetStr('oemurl_label'))), 1, 0);

    $oem_grid -> AddChild(new HuiString('oemurl', array('disp' => 'pass', 'size' => '30', 'value' => $mod_cfg -> GetKey('oem-url'))), 1, 1);

    // OEM big logo
    //
    $oem_grid -> AddChild(new HuiLabel('oembiglogo_label', array('label' => $amp_locale -> GetStr('oembiglogo_label'))), 2, 0);

    $oem_grid -> AddChild(new HuiFile('oembiglogo', array('disp' => 'pass')), 2, 1);

    // OEM link logo
    //
    $oem_grid -> AddChild(new HuiLabel('oemlogo_label', array('label' => $amp_locale -> GetStr('oemlogo_label'))), 3, 0);

    $oem_grid -> AddChild(new HuiFile('oemlinklogo', array('disp' => 'pass')), 3, 1);

    $oem_vgroup -> AddChild($oem_grid);

    $oem_vgroup -> AddChild(new HuiSubmit('oemsubmit', array('caption' => $amp_locale -> GetStr('oem_submit'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'setoem', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $oem_form = new HuiForm('setoemform', array('action' => $form_events_call -> GetEventsCallString()));
    $oem_form -> AddChild($oem_vgroup);

    // Enabled icons
    //
    $enable_vgroup = new HuiVertGroup('enablevgroup', array('width' => '100%'));

    $enable_vgroup -> AddChild(new HuiLabel('enablelabel', array('label' => $amp_locale -> GetStr('enabled_icons_label'), 'bold' => 'true')));

    $enable_grid = new HuiGrid('enablegrid', array('rows' => '5', 'cols' => '2'));

    // Ampoliros site link
    //
    $enable_grid -> AddChild(new HuiLabel('ampiconlabel', array('label' => $amp_locale -> GetStr('amp_link_enabled_label'))), 0, 1);

    $enable_grid -> AddChild(new HuiCheckBox('ampicon', array('disp' => 'pass', 'checked' => $mod_cfg -> GetKey('ampoliros-link-disabled') ? 'false' : 'true')), 0, 0);

    // Solarix site link
    //
    $enable_grid -> AddChild(new HuiLabel('solarixiconlabel', array('label' => $amp_locale -> GetStr('solarix_link_enabled_label'))), 1, 1);
    $enable_grid -> AddChild(new HuiCheckBox('solarixicon', array('disp' => 'pass', 'checked' => $mod_cfg -> GetKey('solarix-link-disabled') ? 'false' : 'true')), 1, 0);

    // OEM link
    //
    $enable_grid -> AddChild(new HuiLabel('oemiconlabel', array('label' => $amp_locale -> GetStr('oem_link_enabled_label'))), 2, 1);
    $enable_grid -> AddChild(new HuiCheckBox('oemicon', array('disp' => 'pass', 'checked' => $mod_cfg -> GetKey('oem-link-disabled') ? 'false' : 'true')), 2, 0);

    // Ampoliros big logo
    //
    $enable_grid -> AddChild(new HuiLabel('ampbigiconlabel', array('label' => $amp_locale -> GetStr('amp_biglogo_enabled_label'))), 3, 1);
    $enable_grid -> AddChild(new HuiCheckBox('ampbigicon', array('disp' => 'pass', 'checked' => $mod_cfg -> GetKey('ampoliros-biglogo-disabled') ? 'false' : 'true')), 3, 0);

    // OEM logo
    //
    $enable_grid -> AddChild(new HuiLabel('oemiconlabel', array('label' => $amp_locale -> GetStr('oem_biglogo_enabled_label'))), 4, 1);
    $enable_grid -> AddChild(new HuiCheckBox('oembigicon', array('disp' => 'pass', 'checked' => $mod_cfg -> GetKey('oem-biglogo-disabled') ? 'false' : 'true')), 4, 0);

    $enable_vgroup -> AddChild($enable_grid);

    $enable_vgroup -> AddChild(new HuiSubmit('enablesubmit', array('caption' => $amp_locale -> GetStr('enable_submit'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'setenabledicons', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $enable_form = new HuiForm('setenableform', array('action' => $form_events_call -> GetEventsCallString()));
    $enable_form -> AddChild($enable_vgroup);

    // Advanced settings
    //
    $advanced_vgroup = new HuiVertGroup('enablevgroup', array('width' => '100%'));

    $advanced_vgroup -> AddChild(new HuiLabel('enablelabel', array('label' => $amp_locale -> GetStr('advancedsettings_label'), 'bold' => 'true')));

    $advanced_grid = new HuiGrid('enablegrid', array('rows' => '2', 'cols' => '2'));

    // Compressed output buffering
    //
    $advanced_grid -> AddChild(new HuiLabel('compressed-ob-label', array('label' => $amp_locale -> GetStr('compressed-ob_label'))), 0, 1);

    if (!strlen($compressed_ob)) {
        if (AMP_COMPRESSED_OB)
            $compressed_ob = 'true';
        else
            $compressed_ob = 'false';
    }

    $advanced_grid -> AddChild(new HuiCheckBox('compressed-ob', array('disp' => 'pass', 'checked' => $compressed_ob)), 0, 0);

    // HUI code comments
    //
    $advanced_grid -> AddChild(new HuiLabel('hui-comments-label', array('label' => $amp_locale -> GetStr('hui-comments_label'))), 1, 1);

    if (!strlen($hui_comments)) {
        if (AMP_HUI_COMMENTS)
            $hui_comments = 'true';
        else
            $hui_comments = 'false';
    }

    $advanced_grid -> AddChild(new HuiCheckBox('hui-comments', array('disp' => 'pass', 'checked' => $hui_comments)), 1, 0);

    $advanced_vgroup -> AddChild($advanced_grid);

    $advanced_vgroup -> AddChild(new HuiSubmit('enablesubmit', array('caption' => $amp_locale -> GetStr('advanced_submit'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'setadvanced', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $advanced_form = new HuiForm('setenableform', array('action' => $form_events_call -> GetEventsCallString()));
    $advanced_form -> AddChild($advanced_vgroup);

    $tab_headers[0]['label'] = $amp_locale -> GetStr('oemframe_label');
    $tab_headers[1]['label'] = $amp_locale -> GetStr('enabled_icons_label');
    $tab_headers[2]['label'] = $amp_locale -> GetStr('advancedsettings_label');

    $tab = new HuiTab('interface', array('tabactionfunction' => 'interface_tab_action_builder', 'activetab' => (isset($eventData['activetab']) ? $eventData['activetab'] : ''), 'tabs' => $tab_headers));

    $tab -> AddChild($oem_form);
    $tab -> AddChild($enable_form);
    $tab -> AddChild($advanced_form);

    $hui_mainframe -> AddChild($tab);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('default_title');
}

$main_disp -> AddEvent('themes', 'main_themes');
function main_themes($eventData) {
    global $gEnv, $hui_mainframe, $hui_titlebar, $amp_locale;

    //$mod_cfg = new ModuleConfig( $env['ampdb'], 'ampoliros' );

    $themes_query = & $gEnv['root']['db'] -> Execute('SELECT name,catalog '.'FROM huithemes ');

    while (!$themes_query -> eof) {
        $tmp_locale = new Locale($themes_query -> Fields('catalog'), AMP_LANG);
        $elements[$themes_query -> Fields('name')] = $tmp_locale -> GetStr($themes_query -> Fields('name'));

        $themes_query -> MoveNext();
    }

    asort($elements);

    $xml_def = '<vertgroup><name>vgroup</name><args><halign>center</halign></args><children>
      <form><name>theme</name><args><action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'themes', ''), array('pass', 'settheme', '')))).'</action></args><children>
        <grid><name>themegrid</name><children>
          <label row="0" col="0"><name>themelabel</name><args><label type="encoded">'.urlencode($amp_locale -> GetStr('themes_label')).'</label></args></label>
          <listbox row="1" col="0"><name>theme</name><args><elements type="array">'.huixml_encode($elements).'</elements><default>'. ($gEnv['hui']['theme']['name'] ? $gEnv['hui']['theme']['name'] : $gEnv['hui']['theme']['default']).'</default><disp>pass</disp><size>10</size></args></listbox>
        </children></grid>
        <submit><name>submit</name><args><caption type="encoded">'.urlencode($amp_locale -> GetStr('settheme_submit')).'</caption></args></submit>
      </children></form>
    </children></vertgroup>';

    $hui_mainframe -> AddChild(new HuiXml('page', array('definition' => $xml_def)));

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('themes_title');
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $gEnv, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('locale_help', array('node' => 'ampoliros.root.interface.'.$eventData['node'], 'language' => AMP_LANG)));
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
