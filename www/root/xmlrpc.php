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
// $Id: xmlrpc.php,v 1.27 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
Carthag :: import('com.solarix.ampoliros.hui.Hui');
Carthag :: import('com.solarix.ampoliros.webservices.xmlrpc.XmlRpcAccount');
Carthag :: import('com.solarix.ampoliros.webservices.xmlrpc.XmlRpcProfile');
Carthag :: import('com.solarix.ampoliros.webservices.xmlrpc.XmlRpcUser');
Carthag :: import('com.solarix.ampoliros.webservices.xmlrpc.XmlRpc_Client');
OpenLibrary('ampshared.library');

$log = new Logger(AMP_LOG);
$amp_locale = new Locale('amp_root_xmlrpc', $gEnv['root']['locale']['language']);
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('xmlrpc_title')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('xmlrpc_title'), 'icon' => 'network'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

$menu_frame = new HuiHorizGroup('menuframe');
$menu_frame -> AddChild(new HuiMenu('magellanmainmenu', array('menu' => get_ampoliros_root_menu_def($env['sitelocale']))));
$hui_mainvertgroup -> AddChild($menu_frame);

// Profiles bar
//
$hui_profilestoolbar = new HuiToolBar('profilestoolbar');

$home_action = new HuiEventsCall();
$home_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_homebutton = new HuiButton('homebutton', array('label' => $amp_locale -> GetStr('profiles_button'), 'themeimage' => 'view_tree', 'horiz' => 'true', 'action' => $home_action -> GetEventsCallString()));
$hui_profilestoolbar -> AddChild($hui_homebutton);

$newprofile_action = new HuiEventsCall();
$newprofile_action -> AddEvent(new HuiEvent('main', 'newprofile', ''));
$hui_newprofilebutton = new HuiButton('newprofilebutton', array('label' => $amp_locale -> GetStr('newprofile_button'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => $newprofile_action -> GetEventsCallString()));
$hui_profilestoolbar -> AddChild($hui_newprofilebutton);

// Users bar
//
$hui_userstoolbar = new HuiToolBar('userstoolbar');

$users_action = new HuiEventsCall();
$users_action -> AddEvent(new HuiEvent('main', 'users', ''));
$hui_usersbutton = new HuiButton('usersbutton', array('label' => $amp_locale -> GetStr('users_button'), 'themeimage' => 'view_detailed', 'horiz' => 'true', 'action' => $users_action -> GetEventsCallString()));
$hui_userstoolbar -> AddChild($hui_usersbutton);

$newuser_action = new HuiEventsCall();
$newuser_action -> AddEvent(new HuiEvent('main', 'newuser', ''));
$hui_newuserbutton = new HuiButton('newuserbutton', array('label' => $amp_locale -> GetStr('newuser_button'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => $newuser_action -> GetEventsCallString()));
$hui_userstoolbar -> AddChild($hui_newuserbutton);

// Accounts tool bar
//
$hui_accountstoolbar = new HuiToolBar('accountstoolbar');

$accounts_action = new HuiEventsCall();
$accounts_action -> AddEvent(new HuiEvent('main', 'accounts', ''));
$hui_accountsbutton = new HuiButton('accountsbutton', array('label' => $amp_locale -> GetStr('accounts_button'), 'themeimage' => 'view_detailed', 'horiz' => 'true', 'action' => $accounts_action -> GetEventsCallString()));
$hui_accountstoolbar -> AddChild($hui_accountsbutton);

$newaccount_action = new HuiEventsCall();
$newaccount_action -> AddEvent(new HuiEvent('main', 'newaccount', ''));
$hui_newaccountbutton = new HuiButton('newaccountbutton', array('label' => $amp_locale -> GetStr('newaccount_button'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => $newaccount_action -> GetEventsCallString()));
$hui_accountstoolbar -> AddChild($hui_newaccountbutton);

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

$hui_toolbarframe -> AddChild($hui_profilestoolbar);
$hui_toolbarframe -> AddChild($hui_userstoolbar);
$hui_toolbarframe -> AddChild($hui_accountstoolbar);
$hui_toolbarframe -> AddChild($hui_helptoolbar);
$hui_mainvertgroup -> AddChild($hui_toolbarframe);

$hui_mainframe = new HuiHorizFrame('mainframe');
$hui_mainstatus = new HuiStatusBar('mainstatusbar');

// Pass dispatcher
//
$pass_disp = new HuiDispatcher('pass');

$pass_disp -> AddEvent('adduser', 'pass_adduser');
function pass_adduser($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    $xuser = new XmlRpcUser($env['ampdb']);
    $result = $xuser -> Add($eventData['username'], $eventData['password'], $eventData['profileid'], $eventData['siteid']);

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('adduserok_status');
}

$pass_disp -> AddEvent('removeuser', 'pass_removeuser');
function pass_removeuser($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['userid'])) {
        $xuser = new XmlRpcUser($env['ampdb'], $eventData['userid']);
        $result = $xuser -> Remove();
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('remuserok_status');
}

$pass_disp -> AddEvent('chpasswd', 'pass_chpasswd');
function pass_chpasswd($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['userid'])) {
        if (strlen($eventData['password'])) {
            $xuser = new XmlRpcUser($env['ampdb'], $eventData['userid']);
            $result = $xuser -> ChangePassword($eventData['password']);
        }
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('chpasswdok_status');
}

$pass_disp -> AddEvent('assignprofile', 'pass_assignprofile');
function pass_assignprofile($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['userid'])) {
        $xuser = new XmlRpcUser($env['ampdb'], $eventData['userid']);
        $result = $xuser -> AssignProfile($eventData['profileid']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('chprofileok_status');
}

$pass_disp -> AddEvent('assignsite', 'pass_assignsite');
function pass_assignsite($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['userid'])) {
        $xuser = new XmlRpcUser($env['ampdb'], $eventData['userid']);
        $result = $xuser -> AssignSite($eventData['siteid']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('chsiteok_status');
}

$pass_disp -> AddEvent('newprofile', 'pass_newprofile');
function pass_newprofile($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['profilename'])) {
        $xprofile = new XmlRpcProfile($env['ampdb']);
        $result = $xprofile -> Add($eventData['profilename']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('newprofileok_status');
}

$pass_disp -> AddEvent('remprofile', 'pass_remprofile');
function pass_remprofile($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['profileid'])) {
        $xprofile = new XmlRpcProfile($env['ampdb'], $eventData['profileid']);
        $result = $xprofile -> Remove();
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('remprofileok_status');
}

$pass_disp -> AddEvent('renprofile', 'pass_renprofile');
function pass_renprofile($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['profileid']) and !empty($eventData['profilename'])) {
        $xprofile = new XmlRpcProfile($env['ampdb'], $eventData['profileid']);
        $result = $xprofile -> Rename($eventData['profilename']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('renprofileok_status');
}

$pass_disp -> AddEvent('enablenode', 'pass_enablenode');
function pass_enablenode($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['profileid']) and strlen($eventData['nodetype']) and !empty($eventData['module'])) {
        $xprofile = new XmlRpcProfile($env['ampdb'], $eventData['profileid']);
        $result = $xprofile -> EnableNode($eventData['nodetype'], $eventData['module'], $eventData['method']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('nodeenabledok_status');
}

$pass_disp -> AddEvent('disablenode', 'pass_disablenode');
function pass_disablenode($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['profileid']) and strlen($eventData['nodetype']) and !empty($eventData['module'])) {
        $xprofile = new XmlRpcProfile($env['ampdb'], $eventData['profileid']);
        $result = $xprofile -> DisableNode($eventData['nodetype'], $eventData['module'], $eventData['method']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('nodedisabledok_status');
}

$pass_disp -> AddEvent('createaccount', 'pass_createaccount');
function pass_createaccount($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $acc = new XmlRpcAccount($env['ampdb']);
    if ($acc -> Create($eventData['name'], $eventData['host'], $eventData['port'], $eventData['cgi'], $eventData['username'], $eventData['password'], $eventData['proxy'], $eventData['proxyport'])) {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('accountcreated_status');
    } else {
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('accountnotcreated_status');
    }
}

$pass_disp -> AddEvent('removeaccount', 'pass_removeaccount');
function pass_removeaccount($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['accountid'])) {
        $acc = new XmlRpcAccount($env['ampdb'], $eventData['accountid']);
        $result = $acc -> Remove();
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('remaccountok_status');
}

$pass_disp -> AddEvent('updateaccount', 'pass_updateaccount');
function pass_updateaccount($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $result = false;

    if (!empty($eventData['accountid'])) {
        $acc = new XmlRpcAccount($env['ampdb'], $eventData['accountid']);
        $result = $acc -> Update($eventData['name'], $eventData['host'], $eventData['port'], $eventData['cgi'], $eventData['username'], $eventData['password'], $eventData['proxy'], $eventData['proxyport']);
    }

    if ($result)
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('accountupdated_status');
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

function remoteprofiles_list_action_builder($pageNumber) {
    return build_events_call_string('', array(array('main', 'default', array('remoteprofilespage' => $pageNumber))));
}

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar, $hui_mainstatus;

    $prof_query = & $env['ampdb'] -> Execute('SELECT id,profilename '.'FROM xmlrpcprofiles '.'ORDER BY profilename');

    $profiles = array();
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['profilename'];
        $prof_query -> MoveNext();
    }

    if (count($profiles)) {
        $headers[0]['label'] = $amp_locale -> GetStr('profilename_header');

        $row = 0;

        $hui_profiles_table = new HuiTable('profilestable', array('headers' => $headers, 'rowsperpage' => '10', 'pagesactionfunction' => 'remoteprofiles_list_action_builder', 'pagenumber' => $eventData['remoteprofilespage']));

        while (list ($id, $profile_name) = each($profiles)) {
            $hui_profiles_table -> AddChild(new HuiLabel('profnamelabel'.$row, array('label' => $profile_name)), $row, 0);

            $hui_profile_toolbar[$row] = new HuiToolBar('moduletoolbar'.$row);

            $profile_action[$row] = new HuiEventsCall();
            $profile_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $id)));
            $hui_profile_button[$row] = new HuiButton('profilebutton'.$row, array('label' => $amp_locale -> GetStr('editprofile_label'), 'horiz' => 'true', 'themeimage' => 'view_tree', 'action' => $profile_action[$row] -> GetEventsCallString()));
            $hui_profile_toolbar[$row] -> AddChild($hui_profile_button[$row]);

            $rename_action[$row] = new HuiEventsCall();
            $rename_action[$row] -> AddEvent(new HuiEvent('main', 'renameprofile', array('profileid' => $id)));
            $hui_rename_button[$row] = new HuiButton('renamebutton'.$row, array('label' => $amp_locale -> GetStr('renameprofile_label'), 'horiz' => 'true', 'themeimage' => 'edit', 'action' => $rename_action[$row] -> GetEventsCallString()));
            $hui_profile_toolbar[$row] -> AddChild($hui_rename_button[$row]);

            $remove_action[$row] = new HuiEventsCall();
            $remove_action[$row] -> AddEvent(new HuiEvent('main', 'default', ''));
            $remove_action[$row] -> AddEvent(new HuiEvent('pass', 'remprofile', array('profileid' => $id)));
            $hui_remove_button[$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removeprofile_label'), 'horiz' => 'true', 'themeimage' => 'edittrash', 'action' => $remove_action[$row] -> GetEventsCallString(), 'needconfirm' => 'true', 'confirmmessage' => sprintf($amp_locale -> GetStr('removeprofilequestion_label'), $profile_name)));
            $hui_profile_toolbar[$row] -> AddChild($hui_remove_button[$row]);

            $hui_profiles_table -> AddChild($hui_profile_toolbar[$row], $row, 1);

            $row ++;
        }

        $hui_mainframe -> AddChild($hui_profiles_table);
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('noprofiles_status');

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('default_title');
}

$main_disp -> AddEvent('newprofile', 'main_newprofile');
function main_newprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_profile_grid = new HuiGrid('newprofilegrid', array('rows' => '2', 'cols' => '2'));

    // Profile fields
    //
    $hui_profile_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('profilename_label').' (*)')), 0, 0);
    $hui_profile_grid -> AddChild(new HuiString('profilename', array('disp' => 'pass')), 0, 1);

    $hui_vgroup -> AddChild($hui_profile_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('newprofile_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'newprofile', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_form = new HuiForm('newprofileform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('newprofile_title');
}

$main_disp -> AddEvent('renameprofile', 'main_renameprofile');
function main_renameprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $prof_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcprofiles '.'WHERE id='.$eventData['profileid']);

    $prof_data = $prof_query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_profile_grid = new HuiGrid('renprofilegrid', array('rows' => '2', 'cols' => '2'));

    // Profile fields
    //
    $hui_profile_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('profilename_label').' (*)')), 0, 0);
    $hui_profile_grid -> AddChild(new HuiString('profilename', array('disp' => 'pass', 'value' => $prof_data['profilename'])), 0, 1);

    $hui_vgroup -> AddChild($hui_profile_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('renameprofile_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'renprofile', array('profileid' => $eventData['profileid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_form = new HuiForm('renameprofileform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$prof_data['profilename'].' - '.$amp_locale -> GetStr('renameprofile_title');
}

function editprofile_list_action_builder($pageNumber) {
    $tmp_main_disp = new HuiDispatcher('main');

    $event_data = $tmp_main_disp -> GetEventData();
    return build_events_call_string('', array(array('main', 'editprofile', array('editprofilepage' => $pageNumber, 'profileid' => $event_data['profileid']))));
}

$main_disp -> AddEvent('editprofile', 'main_editprofile');
function main_editprofile($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar, $gEnv;

    $prof_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcprofiles '.'WHERE id='.$eventData['profileid']);

    $prof_data = $prof_query -> Fields();

    $methods_query = & $env['ampdb'] -> Execute('SELECT module,name,unsecure,catalog '.'FROM xmlrpcmethods '.'ORDER BY module, name');

    if ($methods_query -> NumRows()) {
        $nodes = $sec = $desc = array();
        $prev_catalog = $tmp_locale = '';

        while (!$methods_query -> eof) {
            $nodes[$methods_query -> Fields('module')][] = $methods_query -> Fields('name');

            $sec[$methods_query -> Fields('module')][$methods_query -> Fields('name')] = $methods_query -> Fields('unsecure') == $env['ampdb'] -> fmttrue ? false : true;

            $tmp_description = '';
            if (strlen($methods_query -> Fields('catalog'))) {
                if ($prev_catalog != $methods_query -> Fields('catalog'))
                    $tmp_locale = new Locale($methods_query -> Fields('catalog'), $gEnv['root']['locale']['language']);

                $desc[$methods_query -> Fields('module')][$methods_query -> Fields('name')] = $tmp_locale -> GetStr($methods_query -> Fields('name'));

                $prev_catalog = $methods_query -> Fields('catalog');
            }

            $methods_query -> MoveNext();
        }

        $row = 0;

        $headers[0]['label'] = '';
        $headers[1]['label'] = $amp_locale -> GetStr('xmlrpcmodule_header');
        $headers[2]['label'] = '';
        $headers[3]['label'] = $amp_locale -> GetStr('xmlrpcmethod_header');
        $headers[4]['label'] = $amp_locale -> GetStr('docstring_header');
        $headers[5]['label'] = $amp_locale -> GetStr('security_header');

        $hui_methods_table = new HuiTable('methodstable', array('headers' => $headers, 'rowsperpage' => '15', 'pagesactionfunction' => 'editprofile_list_action_builder', 'pagenumber' => $eventData['editprofilepage'], 'sessionobjectusername' => $eventData['profileid']));

        while (list ($module, $methods) = each($nodes)) {
            $xprofile = new XmlRpcProfile($env['ampdb'], $eventData['profileid']);
            $node_state = $xprofile -> NodeCheck(XMLRPCPROFILE_NODETYPE_MODULE, $module);

            switch ($node_state) {
                case XMLRPCPROFILE_MODULENODE_FULLYENABLED :
                    $icon = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
                    $enabled = true;
                    break;

                case XMLRPCPROFILE_MODULENODE_PARTIALLYENABLED :
                    $icon = $hui_mainframe -> mThemeHandler -> mStyle['goldball'];
                    $enabled = true;
                    break;

                case XMLRPCPROFILE_MODULENODE_NOTENABLED :
                    $icon = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
                    $enabled = false;
                    break;
            }

            $hui_methods_table -> AddChild(new HuiImage('statusimage'.$row, array('imageurl' => $icon)), $row, 0);
            $hui_methods_table -> AddChild(new HuiLabel('modulelabel'.$row, array('label' => $module)), $row, 1);

            $hui_module_toolbar[$row] = new HuiToolBar('moduletoolbar'.$row);

            if ($enabled) {
                $disable_action[$row] = new HuiEventsCall();
                $disable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                $disable_action[$row] -> AddEvent(new HuiEvent('pass', 'disablenode', array('nodetype' => XMLRPCPROFILE_NODETYPE_MODULE, 'module' => $module, 'profileid' => $eventData['profileid'])));
                $hui_disable_button[$row] = new HuiButton('disablebutton'.$row, array(label => $amp_locale -> GetStr('disablenode_label'), 'horiz' => 'true', 'themeimage' => 'lock', 'action' => $disable_action[$row] -> GetEventsCallString()));
                $hui_module_toolbar[$row] -> AddChild($hui_disable_button[$row]);
            }

            if (!$enabled or $node_state == XMLRPCPROFILE_MODULENODE_PARTIALLYENABLED) {
                $enable_action[$row] = new HuiEventsCall();
                $enable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                $enable_action[$row] -> AddEvent(new HuiEvent('pass', 'enablenode', array('nodetype' => XMLRPCPROFILE_NODETYPE_MODULE, 'module' => $module, 'profileid' => $eventData['profileid'])));
                $hui_enable_button[$row] = new HuiButton('enablebutton'.$row, array(label => $amp_locale -> GetStr('enablenode_label'), 'horiz' => 'true', 'themeimage' => 'unlock', 'action' => $enable_action[$row] -> GetEventsCallString()));
                $hui_module_toolbar[$row] -> AddChild($hui_enable_button[$row]);
            }

            $hui_methods_table -> AddChild($hui_module_toolbar[$row], $row, 6);

            $row ++;

            while (list (, $method) = each($methods)) {
                $node_state = $xprofile -> NodeCheck(XMLRPCPROFILE_NODETYPE_METHOD, $module, $method);

                switch ($node_state) {
                    case XMLRPCPROFILE_METHODNODE_ENABLED :
                        $icon = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
                        $enabled = true;
                        break;

                    case XMLRPCPROFILE_METHODNODE_NOTENABLED :
                        $icon = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
                        $enabled = false;
                        break;
                }

                $hui_methods_table -> AddChild(new HuiImage('statusimage'.$row, array('imageurl' => $icon)), $row, 2);
                $hui_methods_table -> AddChild(new HuiLabel('methodlabel'.$row, array('label' => $method)), $row, 3);
                $img = ($sec[$module][$method] == true ? 'button_ok' : 'button_cancel');

                $secure_image = $hui_methods_table -> mThemeHandler -> mIconsBase.$hui_methods_table -> mThemeHandler -> mIconsSet['actions'][$img]['base'].'/actions/'.$hui_methods_table -> mThemeHandler -> mIconsSet['actions'][$img]['file'];

                $hui_methods_table -> AddChild(new HuiLabel('desclabel'.$row, array('label' => $desc[$module][$method])), $row, 4);
                $hui_methods_table -> AddChild(new HuiImage('secure'.$row, array('imageurl' => $secure_image)), $row, 5);

                $hui_method_toolbar[$row] = new HuiToolBar('methodtoolbar'.$row);

                if ($enabled) {
                    $disable_action[$row] = new HuiEventsCall();
                    $disable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                    $disable_action[$row] -> AddEvent(new HuiEvent('pass', 'disablenode', array('nodetype' => XMLRPCPROFILE_NODETYPE_METHOD, 'method' => $method, 'module' => $module, 'profileid' => $eventData['profileid'])));
                    $hui_disable_button[$row] = new HuiButton('disablebutton'.$row, array(label => $amp_locale -> GetStr('disablenode_label'), 'horiz' => 'true', 'themeimage' => 'lock', 'action' => $disable_action[$row] -> GetEventsCallString()));
                    $hui_method_toolbar[$row] -> AddChild($hui_disable_button[$row]);
                } else {
                    $enable_action[$row] = new HuiEventsCall();
                    $enable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                    $enable_action[$row] -> AddEvent(new HuiEvent('pass', 'enablenode', array('nodetype' => XMLRPCPROFILE_NODETYPE_METHOD, 'method' => $method, 'module' => $module, 'profileid' => $eventData['profileid'])));
                    $hui_enable_button[$row] = new HuiButton('enablebutton'.$row, array(label => $amp_locale -> GetStr('enablenode_label'), 'horiz' => 'true', 'themeimage' => 'unlock', 'action' => $enable_action[$row] -> GetEventsCallString()));
                    $hui_method_toolbar[$row] -> AddChild($hui_enable_button[$row]);
                }

                $hui_methods_table -> AddChild($hui_method_toolbar[$row], $row, 6);

                $row ++;
            }
        }

        $hui_mainframe -> AddChild($hui_methods_table);
    }

    $hui_titlebar -> mTitle.= ' - '.$prof_data['profilename'].' - '.$amp_locale -> GetStr('editprofile_title');
}

function users_list_action_builder($pageNumber) {
    return build_events_call_string('', array(array('main', 'users', array('userspage' => $pageNumber))));
}

$main_disp -> AddEvent('users', 'main_users');
function main_users($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar, $hui_mainstatus;

    $users_query = & $env['ampdb'] -> Execute('SELECT id,username,profileid,siteid '.'FROM xmlrpcusers '.'ORDER BY username');

    $prof_query = & $env['ampdb'] -> Execute('SELECT id,profilename '.'FROM xmlrpcprofiles '.'ORDER BY profilename');

    $profiles = array();
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['profilename'];
        $prof_query -> MoveNext();
    }

    if ($users_query -> NumRows()) {
        $headers[0]['label'] = $amp_locale -> GetStr('username_header');
        $headers[1]['label'] = $amp_locale -> GetStr('userprofilename_header');
        $headers[2]['label'] = $amp_locale -> GetStr('usersitename_header');

        $row = 0;

        $hui_users_table = new HuiTable('userstable', array('headers' => $headers, 'rowsperpage' => '10', 'pagesactionfunction' => 'users_list_action_builder', 'pagenumber' => $eventData['userspage']));

        while (!$users_query -> eof) {
            $user_data = $users_query -> Fields();

            $site_id = '';
            if ($user_data['siteid']) {
                $site_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id='.$user_data['siteid']);
                $site_id = $site_query -> Fields('siteid');
            }

            $hui_users_table -> AddChild(new HuiLabel('usernamelabel'.$row, array('label' => strlen($user_data['username']) ? $user_data['username'] : $amp_locale -> GetStr('anonuser_label'))), $row, 0);
            $hui_users_table -> AddChild(new HuiLabel('userprofilelabel'.$row, array('label' => $user_data['profileid'] ? $profiles[$user_data['profileid']] : $amp_locale -> GetStr('noprofileid_label'))), $row, 1);
            $hui_users_table -> AddChild(new HuiLabel('usersitelabel'.$row, array('label' => strlen($site_id) ? $site_id : $amp_locale -> GetStr('nositeid_label'))), $row, 2);

            $hui_user_toolbar[$row] = new HuiToolBar('usertoolbar'.$row);

            $profile_action[$row] = new HuiEventsCall();
            $profile_action[$row] -> AddEvent(new HuiEvent('main', 'chprofile', array('userid' => $user_data['id'])));
            $hui_profile_button[$row] = new HuiButton('profilebutton'.$row, array('label' => $amp_locale -> GetStr('chprofile_label'), 'horiz' => 'true', 'themeimage' => 'view_tree', 'action' => $profile_action[$row] -> GetEventsCallString()));
            $hui_user_toolbar[$row] -> AddChild($hui_profile_button[$row]);

            $site_action[$row] = new HuiEventsCall();
            $site_action[$row] -> AddEvent(new HuiEvent('main', 'chsite', array('userid' => $user_data['id'])));
            $hui_site_button[$row] = new HuiButton('sitebutton'.$row, array('label' => $amp_locale -> GetStr('chsite_label'), 'horiz' => 'true', 'themeimage' => 'view_tree', 'action' => $site_action[$row] -> GetEventsCallString()));
            $hui_user_toolbar[$row] -> AddChild($hui_site_button[$row]);

            $chpasswd_action[$row] = new HuiEventsCall();
            $chpasswd_action[$row] -> AddEvent(new HuiEvent('main', 'chpassword', array('userid' => $user_data['id'])));
            $hui_chpasswd_button[$row] = new HuiButton('chpasswdbutton'.$row, array('label' => $amp_locale -> GetStr('chpasswd_label'), 'horiz' => 'true', 'themeimage' => 'edit', 'action' => $chpasswd_action[$row] -> GetEventsCallString()));
            $hui_user_toolbar[$row] -> AddChild($hui_chpasswd_button[$row]);

            $remove_action[$row] = new HuiEventsCall();
            $remove_action[$row] -> AddEvent(new HuiEvent('main', 'users', ''));
            $remove_action[$row] -> AddEvent(new HuiEvent('pass', 'removeuser', array('userid' => $user_data['id'])));
            $hui_remove_button[$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removeuser_label'), 'horiz' => 'true', 'themeimage' => 'edittrash', 'action' => $remove_action[$row] -> GetEventsCallString(), 'needconfirm' => 'true', 'confirmmessage' => sprintf($amp_locale -> GetStr('removeuserquestion_label'), $user_data['username'])));
            $hui_user_toolbar[$row] -> AddChild($hui_remove_button[$row]);

            $hui_users_table -> AddChild($hui_user_toolbar[$row], $row, 3);

            $users_query -> MoveNext();
            $row ++;
        }

        $hui_mainframe -> AddChild($hui_users_table);
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('nousers_status');

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('users_title');
}

$main_disp -> AddEvent('newuser', 'main_newuser');
function main_newuser($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $prof_query = & $env['ampdb'] -> Execute('SELECT id,profilename '.'FROM xmlrpcprofiles '.'ORDER BY profilename');

    $profiles = array();
    $profiles[0] = $amp_locale -> GetStr('noprofileid_label');
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['profilename'];
        $prof_query -> MoveNext();
    }

    $sites_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT id,siteid '.'FROM sites '.'ORDER BY siteid');

    $sites = array();
    $sites[0] = $amp_locale -> GetStr('nositeid_label');
    while (!$sites_query -> eof) {
        $sites[$sites_query -> Fields('id')] = $sites_query -> Fields('siteid');
        $sites_query -> MoveNext();
    }

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('newusergrid', array('rows' => '4', 'cols' => '2'));

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('username_label').' (*)')), 0, 0);
    $hui_user_grid -> AddChild(new HuiString('username', array('disp' => 'pass')), 0, 1);

    $hui_user_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('userpassword_label').' (*)')), 1, 0);
    $hui_user_grid -> AddChild(new HuiString('password', array('disp' => 'pass', 'password' => 'true')), 1, 1);

    $hui_user_grid -> AddChild(new HuiLabel('profilelabel', array('label' => $amp_locale -> GetStr('userprofile_label').' (*)')), 2, 0);
    $hui_user_grid -> AddChild(new HuiComboBox('profileid', array('disp' => 'pass', 'elements' => $profiles)), 2, 1);

    $hui_user_grid -> AddChild(new HuiLabel('sitelabel', array('label' => $amp_locale -> GetStr('usersite_label'))), 3, 0);
    $hui_user_grid -> AddChild(new HuiComboBox('siteid', array('disp' => 'pass', 'elements' => $sites)), 3, 1);

    $hui_vgroup -> AddChild($hui_user_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('newuser_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'adduser', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('newuserform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('newuser_title');
}

$main_disp -> AddEvent('chpassword', 'main_chpassword');
function main_chpassword($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcusers '.'WHERE id='.$eventData['userid']);

    $user_data = $user_query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('chpasswdgrid', array('rows' => '2', 'cols' => '2'));

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('pwdlabel', array('label' => $amp_locale -> GetStr('chpassword_label').' (*)')), 0, 0);
    $hui_user_grid -> AddChild(new HuiString('password', array('disp' => 'pass', 'password' => 'true')), 0, 1);

    $hui_vgroup -> AddChild($hui_user_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('chpasswd_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'chpasswd', array('userid' => $eventData['userid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('chpasswdform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('chpasswd_title');
}

$main_disp -> AddEvent('chprofile', 'main_chprofile');
function main_chprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcusers '.'WHERE id='.$eventData['userid'].' ');

    $user_data = $user_query -> Fields();

    $prof_query = & $env['ampdb'] -> Execute('SELECT id,profilename '.'FROM xmlrpcprofiles '.'ORDER BY profilename');

    $profiles = array();
    $profiles[0] = $amp_locale -> GetStr('noprofileid_label');
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['profilename'];
        $prof_query -> MoveNext();
    }

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('chprofilegrid', array('rows' => '2', 'cols' => '2'));

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('profilelabel', array('label' => $amp_locale -> GetStr('changeprofile_label').' (*)')), 0, 0);
    $hui_user_grid -> AddChild(new HuiComboBox('profileid', array('disp' => 'pass', 'elements' => $profiles, 'default' => $user_data['profileid'])), 0, 1);

    $hui_vgroup -> AddChild($hui_user_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('chprofile_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'assignprofile', array('userid' => $eventData['userid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('chprofileform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('chprofile_title');
}

$main_disp -> AddEvent('chsite', 'main_chsite');
function main_chsite($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcusers '.'WHERE id='.$eventData['userid'].' ');

    $user_data = $user_query -> Fields();

    $sites_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT id,siteid '.'FROM sites '.'ORDER BY siteid');

    $sites = array();
    $sites[0] = $amp_locale -> GetStr('nositeid_label');
    while (!$sites_query -> eof) {
        $sites[$sites_query -> Fields('id')] = $sites_query -> Fields('siteid');
        $sites_query -> MoveNext();
    }

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('chprofilegrid', array('rows' => '2', 'cols' => '2'));

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('profilelabel', array('label' => $amp_locale -> GetStr('changesite_label').' (*)')), 0, 0);
    $hui_user_grid -> AddChild(new HuiComboBox('siteid', array('disp' => 'pass', 'elements' => $sites, 'default' => $user_data['siteid'])), 0, 1);

    $hui_vgroup -> AddChild($hui_user_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('chsite_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'assignsite', array('userid' => $eventData['userid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('chprofileform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('chsite_title');
}

function accounts_list_action_builder($pageNumber) {
    return build_events_call_string('', array(array('main', 'accounts', array('accountspage' => $pageNumber))));
}

$main_disp -> AddEvent('accounts', 'main_accounts');
function main_accounts($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $acc_query = & $env['ampdb'] -> Execute('SELECT id,name,host '.'FROM xmlrpcaccounts '.'ORDER BY name');

    if ($acc_query -> NumRows() > 0) {
        $headers[0]['label'] = $amp_locale -> GetStr('accountname_header');
        $headers[1]['label'] = $amp_locale -> GetStr('host_header');

        $row = 0;

        $hui_accounts_table = new HuiTable('accountstable', array('headers' => $headers, 'rowsperpage' => '10', 'pagesactionfunction' => 'accounts_list_action_builder', 'pagenumber' => $eventData['accountspage']));

        while (!$acc_query -> eof) {
            $acc_data = $acc_query -> Fields();

            $hui_accounts_table -> AddChild(new HuiLabel('accnamelabel'.$row, array('label' => $acc_data['name'])), $row, 0);
            $hui_accounts_table -> AddChild(new HuiLabel('hostlabel'.$row, array('label' => $acc_data['host'])), $row, 1);

            $hui_account_toolbar[$row] = new HuiToolBar('accounttoolbar'.$row);

            $show_action[$row] = new HuiEventsCall();
            $show_action[$row] -> AddEvent(new HuiEvent('main', 'showaccount', array('accountid' => $acc_data['id'])));
            $hui_show_button[$row] = new HuiButton('showbutton'.$row, array('label' => $amp_locale -> GetStr('showaccount_label'), 'horiz' => 'true', 'themeimage' => 'viewmag', 'action' => $show_action[$row] -> GetEventsCallString()));
            $hui_account_toolbar[$row] -> AddChild($hui_show_button[$row]);

            $methods_action[$row] = new HuiEventsCall();
            $methods_action[$row] -> AddEvent(new HuiEvent('main', 'showmethods', array('accountid' => $acc_data['id'])));
            $hui_methods_button[$row] = new HuiButton('methodsbutton'.$row, array('label' => $amp_locale -> GetStr('showmethods_label'), 'horiz' => 'true', 'themeimage' => 'view_tree', 'action' => $methods_action[$row] -> GetEventsCallString()));
            $hui_account_toolbar[$row] -> AddChild($hui_methods_button[$row]);

            $edit_action[$row] = new HuiEventsCall();
            $edit_action[$row] -> AddEvent(new HuiEvent('main', 'updateaccount', array('accountid' => $acc_data['id'])));
            $hui_edit_button[$row] = new HuiButton('editbutton'.$row, array('label' => $amp_locale -> GetStr('editaccount_label'), 'horiz' => 'true', 'themeimage' => 'edit', 'action' => $edit_action[$row] -> GetEventsCallString()));
            $hui_account_toolbar[$row] -> AddChild($hui_edit_button[$row]);

            $remove_action[$row] = new HuiEventsCall();
            $remove_action[$row] -> AddEvent(new HuiEvent('main', 'accounts', ''));
            $remove_action[$row] -> AddEvent(new HuiEvent('pass', 'removeaccount', array('accountid' => $acc_data['id'])));
            $hui_remove_button[$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removeaccount_label'), 'horiz' => 'true', 'themeimage' => 'edittrash', 'action' => $remove_action[$row] -> GetEventsCallString(), 'needconfirm' => 'true', 'confirmmessage' => sprintf($amp_locale -> GetStr('removeaccountquestion_label'), $acc_data['name'])));
            $hui_account_toolbar[$row] -> AddChild($hui_remove_button[$row]);

            $hui_accounts_table -> AddChild($hui_account_toolbar[$row], $row, 2);

            $row ++;
            $acc_query -> MoveNext();
        }

        $hui_mainframe -> AddChild($hui_accounts_table);
    } else
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('noaccounts_status');

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('accounts_title');
}

$main_disp -> AddEvent('newaccount', 'main_newaccount');
function main_newaccount($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_form_grid = new HuiGrid('newaccountgrid', array('rows' => '6', 'cols' => '2'));

    $hui_form_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('accountname_label').' (*)')), 0, 0);
    $hui_form_grid -> AddChild(new HuiString('name', array('disp' => 'pass')), 0, 1);

    $hui_form_grid -> AddChild(new HuiLabel('hostlabel', array('label' => $amp_locale -> GetStr('host_label').' (*)')), 1, 0);
    $hui_form_grid -> AddChild(new HuiString('host', array('disp' => 'pass')), 1, 1);

    $hui_form_grid -> AddChild(new HuiLabel('cgilabel', array('label' => $amp_locale -> GetStr('cgi_label').' (*)')), 2, 0);
    $hui_form_grid -> AddChild(new HuiString('cgi', array('disp' => 'pass')), 2, 1);

    $hui_form_grid -> AddChild(new HuiLabel('portlabel', array('label' => $amp_locale -> GetStr('port_label').' (*)')), 3, 0);
    $hui_form_grid -> AddChild(new HuiString('port', array('disp' => 'pass')), 3, 1);

    $hui_form_grid -> AddChild(new HuiLabel('usernamelabel', array('label' => $amp_locale -> GetStr('username_label').' (*)')), 4, 0);
    $hui_form_grid -> AddChild(new HuiString('username', array('disp' => 'pass')), 4, 1);

    $hui_form_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('password_label').' (*)')), 5, 0);
    $hui_form_grid -> AddChild(new HuiString('password', array('disp' => 'pass', 'password' => 'true')), 5, 1);

    $hui_form_grid -> AddChild(new HuiLabel('proxylabel', array('label' => $amp_locale -> GetStr('proxy_label'))), 6, 0);
    $hui_form_grid -> AddChild(new HuiString('proxy', array('disp' => 'pass')), 6, 1);

    $hui_form_grid -> AddChild(new HuiLabel('proxyportlabel', array('label' => $amp_locale -> GetStr('proxyport_label'))), 7, 0);
    $hui_form_grid -> AddChild(new HuiString('proxyport', array('disp' => 'pass')), 7, 1);

    $hui_vgroup -> AddChild($hui_form_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit', array('caption' => $amp_locale -> GetStr('createaccount_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'createaccount', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'accounts', ''));

    $hui_form = new HuiForm('newsiteform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('newaccount_title');
}

$main_disp -> AddEvent('updateaccount', 'main_updateaccount');
function main_updateaccount($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $acc_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcaccounts '.'WHERE id='.$eventData['accountid']);

    $acc_data = $acc_query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_form_grid = new HuiGrid('newaccountgrid', array('rows' => '6', 'cols' => '2'));

    $hui_form_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('accountname_label').' (*)')), 0, 0);
    $hui_form_grid -> AddChild(new HuiString('name', array('disp' => 'pass', 'value' => $acc_data['name'])), 0, 1);

    $hui_form_grid -> AddChild(new HuiLabel('hostlabel', array('label' => $amp_locale -> GetStr('host_label').' (*)')), 1, 0);
    $hui_form_grid -> AddChild(new HuiString('host', array('disp' => 'pass', 'value' => $acc_data['host'])), 1, 1);

    $hui_form_grid -> AddChild(new HuiLabel('cgilabel', array('label' => $amp_locale -> GetStr('cgi_label').' (*)')), 2, 0);
    $hui_form_grid -> AddChild(new HuiString('cgi', array('disp' => 'pass', 'value' => $acc_data['cgi'])), 2, 1);

    $hui_form_grid -> AddChild(new HuiLabel('portlabel', array('label' => $amp_locale -> GetStr('port_label').' (*)')), 3, 0);
    $hui_form_grid -> AddChild(new HuiString('port', array('disp' => 'pass', 'value' => $acc_data['port'])), 3, 1);

    $hui_form_grid -> AddChild(new HuiLabel('usernamelabel', array('label' => $amp_locale -> GetStr('username_label').' (*)')), 4, 0);
    $hui_form_grid -> AddChild(new HuiString('username', array('disp' => 'pass', 'value' => $acc_data['username'])), 4, 1);

    $hui_form_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('password_label').' (*)')), 5, 0);
    $hui_form_grid -> AddChild(new HuiString('password', array('disp' => 'pass', 'password' => 'true', 'value' => $acc_data['password'])), 5, 1);

    $hui_form_grid -> AddChild(new HuiLabel('proxylabel', array('label' => $amp_locale -> GetStr('proxy_label'))), 6, 0);
    $hui_form_grid -> AddChild(new HuiString('proxy', array('disp' => 'pass', 'value' => $acc_data['proxy'])), 6, 1);

    $hui_form_grid -> AddChild(new HuiLabel('proxyportlabel', array('label' => $amp_locale -> GetStr('proxyport_label'))), 7, 0);
    $hui_form_grid -> AddChild(new HuiString('proxyport', array('disp' => 'pass', 'value' => $acc_data['proxyport'])), 7, 1);

    $hui_vgroup -> AddChild($hui_form_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit', array('caption' => $amp_locale -> GetStr('updateaccount_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'updateaccount', array('accountid' => $eventData['accountid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'accounts', ''));

    $hui_form = new HuiForm('newsiteform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$acc_data['name'].' - '.$amp_locale -> GetStr('updateaccount_title');
}

$main_disp -> AddEvent('showaccount', 'main_showaccount');
function main_showaccount($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $acc_query = & $env['ampdb'] -> execute('SELECT * '.'FROM xmlrpcaccounts '.'WHERE id='.$eventData['accountid']);

    $acc_data = $acc_query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_form_grid = new HuiGrid('newaccountgrid', array('rows' => '6', 'cols' => '2'));

    $hui_form_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('accountname_label'))), 0, 0);
    $hui_form_grid -> AddChild(new HuiString('name', array('disp' => 'pass', 'value' => $acc_data['name'])), 0, 1);

    $hui_form_grid -> AddChild(new HuiLabel('hostlabel', array('label' => $amp_locale -> GetStr('host_label'))), 1, 0);
    $hui_form_grid -> AddChild(new HuiString('host', array('disp' => 'pass', 'value' => $acc_data['host'])), 1, 1);

    $hui_form_grid -> AddChild(new HuiLabel('cgilabel', array('label' => $amp_locale -> GetStr('cgi_label'))), 2, 0);
    $hui_form_grid -> AddChild(new HuiString('cgi', array('disp' => 'pass', 'value' => $acc_data['cgi'])), 2, 1);

    $hui_form_grid -> AddChild(new HuiLabel('portlabel', array('label' => $amp_locale -> GetStr('port_label'))), 3, 0);
    $hui_form_grid -> AddChild(new HuiString('port', array('disp' => 'pass', 'value' => $acc_data['port'])), 3, 1);

    $hui_form_grid -> AddChild(new HuiLabel('usernamelabel', array('label' => $amp_locale -> GetStr('username_label'))), 4, 0);
    $hui_form_grid -> AddChild(new HuiString('username', array('disp' => 'pass', 'value' => $acc_data['username'])), 4, 1);

    $hui_form_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('password_label'))), 5, 0);
    $hui_form_grid -> AddChild(new HuiString('password', array('disp' => 'pass', 'value' => $acc_data['password'])), 5, 1);

    $hui_form_grid -> AddChild(new HuiLabel('proxylabel', array('label' => $amp_locale -> GetStr('proxy_label'))), 6, 0);
    $hui_form_grid -> AddChild(new HuiString('proxy', array('disp' => 'pass', 'value' => $acc_data['proxy'])), 6, 1);

    $hui_form_grid -> AddChild(new HuiLabel('proxyportlabel', array('label' => $amp_locale -> GetStr('proxyport_label'))), 7, 0);
    $hui_form_grid -> AddChild(new HuiString('proxyport', array('disp' => 'pass', 'value' => $acc_data['proxyport'])), 7, 1);

    $hui_vgroup -> AddChild($hui_form_grid);
    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$acc_data['name'].' - '.$amp_locale -> GetStr('showaccount_title');
}

function methods_list_action_builder($pageNumber) {
    $tmp_main_disp = new HuiDispatcher('main');

    $event_data = $tmp_main_disp -> GetEventData();
    return build_events_call_string('', array(array('main', 'showmethods', array('methodspage' => $pageNumber, 'accountid' => $event_data['accountid']))));
}

$main_disp -> AddEvent('showmethods', 'main_showmethods');
function main_showmethods($eventData) {
    global $env, $gEnv, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $acc_query = & $env['ampdb'] -> execute('SELECT name '.'FROM xmlrpcaccounts '.'WHERE id='.$eventData['accountid']);

    $acc_data = $acc_query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $xmlrpc_account = new XmlRpcAccount($gEnv['root']['db'], $eventData['accountid']);
    $xmlrpc_client = new XmlRpc_Client($xmlrpc_account -> mCgi, $xmlrpc_account -> mHost, $xmlrpc_account -> mPort);
    $xmlrpc_client -> SetProxy($xmlrpc_account -> mProxy, $xmlrpc_account -> mProxyPort);
    $xmlrpc_client -> SetCredentials($xmlrpc_account -> mUsername, $xmlrpc_account -> mPassword);

    $xmlrpc_message = new XmlRpcMsg('system.listMethods');
    $xmlrpc_resp = $xmlrpc_client -> Send($xmlrpc_message);

    if ($xmlrpc_resp) {
        if (!$xmlrpc_resp -> FaultCode()) {
            $xv = $xmlrpc_resp -> Value();
            if (is_object($xv)) {
                $methods = xmlrpc_decode($xv);
                //$methods_val = $xv->ScalarVal();

                if (is_array($methods)) {
                    $headers[0]['label'] = $amp_locale -> GetStr('method.header');
                    $methods_table = new HuiTable('methods', array('elements' => $elements, 'headers' => $headers, 'rowsperpage' => '20', 'pagesactionfunction' => 'methods_list_action_builder', 'pagenumber' => $eventData['methodspage']));

                    $row = 0;

                    while (list ($key, $val) = each($methods)) {
                        $methods_table -> AddChild(new HuiLabel('method', array('label' => $val)), $row, 0);
                        $row ++;
                    }
                    $hui_vgroup -> AddChild($methods_table);
                }
            }
        } else
            echo 'error in response from server';
    } else
        echo 'invalid response form server';

    //$hui_vgroup

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$acc_data['name'].' - '.$amp_locale -> GetStr('showmethods_title');
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('xmlrpc_help', array('node' => 'ampoliros.root.xmlrpc.'.$eventData['node'], 'language' => AMP_LANG)));
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
