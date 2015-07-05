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
// $Id: security.php,v 1.21 2004-07-14 13:15:37 alex Exp $

// ----- Initialization -----
//
require ('./auth.php');

import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.hui.HuiEventsCall');
import('com.solarix.ampoliros.security.SecurityLayer');
OpenLibrary('ampshared.library');

$gLocale = new Locale('amp_root_security', $gEnv['root']['locale']['language']);
$gHui = new Hui($gEnv['root']['db']);
$gHui -> LoadWidget('xml');
$gHui -> LoadWidget('amppage');
$gHui -> LoadWidget('amptoolbar');

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale -> GetStr('security.title');

$gToolbars['main'] = array('check' => array('label' => $gLocale -> GetStr('check.toolbar'), 'themeimage' => 'viewmag', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'default', '')))), 'settings' => array('label' => $gLocale -> GetStr('settings.toolbar'), 'themeimage' => 'configure', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'settings', '')))));

$gToolbars['help'] = array('help' => array('label' => $gLocale -> GetStr('help.toolbar'), 'themeimage' => 'help', 'horiz' => 'true', 'action' => build_events_call_string('', array(array('main', 'help', '')))));

// ----- Action dispatcher -----
//
$gAction_disp = new HuiDispatcher('action');

$gAction_disp -> AddEvent('set_security_preset', 'action_set_security_preset');
function action_set_security_preset($eventData) {
    global $gEnv, $gLocale, $gPage_status;

    $amp_security = new SecurityLayer();

    $amp_security -> SetPredefinedLevel($eventData['preset']);

    $gPage_status = $gLocale -> GetStr('security_settings_set.status');
}

$gAction_disp -> AddEvent('set_access_prefs', 'action_set_access_prefs');
function action_set_access_prefs($eventData) {
    global $gEnv, $gLocale, $gPage_status;

    $amp_security = new SecurityLayer();

    $amp_security -> SetSessionLifetime($eventData['sessionlifetime']);
    $amp_security -> SetMaxWrongLogins($eventData['maxwronglogins']);
    $amp_security -> SetWrongLoginDelay($eventData['wronglogindelay']);
    $eventData['lockunsecurewebservices'] == 'on' ? $amp_security -> LockUnsecureWebServices() : $amp_security -> UnLockUnsecureWebServices();
    $eventData['onlyhttpsroot'] == 'on' ? $amp_security -> AcceptOnlyHttpsRootAccess(true) : $amp_security -> AcceptOnlyHttpsRootAccess(false);
    $eventData['onlyhttpssite'] == 'on' ? $amp_security -> AcceptOnlyHttpsSiteAccess(true) : $amp_security -> AcceptOnlyHttpsSiteAccess(false);

    $gPage_status = $gLocale -> GetStr('security_settings_set.status');
}

$gAction_disp -> AddEvent('set_alerts_prefs', 'action_set_alerts_prefs');
function action_set_alerts_prefs($eventData) {
    global $gEnv, $gLocale, $gPage_status;

    $amp_security = new SecurityLayer();

    $alerts['wronglocalrootlogin'] = $eventData['wronglocalrootlogin'] == 'on' ? true : false;
    $alerts['wronglocaluserlogin'] = $eventData['wronglocaluserlogin'] == 'on' ? true : false;
    $alerts['wrongremotelogin'] = $eventData['wrongremotelogin'] == 'on' ? true : false;
    $alerts['moduleoperation'] = $eventData['moduleoperation'] == 'on' ? true : false;
    $alerts['modulesiteoperation'] = $eventData['sitemoduleoperation'] == 'on' ? true : false;
    $alerts['siteoperation'] = $eventData['siteoperation'] == 'on' ? true : false;

    $amp_security -> SetAlertEvents($alerts);
    $amp_security -> SetAlertsEmail($eventData['alertdestinationemail']);

    $gPage_status = $gLocale -> GetStr('security_settings_set.status');
}

$gAction_disp -> AddEvent('set_reports_prefs', 'action_set_reports_prefs');
function action_set_reports_prefs($eventData) {
    global $gEnv, $gLocale, $gPage_status;

    $amp_security = new SecurityLayer();

    $amp_security -> SetReportsEmail($eventData['reportdestinationemail']);
    $amp_security -> SetReportsInterval($eventData['enablereports'] == 'on' ? $eventData['reportsinterval'] : '0');

    $gPage_status = $gLocale -> GetStr('security_settings_set.status');
}

$gAction_disp -> AddEvent('clean_access_log', 'action_clean_access_log');
function action_clean_access_log($eventData) {
    global $gEnv, $gLocale, $gPage_status;

    $amp_security = new SecurityLayer();

    $amp_security -> EraseAccessLog();

    $gPage_status = $gLocale -> GetStr('access_log_erased.status');
}

$gAction_disp -> AddEvent('logout_sessions', 'action_logout_sessions');
function action_logout_sessions($eventData) {
    global $gLocale, $gPage_status;

    $amp_security = new SecurityLayer();

    foreach ($eventData['sessions'] as $id => $session) {
        $amp_security -> LogoutSession($session);
    }

    $gPage_status = $gLocale -> GetStr('sessions_logged_out.status');
}

$gAction_disp -> Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new HuiDispatcher('main');

function default_tab_builder($tab) {
    return build_events_call_string('', array(array('main', 'default', array('tab' => $tab))));
}

$gMain_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $gEnv, $gXml_def, $gLocale, $gPage_title;

    //$tabs[0]['label'] = $gLocale->GetStr( 'currentactivities.tab' );
    $tabs[0]['label'] = $gLocale -> GetStr('accesslog.tab');
    $tabs[1]['label'] = $gLocale -> GetStr('loggedusers.tab');
    $tabs[2]['label'] = $gLocale -> GetStr('securitycheck.tab');

    $amp_security = new SecurityLayer();
    $security_check = $amp_security -> SecurityCheck();

    $logged_users = $amp_security -> GetLoggedSessions();

    $root_sessions = $users_sessions = array();

    foreach ($logged_users['root'] as $root_session) {
        $root_sessions[$root_session] = $root_session;
    }

    foreach ($logged_users['sites'] as $user => $sessions) {
        $users_sessions[$user] = $user;

        foreach ($sessions as $session) {
            //$users_sessions[$user.'-'.$session] = '- '.$session;
            $users_sessions[$session] = '- '.$session;
        }
    }

    $tmp_key = array_search('', $security_check['unsecurewebservicesaccounts']);
    if (strlen($tmp_key))
        $security_check['unsecurewebservicesaccounts'][$tmp_key] = 'Anonymous';

    $gXml_def = '<tab><name>security</name>
      <args>
        <tabs type="array">'.huixml_encode($tabs).'</tabs>
        <tabactionfunction>default_tab_builder</tabactionfunction>
        <activetab>'. (isset($eventData['tab']) ? $eventData['tab'] : '').'</activetab>
      </args>
      <children>
    
        <vertgroup><name></name>
          <children>
    
            <table><name>accesslog</name>
              <args>
                <headers type="array">'.huixml_encode(array('0' => array('label' => $gLocale -> GetStr('accesslog.header')))).'</headers>
              </args>
              <children>
    
                <text row="0" col="0"><name>accesslog</name>
                  <args>
                    <readonly>true</readonly>
                    <value type="encoded">'.urlencode($amp_security -> GetAccessLog()).'</value>
                    <cols>120</cols>
                    <rows>15</rows>
                  </args>
                </text>
                
                <button row="1" col="0"><name>erase</name>
                  <args>
                    <themeimage>edittrash</themeimage>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('eraselog.button')).'</label>
                    <horiz>true</horiz>
                    <frame>false</frame>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default', ''), array('action', 'clean_access_log', '')))).'</action>
                  </args>
                </button>
    
              </children>
            </table>
    
          </children>
        </vertgroup>
    
        <vertgroup>
          <children>
    
            <label>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('root_sessions.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <form><name>rootsessions</name>
              <args>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'logout_sessions')))).'</action>
              </args>
              <children>
            <listbox><name>sessions</name>
              <args>
                <size>5</size>
                <elements type="array">'.huixml_encode($root_sessions).'</elements>
                <multiselect>true</multiselect>
                <disp>action</disp>
              </args>
            </listbox>
              </children>
            </form>
    
            <button>
              <args>
                <horiz>true</horiz>
                <frame>false</frame>
                <label type="encoded">'.urlencode($gLocale -> GetStr('logout_sessions.button')).'</label>
                <themeimage>exit</themeimage>
                <formsubmit>rootsessions</formsubmit>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'logout_sessions')))).'</action>
              </args>
            </button>';

    $gXml_def.= '        <label>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('users_sessions.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
            <form><name>userssessions</name>
              <args>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'logout_sessions')))).'</action>
              </args>
              <children>
            <listbox><name>sessions</name>
              <args>
                <size>15</size>
                <elements type="array">'.huixml_encode($users_sessions).'</elements>
                <multiselect>true</multiselect>
                <disp>action</disp>
              </args>
            </listbox>
              </children>
            </form>
    
            <button>
              <args>
                <horiz>true</horiz>
                <frame>false</frame>
                <label type="encoded">'.urlencode($gLocale -> GetStr('logout_sessions.button')).'</label>
                <themeimage>exit</themeimage>
                <formsubmit>userssessions</formsubmit>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'default'), array('action', 'logout_sessions')))).'</action>
              </args>
            </button>';

    $gXml_def.= '      </children>
        </vertgroup>
    
        <vertgroup><name></name>
          <children>
    
            <label><name>tabtitle</name>
              <args>
                <label type="encoded">'.urlencode($gLocale -> GetStr('securitycheck.label')).'</label>
                <bold>true</bold>
              </args>
            </label>
    
                    <grid>
                      <children>
    
                        <button row="0" col="0"><name>check</name>
                          <args>
                            <themeimage>'. ($security_check['rootpasswordcheck'] == false ? 'button_cancel' : 'button_ok').'</themeimage>
                            <disabled>true</disabled>
                          </args>
                        </button>
    
                        <label row="0" col="1"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('root_password_check.label')).'</label>
                          </args>
                        </label>
    
                        <label row="0" col="2"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr($security_check['rootpasswordcheck'] ? 'check_password_ok.label' : 'check_password_unsafe.label')).'</label>
                          </args>
                        </label>
    
                        <button row="1" col="0"><name>check</name>
                          <args>
                            <themeimage>'. ($security_check['rootdbpasswordcheck'] == false ? 'button_cancel' : 'button_ok').'</themeimage>
                            <disabled>true</disabled>
                          </args>
                        </button>
    
                        <label row="1" col="1"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('root_dbpassword_check.label')).'</label>
                          </args>
                        </label>
    
                        <label row="1" col="2"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr($security_check['rootdbpasswordcheck'] ? 'check_password_ok.label' : 'check_password_unsafe.label')).'</label>
                          </args>
                        </label>
    
                        <button row="2" col="0" halign="" valign="top"><name>check</name>
                          <args>
                            <themeimage>'. (count($security_check['siteswithunsecuredbpassword']) ? 'button_cancel' : 'button_ok').'</themeimage>
                            <disabled>true</disabled>
                          </args>
                        </button>
    
                        <label row="2" col="1" halign="" valign="top"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('unsecure_sites_db_check.label')).'</label>
                          </args>
                        </label>
    
                        <listbox row="2" col="2"><name>check</name>
                          <args>
                            <readonly>true</readonly>
                            <elements type="array">'.huixml_encode($security_check['siteswithunsecuredbpassword']).'</elements>
                            <size>5</size>
                          </args>
                        </listbox>
    
                        <button row="3" col="0" halign="" valign="top"><name>check</name>
                          <args>
                            <themeimage>'. (count($security_check['unsecurelocalaccounts']) ? 'button_cancel' : 'button_ok').'</themeimage>
                            <disabled>true</disabled>
                          </args>
                        </button>
    
                        <label row="3" col="1" halign="" valign="top"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('local_accounts_check.label')).'</label>
                          </args>
                        </label>
    
                        <listbox row="3" col="2"><name>check</name>
                          <args>
                            <readonly>true</readonly>
                            <elements type="array">'.huixml_encode($security_check['unsecurelocalaccounts']).'</elements>
                            <size>5</size>
                          </args>
                        </listbox>
    
                        <button row="4" col="0" halign="" valign="top"><name>check</name>
                          <args>
                            <themeimage>'. (count($security_check['unsecurewebservicesprofiles']) ? 'button_cancel' : 'button_ok').'</themeimage>
                            <disabled>true</disabled>
                          </args>
                        </button>
    
                        <label row="4" col="1" halign="" valign="top"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('webservices_profiles_check.label')).'</label>
                          </args>
                        </label>
    
                        <listbox row="4" col="2"><name>check</name>
                          <args>
                            <readonly>true</readonly>
                            <elements type="array">'.huixml_encode($security_check['unsecurewebservicesprofiles']).'</elements>
                            <size>5</size>
                          </args>
                        </listbox>
    
                        <button row="5" col="0" halign="" valign="top"><name>check</name>
                          <args>
                            <themeimage>'. (count($security_check['unsecurewebservicesaccounts']) ? 'button_cancel' : 'button_ok').'</themeimage>
                            <disabled>true</disabled>
                          </args>
                        </button>
    
                        <label row="5" col="1" halign="" valign="top"><name>check</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('webservices_accounts_check.label')).'</label>
                          </args>
                        </label>
    
                        <listbox row="5" col="2"><name>check</name>
                          <args>
                            <readonly>true</readonly>
                            <elements type="array">'.huixml_encode($security_check['unsecurewebservicesaccounts']).'</elements>
                            <size>5</size>
                          </args>
                        </listbox>
    
                      </children>
                    </grid>
    
          </children>
        </vertgroup>
    
      </children>
    </tab>';

    $gPage_title.= ' - '.$gLocale -> GetStr('security_check.title');
}

function settings_tab_builder($tab) {
    return build_events_call_string('', array(array('main', 'settings', array('tab' => $tab))));
}

$gMain_disp -> AddEvent('settings', 'main_settings');
function main_settings($eventData) {
    global $gEnv, $gXml_def, $gLocale, $gPage_title;

    $amp_security = new SecurityLayer();
    $session_lifetime = $amp_security -> GetSessionLifetime();
    $max_wrong_logins = $amp_security -> GetMaxWrongLogins();
    $wrong_login_delay = $amp_security -> GetWrongLoginDelay();
    $lock_unsecure_webservices = $amp_security -> GetUnsecureWebServicesLock();
    $only_https_root = $amp_security -> GetOnlyHttpsRootAccess();
    $only_https_site = $amp_security -> GetOnlyHttpsSiteAccess();

    $alerts_on = $amp_security -> GetAlertEvents();

    $wrong_local_root_login = $alerts_on['wronglocalrootlogin'] ? 'true' : 'false';
    $wrong_local_user_login = $alerts_on['wronglocaluserlogin'] ? 'true' : 'false';
    $wrong_remote_login = $alerts_on['wrongremotelogin'] ? 'true' : 'false';
    $module_operation = $alerts_on['moduleoperation'] ? 'true' : 'false';
    $sitemodule_operation = $alerts_on['modulesiteoperation'] ? 'true' : 'false';
    $site_operation = $alerts_on['siteoperation'] ? 'true' : 'false';

    $reports_interval = $amp_security -> GetReportsInterval();
    $reports_enabled = $reports_interval ? 'true' : 'false';
    $report_destination_email = $amp_security -> GetReportsEmail();

    $alert_destination_email = $amp_security -> GetAlertsEmail();

    $tabs[0]['label'] = $gLocale -> GetStr('security_presets.tab');
    $tabs[1]['label'] = $gLocale -> GetStr('access_settings.tab');
    $tabs[2]['label'] = $gLocale -> GetStr('alerts_settings.tab');
    $tabs[3]['label'] = $gLocale -> GetStr('reports_settings.tab');

    $gXml_def = '<tab><name>security</name>
      <args>
        <tabs type="array">'.huixml_encode($tabs).'</tabs>
        <tabactionfunction>settings_tab_builder</tabactionfunction>
        <activetab>'. (isset($eventData['tab']) ? $eventData['tab'] : '').'</activetab>
      </args>
      <children>
    
        <vertgroup><name></name>
          <children>
    
            <table><name>presets</name>
              <args>
                <headers type="array">'.huixml_encode(array(0 => array('label' => $gLocale -> GetStr('security_presets.label')))).'</headers>
              </args>
              <children>
    
              <button row="0" col="0"><name>preset</name>
                <args>
                  <themeimage>decrypted</themeimage>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_low.label')).'</label>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_security_preset', array('preset' => AMPOLIROS_SECURITY_PRESET_LOW))))).'</action>
                </args>
              </button>
    
              <label row="0" col="1"><name>details</name>
                <args>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_low.text')).'</label>
                  <nowrap>false</nowrap>
                </args>
              </label>
    
              <button row="1" col="0"><name>preset</name>
                <args>
                  <themeimage>encrypted</themeimage>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_normal.label')).'</label>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_security_preset', array('preset' => AMPOLIROS_SECURITY_PRESET_NORMAL))))).'</action>
                </args>
              </button>
    
              <label row="1" col="1"><name>details</name>
                <args>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_normal.text')).'</label>
                  <nowrap>false</nowrap>
                </args>
              </label>
    
              <button row="2" col="0"><name>preset</name>
                <args>
                  <themeimage>encrypted</themeimage>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_high.label')).'</label>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_security_preset', array('preset' => AMPOLIROS_SECURITY_PRESET_HIGH))))).'</action>
                </args>
              </button>
    
              <label row="2" col="1"><name>details</name>
                <args>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_high.text')).'</label>
                  <nowrap>false</nowrap>
                </args>
              </label>
    
              <button row="3" col="0"><name>preset</name>
                <args>
                  <themeimage>encrypted</themeimage>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_paranoid.label')).'</label>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_security_preset', array('preset' => AMPOLIROS_SECURITY_PRESET_PARANOID))))).'</action>
                </args>
              </button>
    
              <label row="3" col="1"><name>details</name>
                <args>
                  <label type="encoded">'.urlencode($gLocale -> GetStr('level_paranoid.text')).'</label>
                  <nowrap>false</nowrap>
                </args>
              </label>
    
              </children>
            </table>
    
          </children>
        </vertgroup>
    
        <vertgroup><name></name>
          <children>
    
            <table><name>access</name>
              <args>
                <headers type="array">'.huixml_encode(array(0 => array('label' => $gLocale -> GetStr('access_settings.label')))).'</headers>
              </args>
              <children>
    
                <form row="0" col="0"><name>access</name>
                  <args>
                    <method>post</method>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_access_prefs', '')))).'</action>
                  </args>
                  <children>
                    <grid>
                      <children>
    
                        <label row="0" col="0"><name>sessionlifetime</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('session_lifetime.label')).'</label>
                          </args>
                        </label>
    
                        <string row="0" col="1"><name>sessionlifetime</name>
                          <args>
                            <value>'.$session_lifetime.'</value>
                            <disp>action</disp>
                            <size>10</size>
                          </args>
                        </string>
    
                        <label row="1" col="0"><name>maxwronglogins</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('max_wrong_logins.label')).'</label>
                          </args>
                        </label>
    
                        <string row="1" col="1"><name>maxwronglogins</name>
                          <args>
                            <value>'.$max_wrong_logins.'</value>
                            <disp>action</disp>
                            <size>4</size>
                          </args>
                        </string>
    
                        <label row="2" col="0"><name>wronglogindelay</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('wrong_login_delay.label')).'</label>
                          </args>
                        </label>
    
                        <string row="2" col="1"><name>wronglogindelay</name>
                          <args>
                            <value>'.$wrong_login_delay.'</value>
                            <disp>action</disp>
                            <size>3</size>
                          </args>
                        </string>
    
                        <label row="3" col="0"><name>lockunsecurewebservices</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('block_unsecure_webservices.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="3" col="1"><name>lockunsecurewebservices</name>
                          <args>
                            <checked>'. ($lock_unsecure_webservices ? 'true' : 'false').'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="4" col="0"><name>onlyhttpsroot</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('only_https_root.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="4" col="1"><name>onlyhttpsroot</name>
                          <args>
                            <checked>'. ($only_https_root ? 'true' : 'false').'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="5" col="0"><name>onlyhttpssite</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('only_https_site.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="5" col="1"><name>onlyhttpssite</name>
                          <args>
                            <checked>'. ($only_https_site ? 'true' : 'false').'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                      </children>
                    </grid>
                  </children>
                </form>
    
                <button row="1" col="0"><name>apply</name>
                  <args>
                    <horiz>true</horiz>
                    <frame>false</frame>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('apply.submit')).'</label>
                    <themeimage>button_ok</themeimage>
                    <formsubmit>access</formsubmit>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_access_prefs', '')))).'</action>
                  </args>
                </button>
              </children>
            </table>
    
          </children>
        </vertgroup>
    
        <vertgroup><name></name>
          <children>
    
            <table><name>alerts</name>
              <args>
                <headers type="array">'.huixml_encode(array(0 => array('label' => $gLocale -> GetStr('alerts_settings.label')))).'</headers>
              </args>
              <children>
    
                <form row="0" col="0"><name>alerts</name>
                  <args>
                    <method>post</method>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_alerts_prefs', '')))).'</action>
                  </args>
                  <children>
                    <grid>
                      <children>
    
                        <label row="0" col="0"><name>alertonevents</name>
                          <args>
                            <bold>true</bold>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('alert_on_events.label')).'</label>
                          </args>
                        </label>
    
                        <label row="1" col="0"><name>wronglocalrootlogin</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('wrong_local_root_login.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="1" col="1"><name>wronglocalrootlogin</name>
                          <args>
                            <checked>'.$wrong_local_root_login.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="2" col="0"><name>wronglocaluserlogin</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('wrong_local_user_login.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="2" col="1"><name>wronglocaluserlogin</name>
                          <args>
                            <checked>'.$wrong_local_user_login.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="3" col="0"><name>wrongremotelogin</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('wrong_remote_login.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="3" col="1"><name>wrongremotelogin</name>
                          <args>
                            <checked>'.$wrong_remote_login.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="4" col="0"><name>moduleoperation</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('module_operation.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="4" col="1"><name>moduleoperation</name>
                          <args>
                            <checked>'.$module_operation.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="5" col="0"><name>sitemoduleoperation</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('sitemodule_operation.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="5" col="1"><name>sitemoduleoperation</name>
                          <args>
                            <checked>'.$sitemodule_operation.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="6" col="0"><name>siteoperation</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('site_operation.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="6" col="1"><name>siteoperation</name>
                          <args>
                            <checked>'.$site_operation.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="7" col="0"><name>alertdestinationemail</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('alert_destination_email.label')).'</label>
                          </args>
                        </label>
    
                        <string row="7" col="1"><name>alertdestinationemail</name>
                          <args>
                            <value type="encoded">'.urlencode($alert_destination_email).'</value>
                            <disp>action</disp>
                            <size>25</size>
                          </args>
                        </string>
    
                      </children>
                    </grid>
                  </children>
                </form>
    
                <button row="1" col="0"><name>apply</name>
                  <args>
                    <horiz>true</horiz>
                    <frame>false</frame>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('apply.submit')).'</label>
                    <themeimage>button_ok</themeimage>
                    <formsubmit>alerts</formsubmit>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_alerts_prefs', '')))).'</action>
                  </args>
                </button>
              </children>
            </table>
    
          </children>
        </vertgroup>
    
        <vertgroup><name></name>
          <children>
    
            <table><name>alerts</name>
              <args>
                <headers type="array">'.huixml_encode(array(0 => array('label' => $gLocale -> GetStr('reports_settings.label')))).'</headers>
              </args>
              <children>
    
                <form row="0" col="0"><name>alerts</name>
                  <args>
                    <method>post</method>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_reports_prefs', '')))).'</action>
                  </args>
                  <children>
                    <grid>
                      <children>
    
                        <label row="0" col="0"><name>enablereports</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('enable_reports.label')).'</label>
                          </args>
                        </label>
    
                        <checkbox row="0" col="1"><name>enablereports</name>
                          <args>
                            <checked>'.$reports_enabled.'</checked>
                            <disp>action</disp>
                          </args>
                        </checkbox>
    
                        <label row="1" col="0"><name>reportsinterval</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('reports_interval.label')).'</label>
                          </args>
                        </label>
    
                        <string row="1" col="1"><name>reportsinterval</name>
                          <args>
                            <value>'.$reports_interval.'</value>
                            <disp>action</disp>
                            <size>3</size>
                          </args>
                        </string>
    
                        <label row="2" col="0"><name>reportdestinationemail</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale -> GetStr('report_destination_email.label')).'</label>
                          </args>
                        </label>
    
                        <string row="2" col="1"><name>reportdestinationemail</name>
                          <args>
                            <value type="encoded">'.urlencode($report_destination_email).'</value>
                            <disp>action</disp>
                            <size>25</size>
                          </args>
                        </string>
    
                      </children>
                    </grid>
                  </children>
                </form>
    
                <button row="1" col="0"><name>apply</name>
                  <args>
                    <horiz>true</horiz>
                    <frame>false</frame>
                    <label type="encoded">'.urlencode($gLocale -> GetStr('apply.submit')).'</label>
                    <themeimage>button_ok</themeimage>
                    <formsubmit>alerts</formsubmit>
                    <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'settings', ''), array('action', 'set_reports_prefs', '')))).'</action>
                  </args>
                </button>
              </children>
            </table>
    
          </children>
        </vertgroup>
    
      </children>
    </tab>';

    $gPage_title.= ' - '.$gLocale -> GetStr('settings.title');
}

$gMain_disp -> Dispatch();

// ----- Rendering -----
//
$gHui -> AddChild(new HuiAmpPage('page', array('pagetitle' => $gPage_title, 'menu' => get_ampoliros_root_menu_def($gEnv['root']['locale']['language']), 'toolbars' => array(new HuiAmpToolbar('main', array('toolbars' => $gToolbars))), 'maincontent' => new HuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status, 'icon' => 'package_utilities')));

$gHui -> Render();

?>
