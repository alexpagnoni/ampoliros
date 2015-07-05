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
// $Id: maintenance.php,v 1.19 2004-07-08 15:04:24 alex Exp $

// ----- Initialization -----
//
require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.locale.Locale');
Carthag :: import('com.solarix.ampoliros.locale.LocaleCountry');
Carthag :: import('com.solarix.ampoliros.hui.Hui');
Carthag :: import('com.solarix.ampoliros.hui.HuiEventsCall');
OpenLibrary('ampshared.library');

Carthag :: import('com.solarix.ampoliros.maintenance.AmpolirosMaintenanceHandler');

$gLocale = new Locale('amp_root_maintenance', $gEnv['root']['locale']['language']);

$gHui = new Hui($gEnv['root']['db']);
$gHui -> LoadWidget('xml');
$gHui -> LoadWidget('amppage');
$gHui -> LoadWidget('amptoolbar');

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale -> GetStr('maintenance.title');

$gToolbars['main'] = array('ampoliros' => array('label' => $gLocale -> GetStr('general.toolbar'), 'themeimage' => 'configure', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'default', '')))), 'general' => array('label' => $gLocale -> GetStr('ampoliros.toolbar'), 'themeimage' => 'configure', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'ampoliros', '')))));

$gToolbars['help'] = array('help' => array('label' => $gLocale -> GetStr('help.toolbar'), 'themeimage' => 'help', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'help', '')))));

// ----- Action dispatcher -----
//
$gAction_disp = new HuiDispatcher('action');

$gAction_disp -> AddEvent('clear_systemlogs', 'action_clear_systemlogs');
function action_clear_systemlogs($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_logs.maintenance', HANDLER_PATH);
    $maint = new AmpolirosLogsMaintenance();
    $maint -> mCleanAmpLog = true;
    $maint -> mCleanAmpDbLog = true;
    $maint -> mCleanPhpLog = true;
    $maint -> mCleanRemoteLog = true;
    $maint -> mCleanAccessLog = true;
    $maint -> CleanSystemLogs();

    $gPage_status = $gLocale -> GetStr('systemlogs_cleaned.status');
}

$gAction_disp -> AddEvent('clear_siteslogs', 'action_clear_siteslogs');
function action_clear_siteslogs($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_logs.maintenance', HANDLER_PATH);
    $maint = new AmpolirosLogsMaintenance();

    $maint -> CleanSitesLogs();

    $gPage_status = $gLocale -> GetStr('siteslogs_cleaned.status');
}

$gAction_disp -> AddEvent('clear_cache', 'action_clear_cache');
function action_clear_cache($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> CleanCache();

    $gPage_status = $gLocale -> GetStr('cache_cleaned.status');
}

$gAction_disp -> AddEvent('clear_pidfiles', 'action_clear_pidfiles');
function action_clear_pidfiles($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> CleanPidFiles();

    $gPage_status = $gLocale -> GetStr('pidfiles_cleaned.status');
}

$gAction_disp -> AddEvent('clear_sessions', 'action_clear_sessions');
function action_clear_sessions($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> CleanSessions();

    $gPage_status = $gLocale -> GetStr('sessions_cleaned.status');
}

$gAction_disp -> AddEvent('clear_tempdirs', 'action_clear_tempdirs');
function action_clear_tempdirs($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> CleanAmpTempDirs();

    $gPage_status = $gLocale -> GetStr('tempdirs_cleaned.status');
}

$gAction_disp -> AddEvent('clear_clipboard', 'action_clear_clipboard');
function action_clear_clipboard($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> CleanClipboard();

    $gPage_status = $gLocale -> GetStr('clipboard_cleaned.status');
}

$gAction_disp -> AddEvent('clear_all', 'action_clear_all');
function action_clear_all($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> CleanCache();
    $maint -> CleanSessions();
    $maint -> CleanPidFiles();
    $maint -> CleanAmpTempDirs();
    $maint -> CleanClipboard();

    OpenLibrary('ampoliros_logs.maintenance', HANDLER_PATH);
    $maint = new AmpolirosLogsMaintenance();
    $maint -> mCleanAmpLog = true;
    $maint -> mCleanAmpDbLog = true;
    $maint -> mCleanPhpLog = true;
    $maint -> mCleanRemoteLog = true;
    $maint -> mCleanAccessLog = true;
    $maint -> CleanSystemLogs();
    $maint -> CleanSitesLogs();

    $gPage_status = $gLocale -> GetStr('all_cleaned.status');
}

$gAction_disp -> AddEvent('set_ampoliros', 'action_set_ampoliros');
function action_set_ampoliros($eventData) {
    global $gPage_status, $gLocale;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    $maint = new AmpolirosCacheMaintenance();
    $maint -> SetCleanCache(isset($eventData['cache']) and $eventData['cache'] == 'on' ? true : false);
    $maint -> SetCleanSessions(isset($eventData['sessions']) and $eventData['sessions'] == 'on' ? true : false);
    $maint -> SetCleanPidFiles(isset($eventData['pidfiles']) and $eventData['pidfiles'] == 'on' ? true : false);
    $maint -> SetCleanAmpTempDirs(isset($eventData['amptempdirs']) and $eventData['amptempdirs'] == 'on' ? true : false);
    $maint -> SetCleanClipboard(isset($eventData['clipboard']) and $eventData['clipboard'] == 'on' ? true : false);

    OpenLibrary('ampoliros_logs.maintenance', HANDLER_PATH);
    $maint = new AmpolirosLogsMaintenance();

    switch ($eventData['amplog']) {
        case 'clean' :
            $maint -> SetCleanAmpLog(true);
            $maint -> SetRotateAmpLog(false);
            break;
        case 'rotate' :
            $maint -> SetCleanAmpLog(false);
            $maint -> SetRotateAmpLog(true);
            break;
        case 'leave' :
            $maint -> SetCleanAmpLog(false);
            $maint -> SetRotateAmpLog(false);
            break;
    }

    switch ($eventData['ampdblog']) {
        case 'clean' :
            $maint -> SetCleanAmpdbLog(true);
            $maint -> SetRotateAmpdbLog(false);
            break;
        case 'rotate' :
            $maint -> SetCleanAmpdbLog(false);
            $maint -> SetRotateAmpdbLog(true);
            break;
        case 'leave' :
            $maint -> SetCleanAmpdbLog(false);
            $maint -> SetRotateAmpdbLog(false);
            break;
    }

    switch ($eventData['phplog']) {
        case 'clean' :
            $maint -> SetCleanphpLog(true);
            $maint -> SetRotatephpLog(false);
            break;
        case 'rotate' :
            $maint -> SetCleanphpLog(false);
            $maint -> SetRotatephpLog(true);
            break;
        case 'leave' :
            $maint -> SetCleanphpLog(false);
            $maint -> SetRotatephpLog(false);
            break;
    }

    switch ($eventData['remotelog']) {
        case 'clean' :
            $maint -> SetCleanremotelog(true);
            $maint -> SetRotateremotelog(false);
            break;
        case 'rotate' :
            $maint -> SetCleanremotelog(false);
            $maint -> SetRotateremotelog(true);
            break;
        case 'leave' :
            $maint -> SetCleanremotelog(false);
            $maint -> SetRotateremotelog(false);
            break;
    }

    switch ($eventData['accesslog']) {
        case 'clean' :
            $maint -> SetCleanaccesslog(true);
            $maint -> SetRotateaccesslog(false);
            break;
        case 'rotate' :
            $maint -> SetCleanaccesslog(false);
            $maint -> SetRotateaccesslog(true);
            break;
        case 'leave' :
            $maint -> SetCleanaccesslog(false);
            $maint -> SetRotateaccesslog(false);
            break;
    }

    switch ($eventData['siteslogs']) {
        case 'clean' :
            $maint -> SetCleansiteslogs(true);
            $maint -> SetRotatesiteslogs(false);
            break;
        case 'rotate' :
            $maint -> SetCleansiteslogs(false);
            $maint -> SetRotatesiteslogs(true);
            break;
        case 'leave' :
            $maint -> SetCleansiteslogs(false);
            $maint -> SetRotatesiteslogs(false);
            break;
    }

    $gPage_status = $gLocale -> GetStr('settings_set.status');
}

$gAction_disp -> AddEvent('set_report', 'action_set_report');
function action_set_report($eventData) {
    global $gPage_status, $gLocale;

    $main = new AmpolirosMaintenanceHandler();

    if (isset($eventData['reportenabled']) and $eventData['reportenabled'] == 'on') {
        $main -> EnableReports();
    } else {
        $main -> DisableReports();
    }

    $main -> SetReportsEmail($eventData['reportemail']);

    $gPage_status = $gLocale -> GetStr('settings_set.status');
}

$gAction_disp -> AddEvent('set_general', 'action_set_general');
function action_set_general($eventData) {
    global $gPage_status, $gLocale;

    $main = new AmpolirosMaintenanceHandler();
    $tasks = $main -> GetTasksList();

    foreach ($tasks as $task) {
        if (isset($eventData[$task['name'].'_task']) and $eventData[$task['name'].'_task'] == 'on') {
            $main -> EnableTask($task['name']);
        } else {
            $main -> DisableTask($task['name']);
        }
    }

    $gPage_status = $gLocale -> GetStr('settings_set.status');
}

$gAction_disp -> AddEvent('run_maintenance', 'action_run_maintenance');
function action_run_maintenance($eventData) {
    global $gPage_status, $gLocale;

    require ('ampmaintenance.php');

    $gEnv['core']['interface'] = AMP_INTERFACE_WEB;

    $gPage_status = $gLocale -> GetStr('maintenance_done.status');
}

$gAction_disp -> Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new HuiDispatcher('main');

function general_tab_builder($tab) {
    return build_events_call_string('', array(array('main', 'default', array('tab' => $tab))));
}

$gMain_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $gEnv, $gXml_def, $gLocale, $gPage_title;

    $main = new AmpolirosMaintenanceHandler();
    $main_time = $main -> GetLastMaintenanceTime();
    $tasks = $main -> GetTasksList();

    $tabs[0]['label'] = $gLocale -> GetStr('general_status.tab');
    $tabs[1]['label'] = $gLocale -> GetStr('general_report.tab');
    $tabs[2]['label'] = $gLocale -> GetStr('general_tasks.tab');

    $country = new LocaleCountry($GLOBALS['gEnv']['root']['locale']['country']);

    $date_array = $country -> GetDateArrayFromUnixTimestamp($main_time);

    $row = 0;

    $gXml_def = '<vertgroup>
      <children>
    
      <tab><name>general</name>
        <args>
          <tabs type="array">'.huixml_encode($tabs).'</tabs>
          <tabactionfunction>general_tab_builder</tabactionfunction>
          <activetab>'. (isset($eventData['tab']) ? $eventData['tab'] : '').'</activetab>
        </args>
        <children>
    
        <vertgroup>
          <children>
    
            <label><name>status</name>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('status.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
        <horizgroup>
          <children>
    
            <label>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('last_maintenance.label')).'</label>
              </args>
            </label>
    
            <date>
              <args>
                <readonly>true</readonly>
                <type>date</type>
                <value type="array">'.huixml_encode($date_array).'</value>
              </args>
            </date>
    
            <date>
              <args>
                <readonly>true</readonly>
                <type>time</type>
                <value type="array">'.huixml_encode($date_array).'</value>
              </args>
            </date>
    
          </children>
        </horizgroup>
    
            <horizbar/>';

    if (isset($GLOBALS['gEnv']['runtime']['maintenance']['result'])) {
        $row = 0;

        $gXml_def.= '
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('report.label')).'</label>
                    <bold>true</bold>
                  </args>
                </label>
        <grid><children>';

        foreach ($GLOBALS['gEnv']['runtime']['maintenance']['result'] as $task => $result) {
            $gXml_def.= '<label row="'.$row.'" col="0">
              <args>
                <nowrap>true</nowrap>
                <label type="encoded">'.urlencode($tasks[$task]['description']).'</label>
              </args>
            </label>
            <button row="'.$row.'" col="1">
              <args>
                <themeimage>'. ($result ? 'button_ok' : 'button_cancel').'</themeimage>
                <disabled>true</disabled>
              </args>
            </button>';
            $row ++;
        }

        $gXml_def.= '</children></grid><horizbar/>';
    }

    $gXml_def.= '        <button>
              <args>
                <themeimage>button_ok</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('run_maintenance.button')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'run_maintenance')))).'</action>
              </args>
            </button>
    
          </children>
        </vertgroup>
    
        <vertgroup>
          <children>
    
            <label>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('report.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <form><name>report</name>
              <args>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'set_report')))).'</action>
              </args>
              <children>
    
            <grid>
              <children>
    
                <label row="0" col="0">
                  <args>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('report_enabled.label')).'</label>
                  </args>
                </label>
    
                <checkbox row="0" col="1"><name>reportenabled</name>
                  <args>
                    <disp>action</disp>
                    <checked>'. ($main -> GetReportsEnableStatus() ? 'true' : 'false').'</checked>
                  </args>
                </checkbox>
    
                <label row="1" col="0">
                  <args>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('report_email.label')).'</label>
                  </args>
                </label>
    
                <string row="1" col="1"><name>reportemail</name>
                  <args>
                    <disp>action</disp>
                    <value type="encoded">'.urlencode($main -> GetReportsEmail()).'</value>
                    <size>25</size>
                  </args>
                </string>
    
              </children>
            </grid>
    
            </children>
            </form>
    
            <horizbar/>
    
            <button>
              <args>
                <themeimage>button_ok</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('apply.button')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <formsubmit>report</formsubmit>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'set_report')))).'</action>
              </args>
            </button>
    
          </children>
        </vertgroup>
    
        <vertgroup>
          <children>
    
            <label>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('scheduled_tasks.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <form><name>settings</name>
              <args>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'set_general')))).'</action>
              </args>
              <children>
    
                <grid>
                  <children>';

    reset($tasks);

    foreach ($tasks as $task) {
        $gXml_def.= '<checkbox row="'.$row.'" col="0"><name type="encoded">'.urlencode($task['name'].'_task').'</name>
          <args>
            <disp>action</disp>
            <checked>'. ($task['enabled'] ? 'true' : 'false').'</checked>
          </args>
        </checkbox>
        <label row="'.$row.'" col="1">
          <args>
            <label type="encoded">'.urlencode($task['description']).'</label>
            <nowrap>false</nowrap>
          </args>
        </label>';

        $row ++;
    }

    $gXml_def.= '              </children>
                </grid>
    
              </children>
            </form>
    
        <horizbar/>
    
            <button>
              <args>
                <themeimage>button_ok</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('apply.button')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <formsubmit>settings</formsubmit>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'set_general')))).'</action>
              </args>
            </button>
    
          </children>
          </vertgroup>
    
          </children>
        </tab>
    
      </children>
    </vertgroup>';

    $gPage_title.= ' - '.$gLocale -> GetStr('general.title');
}

function ampoliros_tab_builder($tab) {
    return build_events_call_string('', array(array('main', 'ampoliros', array('tab' => $tab))));
}

$gMain_disp -> AddEvent('ampoliros', 'main_ampoliros');
function main_ampoliros($eventData) {
    global $gEnv, $gXml_def, $gLocale, $gPage_title;

    OpenLibrary('ampoliros_cache.maintenance', HANDLER_PATH);
    OpenLibrary('ampoliros_logs.maintenance', HANDLER_PATH);

    $country = new LocaleCountry($GLOBALS['gEnv']['root']['locale']['country']);

    $tabs[0]['label'] = $gLocale -> GetStr('ampoliros_status.tab');
    $tabs[1]['label'] = $gLocale -> GetStr('ampoliros_settings.tab');

    $logs_main = new AmpolirosLogsMaintenance();
    $cache_main = new AmpolirosCacheMaintenance();

    $gXml_def = '<tab><name>ampoliros</name>
      <args>
        <tabs type="array">'.huixml_encode($tabs).'</tabs>
        <tabactionfunction>ampoliros_tab_builder</tabactionfunction>
        <activetab>'. (isset($eventData['tab']) ? $eventData['tab'] : '').'</activetab>
      </args>
      <children>
    
        <vertgroup><name></name>
          <children>
    
            <label><name>tabtitle</name>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('ampoliros_status.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
        <grid>
          <children>
    
            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('systemlogs_size.label')).'</label>
              </args>
            </label>
    
            <string row="0" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($logs_main -> GetSystemLogsSize())).'</value>
              </args>
            </string>
    
            <button row="0" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_systemlogs')))).'</action>
              </args>
            </button>
    
            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('siteslogs_size.label')).'</label>
              </args>
            </label>
    
            <string row="1" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($logs_main -> GetSitesLogsSize())).'</value>
              </args>
            </string>
    
            <button row="1" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_siteslogs')))).'</action>
              </args>
            </button>
    
            <label row="2" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('cache_size.label')).'</label>
              </args>
            </label>
    
            <string row="2" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($cache_main -> GetCacheSize())).'</value>
              </args>
            </string>
    
            <button row="2" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_cache')))).'</action>
              </args>
            </button>
    
            <label row="3" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('sessions_size.label')).'</label>
              </args>
            </label>
    
            <string row="3" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($cache_main -> GetSessionsSize())).'</value>
              </args>
            </string>
    
            <button row="3" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_sessions')))).'</action>
              </args>
            </button>
    
            <label row="4" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('pidfiles_size.label')).'</label>
              </args>
            </label>
    
            <string row="4" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($cache_main -> GetPidFilesSize())).'</value>
              </args>
            </string>
    
            <button row="4" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_pidfiles')))).'</action>
              </args>
            </button>
    
            <label row="5" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('tempdirs_size.label')).'</label>
              </args>
            </label>
    
            <string row="5" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($cache_main -> GetAmpTempDirsSize())).'</value>
              </args>
            </string>
    
            <button row="5" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_tempdirs')))).'</action>
              </args>
            </button>
    
            <label row="6" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clipboard_size.label')).'</label>
              </args>
            </label>
    
            <string row="6" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($cache_main -> GetClipboardSize())).'</value>
              </args>
            </string>
    
            <button row="6" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clear.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_clipboard')))).'</action>
              </args>
            </button>
    
            <label row="7" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('cleanable_size.label')).'</label>
              </args>
            </label>
    
            <string row="7" col="1">
              <args>
                <size>15</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode($country -> FormatNumber($logs_main -> GetCleanableDiskSize() + $cache_main -> GetCleanableDiskSize())).'</value>
              </args>
            </string>
    
            <button row="7" col="2">
              <args>
                <themeimage>editdelete</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clearall.label')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'clear_all')))).'</action>
              </args>
            </button>
    
          </children>
        </grid>
    
          </children>
        </vertgroup>
    
        <vertgroup><name></name>
          <children>
    
            <label><name>tabtitle</name>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('ampoliros_settings.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <form><name>settings</name>
              <args>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'set_ampoliros')))).'</action>          </args>
              <children>
        <vertgroup>
          <children>
        <grid>
          <children>
    
            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('action_clean.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <label row="0" col="1">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('action_rotate.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <label row="0" col="2">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('action_none.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <radio row="1" col="0" halign="center"><name>amplog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetCleanAmpLog() ? 'true' : 'false').'</checked>
                <value>clean</value>
              </args>
            </radio>
    
            <radio row="1" col="1" halign="center"><name>amplog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetRotateAmpLog() ? 'true' : 'false').'</checked>
                <value>rotate</value>
              </args>
            </radio>
    
            <radio row="1" col="2" halign="center"><name>amplog</name>
              <args>
                <disp>action</disp>
                <checked>'. (($logs_main -> GetCleanAmpLog() or $logs_main -> GetRotateAmpLog()) ? 'false' : 'true').'</checked>
                <value>leave</value>
              </args>
            </radio>
    
            <label row="1" col="3">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('amplog_size.label')).'</label>
              </args>
            </label>
    
            <radio row="2" col="0" halign="center"><name>ampdblog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetCleanAmpDbLog() ? 'true' : 'false').'</checked>
                <value>clean</value>
              </args>
            </radio>
    
            <radio row="2" col="1" halign="center"><name>ampdblog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetRotateAmpDbLog() ? 'true' : 'false').'</checked>
                <value>rotate</value>
              </args>
            </radio>
    
            <radio row="2" col="2" halign="center"><name>ampdblog</name>
              <args>
                <disp>action</disp>
                <checked>'. (($logs_main -> GetCleanAmpDbLog() or $logs_main -> GetRotateAmpDbLog()) ? 'false' : 'true').'</checked>
                <value>leave</value>
              </args>
            </radio>
    
            <label row="2" col="3">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('ampdblog_size.label')).'</label>
              </args>
            </label>
    
            <radio row="3" col="0" halign="center"><name>accesslog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetCleanAccessLog() ? 'true' : 'false').'</checked>
                <value>clean</value>
              </args>
            </radio>
    
            <radio row="3" col="1" halign="center"><name>accesslog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetRotateAccessLog() ? 'true' : 'false').'</checked>
                <value>rotate</value>
              </args>
            </radio>
    
            <radio row="3" col="2" halign="center"><name>accesslog</name>
              <args>
                <disp>action</disp>
                <checked>'. (($logs_main -> GetCleanAccessLog() or $logs_main -> GetRotateAccessLog()) ? 'false' : 'true').'</checked>
                <value>leave</value>
              </args>
            </radio>
    
            <label row="3" col="3">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('accesslog_size.label')).'</label>
              </args>
            </label>
    
            <radio row="4" col="0" halign="center"><name>remotelog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetCleanRemoteLog() ? 'true' : 'false').'</checked>
                <value>clean</value>
              </args>
            </radio>
    
            <radio row="4" col="1" halign="center"><name>remotelog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetRotateRemoteLog() ? 'true' : 'false').'</checked>
                <value>rotate</value>
              </args>
            </radio>
    
            <radio row="4" col="2" halign="center"><name>remotelog</name>
              <args>
                <disp>action</disp>
                <checked>'. (($logs_main -> GetCleanRemoteLog() or $logs_main -> GetRotateRemoteLog()) ? 'false' : 'true').'</checked>
                <value>leave</value>
              </args>
            </radio>
    
            <label row="4" col="3">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('remotelog_size.label')).'</label>
              </args>
            </label>
    
            <radio row="5" col="0" halign="center"><name>phplog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetCleanPhpLog() ? 'true' : 'false').'</checked>
                <value>clean</value>
              </args>
            </radio>
    
            <radio row="5" col="1" halign="center"><name>phplog</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetRotatePhpLog() ? 'true' : 'false').'</checked>
                <value>rotate</value>
              </args>
            </radio>
    
            <radio row="5" col="2" halign="center"><name>phplog</name>
              <args>
                <disp>action</disp>
                <checked>'. (($logs_main -> GetCleanPhpLog() or $logs_main -> GetRotatePhpLog()) ? 'false' : 'true').'</checked>
                <value>leave</value>
              </args>
            </radio>
    
            <label row="5" col="3">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('phplog_size.label')).'</label>
              </args>
            </label>
    
            <radio row="6" col="0" halign="center"><name>siteslogs</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetCleanSitesLogs() ? 'true' : 'false').'</checked>
                <value>clean</value>
              </args>
            </radio>
    
            <radio row="6" col="1" halign="center"><name>siteslogs</name>
              <args>
                <disp>action</disp>
                <checked>'. ($logs_main -> GetRotateSitesLogs() ? 'true' : 'false').'</checked>
                <value>rotate</value>
              </args>
            </radio>
    
            <radio row="6" col="2" halign="center"><name>siteslogs</name>
              <args>
                <disp>action</disp>
                <checked>'. (($logs_main -> GetCleanSitesLogs() or $logs_main -> GetRotateSitesLogs()) ? 'false' : 'true').'</checked>
                <value>leave</value>
              </args>
            </radio>
    
            <label row="6" col="3">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('siteslogs_size.label')).'</label>
              </args>
            </label>
    
          </children>
        </grid>
    
        <horizbar/>
    
        <grid>
          <children>
            <checkbox row="0" col="0"><name>cache</name>
              <args>
                <disp>action</disp>
                <checked>'. ($cache_main -> GetCleanCache() ? 'true' : 'false').'</checked>
              </args>
            </checkbox>
    
            <label row="0" col="1">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('cache_size.label')).'</label>
              </args>
            </label>
    
            <checkbox row="1" col="0"><name>sessions</name>
              <args>
                <disp>action</disp>
                <checked>'. ($cache_main -> GetCleanSessions() ? 'true' : 'false').'</checked>
              </args>
            </checkbox>
    
            <label row="1" col="1">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('sessions_size.label')).'</label>
              </args>
            </label>
    
            <checkbox row="2" col="0"><name>pidfiles</name>
              <args>
                <disp>action</disp>
                <checked>'. ($cache_main -> GetCleanPidFiles() ? 'true' : 'false').'</checked>
              </args>
            </checkbox>
    
            <label row="2" col="1">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('pidfiles_size.label')).'</label>
              </args>
            </label>
    
            <checkbox row="3" col="0"><name>amptempdirs</name>
              <args>
                <disp>action</disp>
                <checked>'. ($cache_main -> GetCleanAmpTempDirs() ? 'true' : 'false').'</checked>
              </args>
            </checkbox>
    
            <label row="3" col="1">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('tempdirs_size.label')).'</label>
              </args>
            </label>
    
            <checkbox row="4" col="0"><name>clipboard</name>
              <args>
                <disp>action</disp>
                <checked>'. ($cache_main -> GetCleanClipboard() ? 'true' : 'false').'</checked>
              </args>
            </checkbox>
    
            <label row="4" col="1">
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('clipboard_size.label')).'</label>
              </args>
            </label>
    
          </children>
        </grid>
    
          </children>
        </vertgroup>
    
              </children>
            </form>
    
            <horizbar/>
    
            <button>
              <args>
                <themeimage>button_ok</themeimage>
                <label type="encoded">'.urlencode($gLocale -> GetStr('apply.button')).'</label>
                <horiz>true</horiz>
                <frame>false</frame>
                <formsubmit>settings</formsubmit>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'ampoliros'), array('action', 'set_ampoliros')))).'</action>
              </args>
            </button>
    
          </children>
        </vertgroup>
    
      </children>
    </tab>';

    $gPage_title.= ' - '.$gLocale -> GetStr('ampoliros.title');
}

$gMain_disp -> Dispatch();

// ----- Rendering -----
//
$gHui -> AddChild(new HuiAmpPage('page', array('pagetitle' => $gPage_title, 'menu' => get_ampoliros_root_menu_def($gEnv['root']['locale']['language']), 'toolbars' => array(new HuiAmpToolbar('main', array('toolbars' => $gToolbars))), 'maincontent' => new HuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status, 'icon' => 'package_utilities')));

$gHui -> Render();

?>
