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
// $Id: info.php,v 1.26 2004-07-08 15:04:24 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.locale.Locale');
Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.hui.Hui');
OpenLibrary('modules.library');
OpenLibrary('misc.library');
OpenLibrary('ampshared.library');

$log = new logger(AMP_LOG);
$amp_locale = new locale('amp_root_info', $env['amplocale']);
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('info_pagetitle')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('info_pagetitle'), 'icon' => 'help_index'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

$menu_frame = new HuiHorizGroup('menuframe');
$menu_frame -> AddChild(new HuiMenu('magellanmainmenu', array('menu' => get_ampoliros_root_menu_def($env['sitelocale']))));
$hui_mainvertgroup -> AddChild($menu_frame);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$version_action = new HuiEventsCall();
$version_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_versionbutton = new HuiButton('versionbutton', array('label' => $amp_locale -> GetStr('version_button'), 'themeimage' => 'contents', 'action' => $version_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_versionbutton);

$sysinfo_action = new HuiEventsCall();
$sysinfo_action -> AddEvent(new HuiEvent('main', 'sysinfo', ''));
$hui_sysinfobutton = new HuiButton('sysinfobutton', array('label' => $amp_locale -> GetStr('sysinfo_button'), 'themeimage' => 'configure', 'action' => $sysinfo_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_sysinfobutton);

$phpinfo_action = new HuiEventsCall();
$phpinfo_action -> AddEvent(new HuiEvent('main', 'phpinfo', ''));
$hui_phpinfobutton = new HuiButton('phpinfobutton', array('label' => $amp_locale -> GetStr('phpinfo_button'), 'themeimage' => 'run', 'action' => $phpinfo_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_phpinfobutton);

$license_action = new HuiEventsCall();
$license_action -> AddEvent(new HuiEvent('main', 'license', ''));
$hui_licensebutton = new HuiButton('licensebutton', array('label' => $amp_locale -> GetStr('license_button'), 'themeimage' => 'configure', 'action' => $license_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_licensebutton);

$changes_action = new HuiEventsCall();
$changes_action -> AddEvent(new HuiEvent('main', 'changes', ''));
$hui_changesbutton = new HuiButton('changesbutton', array('label' => $amp_locale -> GetStr('changes_button'), 'themeimage' => 'contents', 'action' => $changes_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_changesbutton);

if (file_exists(AMP_LOG)) {
    $amplog_action = new HuiEventsCall();
    $amplog_action -> AddEvent(new HuiEvent('main', 'showamplog', ''));
    $hui_amplog_button = new HuiButton('amplogbutton', array('label' => $amp_locale -> GetStr('amplog_button'), 'themeimage' => 'toggle_log', 'action' => $amplog_action -> GetEventsCallString()));
    $hui_maintoolbar -> AddChild($hui_amplog_button);
}

if (file_exists(AMP_REMOTE_LOG)) {
    $ampremotelog_action = new HuiEventsCall();
    $ampremotelog_action -> AddEvent(new HuiEvent('main', 'showampremotelog', ''));
    $hui_ampremotelog_button = new HuiButton('amplogbutton', array('label' => $amp_locale -> GetStr('ampremotelog_button'), 'themeimage' => 'toggle_log', 'action' => $ampremotelog_action -> GetEventsCallString()));
    $hui_maintoolbar -> AddChild($hui_ampremotelog_button);
}

if (file_exists(AMP_DBLOG)) {
    $ampdblog_action = new HuiEventsCall();
    $ampdblog_action -> AddEvent(new HuiEvent('main', 'showampdblog', ''));
    $hui_ampdblog_button = new HuiButton('ampdblogbutton', array('label' => $amp_locale -> GetStr('ampdblog_button'), 'themeimage' => 'toggle_log', 'action' => $ampdblog_action -> GetEventsCallString()));
    $hui_maintoolbar -> AddChild($hui_ampdblog_button);
}

if (file_exists(PHP_LOG)) {
    $phplog_action = new HuiEventsCall();
    $phplog_action -> AddEvent(new HuiEvent('main', 'showphplog', ''));
    $hui_phplog_button = new HuiButton('phplogbutton', array('label' => $amp_locale -> GetStr('phplog_button'), 'themeimage' => 'toggle_log', 'action' => $phplog_action -> GetEventsCallString()));
    $hui_maintoolbar -> AddChild($hui_phplog_button);
}

// Help tool bar
//
$hui_helptoolbar = new HuiToolBar('helpbar');

$main_disp = new HuiDispatcher('main');
$event_name = $main_disp -> GetEventName();

if (strcmp($event_name, 'help')) {
    $help_action = new HuiEventsCall();
    $help_action -> AddEvent(new HuiEvent('main', 'help', array('node' => $event_name)));
    $hui_helpbutton = new HuiButton('helpbutton', array('label' => $amp_locale -> GetStr('help_button'), 'themeimage' => 'help', 'action' => $help_action -> GetEventsCallString()));

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

$pass_disp -> AddEvent('cleanamplog', 'pass_cleanamplog');
function pass_cleanamplog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $temp_log = new Logger(AMP_LOG);

    if ($temp_log -> CleanLog()) {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('logcleaned_status');
    } else {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('lognotcleaned_status');
    }
}

$pass_disp -> AddEvent('cleanampdblog', 'pass_cleanampdblog');
function pass_cleanampdblog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $temp_log = new Logger(AMP_DBLOG);

    if ($temp_log -> CleanLog()) {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('logcleaned_status');
    } else {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('lognotcleaned_status');
    }
}

$pass_disp -> AddEvent('cleanampremotelog', 'pass_cleanampremotelog');
function pass_cleanampremotelog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $temp_log = new Logger(AMP_REMOTE_LOG);

    if ($temp_log -> CleanLog()) {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('logcleaned_status');
    } else {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('lognotcleaned_status');
    }
}

$pass_disp -> AddEvent('cleanphplog', 'pass_cleanphplog');
function pass_cleanphplog($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $temp_log = new Logger(PHP_LOG);

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

$main_disp -> AddEvent('sysinfo', 'main_sysinfo');
function main_sysinfo($eventData) {
    global $env, $hui_mainframe, $amp_locale, $pass_disp, $hui_mainstatus;

    /*$headers[0]['label'] = $amp_locale->GetStr( 'status_header' );
    $headers[1]['label'] = $amp_locale->GetStr( 'moduleid_header' );
    $headers[2]['label'] = $amp_locale->GetStr( 'modactivationdate_header' );
    $headers[3]['label'] = $amp_locale->GetStr( 'moddeps_header' );*/

    $hui_info_table = new HuiTable('sysinfotable', '');

    // Required features

    // /ampcgi/ alias
    //
    $row = 0;

    if (@ fopen(((isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').AMP_HOST.CGI_URL.'clear.gif', 'r')) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('ampcgi_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
        $check_result = sprintf($amp_locale -> GetStr('ampcgi_not_available_label'), CGI_URL, AMP_HOST.CGI_URL);
    }
    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('required_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => sprintf($amp_locale -> GetStr('ampcgi_test_label'), CGI_URL))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // PHP version check
    //
    $row ++;

    //if ( ereg( '[4-9]\.[0-9]\.[5-9].*', phpversion() ) or ereg( '[4-9]\.[1-9]\.[0-9].*', phpversion() ) )
    if (ereg("[5-9]\.[0-9]\.[0-9].*", phpversion())) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = sprintf($amp_locale -> GetStr('php_available_label'), phpversion());
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
        $check_result = sprintf($amp_locale -> GetStr('php_not_available_label'), phpversion());
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('required_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('php_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // File upload support
    //
    $row ++;

    if (ini_get('file_uploads') == '1') {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('fileupload_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
        $check_result = $amp_locale -> GetStr('fileupload_not_available_label');
        $systemok = false;
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('required_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('fileupload_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // XML support
    //
    $row ++;

    if (function_exists('xml_set_object')) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('xml_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
        $check_result = $amp_locale -> GetStr('xml_not_available_label');
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('required_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('xml_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // Zlib support
    //
    $row ++;

    if (function_exists('gzinflate')) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('zlib_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
        $check_result = $amp_locale -> GetStr('zlib_not_available_label');
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('required_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('zlib_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // Database support
    //
    $row ++;

    if (function_exists('mysql_connect') or function_exists('pg_connect')) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('db_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
        $check_result = $amp_locale -> GetStr('db_not_available_label');
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('required_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('db_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // Optional features

    // Crontab
    //
    $row ++;

    if (strlen(ROOTCRONTAB)) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('crontab_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['goldball'];
        $check_result = $amp_locale -> GetStr('crontab_not_available_label');
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('optional_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('crontab_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // XMLRPC auth
    //
    $row ++;

    if (php_sapi_name() != 'cgi') {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('xmlrpc_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['goldball'];
        $check_result = $amp_locale -> GetStr('xmlrpc_not_available_label');
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('optional_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('xmlrpc_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    // XMLRPC curl
    //
    $row ++;

    if (function_exists('curl_init')) {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
        $check_result = $amp_locale -> GetStr('xmlrpc_ssl_available_label');
    } else {
        $ball = $hui_mainframe -> mThemeHandler -> mStyle['goldball'];
        $check_result = $amp_locale -> GetStr('xmlrpc_ssl_not_available_label');
    }

    $hui_info_table -> AddChild(new HuiLabel('required'.$row, array('label' => $amp_locale -> GetStr('optional_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $ball)), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('xmlrpc_ssl_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => $check_result)), $row, 3);

    /*
    	// Windows COM auth
    	//
    	$row++;
    
    	if ( function_exists( 'com_load' ) )
    	{
    		$ball = $hui_mainframe->mThemeHandler->mStyle['greenball'];
    		$check_result = $amp_locale->GetStr( 'com_available_label' );
    	}
    	else
            {
                $ball = $hui_mainframe->mThemeHandler->mStyle['goldball'];
    		$check_result = $amp_locale->GetStr( 'com_not_available_label' );
    	}
    
    	$hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'optional_label' ) ) ), $row, 0 );
    	$hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
    	$hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'com_test_label' ) ) ), $row, 2 );
    	$hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );
    */
    // Informations

    // Operating system
    //
    $row ++;

    $hui_info_table -> AddChild(new HuiLabel('info'.$row, array('label' => $amp_locale -> GetStr('info_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['greenball'])), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('os_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => PHP_OS)), $row, 3);

    // Web server interface
    //
    $row ++;

    $hui_info_table -> AddChild(new HuiLabel('info'.$row, array('label' => $amp_locale -> GetStr('info_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['greenball'])), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('webserver_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => php_sapi_name())), $row, 3);

    // Current user
    //
    $row ++;

    $hui_info_table -> AddChild(new HuiLabel('info'.$row, array('label' => $amp_locale -> GetStr('info_label'))), $row, 0);
    $hui_info_table -> AddChild(new HuiImage('status'.$row, array('imageurl' => $hui_mainframe -> mThemeHandler -> mStyle['greenball'])), $row, 1);
    $hui_info_table -> AddChild(new HuiLabel('ampcgi'.$row, array('label' => $amp_locale -> GetStr('user_test_label'))), $row, 2);
    $hui_info_table -> AddChild(new HuiLabel('checkresult'.$row, array('label' => get_current_user())), $row, 3);

    $hui_mainframe -> AddChild($hui_info_table);
}

$main_disp -> AddEvent('showamplog', 'main_showamplog');
function main_showamplog($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $log_content = '';

    if (file_exists(AMP_LOG)) {
        $log_toolbar = new HuiToolBar('logbar');

        $cleanlog_action = new HuiEventsCall();
        $cleanlog_action -> AddEvent(new HuiEvent('main', 'showamplog', ''));
        $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleanamplog', ''));
        $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

        $log_toolbar -> AddChild($cleanlog_button);
        $log_frame = new HuiHorizFrame('logframe');
        $log_frame -> AddChild($log_toolbar);
        $hui_mainvertgroup -> AddChild($log_frame);

        if (file_exists(AMP_LOG)) {
            $log_content = file_get_contents(AMP_LOG);
        }
    }

    $hui_vgroup -> AddChild(new HuiText('amplog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($log_content), 'rows' => '20', 'cols' => '120')), 0, 1);

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('amplog_title');
}

$main_disp -> AddEvent('showampremotelog', 'main_showampremotelog');
function main_showampremotelog($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $log_content = '';

    if (file_exists(AMP_REMOTE_LOG)) {
        $log_toolbar = new HuiToolBar('logbar');

        $cleanlog_action = new HuiEventsCall();
        $cleanlog_action -> AddEvent(new HuiEvent('main', 'showampremotelog', ''));
        $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleanampremotelog', ''));
        $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

        $log_toolbar -> AddChild($cleanlog_button);
        $log_frame = new HuiHorizFrame('logframe');
        $log_frame -> AddChild($log_toolbar);
        $hui_mainvertgroup -> AddChild($log_frame);

        if (file_exists(AMP_REMOTE_LOG)) {
            $log_content = file_get_contents(AMP_REMOTE_LOG);
        }
    }

    $hui_vgroup -> AddChild(new HuiText('amplog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($log_content), 'rows' => '20', 'cols' => '120')), 0, 1);

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('ampremotelog_title');
}

$main_disp -> AddEvent('showampdblog', 'main_showampdblog');
function main_showampdblog($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $log_content = '';

    if (file_exists(AMP_DBLOG)) {
        $log_toolbar = new HuiToolBar('logbar');

        $cleanlog_action = new HuiEventsCall();
        $cleanlog_action -> AddEvent(new HuiEvent('main', 'showampdblog', ''));
        $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleanampdblog', ''));
        $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

        $log_toolbar -> AddChild($cleanlog_button);
        $log_frame = new HuiHorizFrame('logframe');
        $log_frame -> AddChild($log_toolbar);
        $hui_mainvertgroup -> AddChild($log_frame);

        if (file_Exists(AMP_DBLOG)) {
            $log_content = file_get_contents(AMP_DBLOG);
        }
    }

    $hui_vgroup -> AddChild(new HuiText('ampdblog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($log_content), 'rows' => '20', 'cols' => '120')), 0, 1);

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('ampdblog_title');
}

$main_disp -> AddEvent('showphplog', 'main_showphplog');
function main_showphplog($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar, $hui_mainvertgroup;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $log_content = '';

    if (file_exists(PHP_LOG)) {
        $log_toolbar = new HuiToolBar('logbar');

        $cleanlog_action = new HuiEventsCall();
        $cleanlog_action -> AddEvent(new HuiEvent('main', 'showphplog', ''));
        $cleanlog_action -> AddEvent(new HuiEvent('pass', 'cleanphplog', ''));
        $cleanlog_button = new HuiButton('cleanlogbutton', array('label' => $amp_locale -> GetStr('cleanlog_button'), 'themeimage' => 'editdelete', 'action' => $cleanlog_action -> GetEventsCallString()));

        $log_toolbar -> AddChild($cleanlog_button);
        $log_frame = new HuiHorizFrame('logframe');
        $log_frame -> AddChild($log_toolbar);
        $hui_mainvertgroup -> AddChild($log_frame);

        if (file_exists(PHP_LOG)) {
            $log_content = file_get_contents(PHP_LOG);
        }
    }

    $hui_vgroup -> AddChild(new HuiText('phplog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($log_content), 'rows' => '20', 'cols' => '120')), 0, 1);
    $hui_mainframe -> AddChild($hui_vgroup);
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('phplog_title');
}

$main_disp -> AddEvent('license', 'main_license');
function main_license($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $license_content = '';

    if (file_exists(PRIVATE_TREE.'LICENSE')) {
        $license_content = file_get_contents(PRIVATE_TREE.'LICENSE');
    }

    $hui_vgroup -> AddChild(new HuiText('license', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($license_content), 'rows' => '20', 'cols' => '80')), 0, 1);

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('license_title');
}

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $version_content = '';

    if (file_exists(PRIVATE_TREE.'VERSION')) {
        $version_content = file_get_contents(PRIVATE_TREE.'VERSION');
    }

    $hui_vgroup -> AddChild(new HuiText('version', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($version_content), 'rows' => '20', 'cols' => '80')), 0, 1);
    $hui_mainframe -> AddChild($hui_vgroup);
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('version_title');
}

$main_disp -> AddEvent('changes', 'main_changes');
function main_changes($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $changes_content = '';
    if (file_exists(PRIVATE_TREE.'CHANGES')) {
        $changes_content = file_get_contents(PRIVATE_TREE.'CHANGES');
    }

    $hui_vgroup -> AddChild(new HuiText('changelog', array('disp' => 'pass', 'readonly' => 'true', 'value' => htmlentities($changes_content), 'rows' => '20', 'cols' => '80')), 0, 1);
    $hui_mainframe -> AddChild($hui_vgroup);
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('changes_title');
}

$main_disp -> AddEvent('phpinfo', 'main_phpinfo');
function main_phpinfo($eventData) {
    phpinfo();
    $carthag = Carthag::instance();
    $carthag->halt();
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('informations_help', array('node' => 'ampoliros.root.informations.'.$eventData['node'], 'language' => AMP_LANG)));
}

$main_disp -> AddEvent('about', 'main_about');
function main_about($eventData) {
    global $hui_mainframe, $amp_locale, $hui_titlebar;

    $xml_def = '<vertgroup>
          <args>
            <groupalign>center</groupalign>
            <align>center</align>
          </args>
          <children>
        
            <image>
              <args>
                <imageurl type="encoded">'.urlencode(CGI_URL.'ampoliros_logo.png').'</imageurl>
                <width>250</width>
                <height>80</height>
              </args>
            </image>
        
            <label>
              <args>
                <label type="encoded">'.urlencode($amp_locale -> GetStr('ampoliros_copyright.label')).'</label>
              </args>
            </label>
        
            <link>
              <args>
                <label type="encoded">'.urlencode('www.solarix.it/prodotti/ampoliros/').'</label>
                <link type="encoded">'.urlencode('http://www.solarix.it/prodotti/ampoliros/').'</link>
                <target>_blank</target>
              </args>
            </link>
        
          </children>
        </vertgroup>';

    $hui_mainframe -> AddChild(new HuiXml('page', array('definition' => $xml_def)));
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
