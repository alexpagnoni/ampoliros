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
// $Id: Debugger.php,v 1.7 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.debug');

/*!
 @class Debugger
 @abstract Class for Ampoliros processes debugging.
 */
class Debugger extends Object {
    public $mPid;
    public $mPidStructure = array();
    public $mRead = false;
    public $mLogEvents = array();
    public $mCalledHooks = array();
    public $mHuiEvents = array();
    public $mExecutedQueries = array();
    public $mSessionId = array();
    public $mState;
    public $mInterface;
    public $mMode;
    public $mOpenedLibraries = array();
    public $mLoadedExtensions = array();
    public $mDefinedClasses = array();
    public $mDefinedFunctions = array();
    public $mIncludedFiles = array();
    public $mMemoryUsage = 0;
    public $mProfiler = array();
    public $mDbProfiler = array();
    public $mPidSize;
    public $mDbTotalLoad;

    public function Debugger($pid) {
        $this -> mPid = $pid;
    }

    public function checkPidFile() {
        if (file_exists(TMP_PATH.'pids/'.$this -> mPid) and filesize(TMP_PATH.'pids/'.$this -> mPid))
            return true;
        return false;
    }

    public function isCurrentPid() {
        import('com.solarix.ampoliros.core.Ampoliros');
        $amp = Ampoliros :: instance('Ampoliros');
        if ($this -> mPid == $amp -> getPid())
            return true;
        else
            return false;
    }

    public function readPidFile() {
        $result = false;

        if ($this -> CheckPidFile()) {
            $this -> mPidSize = filesize(TMP_PATH.'pids/'.$this -> mPid);

            if ($fh = @ fopen(TMP_PATH.'pids/'.$this -> mPid, 'r')) {
                $this -> mPidStructure = unserialize(file_get_contents(TMP_PATH.'pids/'.$this -> mPid));

                if (is_array($this -> mPidStructure)) {
                    $this -> mProfiler = array();
                    $this -> mLogEvents = array();
                    $this -> mLibraries = array();
                    $this -> mSessionId = $this -> mPidStructure['gEnv']['runtime']['sessionid'];

                    switch ($this -> mPidStructure['gEnv']['core']['state']) {
                        case AMP_STATE_SETUP :
                            $this -> mState = 'SETUP';
                            break;
                        case AMP_STATE_DEVELOPMENT :
                            $this -> mState = 'DEVELOPMENT';
                            break;
                        case AMP_STATE_DEBUG :
                            $this -> mState = 'DEBUG';
                            break;
                        case AMP_STATE_PRODUCTION :
                            $this -> mState = 'PRODUCTION';
                            break;
                        case AMP_STATE_UPGRADE :
                            $this -> mState = 'UPGRADE';
                            break;
                    }

                    switch ($this -> mPidStructure['gEnv']['core']['interface']) {
                        case AMP_INTERFACE_UNKNOWN :
                            $this -> mInterface = 'UNKNOWN';
                            break;
                        case AMP_INTERFACE_CONSOLE :
                            $this -> mInterface = 'CONSOLE';
                            break;
                        case AMP_INTERFACE_WEB :
                            $this -> mInterface = 'WEB';
                            break;
                        case AMP_INTERFACE_REMOTE :
                            $this -> mInterface = 'REMOTE';
                            break;
                        case AMP_INTERFACE_GUI :
                            $this -> mInterface = 'GUI';
                            break;
                        case AMP_INTERFACE_EXTERNAL :
                            $this -> mInterface = 'EXTERNAL';
                    }

                    switch ($this -> mPidStructure['gEnv']['core']['mode']) {
                        case AMP_MODE_ROOT :
                            $this -> mMode = 'ROOT';
                            break;
                        case AMP_MODE_SITE :
                            $this -> mMode = 'SITE';
                            break;
                    }

                    if (is_array($this -> mPidStructure['gEnv']['runtime']['debug']['logs'])) {
                        while (list ($log, $event_array) = each($this -> mPidStructure['gEnv']['runtime']['debug']['logs'])) {
                            while (list (, $event) = each($event_array)) {
                                $this -> mLogEvents[] = $event;
                            }
                        }
                    }

                    if (isset($this -> mPidStructure['gEnv']['runtime']['debug']['hooks']) and is_array($this -> mPidStructure['gEnv']['runtime']['debug']['hooks'])) {
                        while (list ($hook, $functions) = each($this -> mPidStructure['gEnv']['runtime']['debug']['hooks'])) {
                            while (list (, $function) = each($functions)) {
                                $this -> mCalledHooks[] = $hook.' -> '.$function;
                            }
                        }
                    }

                    if (isset($this -> mPidStructure['gEnv']['runtime']['disp']['hui']) and is_array($this -> mPidStructure['gEnv']['runtime']['disp']['hui'])) {
                        $this -> mHuiEvents = $this -> mPidStructure['gEnv']['runtime']['disp']['hui'];
                    }

                    if (isset($this -> mPidStructure['gEnv']['runtime']['debug']['queries']) and is_array($this -> mPidStructure['gEnv']['runtime']['debug']['queries'])) {
                        while (list (, $query) = each($this -> mPidStructure['gEnv']['runtime']['debug']['queries'])) {
                            $this -> mExecutedQueries[] = $query;
                        }
                    }

                    $this -> mMemoryUsage = $this -> mPidStructure['gEnv']['runtime']['debug']['memoryusage'];

                    end($this -> mPidStructure['gEnv']['runtime']['debug']['loadtime'] -> history);
                    $total_time = current($this -> mPidStructure['gEnv']['runtime']['debug']['loadtime'] -> history);
                    reset($this -> mPidStructure['gEnv']['runtime']['debug']['loadtime'] -> history);
                    $prev_time = 0;

                    while (list ($mark, $time) = each($this -> mPidStructure['gEnv']['runtime']['debug']['loadtime'] -> history)) {
                        $perc = round(($time - $prev_time) * 100 / $total_time, 3);
                        $this -> mProfiler[] = $time.' - '.$mark.': '. ($time - $prev_time).' ('.$perc.'%)';
                        $prev_time = $time;
                    }
                    $total_time = 0;

                    while (list ($section, $time) = each($this -> mPidStructure['gEnv']['runtime']['debug']['dbloadtime'] -> history)) {
                        $total_time += $time;
                    }
                    $this -> mDbTotalLoad = $total_time;

                    reset($this -> mPidStructure['gEnv']['runtime']['debug']['dbloadtime'] -> history);
                    while (list ($section, $time) = each($this -> mPidStructure['gEnv']['runtime']['debug']['dbloadtime'] -> history)) {
                        $perc = round($time * 100 / $total_time, 3);
                        $this -> mDbProfiler[] = $time.' ('.$perc.'%): '.$section;
                    }

                    while (list (, $library) = each($this -> mPidStructure['gEnv']['runtime']['libraries'])) {
                        $this -> mLibraries[] = $library;
                    }

                    if (is_array($this -> mPidStructure['classes'])) {
                        $this -> mDefinedClasses = $this -> mPidStructure['classes'];
                    }

                    if (is_array($this -> mPidStructure['functions'])) {
                        $this -> mDefinedFunctions = $this -> mPidStructure['functions'];
                    }

                    if (is_array($this -> mPidStructure['extensions'])) {
                        $this -> mLoadedExtensions = $this -> mPidStructure['extensions'];
                    }

                    if (is_array($this -> mPidStructure['includedfiles'])) {
                        $this -> mIncludedFiles = $this -> mPidStructure['includedfiles'];
                    }

                    $result = $this -> mRead = true;
                }

                fclose($fh);
            }
        }

        return $result;
    }

    public function guessModule() {
        $result = false;
        if (!$this -> mRead)
            $this -> ReadPidFile();

        global $gEnv;

        $page = basename($this -> mPidStructure['gEnv']['runtime']['pagename']);

        if (strlen($page)) {
            $guess_query = $gEnv['root']['db'] -> Execute('SELECT modname,modules.id,'.'modules.modversion AS modversion,'.'modules.supportemail AS supportemail,'.'modules.bugsemail AS bugsemail,'.'modules.authoremail AS authoremail,'.'modules.maintaineremail AS maintaineremail '.'FROM modregister,elementtypes,modules '.'WHERE elementfile LIKE '.$gEnv['root']['db'] -> Format_Text('%'.$page).' '.'AND elementtypes.typename='.$gEnv['root']['db'] -> Format_Text($this -> mPidStructure['gEnv']['core']['mode'] == AMP_MODE_ROOT ? 'rootpage' : 'adminpage').' '.'AND elementtypes.id=modregister.categoryid '.'AND modname=modules.modid');

            if ($guess_query -> NumRows()) {
                $guess_data = $guess_query -> Fields();
                $bug_email = '';
                if (strlen($guess_data['bugsemail']))
                    $bug_email = $guess_data['bugsemail'];
                else
                    if (strlen($guess_data['supportemail']))
                        $bug_email = $guess_data['supportemail'];
                    else
                        if (strlen($guess_data['authoremail']))
                            $bug_email = $guess_data['authoremail'];
                        else
                            if (strlen($guess_data['maintaineremail']))
                                $bug_email = $guess_data['maintaineremail'];

                $result = array();
                $result['module'] = $guess_data['modname'];
                $result['version'] = $guess_data['modversion'];
                $result['email'] = $bug_email;

                if ($guess_data['modname'] == 'ampoliros') {
                    $result['ampolirosemail'] = $bug_email;
                } else {
                    $amp_query = $gEnv['root']['db'] -> Execute('SELECT bugsemail '.'FROM modules '.'WHERE modid='.$gEnv['root']['db'] -> Format_Text('ampoliros'));

                    $result['ampolirosemail'] = $amp_query -> Fields('bugsemail');

                    if (!strlen($result['email']))
                        $result['email'] = $result['ampolirosemail'];
                }
            }
        }

        if (!is_array($result)) {
            $result = array();
            $amp_query = $gEnv['root']['db'] -> Execute('SELECT bugsemail FROM modules WHERE modid='.$gEnv['root']['db'] -> Format_Text('ampoliros'));
            $result['email'] = $result['ampolirosemail'] = $amp_query -> Fields('bugsemail');
            $result['module'] = $result['module'] = '';
        }

        return $result;
    }

    public function submitBugReport($moddata, $from, $message = '', $notifyAmpolirosTeam = false) {
        if (is_array($moddata) and isset($moddata['email']) and strlen($from)) {
            OpenLibrary('mail.library');

            $bug_report = new Mail();
            $bug_report -> From($from);
            $bug_report -> ReplyTo($from);
            $bug_report -> To($moddata['email']);
            $bug_report -> Subject('Ampoliros bug report for '. (strlen($moddata['module']) ? '"'.$moddata['module'].'['.$moddata['version'].']"' : 'undefined').' module');

            $bug_report -> Body('Ampoliros bug report for '. (strlen($moddata['module']) ? '"'.$moddata['module'].'['.$moddata['version'].']"' : 'undefined').' module'."\n\n". (strlen($message) ? 'Message from bug report submitter:'."\n\n".$message."\n\n" : "\n\n"));
            if ($notifyAmpolirosTeam and $moddata['email'] != $moddata['ampolirosemail'])
                $bug_report -> Cc($moddata['ampolirosemail']);
            $bug_report -> Attach(TMP_PATH.'pids/'.$this -> mPid, 'text/plain', 'attachment');
            $bug_report -> Send();

            return true;
        }
        return false;
    }
}

?>