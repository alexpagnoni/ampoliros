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
// $Id: Semaphore.php,v 1.10 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.process');

// Deprecated defines
define ('AMPOLIROS_SEMAPHORE_STATUS_GREEN', 'green');
define ('AMPOLIROS_SEMAPHORE_STATUS_RED', 'red');

/**
 * Questa classe fornisce un meccanismo di controllo delle risorse
 * basato sul concetto di semaforo.
 * @author Alex Pagnoni <alex.pagnoni@solarix.it>
 * @since 3.5 
 */
class Semaphore extends Object {
	/**
	 * Tipo di risorsa da controllare.
	 * @var string
	 * @access private
	 */
	private $mResourceType;
	/**
	 * Identificativo della risorsa da controllare.
	 * @var string
	 * @access private
	 */
	private $mResource;
	const STATUS_GREEN = 'green';
	const STATUS_RED = 'red';

	/**
	 * Costruisce la classe.
	 * @param string $resourceType tipo di risorsa da controllare.
	 * @param string $resource identificativo della risorsa da controllare.
	 */
	public function Semaphore($resourceType, $resource) {
		$this -> mResourceType = $resourceType;
		$this -> mResource = $resource;
	}

	/**
	 * Imposta il tipo di risorsa da controllare.
	 * @param string $resourceType
	 * @access public
	 * @return void
	 */
	public function setResourceType($resourceType) {
		$this -> mResourceType = $resourceType;
	}

	/**
	 * Restituisce il tipo di risorsa controllata.
	 * @access public
	 * @return string
	 */
	public function getResourceType() {
		return $this -> mResourceType;
	}

	/**
	 * Imposta l'identificativo della risorsa da controllare.
	 * @param string $resource
	 * @access public
	 * @return void
	 */
	public function setResource($resource) {
		$this -> mResource = $resource;
	}

	/**
	 * Restituisce l'identificativo della risorsa controllata.
	 * @access public
	 * @return string
	 */
	public function getResource() {
		return $this -> mResource;
	}

	/**
	 * Restituisce il path completo del file di lock della risorsa.
	 * @access private
	 * @return string
	 */
	public function getFileName() {
		if ($this -> mResourceType and $this -> mResource) {
			return TMP_PATH.'semaphores/'.md5($this -> mResourceType.'_'.$this -> mResource).'.semaphore';
		}
		return '';
	}

	/**
	 * Controlla in che stato è la risorsa.
	 * @return string
	 * @access public
	 */
	public function checkStatus() {
		if ($this -> mResource) {
			clearstatcache();
			if (file_exists($this -> GetFileName()))
				return Semaphore :: STATUS_RED;
		}
		return Semaphore :: STATUS_GREEN;
	}

	/**
	 * Imposta lo stato della risorsa.
	 * @param string $status
	 * @access private
	 * @return bool
	 */
	public function setStatus($status) {
		if ($this -> mResource) {
			switch ($status) {
				case Semaphore :: STATUS_GREEN :
					clearstatcache();
					if (file_exists($this -> GetFileName()))
						unlink($this -> GetFileName());
					return true;
					break;

				case Semaphore :: STATUS_RED :
					clearstatcache();
					if (!file_exists($this -> GetFileName())) {
						if ($fh = fopen($this -> GetFileName(), 'w')) {
							import('com.solarix.ampoliros.core.Ampoliros');
							$amp = Ampoliros :: instance('Ampoliros');

							fputs($fh, serialize(array('pid' => $amp -> getPid(), 'time' => time(), 'resource' => $this -> mResource)));
							fclose($fh);
						} else {
							if (!file_exists(TMP_PATH.'semaphores/'))
								mkdir(TMP_PATH.'semaphores/');
							return false;
						}
					}
					return true;
					break;
			}
		}
		return false;
	}

	/**
	 * Imposta lo stato della risorsa come disponibile.
	 * @access public
	 * @return void
	 */
	public function setGreen() {
		$this -> SetStatus(Semaphore :: STATUS_GREEN);
	}

	/**
	 * Imposta lo stato della risorsa come occupata.
	 * @access public
	 * @return void
	 */
	public function setRed() {
		$this -> SetStatus(Semaphore :: STATUS_RED);
	}

	/**
	 * Restituisce il contenuto del semaforo.
	 * Il contenuto è un array con le chiavi pid, time e resource.
	 * @access public
	 * @return array
	 */
	public function getSemaphoreData() {
		clearstatcache();
		if ($this -> mResource and file_exists($this -> GetFileName())) {
			if (file_exists($this -> GetFileName())) {
				return unserialize(file_get_contents($this -> getFileName()));
			}
		}
		return array();
	}

	/**
	 * Attende fino a che la risorsa non si libera.
	 * @param integer $checkDelay intervallo in secondi di attesa tra ogni tentativo.
	 * @param integer $maxDelay tempo in secondi opzionale dopo il quale il metodo restituisce il controllo.
	 * @return bool
	 * @access public
	 */
	public function waitGreen($checkDelay = 1, $maxDelay = 0) {
		$result = false;
		if ($this -> mResource) {
			if ($maxDelay)
				$start = time();
			$result = true;

			while (!($this -> CheckStatus() == Semaphore :: STATUS_GREEN)) {
				/*
				If delay exceeds the optional maximum, the function returns false and
				the user code should not execute the code that should be executed when
				the semaphore is green.
				*/
				if ($maxDelay and time() > $start + $maxDelay)
					return false;

				sleep($checkDelay);
			}
		}
		return $result;
	}
}

?>