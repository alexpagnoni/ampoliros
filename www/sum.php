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
// $Id: sum.php,v 1.20 2004-07-08 15:04:26 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.hui.HuiDispatcher');

header('P3P: CP="CUR ADM OUR NOR STA NID"');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s'));
header('Cache-control: no-cache, must-revalidate');
header('Pragma: no-cache');

function sum_page() {
    global $gEnv;

    $amp_locale = new Locale('amp_root_menu', AMP_LANG);
    import('com.solarix.ampoliros.module.ModuleConfig');
    $mod_cfg = new ModuleConfig($gEnv['root']['db'], 'ampoliros');

    $hui = new Hui($gEnv['root']['db'], TRUE);
    $hui -> LoadWidget('table');
    $hui -> LoadWidget('page');
    $hui -> LoadWidget('vertgroup');
    $hui -> LoadWidget('vertframe');
    $hui -> LoadWidget('treemenu');

    $hui_page = new HuiPage('page', array('title' => 'Ampoliros'. (strlen(AMP_HOST) ? ' - '.AMP_HOST. (strlen(AMP_DOMAIN) ? '.'.AMP_DOMAIN : '') : ''), 'border' => 'false'));
    $hui_page -> mArgs['background'] = $hui_page -> mThemeHandler -> mStyle['menuback'];
    $hui_mainvertgroup = new HuiVertGroup('mainvertgroup');

    $el[1]['groupname'] = 'Ampoliros';

    $cont = 1;

    $query = & $gEnv['root']['db'] -> Execute('SELECT id FROM sites');
    if ($query -> NumRows()) {
        $el[1]['groupelements'][$cont]['name'] = $amp_locale -> GetStr('siteadmin');
        $el[1]['groupelements'][$cont]['image'] = $hui_page -> mThemeHandler -> mStyle['siteaccess'];
        $el[1]['groupelements'][$cont]['action'] = 'admin/';
        $el[1]['groupelements'][$cont]['themesized'] = 'true';

        $cont ++;
    }

    $el[1]['groupelements'][$cont]['name'] = $amp_locale -> GetStr('rootadmin');
    $el[1]['groupelements'][$cont]['image'] = $hui_page -> mThemeHandler -> mStyle['rootaccess'];
    $el[1]['groupelements'][$cont]['action'] = 'root/';
    $el[1]['groupelements'][$cont]['themesized'] = 'true';

    if ($mod_cfg -> GetKey('ampoliros-link-disabled') != '1') {
        $el[1]['groupelements'][++ $cont]['name'] = $amp_locale -> GetStr('amphome');
        $el[1]['groupelements'][$cont]['image'] = $hui_page -> mThemeHandler -> mStyle['ampminilogo'];
        $el[1]['groupelements'][$cont]['action'] = 'http://www.ampoliros.com/';
        $el[1]['groupelements'][$cont]['target'] = 'op';
        $el[1]['groupelements'][$cont]['themesized'] = 'true';
    }

    if ($mod_cfg -> GetKey('solarix-link-disabled') != '1') {
        $el[1]['groupelements'][++ $cont]['name'] = $amp_locale -> GetStr('solarixhome');
        $el[1]['groupelements'][$cont]['image'] = $hui_page -> mThemeHandler -> mStyle['solarixminilogo'];
        $el[1]['groupelements'][$cont]['action'] = 'http://www.solarix.biz/';
        $el[1]['groupelements'][$cont]['target'] = 'op';
        $el[1]['groupelements'][$cont]['themesized'] = 'true';
    }

    if ($mod_cfg -> GetKey('oem-link-disabled') != '1') {
        $oem_link_filename = $mod_cfg -> GetKey('oem-link-filename');

        if (strlen($oem_link_filename) and file_exists(CGI_PATH.$oem_link_filename)) {
            $el[1]['groupelements'][++ $cont]['name'] = $mod_cfg -> GetKey('oem-name');
            $el[1]['groupelements'][$cont]['image'] = CGI_URL.$oem_link_filename;
            $el[1]['groupelements'][$cont]['action'] = $mod_cfg -> GetKey('oem-url');
            $el[1]['groupelements'][$cont]['target'] = 'parent';
            $el[1]['groupelements'][$cont]['themesized'] = 'false';
        }
    }

    $hui_vertframe = new HuiVertFrame('vertframe');
    $hui_vertframe -> AddChild(new HuiTreeMenu('treemenu', array('elements' => $el, 'width' => '120', 'target' => 'parent', 'allgroupsactive' => 'true')));

    $hui_mainvertgroup -> AddChild($hui_vertframe);
    $hui_page -> AddChild($hui_mainvertgroup);
    $hui -> AddChild($hui_page);
    $hui -> Render();
}

function setup_entry($phaseMark, $phaseCompleted, $phaseName, & $hui_table, $row) {
    global $hui_page, $progress, $phases;

    if (file_exists(TMP_PATH.$phaseMark)) {
        $ball_icon = $hui_page -> mThemeHandler -> mStyle['goldball'];
        $font_color = 'yellow';
        $pre = '<b>';
        $post = '</b>';
    } else
        if (!file_exists(TMP_PATH.$phaseCompleted)) {
            $ball_icon = $hui_page -> mThemeHandler -> mStyle['redball'];
            $font_color = 'black';
            $pre = '';
            $post = '';
        } else {
            $ball_icon = $hui_page -> mThemeHandler -> mStyle['greenball'];
            $font_color = 'black';
            $pre = '';
            $post = '';
            $progress = $row +1;
        }

    $hui_table -> AddChild(new HuiImage('statusimage'.$row, array('imageurl' => $ball_icon)), $row, 0);
    $hui_table -> AddChild(new HuiLabel('phaselabel'.$row, array('label' => $pre.$phaseName.$post, 'nowrap' => 'false')), $row, 1);
}

// Checks if Ampoliros is in setup phase
//
if (!defined('AMPOLIROS_SETUP_PHASE')) {
    sum_page();
} else {
    global $gEnv;

    $progress = 0;

    $amp_locale = new Locale('amp_misc_install', AMP_LANG);

    $hui = new Hui($gEnv['root']['db'], TRUE);
    $hui -> LoadWidget('image');
    $hui -> LoadWidget('label');
    $hui -> LoadWidget('table');
    $hui -> LoadWidget('page');
    $hui -> LoadWidget('vertgroup');
    $hui -> LoadWidget('vertframe');
    $hui -> LoadWidget('treemenu');
    $hui -> LoadWidget('progressbar');

    $hui_page = new HuiPage('page', array('title' => 'Ampoliros'. (strlen(AMP_HOST) ? ' - '.AMP_HOST. (strlen(AMP_DOMAIN) ? '.'.AMP_DOMAIN : '') : '')));
    $hui_page -> mArgs['background'] = $hui_page -> mThemeHandler -> mStyle['menuback'];
    $hui_mainvertgroup = new HuiVertGroup('mainvertgroup');

    $headers[1]['label'] = $amp_locale -> GetStr('setupphase_header');

    $hui_table = new HuiTable('sumtable', array('headers' => $headers));

    $phase = 0;
    $phases = 13;

    setup_entry('.checkingsystem', '.systemchecked', $amp_locale -> GetStr('systemcheckphase_label'), $hui_table, $phase ++);
    setup_entry('.installingfiles', '.filesinstalled', $amp_locale -> GetStr('filesinstallphase_label'), $hui_table, $phase ++);
    setup_entry('.settingedition', '.editionset', $amp_locale -> GetStr('editionchoicephase_label'), $hui_table, $phase ++);
    setup_entry('.creatingdblayers', '.dblayerscreated', $amp_locale -> GetStr('dblayersphase_label'), $hui_table, $phase ++);
    setup_entry('.creatingdb', '.dbcreated', $amp_locale -> GetStr('amprootdbphase_label'), $hui_table, $phase ++);
    setup_entry('.initializingcomponents', '.componentsinitialized', $amp_locale -> GetStr('ampcomponentsphase_label'), $hui_table, $phase ++);
    setup_entry('.settingamphost', '.amphostset', $amp_locale -> GetStr('amphostchoicephase_label'), $hui_table, $phase ++);
    setup_entry('.settingcountry', '.countryset', $amp_locale -> GetStr('countrychoicephase_label'), $hui_table, $phase ++);
    setup_entry('.settinglanguage', '.languageset', $amp_locale -> GetStr('languagechoicephase_label'), $hui_table, $phase ++);
    setup_entry('.settingpassword', '.passwordset', $amp_locale -> GetStr('passwordphase_label'), $hui_table, $phase ++);
    setup_entry('.settingampcentral', '.ampcentralset', $amp_locale -> GetStr('ampcentralphase_label'), $hui_table, $phase ++);
    setup_entry('.cleaningup', '.cleanedup', $amp_locale -> GetStr('cleanupphase_label'), $hui_table, $phase ++);
    setup_entry('.finishingsetup', '.setupfinished', $amp_locale -> GetStr('finishphase_label'), $hui_table, $phase ++);

    $hui_mainvertgroup -> AddChild($hui_table);
    $hui_mainvertgroup -> AddChild(new HuiProgressBar('progress', array('progress' => $progress, 'totalsteps' => $phases)));
    $hui_page -> AddChild($hui_mainvertgroup);
    $hui -> AddChild($hui_page);
    $hui -> Render();
}
?>
