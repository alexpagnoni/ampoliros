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
// $Id: Process.php,v 1.6 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.process');

import('carthag.util.Singleton');

class Process extends Singleton {
    var $pid;

    public function Process() {
        $this -> pid = posix_getpid();

        pcntl_signal(SIGTERM, array($this, 'SignalHandler'));
        pcntl_signal(SIGHUP, array($this, 'SignalHandler'));
        pcntl_signal(SIGCHLD, array($this, 'SignalHandler'));
    }

    public function instance() {
        return Singleton :: instance('process');
    }

    public function getPid() {
        return $this -> pid;
    }

    public function start() {
    }

    pubilc function fork() {
        $child = pcntl_fork();

        if ($child == -1) {
            echo "Unable to fork\n";
        }
        elseif ($child) {
            // Parent
        } else {
            // Child
            $this -> pid = posix_getpid();
            $this -> _StartChild();
        }

        return $child;
    }

    public function signalHandler($signal) {
        switch ($signal) {
            case SIGTERM :
                $this -> Shutdown();
                break;

            case SIGHUP :
                $this -> Restart();
                break;

            case SIGCHLD :
                while (pcntl_waitpid(-1, $status, WNOHANG) > 0) {
                }
                break;

            default :
                }

        return true;
    }

    public function shutdown() {
        exit;
    }

    public function restart() {
    }

    public function waitChildren() {
        while (pcntl_waitpid(-1, $status, WUNTRACED) > 0) {
        }
    }

    public function _startChild() {
    }
}

?>