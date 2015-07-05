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
// $Id: auth.php,v 1.38 2004-07-14 13:15:37 alex Exp $

if (!defined('ADMIN_AUTH_PHP')) {
    define ('ADMIN_AUTH_PHP', true);
    require ('ampoliros.php');
    $amp = Ampoliros :: instance('Ampoliros');
    $amp -> setInterface(Ampoliros :: INTERFACE_WEB);

    if ($amp -> getState() == Ampoliros :: STATE_SETUP)
        $amp -> abort('Setup phase');

    import('com.solarix.ampoliros.locale.Locale');
    import('com.solarix.ampoliros.site.user.User');
    import('com.solarix.ampoliros.hui.Hui');
    import('com.solarix.ampoliros.hui.HuiDispatcher');

    function DoAuth($wrong = false, $reason = '') {
        global $gEnv;
        $amp_locale = new Locale('amp_misc_auth', AMP_LANG);

        $amp = Ampoliros :: instance('Ampoliros');
        $amp -> startRoot();

        $hui = new Hui($gEnv['root']['db']);
        $hui -> LoadWidget('button');
        $hui -> LoadWidget('formarg');
        $hui -> LoadWidget('form');
        $hui -> LoadWidget('grid');
        $hui -> LoadWidget('horizbar');
        $hui -> LoadWidget('horizframe');
        $hui -> LoadWidget('horizgroup');
        $hui -> LoadWidget('image');
        $hui -> LoadWidget('label');
        $hui -> LoadWidget('link');
        $hui -> LoadWidget('page');
        $hui -> LoadWidget('sessionkey');
        $hui -> LoadWidget('statusbar');
        $hui -> LoadWidget('string');
        $hui -> LoadWidget('submit');
        $hui -> LoadWidget('titlebar');
        $hui -> LoadWidget('vertframe');
        $hui -> LoadWidget('vertgroup');

        $hui_page = new HuiPage('loginpage', array('title' => $amp_locale -> GetStr('amplogin'), 'border' => 'false'));
        $hui_topgroup = new HuiVertGroup('topgroup', array('align' => 'center', 'groupalign' => 'center', 'groupvalign' => 'middle', 'height' => '100%'));
        $hui_maingroup = new HuiVertGroup('maingroup', array('align' => 'center'));
        $hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('amplogin'), 'closewidget' => 'false', 'newwindowwidget' => 'false'));
        $hui_mainbframe = new HuiVertFrame('vframe', array('align' => 'center'));
        $hui_mainframe = new HuiHorizGroup('horizframe');
        $hui_mainstatus = new HuiStatusBar('mainstatusbar');

        // Main frame
        //
        $hui_grid = new HuiGrid('grid', array('rows' => '2', 'cols' => '2'));

        $hui_grid -> AddChild(new HuiLabel('usernamelabel', array('label' => $amp_locale -> GetStr('username'))), 0, 0);
        $hui_grid -> AddChild(new HuiString('username', array('disp' => 'login')), 0, 1);

        $hui_grid -> AddChild(new HuiLabel('passwordlabel', array('label' => $amp_locale -> GetStr('password'))), 1, 0);
        $hui_grid -> AddChild(new HuiString('password', array('disp' => 'login', 'password' => 'true')), 1, 1);

        $hui_vgroup = new HuiVertGroup('vertgroup', array('align' => 'center'));
        //$hui_vgroup->AddChild( new HuiLabel( 'titlelabel', array( 'label' => $amp_locale->GetStr( 'amprootlogin' ) ) ) );
        $hui_vgroup -> AddChild($hui_grid);
        $hui_vgroup -> AddChild(new HuiSubmit('submit', array('caption' => $amp_locale -> GetStr('enter'))));

        $form_events_call = new HuiEventsCall();
        $form_events_call -> AddEvent(new HuiEvent('login', 'login', ''));
        $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));

        $hui_form = new HuiForm('form', array('action' => $form_events_call -> GetEventsCallString()));

        $hui_hgroup = new HuiHorizGroup('horizgroup', array('align' => 'middle'));
        //        $hui_hgroup -> AddChild(new HuiButton('amplogo', array('image' => $hui_page -> mThemeHandler -> mStyle['middot'], 'action' => AMP_URL, 'highlight' => false)));
        $hui_hgroup -> AddChild(new HuiButton('password', array('themeimage' => 'password', 'themeimagetype' => 'big', 'action' => AMP_URL, 'highlight' => false)));
        $hui_hgroup -> AddChild($hui_vgroup);

        $hui_form -> AddChild($hui_hgroup);
        $hui_mainframe -> AddChild($hui_form);

        // Wrong account check
        //
        if ($wrong) {
            if ($gEnv['core']['config'] -> Value('ALERT_ON_WRONG_LOCAL_USER_LOGIN') == '1') {
                Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');
                global $login_disp;
                $event_data = $login_disp -> GetEventData();

                $amp_security = new SecurityLayer();
                $amp_security -> SendAlert('Wrong user local login for user '.$event_data['username'].' from remote address '.$_SERVER['REMOTE_ADDR']);
                $amp_security -> LogFailedAccess($event_data['username'], false, $_SERVER['REMOTE_ADDR']);

                unset($amp_security);
            }

            $sleep_time = $gEnv['core']['config'] -> Value('WRONG_LOGIN_DELAY');
            if (!strlen($sleep_time))
                $sleep_time = 1;
            $max_attempts = $gEnv['core']['config'] -> Value('MAX_WRONG_LOGINS');
            if (!strlen($max_attempts))
                $max_attempts = 3;

            sleep($sleep_time);

            if (isset($_SESSION['site_login_attempts'])) {
                $_SESSION['site_login_attempts']++;
                if ($_SESSION['site_login_attempts'] >= $max_attempts)
                    AmpDie($amp_locale -> GetStr('wrongpwd'));
            } else
                $_SESSION['site_login_attempts'] = 1;

            if ($reason)
                $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr($reason);
            else
                $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('wrongpwd');
        } else {
            $_SESSION['site_login_attempts'] = 0;
        }

        // Page render
        //
        $hui_maingroup -> AddChild($hui_titlebar);
        //$hui_maingroup->AddChild( new HuiButton( 'amplogo', array( 'image' => CGI_URL.'ampbigdot.gif', 'action' => AMP_URL ) ) );
        $hui_mainbframe -> AddChild($hui_mainframe);
        $hui_mainbframe -> AddChild(new HuiHorizBar('hb'));
        $hui_mainbframe -> AddChild(new HuiLink('copyright', array('label' => $amp_locale -> GetStr('auth_copyright.label'), 'link' => 'http://www.solarix.it/', 'target' => '_blank')));
        $hui_maingroup -> AddChild($hui_mainbframe);
        $hui_maingroup -> AddChild($hui_mainstatus);
        $hui_topgroup -> AddChild($hui_maingroup);
        $hui_page -> AddChild($hui_topgroup);
        $hui -> AddChild($hui_page);
        $hui -> Render();

        $carthag = Carthag :: instance();
        $carthag -> halt();
    }

    if (isset($GLOBALS['gEnv']['runtime']['disp']['hui']['login'])) {
        $login_disp = new HuiDispatcher('login');

        $login_disp -> AddEvent('login', 'login_login');
        function login_login($eventData) {
            $userquery = $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT * FROM users WHERE username='.$GLOBALS['gEnv']['root']['db'] -> Format_Text($eventData['username']).' AND password='.$GLOBALS['gEnv']['root']['db'] -> Format_Text(md5($eventData['password'])));

            if ($userquery -> NumRows()) {
                session_register('AMP_AUTH_USER');
                $_SESSION['AMP_AUTH_USER'] = $eventData['username'];

                import('com.solarix.ampoliros.security.SecurityLayer');

                $amp_security = new SecurityLayer();
                $amp_security -> LogAccess($eventData['username'], false, false, $_SERVER['REMOTE_ADDR']);

                unset($amp_security);
            } else
                DoAuth(true);

            //		unset( $AMPROOT_AUTH_USER );
        }

        $login_disp -> AddEvent('logout', 'login_logout');
        function login_logout($eventData) {
            Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

            $amp_security = new SecurityLayer();
            $amp_security -> LogAccess($_SESSION['AMP_AUTH_USER'], true, false, $_SERVER['REMOTE_ADDR']);

            session_unregister('AMP_AUTH_USER');
            unset($_SESSION['AMP_AUTH_USER']);
            unset($amp_security);

            DoAuth();
        }

        $login_disp -> Dispatch();
    }

    if ($GLOBALS['gEnv']['core']['config'] -> Value('ONLY_HTTPS_SITE_ACCESS') == '1') {
        if (!isset($_SERVER['HTTPS']) or ($_SERVER['HTTPS'] != 'on')) {
            DoAuth(true, 'only_https_allowed');
        }
    }

    if (!isset($_SESSION['AMP_AUTH_USER'])) {
        DoAuth();
    }

    $sitesquery = $gEnv['root']['db'] -> Execute('SELECT id FROM sites WHERE siteid='.$gEnv['root']['db'] -> Format_Text(GetStoreID($gEnv['root']['db'], $_SESSION['AMP_AUTH_USER'])));
    if ($sitesquery -> NumRows() == 0) {
        DoAuth();
    } else {
        $sitesquery -> Free();
        $amp -> startSite(GetStoreID($gEnv['root']['db'], $_SESSION['AMP_AUTH_USER']), $_SESSION['AMP_AUTH_USER']);
    }

    if (isset($_SESSION['site_login_attempts']))
        unset($_SESSION['site_login_attempts']);

    // Check if the site is enabled
    //
    if ($gEnv['site']['data']['siteactive'] != $gEnv['root']['db'] -> fmttrue)
        DoAuth(true, 'sitedisabled');

    // Must exit if the user called a page for which he isn't enabled
    //
    Carthag :: import('com.solarix.ampoliros.site.user.Permissions');
    $perm = new Permissions($gEnv['site']['db'], $gEnv['user']['group']);

    // :KLUDGE: evil 20020503: strpos() instead of strrpos()
    // Use strrpos() instead of strpos().

    $gEnv['runtime']['pagename'] = !strpos($_SERVER['PHP_SELF'], '/') ? substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1) : $_SERVER['PHP_SELF'];
    $env['pagename'] = $gEnv['runtime']['pagename'];

    import('com.solarix.ampoliros.hui.theme.HuiTheme');

    switch ($gEnv['runtime']['pagename']) {
        case 'index.php' :
        case 'sum.php' :
        case 'header.php' :
        case 'sum.php' :
        case 'main.php' :
            break;

        default :
            $node_id = $perm -> GetNodeIdFromFileName($gEnv['runtime']['pagename']);
            $adloc = new Locale('amp_misc_auth', $gEnv['user']['locale']['language']);

            if ($node_id) {
                if ($perm -> Check($node_id, PERMISSIONS_NODETYPE_PAGE) == PERMISSIONS_NODE_NOTENABLED) {
                    AmpDie($adloc -> GetStr('nopageauth'));
                }
            } else
                AmpDie($adloc -> GetStr('nopageauth'));

            OpenLibrary('sessionkey.hui', HANDLER_PATH);
            $empty = new HuiSessionKey('mainpage', array('sessionobjectnopage' => 'true', 'value' => $gEnv['runtime']['pagename']));
    }

}
?>
