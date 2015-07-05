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
// $Id: xmlrpc.php,v 1.11 2004-07-08 15:04:26 alex Exp $

require ('ampremote.php');

$amp = Ampoliros::instance('Ampoliros');
$amp->setInterface(Ampoliros::INTERFACE_REMOTE);

import('com.solarix.ampoliros.db.DBLayer');
$structure = array();

while (list (, $tmpdata) = each($gEnv['remote']['methods'])) {
    if ($tmpdata['handler'] and $tmpdata['name'] and $tmpdata['function']) {
        if (!defined(strtoupper($tmpdata['handler']).'_XMLRPCMETHOD')) {
            OpenLibrary($tmpdata['handler'].'.xmlrpchandler', HANDLER_PATH);
        }

        $structure[$tmpdata['name']]['function'] = $tmpdata['function'];
        if (isset($tmpdata['signature']))
            $structure[$tmpdata['name']]['signature'] = $tmpdata['signature'];
        if (isset($tmpdata['docstring']))
            $structure[$tmpdata['name']]['docstring'] = $tmpdata['docstring'];
    }
}

$xs = new XmlRpc_Server($structure);
?>
