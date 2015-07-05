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
// $Id: ampadvanced.php,v 1.36 2004-07-14 13:38:57 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.io.log.Logger');
import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.locale.LocaleCountry');
OpenLibrary('hui.library');
OpenLibrary('ampshared.library');
import('com.solarix.ampoliros.debug.Debugger');

$gLocale = new Locale('amp_root_advanced', $gEnv['root']['locale']['language']);
$gHui = new Hui($gEnv['root']['db']);
$gHui -> LoadWidget('amppage');
$gHui -> LoadWidget('amptoolbar');
$gHui -> LoadWidget('button');
$gHui -> LoadWidget('checkbox');
$gHui -> LoadWidget('combobox');
$gHui -> LoadWidget('date');
$gHui -> LoadWidget('empty');
$gHui -> LoadWidget('file');
$gHui -> LoadWidget('formarg');
$gHui -> LoadWidget('form');
$gHui -> LoadWidget('grid');
$gHui -> LoadWidget('helpnode');
$gHui -> LoadWidget('horizbar');
$gHui -> LoadWidget('horizframe');
$gHui -> LoadWidget('horizgroup');
$gHui -> LoadWidget('image');
$gHui -> LoadWidget('label');
$gHui -> LoadWidget('link');
$gHui -> LoadWidget('listbox');
$gHui -> LoadWidget('menu');
$gHui -> LoadWidget('page');
$gHui -> LoadWidget('progressbar');
$gHui -> LoadWidget('radio');
$gHui -> LoadWidget('sessionkey');
$gHui -> LoadWidget('statusbar');
$gHui -> LoadWidget('string');
$gHui -> LoadWidget('submit');
$gHui -> LoadWidget('tab');
$gHui -> LoadWidget('table');
$gHui -> LoadWidget('text');
$gHui -> LoadWidget('titlebar');
$gHui -> LoadWidget('toolbar');
$gHui -> LoadWidget('treemenu');
$gHui -> LoadWidget('vertframe');
$gHui -> LoadWidget('vertgroup');
$gHui -> LoadWidget('xml');

$gPage_content = $gPage_status = '';
$gPage_title = $gLocale -> GetStr('advanced.title');

$gToolbars_array['main'] = array('main' => array('label' => $gLocale -> GetStr('default.button'), 'themeimage' => 'configure', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'default', '')))), 'processes' => array('label' => $gLocale -> GetStr('processes.button'), 'themeimage' => 'run', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'processes', '')))));
$gToolbars_array['help'] = array('help' => array('label' => $gLocale -> GetStr('help.button'), 'themeimage' => 'help', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'help', '')))));

$gState = '';

// Pass dispatcher
//
$gPass_disp = new HuiDispatcher('pass');

$gPass_disp -> AddEvent('setadvanced', 'pass_setadvanced');
function pass_setadvanced($eventData) {
    global $gPage_status, $gLocale, $gEnv, $gState;

    $amp_state = '';

    switch ($eventData['ampstate']) {
        case Ampoliros::STATE_DEBUG :
            $amp_state_str = 'DEBUG';
            $amp_state = 'debug';
            $amp = Ampoliros::instance('Ampoliros');
            $amp->setState(Ampoliros::STATE_DEVELOPMENT); // Do not set it to DEBUG
            break;

        case Ampoliros::STATE_DEVELOPMENT :
            $amp_state_str = 'DEVELOPMENT';
            $amp_state = 'development';
            $amp = Ampoliros::instance('Ampoliros');
            $amp->setState(Ampoliros::STATE_DEVELOPMENT);
            break;

        case AMP_STATE_PRODUCTION :
            $amp_state_str = 'PRODUCTION';
            $amp_state = 'production';
            $amp = Ampoliros::instance('Ampoliros');
            $amp->setState(Ampoliros::STATE_PRODUCTION);
            break;
    }

    if (strlen($amp_state)) {
        $gState = $eventData['ampstate'];

        $log = new Logger($gEnv['root']['log']);

        $amp_cfg = new ConfigFile(AMP_CONFIG);
        $amp_cfg -> SetValue('AMP_STATE', $amp_state);

        //$gEnv['core']['state'] = $eventData['ampstate'];

        $log -> LogEvent('Ampoliros', 'Changed Ampoliros state to '.$amp_state_str, LOGGER_NOTICE);

        $gPage_status = $gLocale -> GetStr('advancedset.status');
        //$hui_page->mJavascript = 'parent.frames.sum.location.reload()';
    } else
        $gPage_status = $gLocale -> GetStr('advancednotset.status');
}

$gPass_disp -> AddEvent('removepid', 'pass_removepid');
function pass_removepid($eventData) {
    global $gLocale, $gPage_status;
    
    if (@ unlink(TMP_PATH.'pids/'.$eventData['pid']))
        $gPage_status = $gLocale -> GetStr('pid_removed.status');
    else
        $gPage_status = $gLocale -> GetStr('pid_not_removed.status');
}

$gPass_disp -> AddEvent('eraseallpids', 'pass_eraseallpids');
function pass_eraseallpids($eventData) {
    global $gLocale, $gPage_status, $gEnv;

    if ($handle = opendir(TMP_PATH.'pids')) {
        import('com.solarix.ampoliros.core.Ampoliros');
        $amp = Ampoliros::instance('Ampoliros');
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..' && $file != $amp->getPid()) {
                @ unlink(TMP_PATH.'pids/'.$file);
            }
        }
        closedir($handle);
    }

    $gPage_status = $gLocale -> GetStr('all_pids_removed.status');
}

$gPass_disp -> AddEvent('removesem', 'pass_removesem');
function pass_removesem($eventData) {
    global $gLocale, $gPage_status;

    if (@ unlink(TMP_PATH.'semaphores/'.$eventData['semaphore']))
        $gPage_status = $gLocale -> GetStr('sem_removed.status');
    else
        $gPage_status = $gLocale -> GetStr('sem_not_removed.status');
}

$gPass_disp -> AddEvent('eraseallsemaphores', 'pass_eraseallsemaphores');
function pass_eraseallsemaphores($eventData) {
    global $gLocale, $gPage_status, $gEnv;

    if ($handle = opendir(TMP_PATH.'semaphores')) {
        while (($file = readdir($handle)) !== false) {
            @ unlink(TMP_PATH.'semaphores/'.$file);
        }
        closedir($handle);
    }

    $gPage_status = $gLocale -> GetStr('all_sems_removed.status');
}

$gPass_disp -> AddEvent('submitbugreport', 'pass_submitbugreport');
function pass_submitbugreport($eventData) {
    global $gLocale, $gEnv, $gPage_status;

    $debug = new Debugger($eventData['pid']);
    $debug -> ReadPidFile();
    $moddata = $debug -> GuessModule();

    if (strlen($eventData['email']))
        $from = $eventData['email'];
    else
        $from = 'bugs@ampoliros.com';

    $notify = false;
    if (isset($eventData['notify']))
        $notify = $eventData['notify'] == 'on' ? true : false;

    if ($debug -> SubmitBugReport($moddata, $from, $eventData['message'], $notify))
        $gPage_status = $gLocale -> GetStr('bugreport_sent.status');
    else
        $gPage_status = $gLocale -> GetStr('bugreport_not_sent.status');
}

$gPass_disp -> Dispatch();

$amp = Ampoliros::instance('Ampoliros');
if ($amp->getState() == Ampoliros::STATE_DEBUG or $amp->getState() == Ampoliros::STATE_DEVELOPMENT) {
    $gToolbars_array['main']['semaphores'] = array('label' => $gLocale -> GetStr('semaphores.button'), 'themeimage' => 'stop', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'semaphores', ''))));
}

$gToolbars = array(new HuiAmpToolBar('main', array('toolbars' => $gToolbars_array)));

// Main dispatcher
//
$gMain_disp = new HuiDispatcher('main');

$gMain_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $gEnv, $gLocale, $gPage_content, $gState;

    $amp = Ampoliros::instance('Ampoliros');
    $state = $amp->getState();
    
    if (strlen($gState)) {
        $state = $gState;
    }

    $xml_def = '<vertgroup><name>state</name><args><width>100%</width></args><children>
          <form><name>state</name><args><action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default', ''), array('pass', 'setadvanced', '')))).'</action></args><children>
            <vertgroup><name>state</name><args><width>100%</width></args><children>
              <label><name>state</name><args><label type="encoded">'.urlencode('<strong>'.$gLocale -> GetStr('state.label').'</strong>').'</label></args></label>
              <radio><name>ampstate</name><args><disp>pass</disp><value>'.Ampoliros::STATE_PRODUCTION.'</value>'. ($state == AMP_STATE_PRODUCTION ? '<checked>true</checked>' : '').'<label type="encoded">'.urlencode($gLocale -> GetStr('production.radio')).'</label></args></radio>
              <radio><name>ampstate</name><args><disp>pass</disp><value>'.Ampoliros::STATE_DEVELOPMENT.'</value>'. ($state == AMP_STATE_DEVELOPMENT ? '<checked>true</checked>' : '').'<label type="encoded">'.urlencode($gLocale -> GetStr('development.radio')).'</label></args></radio>
              <radio><name>ampstate</name><args><disp>pass</disp><value>'.Ampoliros::STATE_DEBUG.'</value>'. ($state == AMP_STATE_DEBUG ? '<checked>true</checked>' : '').'<label type="encoded">'.urlencode($gLocale -> GetStr('debug.radio')).'</label></args></radio>
              <horizbar><name>hb</name></horizbar>
              <button><name>submit</name><args><themeimage>button_ok</themeimage><formsubmit>state</formsubmit><horiz>true</horiz><label>'.$gLocale -> GetStr('submit.button').'</label><action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default', ''), array('pass', 'setadvanced', '')))).'</action></args></button>
            </children></vertgroup>
          </children></form>
        </children></vertgroup>';

    $amp = Ampoliros::instance('Ampoliros');
    if ($amp->getState() == Ampoliros::STATE_DEBUG)
        $amp->setState(Ampoliros::STATE_DEVELOPMENT);

    $gPage_content = new HuiXml('page', array('definition' => $xml_def));
}
function processes_list_action_builder($pageNumber) {
    return build_events_call_string('', array(array('main', 'processes', array('processespage' => $pageNumber))));
}

$gMain_disp -> AddEvent('processes', 'main_processes');
function main_processes($eventData) {
    global $gEnv, $gLocale, $gPage_content, $gToolbars;

    $amp = Ampoliros::instance('Ampoliros');
    if ($amp->getState() == Ampoliros::STATE_DEBUG)
        $amp->setState(Ampoliros::STATE_DEVELOPMENT);
        
    $headers[0]['label'] = $gLocale -> GetStr('pid.header');
    $headers[1]['label'] = $gLocale -> GetStr('creation.header');

    $xml_def = '<table><name>processes</name><args><headers type="array">'.huixml_encode($headers).'</headers><rowsperpage>15</rowsperpage><pagesactionfunction>processes_list_action_builder</pagesactionfunction><pagenumber>'. ((is_array($eventData) and isset($eventData['processespage'])) ? $eventData['processespage'] : '').'</pagenumber></args><children>';

    $row = 0;

    if ($handle = opendir(TMP_PATH.'pids')) {
        $country = new LocaleCountry($gEnv['root']['locale']['country']);

        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                $tmp_debugger = new Debugger($file);

                if (!$tmp_debugger -> IsCurrentPid()) {
                    $toolbar = array();

                    $file_stats = stat(TMP_PATH.'pids/'.$file);
                    $xml_def.= '<label row="'.$row.'" col="0"><name>pid</name><args><label type="encoded">'.urlencode($file).'</label></args></label>';
                    $xml_def.= '<label row="'.$row.'" col="1"><name>date</name><args><label>'.$country -> FormatShortDate($file_stats[10]).' '.$country -> FormatTime($file_stats[10]).'</label></args></label>';

                    $toolbar['main']['remove'] = array('label' => $gLocale -> GetStr('remove.button'), 'themeimage' => 'edittrash', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'processes', ''), array('pass', 'removepid', array('pid' => $file)))), 'needconfirm' => 'true', 'confirmmessage' => $gLocale -> GetStr('remove_confirm.label'));
                    if (($gEnv['core']['state'] == AMP_STATE_DEBUG or $gEnv['core']['state'] == AMP_STATE_DEVELOPMENT) and $tmp_debugger -> CheckPidFile()) {
                        $toolbar['main']['debug'] = array('label' => $gLocale -> GetStr('debug.button'), 'themeimage' => 'run', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'debug', array('pid' => $file)))));
                    }

                    $xml_def.= '<amptoolbar row="'.$row.'" col="2"><name>toolbar</name><args><frame>false</frame><toolbars type="array">'.huixml_encode($toolbar).'</toolbars></args></amptoolbar>';

                    $row ++;
                }
            }
        }
        closedir($handle);
    }

    $xml_def.= '</children></table>';

    $gToolbars_array['debugger'] = array('eraseall' => array('label' => $gLocale -> GetStr('eraseall.button'), 'themeimage' => 'edittrash', 'needconfirm' => 'true', 'horiz' => 'true', 'confirmmessage' => $gLocale -> GetStr('eraseall.confirm'), 'action' => build_events_call_string('', array(array('main', 'processes', ''), array('pass', 'eraseallpids', '')))));

    $gToolbars[] = new HuiAmpToolBar('debugger', array('toolbars' => $gToolbars_array));

    $gPage_content = new HuiXml('page', array('definition' => $xml_def));
}

$gMain_disp -> AddEvent('semaphores', 'main_semaphores');
function main_semaphores($eventData) {
    global $gEnv, $gLocale, $gPage_content, $gToolbars;

    $headers[0]['label'] = $gLocale -> GetStr('resource.header');
    $headers[1]['label'] = $gLocale -> GetStr('semaphore.header');
    $headers[2]['label'] = $gLocale -> GetStr('semaphorepid.header');

    $xml_def = '<table><name>semaphores</name>
          <args>
            <headers type="array">'.huixml_encode($headers).'</headers>
            <rowsperpage>15</rowsperpage>
            <pagesactionfunction>processes_list_action_builder</pagesactionfunction>
            <pagenumber>'. ((is_array($eventData) and isset($eventData['processespage'])) ? $eventData['processespage'] : '').'</pagenumber>
          </args>
          <children>';

    $row = 0;

    if ($handle = opendir(TMP_PATH.'semaphores')) {
        $country = new LocaleCountry($gEnv['root']['locale']['country']);

        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                $toolbar = array();

                $buf = file_get_contents(TMP_PATH.'semaphores/'.$file);
                $content = unserialize($buf);

                $file_stats = stat(TMP_PATH.'semaphores/'.$file);
                $xml_def.= '<label row="'.$row.'" col="0"><name>pid</name><args><label type="encoded">'.urlencode($content['resource']).'</label></args></label>';
                $xml_def.= '<label row="'.$row.'" col="1"><name>pid</name><args><label type="encoded">'.urlencode($file).'</label></args></label>';
                $xml_def.= '<label row="'.$row.'" col="2"><name>pid</name><args><label type="encoded">'.urlencode($content['pid']).'</label></args></label>';
                $xml_def.= '<label row="'.$row.'" col="3"><name>date</name><args><label>'.$country -> FormatShortDate($file_stats[10]).' '.$country -> FormatTime($file_stats[10]).'</label></args></label>';

                $toolbar['main']['remove'] = array('label' => $gLocale -> GetStr('remove_sem.button'), 'themeimage' => 'edittrash', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'semaphores', ''), array('pass', 'removesem', array('semaphore' => $file)))), 'needconfirm' => 'true', 'confirmmessage' => $gLocale -> GetStr('remove_sem_confirm.label'));

                $xml_def.= '<amptoolbar row="'.$row.'" col="4"><name>toolbar</name><args><frame>false</frame><toolbars type="array">'.huixml_encode($toolbar).'</toolbars></args></amptoolbar>';

                $row ++;
            }
        }
        closedir($handle);
    }

    $xml_def.= '</children></table>';

    $gToolbars_array['semaphores'] = array('eraseall' => array('label' => $gLocale -> GetStr('eraseall_sem.button'), 'themeimage' => 'edittrash', 'needconfirm' => 'true', 'horiz' => 'true', 'confirmmessage' => $gLocale -> GetStr('eraseall_sem.confirm'), 'action' => build_events_call_string('', array(array('main', 'semaphores', ''), array('pass', 'eraseallsemaphores', '')))));

    $gToolbars[] = new HuiAmpToolBar('semaphores', array('toolbars' => $gToolbars_array));

    $gPage_content = new HuiXml('page', array('definition' => $xml_def));
}

function debugger_tab_action_builder($tab) {
    $tmp_main_disp = new HuiDispatcher('main');

    $event_data = $tmp_main_disp -> GetEventData();
    return build_events_call_string('', array(array('main', 'debug', array('activetab' => $tab, 'pid' => $event_data['pid']))));
}

$gMain_disp -> AddEvent('debug', 'main_debug');
function main_debug($eventData) {
    global $gEnv, $gLocale, $gPage_content;

    $amp = Ampoliros::instance('Ampoliros');
    if ($amp->getState == Ampoliros::STATE_DEBUG)
        $amp->setState(Ampoliros::STATE_DEVELOPMENT);

    $debugger = new Debugger($eventData['pid']);

    if ($debugger -> CheckPidFile()) {
        $debugger -> ReadPidFile();
        $moddata = $debugger -> GuessModule();

        $country = new LocaleCountry($gEnv['root']['locale']['country']);

        $rowa = 0;
        $rowb = 0;
        $rowc = 0;
        $rowd = 0;
        $rowe = 0;
        $rowf = 0;

        $log_events = '';

        while (list (, $log_event) = each($debugger -> mLogEvents)) {
            $log_events.= $log_event."\n";
        }

        $hui_events = array();

        while (list ($dispatcher, $event) = each($debugger -> mHuiEvents)) {
            $hui_events[] = $dispatcher.'::'.$event['eventname'];

            if (is_array($event['eventdata'])) {
                while (list ($eventdata_name, $eventdata_value) = each($event['eventdata'])) {
                    $hui_events[] = '- '.$eventdata_name.' -> '.$eventdata_value;
                }
            }
        }

        $tabs[0]['label'] = $gLocale -> GetStr('instance.label');
        $tabs[1]['label'] = $gLocale -> GetStr('environment.label');
        $tabs[2]['label'] = $gLocale -> GetStr('runtime.label');
        $tabs[3]['label'] = $gLocale -> GetStr('source.label');
        $tabs[4]['label'] = $gLocale -> GetStr('profiler.label');
        $tabs[5]['label'] = $gLocale -> GetStr('bugreport.label');

        arsort($debugger -> mDbProfiler);

        $xml_def = '<tab><name>debugger</name><args>'. (isset($eventData['activetab']) ? '<activetab>'.$eventData['activetab'].'</activetab>' : '').'<tabactionfunction>debugger_tab_action_builder</tabactionfunction><tabs type="array">'.huixml_encode($tabs).'</tabs></args><children>
                
                  <grid><name>debugger</name><children>
                
                    <label row="'.$rowa ++.'" col="0"><name>instance</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('instance.label')).'</label><bold>true</bold></args></label>
                
                    <label row="'.$rowa.'" col="0"><name>pid</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('pid.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>pid</name><args><readonly>true</readonly><value>'.$eventData['pid'].'</value><size>32</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>sessionid</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('sessionid.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>sessionid</name><args><readonly>true</readonly><value>'.$debugger -> mSessionId.'</value><size>32</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>state</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('state.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>state</name><args><readonly>true</readonly><value>'.$debugger -> mState.'</value><size>15</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>interface</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('interface.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>interface</name><args><readonly>true</readonly><value>'.$debugger -> mInterface.'</value><size>15</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>mode</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('mode.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>mode</name><args><readonly>true</readonly><value>'.$debugger -> mMode.'</value><size>15</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>pagename</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('pagename.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>pagename</name><args><readonly>true</readonly><value>'.$debugger -> mPidStructure['gEnv']['runtime']['pagename'].'</value><size>20</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>siteid</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('siteid.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>siteid</name><args><readonly>true</readonly><value>'.$debugger -> mPidStructure['gEnv']['site']['id'].'</value><size>20</size></args></string>
                
                    <label row="'.$rowa.'" col="0"><name>userid</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('userid.label')).'</label></args></label>
                    <string row="'.$rowa ++.'" col="1"><name>userid</name><args><readonly>true</readonly><value>'.$debugger -> mPidStructure['gEnv']['user']['id'].'</value><size>20</size></args></string>
                
                  </children></grid>
                
                  <grid><name>environment</name><children>
                
                    <label row="'.$rowb ++.'" col="0"><name>environment</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('environment.label')).'</label><bold>true</bold></args></label>
                
                    <label row="'.$rowb.'" col="0"><name>memory</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('memorylimit.label')).'</label></args></label>
                    <string row="'.$rowb ++.'" col="1"><name>memory</name><args><readonly>true</readonly><value>'.$debugger -> mPidStructure['gEnv']['core']['php']['memorylimit'].'</value><size>15</size></args></string>
                
                    <label row="'.$rowb.'" col="0"><name>timelimit</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('timelimit.label')).'</label></args></label>
                    <string row="'.$rowb ++.'" col="1"><name>timelimit</name><args><readonly>true</readonly><value>'.$debugger -> mPidStructure['gEnv']['core']['php']['timelimit'].'</value><size>15</size></args></string>
                
                    <label row="'.$rowb.'" col="0"><name>sessionlifetime</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('sessionlifetime.label')).'</label></args></label>
                    <string row="'.$rowb ++.'" col="1"><name>sessionlifetime</name><args><readonly>true</readonly><value>'.$debugger -> mPidStructure['gEnv']['core']['session']['lifetime'].'</value><size>15</size></args></string>
                
                    <label row="'.$rowb.'" col="0"><name>extensions</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('extensions.label')).'</label></args></label>
                    <listbox row="'.$rowb ++.'" col="1"><name>extensions</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mLoadedExtensions).'</elements><size>10</size></args></listbox>
                
                  </children></grid>
                
                  <grid><name>runtime</name><children>
                
                    <label row="'.$rowc ++.'" col="0"><name>runtime</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('runtime.label')).'</label><bold>true</bold></args></label>
                
                    <label row="'.$rowc.'" col="0"><name>libraries</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('libraries.label')).'</label></args></label>
                    <listbox row="'.$rowc ++.'" col="1"><name>libraries</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mLibraries).'</elements><size>10</size></args></listbox>
                
                    <label row="'.$rowc.'" col="0"><name>logevents</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('logevents.label')).'</label></args></label>
                    <text row="'.$rowc ++.'" col="1"><name>logevents</name><args><readonly>true</readonly><value type="encoded">'.urlencode($log_events).'</value><rows>15</rows><cols>100</cols></args></text>
                
                    <label row="'.$rowc.'" col="0"><name>calledhooks</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('calledhooks.label')).'</label></args></label>
                    <listbox row="'.$rowc ++.'" col="1"><name>calledhooks</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mCalledHooks).'</elements><size>5</size></args></listbox>
                
                    <label row="'.$rowc.'" col="0"><name>huievents</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('huievents.label')).'</label></args></label>
                    <listbox row="'.$rowc ++.'" col="1"><name>huievents</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($hui_events).'</elements><size>5</size></args></listbox>
                
                    <label row="'.$rowc.'" col="0"><name>queries</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('queries.label')).'</label></args></label>
                    <listbox row="'.$rowc ++.'" col="1"><name>queries</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mExecutedQueries).'</elements><size>10</size></args></listbox>
                
                    <label row="'.$rowc.'" col="0"><name>includedfiles</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('includedfiles.label')).'</label></args></label>
                    <listbox row="'.$rowc ++.'" col="1"><name>includedfiles</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mIncludedFiles).'</elements><size>5</size></args></listbox>
                ';

        if (function_exists('memory_get_usage'))
            $xml_def.= '    <label row="'.$rowc.'" col="0">
                              <args>
                                <label type="encoded">'.urlencode($gLocale -> GetStr('memoryusage.label')).'</label>
                              </args>
                            </label>';

        $xml_def.= '    <string row="'.$rowc ++.'" col="1">
                      <args>
                        <value type="encoded">'.urlencode($country -> FormatNumber($debugger -> mMemoryUsage)).'</value>
                        <readonly>true</readonly>
                        <size>15</size>
                      </args>
                    </string>
                
                  </children></grid>
                
                  <grid><name>source</name><children>
                
                    <label row="'.$rowe ++.'" col="0"><name>source</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('source.label')).'</label><bold>true</bold></args></label>
                
                    <label row="'.$rowe.'" col="0"><name>classes</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('classes.label')).'</label></args></label>
                    <listbox row="'.$rowe ++.'" col="1"><name>classes</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mDefinedClasses).'</elements><size>10</size></args></listbox>
                
                    <label row="'.$rowe.'" col="0"><name>functions</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('functions.label')).'</label></args></label>
                    <listbox row="'.$rowe ++.'" col="1"><name>functions</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mDefinedFunctions).'</elements><size>5</size></args></listbox>
                
                  </children></grid>
                
                  <grid><name>profiler</name><children>
                
                    <label row="'.$rowd ++.'" col="0"><name>profiler</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('profiler.label')).'</label><bold>true</bold></args></label>
                
                    <label row="'.$rowd.'" col="0"><name>markers</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('markers.label')).'</label></args></label>
                    <listbox row="'.$rowd ++.'" col="1"><name>markers</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mProfiler).'</elements><size>20</size></args></listbox>
                
                    <label row="'.$rowd.'" col="0"><name>markers</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('dbmarkers.label')).'</label></args></label>
                    <listbox row="'.$rowd ++.'" col="1"><name>markers</name><args><readonly>true</readonly><elements type="array">'.huixml_encode($debugger -> mDbProfiler).'</elements><size>20</size></args></listbox>
                    
                    <label row="'.$rowd.'" col="0"><name>dbload</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('dbload.label')).'</label></args></label>
                    <string row="'.$rowd ++.'" col="1"><name>dbload</name><args><readonly>true</readonly><value>'.$debugger -> mDbTotalLoad.'</value><size>20</size></args></string>
                
                    <label row="'.$rowd.'" col="0"><args><label type="encoded">'.urlencode($gLocale -> GetStr('executedqueries.label')).'</label></args></label>
                    <string row="'.$rowd ++.'" col="1"><name>executedqueries</name><args><readonly>true</readonly><value>'.count($debugger -> mDbProfiler).'</value><size>6</size></args></string>
                
                  </children></grid>
                
                  <form><name>bugreport</name><args><method>post</method><action type="encoded"></action></args><children>
                
                    <vertgroup><name>bugreport</name><children>
                
                      <grid><name>bugreport</name><children>
                
                        <label row="'.$rowf ++.'" col="0"><name>bugreport</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('bugreport.label')).'</label><bold>true</bold></args></label>
                
                        <label row="'.$rowf.'" col="0"><name>module</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('module.label')).'</label></args></label>
                        <label row="'.$rowf ++.'" col="1"><name>module</name><args><label type="encoded">'.urlencode($moddata['module']).'</label></args></label>
                
                        <label row="'.$rowf.'" col="0"><name>bugsemail</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('bugsemail.label')).'</label></args></label>
                        <label row="'.$rowf ++.'" col="1"><name>to</name><args><label type="encoded">'.urlencode($moddata['email']).'</label></args></label>';

        if ($moddata['ampolirosemail'] != $moddata['email'])
            $xml_def.= '        <label row="'.$rowf.'" col="0"><name>notify</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('sendnotify.label')).'</label></args></label>
                                <checkbox row="'.$rowf ++.'" col="1"><name>notify</name><args><disp>pass</disp></args></checkbox>';

        $xml_def.= '        <label row="'.$rowf.'" col="0"><name>email</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('submitteremail.label')).'</label></args></label>
                        <string row="'.$rowf ++.'" col="1"><name>email</name><args><size>25</size><disp>pass</disp></args></string>
                
                        <label row="'.$rowf.'" col="0"><name>message</name><args><label type="encoded">'.urlencode($gLocale -> GetStr('message.label')).'</label></args></label>
                        <text row="'.$rowf ++.'" col="1"><name>message</name><args><cols>80</cols><rows>10</rows><disp>pass</disp></args></text>
                
                      </children></grid>
                
                      <horizbar><name>hb</name></horizbar>
                
                      <button><name>submit</name>
                        <args>
                          <formsubmit>bugreport</formsubmit>
                          <themeimage>button_ok</themeimage>
                          <frame>false</frame>
                          <horiz>true</horiz>
                          <label type="encoded">'.urlencode($gLocale -> GetStr('bugreport.submit')).'</label>
                          <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'debug', array('pid' => $eventData['pid'])), array('pass', 'submitbugreport', array('pid' => $eventData['pid']))))).'</action>
                        </args>
                      </button>
                
                    </children></vertgroup>
                
                  </children></form>
                
                </children></tab>';
    }

    $gPage_content = new HuiXml('page', array('definition' => $xml_def));
}

$gMain_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $gEnv, $gLocale, $gPage_content;
    $gPage_content = new HuiHelpNode('help', array(
        //'node' => 'ampoliros.root.advanced.'.$eventData['node'],
    'language' => AMP_LANG));
}

$gMain_disp -> Dispatch();

$gHui -> AddChild(new HuiAmpPage('page', array('pagetitle' => $gPage_title, 'menu' => get_ampoliros_root_menu_def($gEnv['root']['locale']['language']), 'toolbars' => $gToolbars, 'maincontent' => $gPage_content, 'status' => $gPage_status, 'icon' => 'exec')));
$gHui -> Render();
?>
