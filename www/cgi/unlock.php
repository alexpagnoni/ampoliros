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
// $Id: unlock.php,v 1.16 2004-07-08 15:04:26 alex Exp $

define ('AMPOLIROS_OVERRIDE_LOCK', TRUE);

require ('amproot.php');
require (AMP_PATH.'auth.php');
$amp = Ampoliros::instance('Ampoliros');
$amp->setInterface(Ampoliros::INTERFACE_WEB);

// Erase all semaphores

if ($handle = opendir(TMP_PATH.'semaphores')) {
    while (($file = readdir($handle)) !== false) {
        if ($file != '.' and $file != '..')
            @ unlink(TMP_PATH.'semaphores/'.$file);
    }
    closedir($handle);
}

// Erase system upgrading lock if it exists

if (file_exists(AMP_UPGRADINGSYSTEM_LOCK)) {
    if (@ unlink(AMP_UPGRADINGSYSTEM_LOCK)) {
        Carthag :: import('com.solarix.ampoliros.io.log.Logger');

        $tmp_log = new Logger($gEnv['root']['log']);
        $tmp_log -> LogEvent('Ampoliros', 'Ampoliros has been unlocked.', LOGGER_NOTICE);

        $message = 'System unlocked.';
    } else
        $message = 'Unable to unlock system.';
} else {
    $message = 'System was not locked.';
}

$amp->abort($message);

?>
