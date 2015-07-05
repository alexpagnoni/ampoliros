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
// $Id: sum.php,v 1.18 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

import('com.solarix.ampoliros.locale.Locale');
import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.hui.HuiEvent');
import('com.solarix.ampoliros.hui.HuiEventsCall');

header('P3P: CP="CUR ADM OUR NOR STA NID"');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s'));
header('Cache-control: no-cache, must-revalidate');
header('Pragma: no-cache');

$amp_menu_locale = new Locale('amp_root_menu', $gEnv['root']['locale']['language']);

$hui = new Hui($env['ampdb']);
$hui -> LoadWidget('empty');
$hui -> LoadWidget('grid');
$hui -> LoadWidget('horizframe');
$hui -> LoadWidget('horizgroup');
$hui -> LoadWidget('label');
$hui -> LoadWidget('page');
$hui -> LoadWidget('sessionkey');
$hui -> LoadWidget('treemenu');
$hui -> LoadWidget('vertframe');
$hui -> LoadWidget('vertgroup');

$hui_page = new HuiPage('page', array('title' => 'Ampoliros'. (strlen(AMP_HOST) ? ' - '.AMP_HOST. (strlen(AMP_DOMAIN) ? '.'.AMP_DOMAIN : '') : ''), 'border' => 'false'));
$hui_page -> mArgs['background'] = $hui_page -> mThemeHandler -> mStyle['menuback'];
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');

$groups_query = $env['ampdb'] -> execute('SELECT * '.'FROM rootgroups '.'ORDER BY name');
$num_groups = $groups_query -> numrows();

if ($num_groups > 0) {
    $cont_a = 0;
    unset($el);
    while (!$groups_query -> eof) {
        $group_data = $groups_query -> Fields();
        $el[$group_data['id']]['groupname'] = $group_data['name'];

        if (strlen($group_data['catalog'])) {
            $tmp_locale = new Locale($group_data['catalog'], $gEnv['root']['locale']['language']);
            $el[$group_data['id']]['groupname'] = $tmp_locale -> GetStr($group_data['name']);
        }

        $pagesquery = & $env['db'] -> execute('SELECT * '.'FROM rootpages '.'WHERE groupid='.$group_data['id'].' '.'ORDER BY name');
        if ($pagesquery) {
            $pagesnum = $pagesquery -> numrows();

            if ($pagesnum > 0) {
                $cont_b = 0;
                while (!$pagesquery -> eof) {
                    $pagedata = $pagesquery -> fields();

                    if (strlen($pagedata['catalog']) > 0) {
                        $tmploc = new Locale($pagedata['catalog'], AMP_LANG);
                        $descstr = $tmploc -> GetStr($pagedata['name']);
                    }

                    $tmp_eventscall = new HuiEventsCall($pagedata['location']);
                    $tmp_eventscall -> AddEvent(new HuiEvent('main', 'default', ''));

                    if (strlen($pagedata['themeicontype']))
                        $imageType = $pagedata['themeicontype'];
                    else
                        $imageType = 'apps';

                    strlen($pagedata['themeicon']) ? $imageUrl = $hui_page -> mThemeHandler -> mIconsBase.$hui_page -> mThemeHandler -> mIconsSet[$imageType][$pagedata['themeicon']]['base'].'/'.$imageType.'/'.$hui_page -> mThemeHandler -> mIconsSet[$imageType][$pagedata['themeicon']]['file'] : $imageUrl = $pagedata['iconfile'];

                    $el[$group_data['id']]['groupelements'][$cont_b]['name'] = $descstr;
                    $el[$group_data['id']]['groupelements'][$cont_b]['image'] = $imageUrl;
                    $el[$group_data['id']]['groupelements'][$cont_b]['action'] = $tmp_eventscall -> GetEventsCallString().'&act=def';
                    $el[$group_data['id']]['groupelements'][$cont_b]['themesized'] = 'true';

                    unset($tmp_eventscall);
                    $cont_b ++;
                    $pagesquery -> MoveNext();
                }
            }
        }

        if ($group_data['name'] == 'ampoliros') {
            $pagesquery = & $env['db'] -> execute('SELECT * '.'FROM rootpages '.'WHERE groupid=0 '.'OR groupid IS NULL '.'ORDER BY name');
            if ($pagesquery) {
                $pagesnum = $pagesquery -> numrows();

                if ($pagesnum > 0) {
                    while (!$pagesquery -> eof) {
                        $pagedata = $pagesquery -> fields();

                        if (strlen($pagedata['catalog']) > 0) {
                            $tmploc = new Locale($pagedata['catalog'], AMP_LANG);
                            $descstr = $tmploc -> GetStr($pagedata['name']);
                        }

                        $tmp_eventscall = new HuiEventsCall($pagedata['location']);
                        $tmp_eventscall -> AddEvent(new HuiEvent('main', 'default', ''));

                        $el[$group_data['id']]['groupelements'][$cont_b]['name'] = $descstr;
                        $el[$group_data['id']]['groupelements'][$cont_b]['image'] = $pagedata['iconfile'];
                        $el[$group_data['id']]['groupelements'][$cont_b]['action'] = $tmp_eventscall -> GetEventsCallString().'&act=def';
                        $el[$group_data['id']]['groupelements'][$cont_b]['themesized'] = 'true';

                        unset($tmp_eventscall);
                        $cont_b ++;
                        $pagesquery -> MoveNext();
                    }
                }
            }
        }

        if ($group_data['name'] == 'tools') {
            $logout_events_call = new HuiEventsCall('index.php');
            $logout_events_call -> AddEvent(new HuiEvent('login', 'logout', ''));

            $cont_a ++;
            $el[$group_data['id']]['groupelements'][$cont_a]['name'] = $amp_menu_locale -> GetStr('logout');
            $el[$group_data['id']]['groupelements'][$cont_a]['image'] = $hui_page -> mThemeHandler -> mIconsBase.$hui_page -> mThemeHandler -> mIconsSet['apps']['error']['base'].'/apps/'.$hui_page -> mThemeHandler -> mIconsSet['apps']['error']['file'];
            $el[$group_data['id']]['groupelements'][$cont_a]['action'] = $logout_events_call -> GetEventsCallString();
            $el[$group_data['id']]['groupelements'][$cont_a]['target'] = 'parent';
        }

        $groups_query -> MoveNext();
        $cont_a ++;
    }
}

$hui_vertframe = new HuiVertFrame('vertframe');
$hui_vertframe -> AddChild(new HuiTreeMenu('treemenu', array('elements' => $el, 'width' => '120', 'target' => 'groupop', 'allgroupsactive' => 'false')));

$hui_mainvertgroup -> AddChild($hui_vertframe);

$hui_page -> AddChild($hui_mainvertgroup);
$hui -> AddChild($hui_page);
$hui -> Render();
?>
