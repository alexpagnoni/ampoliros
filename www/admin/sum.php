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

require( './auth.php' );

import('com.solarix.ampoliros.hui.Hui');
import('com.solarix.ampoliros.hui.HuiEvent');
import('com.solarix.ampoliros.hui.HuiEventsCall');
import('com.solarix.ampoliros.site.user.Permissions');

header( 'P3P: CP="CUR ADM OUR NOR STA NID"' );
header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
header( 'Last-Modified: '.gmdate( 'D, d M Y H:i:s' ) );
header( 'Cache-control: no-cache, must-revalidate' );
header( 'Pragma: no-cache' );

$hui = new Hui( $env['ampdb'] );
    $hui->LoadWidget( 'horizframe' );
    $hui->LoadWidget( 'horizgroup' );
    $hui->LoadWidget( 'page' );
    $hui->LoadWidget( 'treemenu' );
    $hui->LoadWidget( 'vertframe' );
    $hui->LoadWidget( 'vertgroup' );


$hui_page = new HuiPage( 'page', array(
                                       'title' => 'Ampoliros - '.$env['sitedata']['sitename'],
                                       'border' => 'false'
                                      ) );
$hui_page->mArgs['background'] = $hui_page->mThemeHandler->mStyle['menuback'];

$hui_mainvertgroup = new HuiVertGroup( 'mainvertgroup' );

$tmpperm = new Permissions( $env['db'], $env['currentgroupserial'] );

$groupsquery = $env['db']->execute( 'select * from admingroups order by name' );
$numgroups   = $groupsquery->numrows();

if ( $numgroups > 0 )
{
    import('com.solarix.ampoliros.site.SiteSettings');
    $site = new SiteSettings( $env['db'] );

    $prefs_id = 0;
    $tools_id = 0;

/*    if ( $env['disp']['action'] == 'open' )
    {
        $menu->id = $env['disp']['id'];
        $site->EditKey( $env['currentuser'].'-lastopenedgroup', $env['disp']['id'] );
    }
    else
    {
        $tid = $site->GetKey( $env['currentuser'].'-lastopenedgroup' );
        if ( !strlen( $tid ) ) $tid = 0;
        $menu->id = $tid;
    }
*/

    $cont = 0;
    unset( $el );

    while ( !$groupsquery->eof )
    {
        $groupdata = $groupsquery->fields();

        if ( $tmpperm->check( $groupdata['id'], 'group' ) != PERMISSIONS_NODE_NOTENABLED )
        {
            if ( $groupdata['name'] == 'tools' ) $tools_id = $groupdata['id'];
            if ( $groupdata['name'] == 'preferences' ) $prefs_id = $groupdata['id'];

            $el[$groupdata['id']]['groupname'] = $groupdata['name'];

            if ( strlen( $groupdata['catalog'] ) > 0 )
            {
                $tmploc = new locale( $groupdata['catalog'], $gEnv['user']['locale']['language'] );
                $descstr = $tmploc->GetStr( $groupdata['name'] );
                $el[$groupdata['id']]['groupname'] = $descstr;
            }

            $pagesquery = &$env['db']->execute( 'select * from adminpages where groupid = '.$groupdata['id'].'  order by name' );
            $pagesnum = $pagesquery->numrows();

            if ( $pagesnum > 0 )
            {
                $contb = 0;

                while ( !$pagesquery->eof )
                {
                    $pagedata = $pagesquery->fields();

                    if ( $tmpperm->check( $pagedata['id'], 'page' ) != PERMISSIONS_NODE_NOTENABLED )
                    {
                        if ( strlen( $pagedata['catalog'] ) > 0 )
                        {
                            $tmploc = new locale( $pagedata['catalog'], $gEnv['user']['locale']['language'] );
                            $descstr = $tmploc->GetStr( $pagedata['name'] );

                            $tmp_eventscall = new HuiEventsCall( $pagedata['location'] );
                            $tmp_eventscall->AddEvent( new HuiEvent( 'main', 'default', '' ) );

                            if ( strlen( $pagedata['themeicontype'] ) ) $imageType = $pagedata['themeicontype'];
                            else $imageType = 'apps';

                            strlen( $pagedata['themeicon'] ) ? $imageUrl = $hui_page->mThemeHandler->mIconsBase.$hui_page->mThemeHandler->mIconsSet[$imageType][$pagedata['themeicon']]['base'].'/'.$imageType.'/'.$hui_page->mThemeHandler->mIconsSet[$imageType][$pagedata['themeicon']]['file'] : $imageUrl = $pagedata['iconfile'];

                            $el[$groupdata['id']]['groupelements'][$contb]['name'] = $descstr;
                            $el[$groupdata['id']]['groupelements'][$contb]['image'] = $imageUrl;
                            $el[$groupdata['id']]['groupelements'][$contb]['action'] = $tmp_eventscall->GetEventsCallString().'&act=def';
                            $el[$groupdata['id']]['groupelements'][$contb]['themesized'] = 'true';

                            unset( $tmp_eventscall );
                        }
                    }

                    $pagesquery->movenext();
                    $contb++;
                }
            }

            if ( $groupdata['name'] == 'tools' )
            {
                $logout_events_call = new HuiEventsCall( 'index.php' );
                $logout_events_call->AddEvent( new HuiEvent( 'login', 'logout', '' ) );

                $cont++;
                $el[$groupdata['id']]['groupelements'][$contb]['name'] = 'Log out';
                $el[$groupdata['id']]['groupelements'][$contb]['image'] = $hui_page->mThemeHandler->mIconsBase.$hui_page->mThemeHandler->mIconsSet['apps']['error']['base'].'/apps/'.$hui_page->mThemeHandler->mIconsSet['apps']['error']['file'];
                $el[$groupdata['id']]['groupelements'][$contb]['action'] = $logout_events_call->GetEventsCallString();
                $el[$groupdata['id']]['groupelements'][$contb]['target'] = 'parent';
                $el[$groupdata['id']]['groupelements'][$contb]['themesized'] = 'true';
            }
        }

        $cont++;
        $groupsquery->movenext();
    }

    if ( $tools_id != 0 )
    {
        $tmp_tools = $el[$tools_id];
        unset( $el[$tools_id] );
        $el[$tools_id] = &$tmp_tools;
    }

    if ( $prefs_id != 0 )
    {
        $tmp_prefs = $el[$prefs_id];
        unset( $el[$prefs_id] );
        $el[$prefs_id] = &$tmp_prefs;
    }

    //if ( $action == 'open' ) $menu->id = $id;
    //$menu->show();
}

$hui_vertframe = new HuiVertFrame( 'vertframe' );
$hui_vertframe->AddChild( new HuiTreeMenu( 'treemenu', array( 'elements' => $el, 'width' => '120', 'target' => 'groupop' ) ) );

$hui_mainvertgroup->AddChild( $hui_vertframe );

$hui_page->AddChild( $hui_mainvertgroup );
$hui->AddChild( $hui_page );
$hui->Render();

?>
