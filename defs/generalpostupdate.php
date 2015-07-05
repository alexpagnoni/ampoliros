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
// $Id: generalpostupdate.php,v 1.15 2004-07-08 15:04:26 alex Exp $

@copy( $tmpdir.'/AUTHORS', PRIVATE_TREE.'AUTHORS' );
@chmod( PRIVATE_TREE.'AUTHORS', 0644 );

@copy( $tmpdir.'/BUGS', PRIVATE_TREE.'BUGS' );
@chmod( PRIVATE_TREE.'BUGS', 0644 );

@copy( $tmpdir.'/CHANGES', PRIVATE_TREE.'CHANGES' );
@chmod( PRIVATE_TREE.'CHANGES', 0644 );

@copy( $tmpdir.'/INSTALL', PRIVATE_TREE.'INSTALL' );
@chmod( PRIVATE_TREE.'INSTALL', 0644 );

@copy( $tmpdir.'/LICENSE', PRIVATE_TREE.'LICENSE' );
@chmod( PRIVATE_TREE.'LICENSE', 0644 );

@copy( $tmpdir.'/README', PRIVATE_TREE.'README' );
@chmod( PRIVATE_TREE.'README', 0644 );

@copy( $tmpdir.'/TODO', PRIVATE_TREE.'TODO' );
@chmod( PRIVATE_TREE.'TODO', 0644 );

@copy( $tmpdir.'/AUTHORS', PRIVATE_TREE.'TROUBLESHOOTING' );
@chmod( PRIVATE_TREE.'TROUBLESHOOTING', 0644 );

@copy( $tmpdir.'/VERSION', PRIVATE_TREE.'VERSION' );
@chmod( PRIVATE_TREE.'VERSION', 0644 );

@copy( $tmpdir.'/www/auth.php', PUBLIC_TREE.'auth.php' );
@chmod( PUBLIC_TREE.'auth.php', 0644 );

@copy( $tmpdir.'/www/index.php', PUBLIC_TREE.'index.php' );
@chmod( PUBLIC_TREE.'index.php', 0644 );

@copy( $tmpdir.'/www/header.php', PUBLIC_TREE.'header.php' );
@chmod( PUBLIC_TREE.'header.php', 0644 );

@copy( $tmpdir.'/www/main.php', PUBLIC_TREE.'main.php' );
@chmod( PUBLIC_TREE.'main.php', 0644 );

@copy( $tmpdir.'/www/sum.php', PUBLIC_TREE.'sum.php' );
@chmod( PUBLIC_TREE.'sum.php', 0644 );

@copy( $tmpdir.'/www/favicon.ico', PUBLIC_TREE.'favicon.ico' );
@chmod( PUBLIC_TREE.'favicon.ico', 0644 );

// Ampoliros dependencies fix
//
$mod_query = &$GLOBALS['gEnv']['root']['db']->Execute( 'SELECT id '.
    'FROM modules '.
    'WHERE modid='.$GLOBALS['gEnv']['root']['db']->Format_Text( 'ampoliros' ) );

$GLOBALS['gEnv']['root']['db']->Execute( 'DELETE FROM moddeps '.
    'WHERE modid='.$mod_query->Fields( 'id' ) );

// :THISRELEASEONLY: 20030705 wuh: this release only
// Since a new color set has been added, the cache should be cleaned.
/*
OpenLibrary( 'cache.library' );
$gc = new CacheGarbageCollector();
$gc->EmptyCache();
unset( $gc );
*/

// :THISRELEASEONLY: 20030829 wuh: this release only
// Create users private directory.
/*
$_sites_query = &$GLOBALS['gEnv']['root']['db']->Execute(
    'SELECT id,siteid '.
    'FROM sites'
    );

$_sites = array();
while ( !$_sites_query->eof )
{
    $_sites[$_sites_query->Fields( 'id' )] = $_sites_query->Fields( 'siteid' );
    $_sites_query->MoveNext();
}

$_users_query = &$GLOBALS['gEnv']['root']['db']->Execute(
    'SELECT username,siteid '.
    'FROM users'
    );

while ( !$_users_query->eof )
{
    $_user_dir = SITESTUFF_PATH.$_sites[$_users_query->Fields( 'siteid' )].'/users/'.$_users_query->Fields( 'username' ).'/';

    if ( !file_exists( $_user_dir ) ) MkDirs( $_user_dir, 0755 );

    $_users_query->MoveNext();
}
*/

// Ampoliros auto reupdate
//
/*
$mod_query = &$GLOBALS['gEnv']['root']['db']->Execute(
    'SELECT modfile '.
    'FROM modules '.
    'WHERE modid='.$GLOBALS['gEnv']['root']['db']->Format_Text( 'ampoliros' ) );

@copy( MODULE_PATH.$mod_query->Fields( 'modfile' ), TMP_PATH.'modinst/reupdate' );
*/

// Fix for 3.3.0 release
//
/*
if ( file_exists( TMP_PATH.'.ampcentralset' ) ) unlink( TMP_PATH.'.ampcentralset' );
if ( file_exists( TMP_PATH.'.editionset' ) ) unlink( TMP_PATH.'.editionset' );
*/
?>
