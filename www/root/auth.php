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
// $Id: auth.php,v 1.35 2004-07-14 13:15:37 alex Exp $

if (!defined('ROOT_AUTH_PHP')) {
    define ('ROOT_AUTH_PHP', true);
    require ('ampoliros.php');
    $amp = Ampoliros::instance('Ampoliros');
    $amp->setInterface(Ampoliros::INTERFACE_WEB);

    if (defined('AMPOLIROS_SETUP_PHASE'))
        $amp->abort('Setup phase');

    import('com.solarix.ampoliros.locale.Locale');
    import('com.solarix.ampoliros.hui.Hui');
    import('com.solarix.ampoliros.hui.HuiDispatcher');

    function DoAuth($wrong = false, $reason = '') {
        global $gEnv;
        $amp_locale = new Locale('amp_misc_auth', $gEnv['root']['locale']['language']);

        $amp = Ampoliros::instance('Ampoliros');
        $amp->startRoot();

        $hui = new Hui($gEnv['root']['db']);
        $hui -> LoadWidget('button');
        $hui -> LoadWidget('empty');
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

        $hui_page = new HuiPage('loginpage', array('title' => $amp_locale -> GetStr('amprootlogin'), 'border' => 'false'));
        $hui_topgroup = new HuiVertGroup('topgroup', array('align' => 'center', 'groupalign' => 'center', 'groupvalign' => 'middle', 'height' => '100%'));
        $hui_maingroup = new HuiVertGroup('maingroup', array('align' => 'center'));
        $hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('amprootlogin'), 'closewidget' => 'false', 'newwindowwidget' => 'false'));
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
            if ($gEnv['core']['config'] -> Value('ALERT_ON_WRONG_LOCAL_ROOT_LOGIN') == '1') {
                Carthag :: import('com.solarix.ampoliros.security.SecurityLayer');

                $amp_security = new SecurityLayer();
                $amp_security -> SendAlert('Wrong root local login from remote address '.$_SERVER['REMOTE_ADDR']);
                $amp_security -> LogFailedAccess('', true, $_SERVER['REMOTE_ADDR']);

                unset($amp_security);
            }

            $sleep_time = $gEnv['core']['config'] -> Value('WRONG_LOGIN_DELAY');
            if (!strlen($sleep_time))
                $sleep_time = 1;
            $max_attempts = $gEnv['core']['config'] -> Value('MAX_WRONG_LOGINS');
            if (!strlen($max_attempts))
                $max_attempts = 3;

            sleep($sleep_time);

            if (isset($_SESSION['root_login_attempts'])) {
                $_SESSION['root_login_attempts']++;
                if ($_SESSION['root_login_attempts'] >= $max_attempts)
                    AmpDie($amp_locale -> GetStr('wrongpwd'));
            } else
                $_SESSION['root_login_attempts'] = 1;

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

        $carthag = Carthag::instance();
        $carthag->halt();
    }

    $login_disp = new HuiDispatcher('login');

    $login_disp -> AddEvent('login', 'login_login');
    function login_login($eventData) {
        //global $AMPROOT_AUTH_USER;

        $fh = @ fopen(CONFIG_PATH.'amprootpwd.cfg', 'r');
        if ($fh) {
            $cpassword = fgets($fh, 4096);
            if ($eventData['username'] == 'amp' and md5($eventData['password']) == $cpassword) {
                session_register('AMPROOT_AUTH_USER');
                $_SESSION['AMPROOT_AUTH_USER'] = $eventData['username'];

                import('com.solarix.ampoliros.security.SecurityLayer');

                $amp_security = new SecurityLayer();
                $amp_security -> LogAccess('', false, true, $_SERVER['REMOTE_ADDR']);

                unset($amp_security);
            } else {
                DoAuth(true);
                unset($_SESSION['AMPROOT_AUTH_USER']);
            }
        } else {
            DoAuth(true);
            unset($AMPROOT_AUTH_USER);
        }
    }

    $login_disp -> AddEvent('logout', 'login_logout');
    function login_logout($eventData) {
        import('com.solarix.ampoliros.security.SecurityLayer');

        $amp_security = new SecurityLayer();
        $amp_security -> LogAccess('', true, true, $_SERVER['REMOTE_ADDR']);

        session_unregister('AMPROOT_AUTH_USER');
        unset($_SESSION['AMPROOT_AUTH_USER']);
        unset($amp_security);
        DoAuth();
    }

    $login_disp -> Dispatch();

    if ($GLOBALS['gEnv']['core']['config'] -> Value('ONLY_HTTPS_ROOT_ACCESS') == '1') {
        if (!isset($_SERVER['HTTPS']) or ($_SERVER['HTTPS'] != 'on')) {
            DoAuth(true, 'only_https_allowed');
        }
    }

    if (!isset($_SESSION['AMPROOT_AUTH_USER'])) {
        DoAuth();
    }

    if (isset($_SESSION['root_login_attempts']))
        unset($_SESSION['root_login_attempts']);

    $amp->startRoot($_SESSION['AMPROOT_AUTH_USER']);
    import('com.solarix.ampoliros.hui.theme.HuiTheme');

    $gEnv['runtime']['pagename'] = !strpos($_SERVER['PHP_SELF'], '/') ? substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1) : $_SERVER['PHP_SELF'];
    $env['pagename'] = $gEnv['runtime']['pagename'];

    switch ($gEnv['runtime']['pagename']) {
        case 'index.php' :
        case 'auth.php' :
        case 'main.php' :
        case 'sum.php' :
        case 'header.php' :
        case 'unlock.php' :
            break;

        default :
            OpenLibrary('sessionkey.hui', HANDLER_PATH);
            $empty = new HuiSessionKey('mainpage', array('sessionobjectnopage' => 'true', 'value' => $gEnv['runtime']['pagename']));
    }

}
?>
