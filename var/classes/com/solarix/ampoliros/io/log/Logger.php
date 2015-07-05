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
// $Id: Logger.php,v 1.7 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.io.log');

OpenLibrary('misc.library');

// Logger levels
//
define ('LOGGER_NOTICE', '1'); // A notice log event.
define ('LOGGER_WARNING', '2'); // A warning log event.
define ('LOGGER_ERROR', '3'); // An error log event.
define ('LOGGER_FAILURE', '4'); // A failure log event.
define ('LOGGER_GENERIC', '5'); // A generic log event. Default if not defined.
define ('LOGGER_DEBUG', '6'); // A debug log event.

/*!
 @class Logger
 @abstract Logging facilities
 */
class Logger extends Object {
	/*! @var mLogFile string - Log file complete path. */
	private $mLogFile;

	/*!
	 @function Logger
	 @abstract Class constructor
	 @param logFile string - Full path of the log file
	 */
	function Logger($logFile) {
		if (!empty($logFile))
			$this -> mLogFile = $logFile;
		else
			ampdie('ampoliros.logger_library.logger_class.logger : Missing logfile');
	}

	/*!
	 @function LogEvent
	 @abstract Logs an event in the log file
	 @param contest string - Contest where the event has been generated, e.g. the name of the function.
	 @param eventstring string - Description of the event
	 @param type integer - Event type. Defaults to LOGGER_GENERIC
	 */
	public function logEvent($contest, $eventstring, $type = LOGGER_GENERIC) {
		$result = false;
		if (strlen($this -> mLogFile) > 0) {
			global $gEnv;
			$timestamp = time();
			$date = getdate($timestamp);
			$log_event = false;

			switch ($type) {
				case LOGGER_NOTICE :
					$evtype = 'NOTICE';
					$log_event = true;
					break;

				case LOGGER_WARNING :
					$evtype = 'WARNING';
                    import('com.solarix.ampoliros.core.Ampoliros');
                    $amp = Ampoliros::instance('Ampoliros');

					switch ($amp->getState()) {
						case Ampoliros::STATE_DEBUG :
						case Ampoliros::STATE_DEVELOPMENT :
						case Ampoliros::STATE_SETUP :
							$log_event = true;
					}
					break;

				case LOGGER_ERROR :
					$evtype = 'ERROR';
					$log_event = true;
					break;

				case LOGGER_FAILURE :
					$evtype = 'FAILURE';
					$log_event = true;
					break;

				case LOGGER_GENERIC :
					$evtype = 'GENERIC';
					$log_event = true;
					break;

				case LOGGER_DEBUG :
					$evtype = 'DEBUG';
                    import('com.solarix.ampoliros.core.Ampoliros');
                    $amp = Ampoliros::instance('Ampoliros');
					if ($amp->getState() == Ampoliros::STATE_DEBUG)
						$log_event = true;
					break;

				default :
					$evtype = 'UNDEFINED';
					$log_event = true;
					break;
			}

			if ($log_event) {
				global $gEnv;

				$logstr = sprintf("%04s/%02s/%02s - %02s:%02s:%02s - %s - %s : %s", $date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes'], $date['seconds'], $evtype, $contest, $eventstring);
				//$logstr = "$date[mday]/$date[mon]/$date[year] - $date[hours]:$date[minutes]:$date[seconds] - ".$evtype." - ".$contest." : ".$eventstring;
				@ error_log($logstr."\n", 3, $this -> mLogFile);

                import('com.solarix.ampoliros.core.Ampoliros');
                $amp = Ampoliros::instance('Ampoliros');
				if ($amp->getState() == Ampoliros::STATE_DEBUG)
					$gEnv['runtime']['debug']['logs'][$this -> mLogFile][] = $logstr;
			}
			$result = $logstr;

			if ($evtype == LOGGER_FAILURE)
				AmpDie($logstring);
		}
		return $result;
	}

	/*!
	 @function LogDie
	 @abstract Logs an event and dies
	 @param contest string - Contest where the event has been generated, e.g. the name of the function.
	 @param eventstring string - Description of the event
	 @param type integer - Event type. Defaults to LOGGER_FAILURE
	 */
	public function logDie($contest, $eventstring, $type = LOGGER_FAILURE) {
		$logstring = $this -> LogEvent($contest, $eventstring, $type);
		ampdie($logstring);
		die('');
	}

	/*!
	 @function CleanLog
	 @abstract Erases the logfile.
	 */
	public function cleanLog() {
		$result = false;
		if (file_exists($this -> mLogFile)) {
			$result = @ unlink($this -> mLogFile);
		}
		return $result;
	}

	/*!
	 @function RawReadLog
	 @abstract Reads the log file and returns it
	 */
	public function rawReadLog() {
		$result = false;
		if (file_exists($this -> mLogFile)) {
			if (file_exists($this -> mLogFile)) {
				$result = file_get_contents($this -> mLogFile);
			}
		}
		return $result;
	}

	/*!
	 @function RawDisplayLog
	 @abstract Reads the log file and displays it to the stdout
	 @discussion This function is deprecated
	 */
	public function rawDisplayLog() {
		if (file_exists($this -> mLogFile)) {
			return @ readfile($this -> mLogFile);
		} else
			return FALSE;
	}

	/*!
	 @function RawDisplayFilterLog
	 @abstract Reads the log file and displays the rows containing a certain word to the stdout
	 @discussion This function is deprecated
	 @param filter string - Word to be contained in the log rows
	 */
	public function rawDisplayFilterLog($filter) {
		$result = false;
		if (file_exists($this -> mLogFile)) {
			if ($fh = @ fopen($this -> mLogFile, 'r')) {
				$row = fgets($fh, 1000);
				while ($row != false) {
					if (strstr($row, $filter) != false)
						echo $row;
					$row = fgets($fh, 1000);
				}
				fclose($fh);
				$result = true;
			}
		}
		return $result;
	}

	public function rotate($logsNumber) {
    	$result = false;
		if (strlen($this -> mLogFile) and is_file($this -> mLogFile)) {
			$dir = dirname($this -> mLogFile);
			$log_name = basename($this -> mLogFile);
			$old_logs = array();

			if ($handle = opendir($dir)) {
				// Search for rotated logs

				while (($file = readdir($handle)) !== false) {
					if (substr($file, 0, strlen($log_name) + 1) == $log_name.'.') {
						$old_logs[substr($file, strlen($log_name) + 1)] = $file;
					}
				}

				if (count($old_logs)) {
					// Remove old logs

					if (count($old_logs) > $logsNumber -1) {
						foreach ($old_logs as $id => $log) {
							if ($id > $logsNumber -1) {
								unlink($dir.'/'.$old_logs[$id]);
								unset($old_logs[$id]);
							}
						}
					}
				}

				krsort($old_logs);

				// Move logs to be rotated

				if ($logsNumber) {
					foreach ($old_logs as $id => $log) {
						copy($dir.'/'.$log, $dir.'/'.$log_name.'.'. ($id +1));
					}
				}

				// Rotate current log

				if ($logsNumber)
					copy($dir.'/'.$log_name, $dir.'/'.$log_name.'.1');
				unlink($dir.'/'.$log_name);
				touch($dir.'/'.$log_name);

				closedir($handle);
				$result = true;
			}
		}
		return $result;
	}
}

?>