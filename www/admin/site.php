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
// $Id: site.php,v 1.20 2004-07-08 15:04:25 alex Exp $

require ('./auth.php');

Carthag :: import('com.solarix.ampoliros.io.log.Logger');
Carthag :: import('com.solarix.ampoliros.locale.Locale');
OpenLibrary('misc.library');
OpenLibrary('hui.library');
OpenLibrary('sites.library');
OpenLibrary('modules.library');

$log = new logger(AMP_LOG);
$amp_locale = new locale('amp_site_site', $gEnv['user']['locale']['language']);

// Initialization
//
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

$hui_page = new HuiPage('page', array('title' => $amp_locale -> GetStr('sitedata_title')));
$hui_mainvertgroup = new HuiVertGroup('mainvertgroup');
$hui_titlebar = new HuiTitleBar('titlebar', array('title' => $amp_locale -> GetStr('sitedata_title'), 'icon' => 'terminal'));
$hui_mainvertgroup -> AddChild($hui_titlebar);

// Main tool bar
//
$hui_maintoolbar = new HuiToolBar('maintoolbar');

$home_action = new HuiEventsCall();
$home_action -> AddEvent(new HuiEvent('main', 'default', ''));
$hui_homebutton = new HuiButton('homebutton', array('label' => $amp_locale -> GetStr('sitedata_button'), 'themeimage' => 'gohome', 'horiz' => 'true', 'action' => $home_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_homebutton);

$edit_action = new HuiEventsCall();
$edit_action -> AddEvent(new HuiEvent('main', 'edit', ''));
$hui_editbutton = new HuiButton('editbutton', array('label' => $amp_locale -> GetStr('sitedataedit_button'), 'themeimage' => 'edit', 'horiz' => 'true', 'action' => $edit_action -> GetEventsCallString()));
$hui_maintoolbar -> AddChild($hui_editbutton);

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

$hui_toolbarframe -> AddChild($hui_maintoolbar);
$hui_toolbarframe -> AddChild($hui_helptoolbar);
$hui_mainvertgroup -> AddChild($hui_toolbarframe);

$hui_mainframe = new HuiHorizFrame('mainframe');
$hui_mainstatus = new HuiStatusBar('mainstatusbar');

// Pass dispatcher
//
$pass_disp = new HuiDispatcher('pass');

$pass_disp -> AddEvent('edit', 'pass_edit');
function pass_edit($eventData) {
    global $env, $hui_mainstatus, $amp_locale;

    $site_sets = new SiteSettings($env['db']);

    $site_sets -> EditKey('sitecompletename', $eventData['sitecompletename']);
    $site_sets -> EditKey('siteaddressa', $eventData['siteaddressa']);
    $site_sets -> EditKey('siteaddressb', $eventData['siteaddressb']);
    $site_sets -> EditKey('sitetown', $eventData['sitetown']);
    $site_sets -> EditKey('sitestate', $eventData['sitestate']);
    $site_sets -> EditKey('sitezip', $eventData['sitezip']);
    $site_sets -> EditKey('sitecountry', $eventData['sitecountry']);
    $site_sets -> EditKey('sitefiscalcode', $eventData['sitefiscalcode']);
    $site_sets -> EditKey('siteemail', $eventData['siteemail']);
    $site_sets -> EditKey('sitephone', $eventData['sitephone']);
    $site_sets -> EditKey('sitefax', $eventData['sitefax']);
    $site_sets -> EditKey('sitelogo', $eventData['sitelogo']);

    $hui_mainstatus -> mArgs['status'] = $amp_locale -> GetStr('sitedataset_status');
}

$pass_disp -> Dispatch();

// Main dispatcher
//
$main_disp = new HuiDispatcher('main');

$main_disp -> AddEvent('default', 'main_default');
function main_default($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_titlebar;

    $site_sets = new SiteSettings($env['db']);

    $hui_grid = new HuiGrid('grid', array('rows' => '11', 'cols' => '2'));

    $hui_grid -> AddChild(new HuiLabel('sitecompletenamelabel', array('label' => $amp_locale -> GetStr('sitecompletename_label'))), 0, 0);
    $hui_grid -> AddChild(new HuiString('sitecompletename', array('value' => $site_sets -> GetKey('sitecompletename'), 'disp' => 'pass', 'readonly' => 'true')), 0, 1);

    $hui_grid -> AddChild(new HuiLabel('siteaddressalabel', array('label' => $amp_locale -> GetStr('siteaddressa_label'))), 1, 0);
    $hui_grid -> AddChild(new HuiString('siteaddressa', array('value' => $site_sets -> GetKey('siteaddressa'), 'disp' => 'pass', 'readonly' => 'true')), 1, 1);

    $hui_grid -> AddChild(new HuiLabel('siteaddressblabel', array('label' => $amp_locale -> GetStr('siteaddressb_label'))), 2, 0);
    $hui_grid -> AddChild(new HuiString('siteaddressb', array('value' => $site_sets -> GetKey('siteaddressb'), 'disp' => 'pass', 'readonly' => 'true')), 2, 1);

    $hui_grid -> AddChild(new HuiLabel('sitetownlabel', array('label' => $amp_locale -> GetStr('sitetown_label'))), 3, 0);
    $hui_grid -> AddChild(new HuiString('sitetown', array('value' => $site_sets -> GetKey('sitetown'), 'disp' => 'pass', 'readonly' => 'true')), 3, 1);

    $hui_grid -> AddChild(new HuiLabel('sitestatelabel', array('label' => $amp_locale -> GetStr('sitestate_label'))), 4, 0);
    $hui_grid -> AddChild(new HuiString('sitestate', array('value' => $site_sets -> GetKey('sitestate'), 'disp' => 'pass', 'readonly' => 'true')), 4, 1);

    $hui_grid -> AddChild(new HuiLabel('siteziplabel', array('label' => $amp_locale -> GetStr('sitezip_label'))), 5, 0);
    $hui_grid -> AddChild(new HuiString('sitezip', array('value' => $site_sets -> GetKey('sitezip'), 'disp' => 'pass', 'readonly' => 'true')), 5, 1);

    $hui_grid -> AddChild(new HuiLabel('sitecountrylabel', array('label' => $amp_locale -> GetStr('sitecountry_label'))), 6, 0);
    $hui_grid -> AddChild(new HuiString('sitecountry', array('value' => $site_sets -> GetKey('sitecountry'), 'disp' => 'pass', 'readonly' => 'true')), 6, 1);

    $hui_grid -> AddChild(new HuiLabel('sitefiscalcodelabel', array('label' => $amp_locale -> GetStr('sitefiscalcode_label'))), 7, 0);
    $hui_grid -> AddChild(new HuiString('sitefiscalcode', array('value' => $site_sets -> GetKey('sitefiscalcode'), 'disp' => 'pass', 'readonly' => 'true')), 7, 1);

    $hui_grid -> AddChild(new HuiLabel('siteemaillabel', array('label' => $amp_locale -> GetStr('siteemail_label'))), 8, 0);
    $hui_grid -> AddChild(new HuiString('siteemail', array('value' => $site_sets -> GetKey('siteemail'), 'disp' => 'pass', 'readonly' => 'true')), 8, 1);

    $hui_grid -> AddChild(new HuiLabel('sitephonelabel', array('label' => $amp_locale -> GetStr('sitephone_label'))), 9, 0);
    $hui_grid -> AddChild(new HuiString('sitephone', array('value' => $site_sets -> GetKey('sitephone'), 'disp' => 'pass', 'readonly' => 'true')), 9, 1);

    $hui_grid -> AddChild(new HuiLabel('sitefaxlabel', array('label' => $amp_locale -> GetStr('sitefax_label'))), 10, 0);
    $hui_grid -> AddChild(new HuiString('sitefax', array('value' => $site_sets -> GetKey('sitefax'), 'disp' => 'pass', 'readonly' => 'true')), 10, 1);

    $hui_vgroup = new HuiVertGroup('vertgroup', array('align' => 'center'));
    $hui_vgroup -> AddChild($hui_grid);

    $hui_mainframe -> AddChild($hui_vgroup);
}

$main_disp -> AddEvent('edit', 'main_edit');
function main_edit($eventData) {
    global $env, $hui_mainframe, $amp_locale, $hui_titlebar;

    $site_sets = new SiteSettings($env['db']);

    $hui_grid = new HuiGrid('grid', array('rows' => '11', 'cols' => '2'));

    $hui_grid -> AddChild(new HuiLabel('sitecompletenamelabel', array('label' => $amp_locale -> GetStr('sitecompletename_label'))), 0, 0);
    $hui_grid -> AddChild(new HuiString('sitecompletename', array('value' => $site_sets -> GetKey('sitecompletename'), 'disp' => 'pass')), 0, 1);

    $hui_grid -> AddChild(new HuiLabel('siteaddressalabel', array('label' => $amp_locale -> GetStr('siteaddressa_label'))), 1, 0);
    $hui_grid -> AddChild(new HuiString('siteaddressa', array('value' => $site_sets -> GetKey('siteaddressa'), 'disp' => 'pass')), 1, 1);

    $hui_grid -> AddChild(new HuiLabel('siteaddressblabel', array('label' => $amp_locale -> GetStr('siteaddressb_label'))), 2, 0);
    $hui_grid -> AddChild(new HuiString('siteaddressb', array('value' => $site_sets -> GetKey('siteaddressb'), 'disp' => 'pass')), 2, 1);

    $hui_grid -> AddChild(new HuiLabel('sitetownlabel', array('label' => $amp_locale -> GetStr('sitetown_label'))), 3, 0);
    $hui_grid -> AddChild(new HuiString('sitetown', array('value' => $site_sets -> GetKey('sitetown'), 'disp' => 'pass')), 3, 1);

    $hui_grid -> AddChild(new HuiLabel('sitestatelabel', array('label' => $amp_locale -> GetStr('sitestate_label'))), 4, 0);
    $hui_grid -> AddChild(new HuiString('sitestate', array('value' => $site_sets -> GetKey('sitestate'), 'disp' => 'pass')), 4, 1);

    $hui_grid -> AddChild(new HuiLabel('siteziplabel', array('label' => $amp_locale -> GetStr('sitezip_label'))), 5, 0);
    $hui_grid -> AddChild(new HuiString('sitezip', array('value' => $site_sets -> GetKey('sitezip'), 'disp' => 'pass')), 5, 1);

    $hui_grid -> AddChild(new HuiLabel('sitecountrylabel', array('label' => $amp_locale -> GetStr('sitecountry_label'))), 6, 0);
    $hui_grid -> AddChild(new HuiString('sitecountry', array('value' => $site_sets -> GetKey('sitecountry'), 'disp' => 'pass')), 6, 1);

    $hui_grid -> AddChild(new HuiLabel('sitefiscalcodelabel', array('label' => $amp_locale -> GetStr('sitefiscalcode_label'))), 7, 0);
    $hui_grid -> AddChild(new HuiString('sitefiscalcode', array('value' => $site_sets -> GetKey('sitefiscalcode'), 'disp' => 'pass')), 7, 1);

    $hui_grid -> AddChild(new HuiLabel('siteemaillabel', array('label' => $amp_locale -> GetStr('siteemail_label'))), 8, 0);
    $hui_grid -> AddChild(new HuiString('siteemail', array('value' => $site_sets -> GetKey('siteemail'), 'disp' => 'pass')), 8, 1);

    $hui_grid -> AddChild(new HuiLabel('sitephonelabel', array('label' => $amp_locale -> GetStr('sitephone_label'))), 9, 0);
    $hui_grid -> AddChild(new HuiString('sitephone', array('value' => $site_sets -> GetKey('sitephone'), 'disp' => 'pass')), 9, 1);

    $hui_grid -> AddChild(new HuiLabel('sitefaxlabel', array('label' => $amp_locale -> GetStr('sitefax_label'))), 10, 0);
    $hui_grid -> AddChild(new HuiString('sitefax', array('value' => $site_sets -> GetKey('sitefax'), 'disp' => 'pass')), 10, 1);

    /*
    $tmpmod = new moduledep( $env[ampdb] );
    
    if ( $tmpmod->isenabled( 'magellan', $env[sitedata][siteid] ) )
    {
        $data = $sets->getkey( 'sitelogo' );
    
        $query = &$env[db]->Execute( 'select * from medias order by medianame' );
        $mvalues[] = 'none';
        $mcaptions[] = $adloc->GetStr( 'nomedia' );
    
        while ( !$query->eof )
        {
            $mdata = $query->fields();
            $mvalues[] = $mdata[id];
            if ( $mdata[id] == $data ) $selected = $mdata[id];
            $mcaptions[] = $mdata['medianame'];
            $query->MoveNext();
        }
    
        $row++;
        $table[0][$row] = new htmltext( $adloc->GetStr( 'logodesc' ) );
        $table[1][$row] = new htmlformselect( '', 'sitelogo', $selected, $mvalues, $mcaptions  );
        $row++;
        $table[0][$row] = new htmltext( $adloc->GetStr( 'logonote' ) );
        $table[0][$row]->colspan = 2;
    }
    
    $hui_grid->AddChild( new HuiLabel( 'sitelogolabel', array( 'label' => $amp_locale->GetStr( 'sitelogo_label' ) ) ), 11, 0 );
    $hui_grid->AddChild( new HuiString( 'sitelogo', array( 'value' => $site_sets->GetKey( 'sitelogo' ), 'disp' => 'pass' ) ), 11, 1 );
    */

    $hui_vgroup = new HuiVertGroup('vertgroup', array('align' => 'center'));
    $hui_vgroup -> AddChild($hui_grid);
    $hui_vgroup -> AddChild(new HuiSubmit('submit', array('caption' => $amp_locale -> GetStr('editdata_submit'))));

    $form_events_call = new HuiEventsCall();
    $form_events_call -> AddEvent(new HuiEvent('main', 'default', ''));
    $form_events_call -> AddEvent(new HuiEvent('pass', 'edit', ''));

    $hui_form = new HuiForm('form', array('action' => $form_events_call -> GetEventsCallString()));
    $hui_form -> AddChild($hui_vgroup);

    $hui_mainframe -> AddChild($hui_form);
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('edit_title');
}

$main_disp -> AddEvent('help', 'main_help');
function main_help($eventData) {
    global $env, $hui_titlebar, $hui_mainframe, $amp_locale;
    $hui_titlebar -> mTitle.= ' - '.$amp_locale -> GetStr('help_title');
    $hui_mainframe -> AddChild(new HuiHelpNode('site_help', array('node' => 'ampoliros.site.site.'.$eventData['node'], 'language' => $env['sitelocale'])));
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
