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
// $Id: index.php,v 1.10 2004-07-08 15:04:25 alex Exp $

require( './auth.php' );
OpenLibrary( 'hui.library' );

if ( strlen( $_SESSION['AMP_AUTH_USER'] ) )
{
    OpenLibrary( 'sessionkey.hui', HANDLER_PATH );

$empty = new HuiSessionKey( 'mainpage', array( 'sessionobjectnopage' => 'true' ) );
if ( !strlen( $empty->mValue ) or !file_exists( $empty->mValue ) ) $main_page_url = 'main.php';
else
{
    $main_page_url = build_events_call_string( $empty->mValue, array( array( 'main', 'default', '' ) ) ).'&amp;act=def';
}

header( 'P3P: CP="CUR ADM OUR NOR STA NID"' );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Ampoliros - <?php echo $env["sitedata"]["sitename"]; ?></title>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
</head>

<frameset cols="150,*" framespacing="0" border="0" frameborder="0">
	<frameset rows="175,*" framespacing="0" border="0">
		<frame name="header" target="groupop" SRC="header.php" scrolling="no" noresize>
		<frame name="sum" target="groupop" SRC="sum.php" scrolling="auto" noresize>
	</frameset>
	<frame name="groupop" src="<?php echo $main_page_url; ?>">
	<noframes>
		<body>
			<p align="center"><strong>Your browser doesn't support frames.</strong></p>
		</body>
	</noframes>
</frameset>
</html>
<?php
}
?>
