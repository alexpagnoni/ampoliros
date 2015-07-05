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
// $Id: Clipboard.php,v 1.8 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.datatransfer');

import('com.solarix.ampoliros.process.Semaphore');

// Deprecated defines
define ('AMPOLIROS_CLIPBOARD_TYPE_TEXT', 'text');
define ('AMPOLIROS_CLIPBOARD_TYPE_RAW', 'raw');
define ('AMPOLIROS_CLIPBOARD_TYPE_FILE', 'file');
define ('AMPOLIROS_CLIPBOARD_TYPE_ARRAY', 'array');
define ('AMPOLIROS_CLIPBOARD_TYPE_OBJECT', 'object');
define ('AMPOLIROS_CLIPBOARD_TYPE_CUSTOM', 'custom');

/**
 * Classe che implementa un meccanismo per trasferire dati
 * tramite operazioni di copia/taglia/incolla.
 * @author Alex Pagnoni <alex.pagnoni@solarix.it>
 * @since 3.5
 */
class Clipboard extends Object {
    private $mType;
    private $mCustomType;
    private $mUnit;
    private $mModule;
    private $mSite;
    private $mUser;
    private $mFileName;
    const TYPE_TEXT = 'text';
    const TYPE_RAW = 'raw';
    const TYPE_FILE = 'file';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_CUSTOM = 'custom';

    /**
     * Costruisce la classe della clipboard.
     * @param string $type tipo di dato da trattare.
     * @param string $customType tipo utente di dato da trattare se $type è impostato ad AMPOLIROS_CLIPBOARD_TYPE_CUSTOM  
     * @param integer $unit unità identificativa della clipboard da utilizzare a partire da 0
     * @param string $module nome del modulo. 
     * @param string $site nome del sito.
     * @param string $user nome dell'utente. 
     */
    public function Clipboard($type, $customType = '', $unit = 0, $module = '', $site = '', $user = '') {
        $this -> mType = $type;
        if ($this -> mType == Clipboard::TYPE_CUSTOM) {
            $this -> mCustomType = $customType;
        }
        $this -> mUnit = $unit;
        $this -> mModule = $module;
        $this -> mSite = $site;
        $this -> mUser = $user;
        $this -> mFileName = TMP_PATH.'clipboard/'.$this -> mType.'_'.$this -> mCustomType.'_'.$this -> mUnit.'_'.$this -> mModule.'_'.$this -> mSite.'_'.$this -> mUser.'.clipboard';
    }

    /**
     * Controlla se la clipboard contiene dati validi.
     * @return bool
     * @access public
     */
    public function isValid() {
        clearstatcache();
        return file_exists($this -> mFileName);
    }

    /**
     * Immagazzina un dato nella clipboard.
     * @param mixed $item dato da salvare.
     * @return bool
     * @access public
     * @see Clipboard::Retrieve()
     */
    public function store(&$item) {
        $result = false;
        $sem = new Semaphore('clipboard', $this -> mFileName);
        $sem -> WaitGreen();
        $sem -> SetRed();

        if ($fh = fopen($this -> mFileName, 'wb')) {
            switch ($this -> mType) {
                case Clipboard::TYPE_TEXT :
                case Clipboard::TYPE_RAW :
                    fwrite($fh, $item);
                    $result = true;
                    break;

                case Clipboard::TYPE_FILE :
                    fwrite($fh, serialize(array('filename' => $item, 'content' => file_get_contents($item))));
                    $result = true;
                    break;

                case Clipboard::TYPE_OBJECT :
                case Clipboard::TYPE_ARRAY :
                case Clipboard::TYPE_CUSTOM :
                    fwrite($fh, serialize($item));
                    $result = true;
                    break;
            }
            fclose($fh);
            $sem -> SetGreen();
        }
        return $result;
    }

    /**
     * Estrae il contenuto della clipboard.
     * @return mixed
     * @access public
     * @see Clipboard::Store()
     */
    public function retrieve() {
        $result = '';
        $sem = new Semaphore('clipboard', $this -> mFileName);
        $sem -> WaitGreen();

        if ($this -> IsValid()) {
            $sem -> SetRed();
            if (file_exists($this -> mFileName)) {
                switch ($this -> mType) {
                    case Clipboard::TYPE_TEXT :
                    case Clipboard::TYPE_RAW :
                        $result = file_get_contents($this -> mFileName);
                        break;

                    case Clipboard::TYPE_FILE :
                    case Clipboard::TYPE_OBJECT :
                    case Clipboard::TYPE_ARRAY :
                    case Clipboard::TYPE_CUSTOM :
                        $result = unserialize(file_get_contents($this -> mFileName));
                        break;
                }
                $sem -> SetGreen();
            }
        }
        return $result;
    }

    /**
     * Svuota il contenuto della clipboard.
     * @return bool
     * @access public
     */
    public function erase() {
        $result = false;
        if ($this -> IsValid()) {
            $sem = new Semaphore('clipboard', $this -> mFileName);
            $sem -> WaitGreen();
            $sem -> SetRed();
            $result = unlink($this -> mFileName);
            $sem -> SetGreen();
        } else
            $result = true;
        return $result;
    }
    
    public function getType() {
        return $this->mType;
    }
    
    public function getCustomType() {
        return $this->mCustomType;
    }
    
    public function getUnit() {
        return $this->mUnit;
    }
    
    public function getModule() {
        return $this->mModule;
    }

    public function getSite() {
        return $this->mSite;
    }
    
    public function getFileName() {
        return $this->mFileName;
    }
}

?>