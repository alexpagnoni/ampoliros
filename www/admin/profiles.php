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
// $Id: profiles.php,v 1.23 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
OpenLibrary('hui.library');
OpenLibrary('users.library');

$log = new Logger(AMP_LOG);
$amp_locale = new Locale('amp_site_profiles', $gEnv['user']['locale']['language']);
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('profiles_title')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('profiles_title'), 'icon' => 'kuser'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

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

if ($gEnv['site']['id'] == $gEnv['user']['id']) {
    $motd_tb = new HuiToolBar('motdtb');
    $motd_action = new HuiEventsCall();
    $motd_action -> AddEvent(New HuiEvent('main', 'motd', ''));
    $motd_button = new HuiButton('motdbutton', array('label' => $amp_locale -> GetStr('motd.button'), 'themeimage' => 'edit', 'horiz' => 'true', 'action' => $motd_action -> GetEventsCallString()));

    $motd_tb -> AddChild($motd_button);
    $hui_toolbarframe -> AddChild($motd_tb);
}

$hui_toolbarframe -> AddChild($hui_helptoolbar);

$hui_mainvertgroup -> AddChild($hui_toolbarframe);

$hui_mainframe = new HuiHorizFrame('mainframe');
$hui_mainstatus = new HuiStatusBar('mainstatusbar');

// Pass dispatcher
//
$pass_disp = new HuiDispatcher('pass');

$pass_disp -> AddEvent('newgroup', 'pass_newgroup');
function pass_newgroup($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $temp_group = new Group($env['ampdb'], $env['db'], $env['currentsiteserial']);
    $group_data['groupname'] = $eventData['groupname'];
    $temp_group -> CreateGroup($group_data);
}

$pass_disp -> AddEvent('rengroup', 'pass_rengroup');
function pass_rengroup($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $temp_group = new Group($env['ampdb'], $env['db'], $env['currentsiteserial'], $eventData['gid']);
    $group_data['groupname'] = $eventData['groupname'];
    $temp_group -> EditGroup($group_data);
}

$pass_disp -> AddEvent('removegroup', 'pass_removegroup');
function pass_removegroup($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    if ($eventData['userstoo'] == 1)
        $delete_users_too = true;
    else
        $delete_users_too = false;

    $temp_group = new Group($env['ampdb'], $env['db'], $env['currentsiteserial'], $eventData['gid']);
    $temp_group -> RemoveGroup($delete_users_too);
}

$pass_disp -> AddEvent('adduser', 'pass_adduser');
function pass_adduser($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    if ($eventData['passworda'] == $eventData['passwordb']) {
        $temp_user = new User($env['ampdb'], $env['currentsiteserial']);
        $user_data['siteid'] = $env['currentsiteserial'];
        $user_data['groupid'] = $eventData['groupid'];
        $user_data['username'] = $eventData['username']. ($GLOBALS['gEnv']['core']['edition'] == AMP_EDITION_ASP ? '@'.$env['currentsiteid'] : '');
        $user_data['password'] = $eventData['passworda'];
        $user_data['fname'] = $eventData['fname'];
        $user_data['lname'] = $eventData['lname'];
        $user_data['email'] = $eventData['email'];
        $user_data['otherdata'] = $eventData['other'];

        $temp_user -> CreateUser($user_data);
    }
}

$pass_disp -> AddEvent('edituser', 'pass_edituser');
function pass_edituser($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $user_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM users '.'WHERE id='.$eventData['uid']);

    $site_id = $user_query -> Fields('siteid');

    if ($site_id == $env['currentsiteserial']) {
        $temp_user = new User($env['ampdb'], $env['currentsiteserial'], $eventData['uid']);
        $user_data['siteid'] = $env['currentsiteserial'];
        $user_data['groupid'] = $eventData['groupid'];
        $user_data['username'] = $eventData['username'];
        $user_data['fname'] = $eventData['fname'];
        $user_data['lname'] = $eventData['lname'];
        $user_data['email'] = $eventData['email'];
        $user_data['otherdata'] = $eventData['other'];

        if (!empty($eventData['oldpassword']) and !empty($eventData['passworda']) and !empty($eventData['passwordb'])) {
            if ($eventData['passworda'] == $eventData['passwordb']) {
                $user_data['password'] = $eventData['passworda'];
            }
        }

        $temp_user -> EditUser($user_data);
    }
}

$pass_disp -> AddEvent('chpasswd', 'pass_chpasswd');
function pass_chpasswd($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $user_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM users '.'WHERE id='.$eventData['uid']);

    $site_id = $user_query -> Fields('siteid');

    if ($site_id == $env['currentsiteserial']) {
        $temp_user = new User($env['ampdb'], $env['currentsiteserial'], $eventData['uid']);
        $temp_user -> ChPasswd($eventData['password']);
    }
}

$pass_disp -> AddEvent('chprofile', 'pass_chprofile');
function pass_chprofile($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $user_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM users '.'WHERE id='.$eventData['uid']);

    $site_id = $user_query -> Fields('siteid');

    if ($site_id == $env['currentsiteserial']) {
        $temp_user = new User($env['ampdb'], $env['currentsiteserial'], $eventData['uid']);
        $user_data['groupid'] = $eventData['profileid'];
        $temp_user -> ChangeGroup($user_data);
    }
}

$pass_disp -> AddEvent('removeuser', 'pass_removeuser');
function pass_removeuser($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $user_query = & $env['ampdb'] -> Execute('SELECT siteid '.'FROM users '.'WHERE id='.$eventData['uid']);

    $site_id = $user_query -> Fields('siteid');

    if ($site_id == $env['currentsiteserial']) {
        $temp_user = new User($env['ampdb'], $env['currentsiteserial'], $eventData['uid']);
        $temp_user -> RemoveUser();
    }
}

$pass_disp -> AddEvent('enablenode', 'pass_enablenode');
function pass_enablenode($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $temp_perm = new Permissions($env['db'], $eventData['gid']);
    $temp_perm -> Enable($eventData['node'], $eventData['ntype']);
}

$pass_disp -> AddEvent('disablenode', 'pass_disablenode');
function pass_disablenode($eventData) {
    global $env, $amp_locale, $hui_mainstatus;

    $temp_perm = new Permissions($env['db'], $eventData['gid']);
    $temp_perm -> Disable($eventData['node'], $eventData['ntype']);
}

if ($gEnv['site']['id'] == $gEnv['user']['id']) {
    $pass_disp -> AddEvent('setmotd', 'pass_setmotd');
    function pass_setmotd($eventData) {
        global $gEnv, $amp_locale, $hui_mainstatus;

        OpenLibrary('sites.library');

        $site = new Site($gEnv['root']['db'], $gEnv['site']['id'], $gEnv['site']['db']);

        $site -> SetMotd($eventData['motd']);
        $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('motd_set.status');
    }
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar;

    $prof_query = & $env['db'] -> Execute('SELECT * '.'FROM groups '.'ORDER BY groupname');

    $profiles = array();
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['groupname'];
        $prof_query -> MoveNext();
    }

    if (count($profiles)) {
        $headers[0]['label'] = $amp_locale -> GetStr('profilename_header');

        $row = 0;

        $hui_profiles_table = new HuiTable('profilestable', array('headers' => $headers));

        while (list ($id, $profile_name) = each($profiles)) {
            $hui_profiles_table -> AddChild(new HuiLabel('profnamelabel'.$row, array('label' => $profile_name)), $row, 0);

            $hui_profile_toolbar[$row] = new HuiToolBar('moduletoolbar'.$row);

            $profile_action[$row] = new HuiEventsCall();
            $profile_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $id)));
            $hui_profile_button[$row] = new HuiButton('profilebutton'.$row, array('label' => $amp_locale -> GetStr('editprofile_label'), 'themeimage' => 'view_tree', 'action' => $profile_action[$row] -> GetEventsCallString()));
            $hui_profile_toolbar[$row] -> AddChild($hui_profile_button[$row]);

            $rename_action[$row] = new HuiEventsCall();
            $rename_action[$row] -> AddEvent(new HuiEvent('main', 'renameprofile', array('profileid' => $id)));
            $hui_rename_button[$row] = new HuiButton('renamebutton'.$row, array('label' => $amp_locale -> GetStr('renameprofile_label'), 'themeimage' => 'edit', 'action' => $rename_action[$row] -> GetEventsCallString()));
            $hui_profile_toolbar[$row] -> AddChild($hui_rename_button[$row]);

            $remove_action[$row] = new HuiEventsCall();
            $remove_action[$row] -> AddEvent(new HuiEvent('main', 'deleteprofile', array('profileid' => $id)));
            $hui_remove_button[$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removeprofile_label'), 'themeimage' => 'edittrash', 'action' => $remove_action[$row] -> GetEventsCallString()));
            $hui_profile_toolbar[$row] -> AddChild($hui_remove_button[$row]);

            $hui_profiles_table -> AddChild($hui_profile_toolbar[$row], $row, 1);

            $row ++;
        }

        $hui_mainframe -> AddChild($hui_profiles_table);
    }

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('default_title');
}

$main_disp -> AddEvent('editprofile', 'main_editprofile');
function main_editprofile($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar;

    $prof_query = & $env['db'] -> execute('SELECT * '.'FROM groups '.'WHERE id='.$eventData['profileid']);

    $prof_data = $prof_query -> Fields();

    $groups_query = & $env['db'] -> Execute('SELECT * '.'FROM admingroups '.'ORDER BY name');

    if ($groups_query -> NumRows()) {
        $perm = new Permissions($env['db'], $eventData['profileid']);
        $row = 0;

        $headers[0]['label'] = '';
        $headers[1]['label'] = $amp_locale -> GetStr('admingroup_header');
        $headers[2]['label'] = '';
        $headers[3]['label'] = $amp_locale -> GetStr('adminpage_header');

        $hui_groups_table = new HuiTable('groupsstable', array('headers' => $headers));

        while (!$groups_query -> eof) {
            $group_data = $groups_query -> Fields();
            $temp_locale = new Locale($group_data['catalog'], $env['sitelocale']);
            $node_state = $perm -> Check($group_data['id'], PERMISSIONS_NODETYPE_GROUP);

            switch ($node_state) {
                case PERMISSIONS_NODE_FULLYENABLED :
                    $icon = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
                    $enabled = true;
                    break;

                case PERMISSIONS_NODE_PARTIALLYENABLED :
                    $icon = $hui_mainframe -> mThemeHandler -> mStyle['goldball'];
                    $enabled = true;
                    break;

                case PERMISSIONS_NODE_NOTENABLED :
                    $icon = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
                    $enabled = false;
                    break;
            }

            $hui_groups_table -> AddChild(new HuiImage('statusimage'.$row, array('imageurl' => $icon)), $row, 0);
            $hui_groups_table -> AddChild(new HuiLabel('grouplabel'.$row, array('label' => $temp_locale -> GetStr($group_data['name']))), $row, 1);

            $hui_group_toolbar[$row] = new HuiToolBar('grouptoolbar'.$row);

            if ($enabled) {
                $disable_action[$row] = new HuiEventsCall();
                $disable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                $disable_action[$row] -> AddEvent(new HuiEvent('pass', 'disablenode', array('ntype' => PERMISSIONS_NODETYPE_GROUP, 'node' => $group_data['id'], 'gid' => $eventData['profileid'])));
                $hui_disable_button[$row] = new HuiButton('disablebutton'.$row, array(label => $amp_locale -> GetStr('disablenode_label'), 'themeimage' => 'lock', 'action' => $disable_action[$row] -> GetEventsCallString()));
                $hui_group_toolbar[$row] -> AddChild($hui_disable_button[$row]);
            }

            if (!$enabled or $node_state == PERMISSIONS_NODE_PARTIALLYENABLED) {
                $enable_action[$row] = new HuiEventsCall();
                $enable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                $enable_action[$row] -> AddEvent(new HuiEvent('pass', 'enablenode', array('ntype' => PERMISSIONS_NODETYPE_GROUP, 'node' => $group_data['id'], 'gid' => $eventData['profileid'])));
                $hui_enable_button[$row] = new HuiButton('enablebutton'.$row, array(label => $amp_locale -> GetStr('enablenode_label'), 'themeimage' => 'unlock', 'action' => $enable_action[$row] -> GetEventsCallString()));
                $hui_group_toolbar[$row] -> AddChild($hui_enable_button[$row]);
            }

            $hui_groups_table -> AddChild($hui_group_toolbar[$row], $row, 4);

            $row ++;

            $pages_query = $env['db'] -> Execute('SELECT * '.'FROM adminpages '.'WHERE groupid='.$group_data['id'].' '.'ORDER BY name');

            while (!$pages_query -> eof) {
                $page_data = $pages_query -> Fields();
                $temp_locale = new Locale($page_data['catalog'], $env['sitelocale']);
                $node_state = $perm -> Check($page_data['id'], 'page');

                switch ($node_state) {
                    case PERMISSIONS_NODE_FULLYENABLED :
                        $icon = $hui_mainframe -> mThemeHandler -> mStyle['greenball'];
                        $enabled = true;
                        break;

                    case PERMISSIONS_NODE_NOTENABLED :
                        $icon = $hui_mainframe -> mThemeHandler -> mStyle['redball'];
                        $enabled = false;
                        break;
                }

                $hui_groups_table -> AddChild(new HuiImage('statusimage'.$row, array('imageurl' => $icon)), $row, 2);
                $hui_groups_table -> AddChild(new HuiLabel('methodlabel'.$row, array('label' => $temp_locale -> GetStr($page_data['name']))), $row, 3);

                $hui_page_toolbar[$row] = new HuiToolBar('pagetoolbar'.$row);

                if ($enabled) {
                    $disable_action[$row] = new HuiEventsCall();
                    $disable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                    $disable_action[$row] -> AddEvent(new HuiEvent('pass', 'disablenode', array('ntype' => PERMISSIONS_NODETYPE_PAGE, 'node' => $page_data['id'], 'gid' => $eventData['profileid'])));
                    $hui_disable_button[$row] = new HuiButton('disablebutton'.$row, array(label => $amp_locale -> GetStr('disablenode_label'), 'themeimage' => 'lock', 'action' => $disable_action[$row] -> GetEventsCallString()));
                    $hui_page_toolbar[$row] -> AddChild($hui_disable_button[$row]);
                } else {
                    $enable_action[$row] = new HuiEventsCall();
                    $enable_action[$row] -> AddEvent(new HuiEvent('main', 'editprofile', array('profileid' => $eventData['profileid'])));
                    $enable_action[$row] -> AddEvent(new HuiEvent('pass', 'enablenode', array('ntype' => PERMISSIONS_NODETYPE_PAGE, 'node' => $page_data['id'], 'gid' => $eventData['profileid'])));
                    $hui_enable_button[$row] = new HuiButton('enablebutton'.$row, array(label => $amp_locale -> GetStr('enablenode_label'), 'themeimage' => 'unlock', 'action' => $enable_action[$row] -> GetEventsCallString()));
                    $hui_page_toolbar[$row] -> AddChild($hui_enable_button[$row]);
                }

                $hui_groups_table -> AddChild($hui_page_toolbar[$row], $row, 4);

                $row ++;
                $pages_query -> MoveNext();
            }

            $groups_query -> MoveNext();
        }

        $hui_mainframe -> AddChild($hui_groups_table);
    }

    $hui_titlebar -> mTitle.= ' - '.$prof_data['groupname'].' - '.$amp_locale -> GetStr('editprofile_title');
}

$main_disp -> AddEvent('newprofile', 'main_newprofile');
function main_newprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_profile_grid = new HuiGrid('newgroupgrid', array('rows' => '2', 'cols' => '2'));

    // Group fields
    //
    $hui_profile_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('groupname_label').' (*)')), 0, 0);
    $hui_profile_grid -> AddChild(new HuiString('groupname', array('disp' => 'pass')), 0, 1);

    $hui_vgroup -> AddChild($hui_profile_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('newprofile_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'newgroup', ''));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_form = new HuiForm('newgroupform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('newgroup_title');
}

$main_disp -> AddEvent('deleteprofile', 'main_deleteprofile');
function main_deleteprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_titlebar;

    $prof_query = & $env['db'] -> execute('SELECT * '.'FROM groups '.'WHERE id='.$eventData['profileid']);

    $prof_data = $prof_query -> Fields();

    $hui_vgroup = new HuiVertGroup('removereqvgroup', array('halign' => 'center', 'groupalign' => 'center'));

    $hui_hgroup1 = new HuiHorizGroup('removereqhgroup', array('align' => 'middle', 'width' => '0%'));
    $hui_hgroup1 -> AddChild(new HuiLabel('removereqlabel', array('label' => sprintf($amp_locale -> GetStr('removeprofilequestion_label'), $prof_data['groupname']))));

    $hui_vgroup -> AddChild($hui_hgroup1);

    $hui_hgroup2 = new HuiHorizGroup('removereqhgroup', array('align' => 'middle', 'groupalign' => 'center'));

    $remove_action = new HuiEventSCall();
    $remove_action -> AddEvent(new HuiEvent('main', 'default', ''));
    $remove_action -> AddEvent(new HuiEvent('pass', 'removegroup', array('gid' => $eventData['profileid'])));
    $remove_button = new HuiButton('removebutton', array('label' => $amp_locale -> GetStr('okremoveprofile_button'), 'horiz' => 'true', 'themeimage' => 'button_ok', 'action' => $remove_action -> GetEventsCallString()));
    $remove_frame = new HuiHorizFrame('removeframe');
    $remove_frame -> AddChild($remove_button);

    $hui_hgroup2 -> AddChild($remove_frame);

    $remove2_action = new HuiEventSCall();
    $remove2_action -> AddEvent(new HuiEvent('main', 'default', ''));
    $remove2_action -> AddEvent(new HuiEvent('pass', 'removegroup', array('gid' => $eventData['profileid'], 'userstoo' => '1')));
    $remove2_button = new HuiButton('remove2button', array('label' => $amp_locale -> GetStr('okremoveprofileandusers_button'), 'horiz' => 'true', 'themeimage' => 'button_ok', 'action' => $remove2_action -> GetEventsCallString()));
    $remove2_frame = new HuiHorizFrame('remove2frame');
    $remove2_frame -> AddChild($remove2_button);

    $hui_hgroup2 -> AddChild($remove2_frame);

    $dontremove_action = new HuiEventsCall();
    $dontremove_action -> AddEvent(new HuiEvent('main', 'default', ''));
    $dontremove_button = new HuiButton('dontremovebutton', array('label' => $amp_locale -> GetStr('dontremoveprofile_button'), 'horiz' => 'true', 'themeimage' => 'stop', 'action' => $dontremove_action -> GetEventsCallString()));
    $dontremove_frame = new HuiHorizFrame('dontremoveframe');
    $dontremove_frame -> AddChild($dontremove_button);

    $hui_hgroup2 -> AddChild($dontremove_frame);

    $ok_action = new HuiEventsCall();
    $ok_action -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_ok_form = new HuiForm('okform', array('action'));

    $hui_vgroup -> AddChild($hui_hgroup2);

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$prof_data['profilename'].' - '.$amp_locale -> GetStr('removeprofile_title');
}

$main_disp -> AddEvent('renameprofile', 'main_renameprofile');
function main_renameprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $prof_query = & $env['db'] -> execute('SELECT * '.'FROM groups '.'WHERE id='.$eventData['profileid']);

    $prof_data = $prof_query -> Fields();

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_profile_grid = new HuiGrid('renprofilegrid', array('rows' => '2', 'cols' => '2'));

    // Profile fields
    //
    $hui_profile_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('profilename_label').' (*)')), 0, 0);
    $hui_profile_grid -> AddChild(new HuiString('groupname', array('disp' => 'pass', 'value' => $prof_data['groupname'])), 0, 1);

    $hui_vgroup -> AddChild($hui_profile_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('renameprofile_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'rengroup', array('gid' => $eventData['profileid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_form = new HuiForm('renameprofileform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$prof_data['groupname'].' - '.$amp_locale -> GetStr('renameprofile_title');
}

$main_disp -> AddEvent('users', 'main_users');
function main_users($eventData) {
    global $env, $amp_locale, $hui_mainframe, $hui_titlebar;

    $users_query = & $env['ampdb'] -> Execute('SELECT id,username,fname,lname,email,groupid '.'FROM users '.'WHERE siteid='.$env['currentsiteserial'].' '.'ORDER BY username');

    $prof_query = & $env['db'] -> Execute('SELECT id,groupname '.'FROM groups '.'ORDER BY groupname');

    $profiles = array();
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['groupname'];
        $prof_query -> MoveNext();
    }

    if ($users_query -> NumRows()) {
        $headers[0]['label'] = $amp_locale -> GetStr('username_header');
        $headers[1]['label'] = $amp_locale -> GetStr('completename_header');
        $headers[2]['label'] = $amp_locale -> GetStr('email_header');
        $headers[3]['label'] = $amp_locale -> GetStr('userprofilename_header');

        $row = 0;

        $hui_users_table = new HuiTable('userstable', array('headers' => $headers));

        while (!$users_query -> eof) {
            $user_data = $users_query -> Fields();

            $hui_users_table -> AddChild(new HuiLabel('usernamelabel'.$row, array('label' => $user_data['username'])), $row, 0);
            $hui_users_table -> AddChild(new HuiLabel('completenamelabel'.$row, array('label' => strcmp($user_data['username'], $env['currentsite']) != 0 ? $user_data['lname'].' '.$user_data['fname'] : $amp_locale -> GetStr('superuser_label'))), $row, 1);
            $hui_users_table -> AddChild(new HuiLabel('emaillabel'.$row, array('label' => $user_data['email'])), $row, 2);
            $hui_users_table -> AddChild(new HuiLabel('userprofilelabel'.$row, array('label' => ($user_data['groupid'] != '0' and strlen($user_data['groupid'])) ? $profiles[$user_data['groupid']] : $amp_locale -> GetStr('noprofileid_label'))), $row, 3);

            if (strcmp($user_data['username'], $env['currentsite']) != 0) {
                $hui_user_toolbar[$row] = new HuiToolBar('usertoolbar'.$row);

                $profile_action[$row] = new HuiEventsCall();
                $profile_action[$row] -> AddEvent(new HuiEvent('main', 'chprofile', array('userid' => $user_data['id'])));
                $hui_profile_button[$row] = new HuiButton('profilebutton'.$row, array('label' => $amp_locale -> GetStr('chprofile_label'), 'themeimage' => 'view_tree', 'action' => $profile_action[$row] -> GetEventsCallString()));
                $hui_user_toolbar[$row] -> AddChild($hui_profile_button[$row]);

                $chpasswd_action[$row] = new HuiEventsCall();
                $chpasswd_action[$row] -> AddEvent(new HuiEvent('main', 'chpassword', array('userid' => $user_data['id'])));
                $hui_chpasswd_button[$row] = new HuiButton('chpasswdbutton'.$row, array('label' => $amp_locale -> GetStr('chpasswd_label'), 'themeimage' => 'edit', 'action' => $chpasswd_action[$row] -> GetEventsCallString()));
                $hui_user_toolbar[$row] -> AddChild($hui_chpasswd_button[$row]);

                $chdata_action[$row] = new HuiEventsCall();
                $chdata_action[$row] -> AddEvent(new HuiEvent('main', 'edituser', array('userid' => $user_data['id'])));
                $hui_chdata_button[$row] = new HuiButton('chdatabutton'.$row, array('label' => $amp_locale -> GetStr('chdata_label'), 'themeimage' => 'edit', 'action' => $chdata_action[$row] -> GetEventsCallString()));
                $hui_user_toolbar[$row] -> AddChild($hui_chdata_button[$row]);

                $remove_action[$row] = new HuiEventsCall();
                $remove_action[$row] -> AddEvent(new HuiEvent('main', 'deleteuser', array('userid' => $user_data['id'])));
                $hui_remove_button[$row] = new HuiButton('removebutton'.$row, array('label' => $amp_locale -> GetStr('removeuser_label'), 'themeimage' => 'edittrash', 'action' => $remove_action[$row] -> GetEventsCallString()));
                $hui_user_toolbar[$row] -> AddChild($hui_remove_button[$row]);

                $hui_users_table -> AddChild($hui_user_toolbar[$row], $row, 4);
            }

            $users_query -> MoveNext();
            $row ++;
        }

        $hui_mainframe -> AddChild($hui_users_table);
    }

    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('users_title');
}

$main_disp -> AddEvent('newuser', 'main_newuser');
function main_newuser($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $prof_query = & $env['db'] -> Execute('SELECT * '.'FROM groups');

    $profiles = array();
    $profiles[0] = $amp_locale -> GetStr('noprofileid_label');
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['groupname'];
        $prof_query -> MoveNext();
    }

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('newusergrid', array('rows' => '7', 'cols' => '2'));

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('namelabel', array('label' => $amp_locale -> GetStr('username_label').' (*)')), 0, 0);
    $hui_user_grid -> AddChild(new HuiString('username', array('disp' => 'pass')), 0, 1);

    $hui_user_grid -> AddChild(new HuiLabel('passwordalabel', array('label' => $amp_locale -> GetStr('userpassworda_label').' (*)')), 1, 0);
    $hui_user_grid -> AddChild(new HuiString('passworda', array('disp' => 'pass', 'password' => 'true')), 1, 1);

    $hui_user_grid -> AddChild(new HuiLabel('passwordblabel', array('label' => $amp_locale -> GetStr('userpasswordb_label').' (*)')), 2, 0);
    $hui_user_grid -> AddChild(new HuiString('passwordb', array('disp' => 'pass', 'password' => 'true')), 2, 1);

    $hui_user_grid -> AddChild(new HuiLabel('profilelabel', array('label' => $amp_locale -> GetStr('usergroup_label').' (*)')), 3, 0);
    $hui_user_grid -> AddChild(new HuiComboBox('groupid', array('disp' => 'pass', 'elements' => $profiles)), 3, 1);

    $hui_user_grid -> AddChild(new HuiLabel('fnamelabel', array('label' => $amp_locale -> GetStr('userfname_label'))), 4, 0);
    $hui_user_grid -> AddChild(new HuiString('fname', array('disp' => 'pass')), 4, 1);

    $hui_user_grid -> AddChild(new HuiLabel('lnamelabel', array('label' => $amp_locale -> GetStr('userlname_label'))), 5, 0);
    $hui_user_grid -> AddChild(new HuiString('lname', array('disp' => 'pass')), 5, 1);

    $hui_user_grid -> AddChild(new HuiLabel('emaillabel', array('label' => $amp_locale -> GetStr('email_label'))), 6, 0);
    $hui_user_grid -> AddChild(new HuiString('email', array('disp' => 'pass')), 6, 1);

    $hui_user_grid -> AddChild(new HuiLabel('otherlabel', array('label' => $amp_locale -> GetStr('userother_label'))), 7, 0);
    $hui_user_grid -> AddChild(new HuiText('other', array('disp' => 'pass', 'rows' => '5', 'cols' => '80')), 7, 1);

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

$main_disp -> AddEvent('deleteuser', 'main_deleteuser');
function main_deleteuser($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM users '.'WHERE id='.$eventData['userid']);

    $user_data = $user_query -> Fields();

    $hui_vgroup = new HuiVertGroup('removereqvgroup', array('halign' => 'center', 'groupalign' => 'center'));

    $hui_hgroup1 = new HuiHorizGroup('removereqhgroup', array('align' => 'middle', 'width' => '0%'));
    $hui_hgroup1 -> AddChild(new HuiLabel('removereqlabel', array('label' => sprintf($amp_locale -> GetStr('removeuserquestion_label'), $user_data['username']))));

    $hui_vgroup -> AddChild($hui_hgroup1);

    $hui_hgroup2 = new HuiHorizGroup('removereqhgroup', array('align' => 'middle', 'groupalign' => 'center'));

    $remove_action = new HuiEventSCall();
    $remove_action -> AddEvent(new HuiEvent('main', 'users', ''));
    $remove_action -> AddEvent(new HuiEvent('pass', 'removeuser', array('uid' => $eventData['userid'])));
    $remove_button = new HuiButton('removebutton', array('label' => $amp_locale -> GetStr('okremoveuser_button'), 'horiz' => 'true', 'themeimage' => 'button_ok', 'action' => $remove_action -> GetEventsCallString()));
    $remove_frame = new HuiHorizFrame('removeframe');
    $remove_frame -> AddChild($remove_button);

    $hui_hgroup2 -> AddChild($remove_frame);

    $dontremove_action = new HuiEventsCall();
    $dontremove_action -> AddEvent(new HuiEvent('main', 'users', ''));
    $dontremove_button = new HuiButton('dontremovebutton', array('label' => $amp_locale -> GetStr('dontremoveuser_button'), 'horiz' => 'true', 'themeimage' => 'stop', 'action' => $dontremove_action -> GetEventsCallString()));
    $dontremove_frame = new HuiHorizFrame('dontremoveframe');
    $dontremove_frame -> AddChild($dontremove_button);

    $hui_hgroup2 -> AddChild($dontremove_frame);

    $ok_action = new HuiEventsCall();
    $ok_action -> AddEvent(new HuiEvent('main', 'default', ''));

    $hui_ok_form = new HuiForm('okform', array('action'));

    $hui_vgroup -> AddChild($hui_hgroup2);

    $hui_mainframe -> AddChild($hui_vgroup);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('removeuser_title');
}

$main_disp -> AddEvent('edituser', 'main_edituser');
function main_edituser($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM users '.'WHERE id='.$eventData['userid']);

    $user_data = $user_query -> Fields();

    $prof_query = & $env['db'] -> Execute('SELECT * '.'FROM groups');

    $profiles = array();
    $profiles[0] = $amp_locale -> GetStr('noprofileid_label');
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['groupname'];
        $prof_query -> MoveNext();
    }

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('editusergrid', '');

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('fnamelabel', array('label' => $amp_locale -> GetStr('userfname_label'))), 0, 0);
    $hui_user_grid -> AddChild(new HuiString('fname', array('disp' => 'pass', 'value' => $user_data['fname'])), 0, 1);

    $hui_user_grid -> AddChild(new HuiLabel('lnamelabel', array('label' => $amp_locale -> GetStr('userlname_label'))), 1, 0);
    $hui_user_grid -> AddChild(new HuiString('lname', array('disp' => 'pass', 'value' => $user_data['lname'])), 1, 1);

    $hui_user_grid -> AddChild(new HuiLabel('emaillabel', array('label' => $amp_locale -> GetStr('email_label'))), 2, 0);
    $hui_user_grid -> AddChild(new HuiString('email', array('disp' => 'pass', 'value' => $user_data['email'])), 2, 1);

    $hui_user_grid -> AddChild(new HuiLabel('otherlabel', array('label' => $amp_locale -> GetStr('userother_label'))), 3, 0);
    $hui_user_grid -> AddChild(new HuiText('other', array('disp' => 'pass', 'rows' => '5', 'cols' => '80', 'value' => $user_data['otherdata'])), 3, 1);

    $hui_vgroup -> AddChild($hui_user_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('edituser_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'edituser', array('uid' => $eventData['userid'], 'groupid' => $user_data['groupid'], 'username' => $user_data['username'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('newuserform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('edituser_title');
}

$main_disp -> AddEvent('chpassword', 'main_chpassword');
function main_chpassword($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM users '.'WHERE id='.$eventData['userid']);

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
    $form_events_call -> AddEvent(new HuiEvent('pass', 'chpasswd', array('uid' => $eventData['userid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('chpasswdform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('chpasswd_title');
}

$main_disp -> AddEvent('chprofile', 'main_chprofile');
function main_chprofile($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_mainstatus, $hui_titlebar;

    $user_query = & $env['ampdb'] -> execute('SELECT * '.'FROM users '.'WHERE id='.$eventData['userid'].' ');

    $user_data = $user_query -> Fields();

    $prof_query = & $env['db'] -> Execute('SELECT * '.'FROM groups '.'ORDER BY groupname');

    $profiles = array();
    $profiles[0] = $amp_locale -> GetStr('noprofileid_label');
    while (!$prof_query -> eof) {
        $prof_data = $prof_query -> Fields();
        $profiles[$prof_data['id']] = $prof_data['groupname'];
        $prof_query -> MoveNext();
    }

    $hui_vgroup = new HuiVertGroup('vgroup');

    $hui_user_grid = new HuiGrid('chprofilegrid', array('rows' => '2', 'cols' => '2'));

    // User fields
    //
    $hui_user_grid -> AddChild(new HuiLabel('profilelabel', array('label' => $amp_locale -> GetStr('changeprofile_label').' (*)')), 0, 0);
    $hui_user_grid -> AddChild(new HuiComboBox('profileid', array('disp' => 'pass', 'elements' => $profiles, 'default' => $user_data['groupid'])), 0, 1);

    $hui_vgroup -> AddChild($hui_user_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit1', array('caption' => $amp_locale -> GetStr('chprofile_submit'))));

    $hui_vgroup -> AddChild(new HuiHorizBar('horizbar1'));
    $hui_vgroup -> AddChild(new HuiLabel('reqfieldslabel', array('label' => $amp_locale -> GetStr('requiredfields_label'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('pass', 'chprofile', array('uid' => $eventData['userid'])));
    $form_events_call -> AddEvent(new HuiEvent('main', 'users', ''));

    $hui_form = new HuiForm('chprofileform', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);

    $hui_titlebar -> mTitle.= ' - '.$user_data['username'].' - '.$amp_locale -> GetStr('chprofile_title');
}

if ($gEnv['site']['id'] == $gEnv['user']['id']) {
    $main_disp -> AddEvent('motd', 'main_motd');
    function main_motd($eventData) {
        global $gEnv, $hui_titlebar, $hui_mainframe, $amp_locale;

        OpenLibrary('sites.library');

        $site = new Site($gEnv['root']['db'], $gEnv['site']['id'], $gEnv['site']['db']);

        $xml_def = '<vertgroup><name>motd</name>
          <children>
        
            <form><name>motd</name>
              <args>
                <method>post</method>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'motd', ''), array('pass', 'setmotd', '')))).'</action>
              </args>
              <children>
        
                <grid><name>motd</name>
        
                  <children>
        
                    <label row="0" col="0" halign="" valign="top"><name>label</name>
                      <args>
                        <label type="encoded">'.urlencode($amp_locale -> GetStr('motd.label')).'</label>
                      </args>
                    </label>
        
                    <text row="0" col="1"><name>motd</name>
                      <args>
                        <rows>10</rows>
                        <cols>80</cols>
                        <disp>pass</disp>
                        <value type="encoded">'.urlencode($site -> GetMotd()).'</value>
                      </args>
                    </text>
        
                  </children>
        
                </grid>
        
              </children>
            </form>
        
            <horizbar><name>hb</name></horizbar>
        
            <button>
              <name>apply</name>
              <args>
                <horiz>true</horiz>
                <frame>false</frame>
                <themeimage>button_ok</themeimage>
                <label type="encoded">'.urlencode($amp_locale -> GetStr('set_motd.submit')).'</label>
                <formsubmit>motd</formsubmit>
                <action type="encoded">'.urlencode(build_events_call_string('', array(array('main', 'motd', ''), array('pass', 'setmotd', '')))).'</action>
              </args>
            </button>
        
          </children>
        </vertgroup>';
        $hui_mainframe -> AddChild(new HuiXml('page', array('definition' => $xml_def)));

        $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('motd.title');
    }
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('profiles_help', array('node' => 'ampoliros.site.profiles.'.$eventData['node'], 'language' => $env['sitelocale'])));
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
