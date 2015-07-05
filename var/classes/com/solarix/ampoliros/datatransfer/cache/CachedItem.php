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
// $Id: CachedItem.php,v 1.9 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.datatransfer.cache');

import('com.solarix.ampoliros.process.Semaphore');
import('com.solarix.ampoliros.db.DBLayer');

define ('AMPOLIROS_CACHE_ITEM_FOUND', -1);
define ('AMPOLIROS_CACHE_ITEM_NOT_FOUND', -2);
define ('AMPOLIROS_CACHE_ITEM_STORED', -5);
define ('AMPOLIROS_CACHE_ITEM_NOT_STORED', -6);
define ('AMPOLIROS_CACHE_ITEM_NOT_EQUAL', -3);
define ('AMPOLIROS_CACHE_ITEM_EQUAL', -4);

/*!
 @class CachedItem

 @abstract Handles Ampoliros items cache.

 @discussion Handles Ampoliros items cache.
 */
class CachedItem extends Object {
	/*! @var mrAmpDb DbLayer class - Ampoliros database handler. */
	protected $mrAmpDb;
	/*! @var mModule string - Module id name. */
	public $mModule;
	/*! @var mItemId string - Item id. */
	public $mItemId;
	/*! @var mItemFile string - Cached item file name. */
	public $mItemFile;
	/*! @var mValidator string - Optional item validator. */
	public $mValidator;
	/*! @var mResult integer - Last action result; may be one of the AMPOLIROS_CACHE_ITEM_x defines. */
	public $mResult = 0;
	public $mSiteId = 0;
	public $mUserId = 0;
	private $cachePath;

	/*!
	 @function CachedItem
	 @abstract Class constructor.
	 @discussion Class constructor.
	 @param rampDb DbLayer class - Ampoliros database handler.
	 @param module string - Module id name.
	 @param itemId string - Item id.
	 */
	public function CachedItem(DBLayer $rampDb, $module, $itemId, $siteId = 0, $userId = 0) {
		$this -> cachePath = TMP_PATH.'ampcache/';
		$siteId = (int) $siteId;
		$userId = (int) $userId;
		$this -> mrAmpDb = $rampDb;

		if (strlen($itemId) and strlen($module)) {
			$this -> mItemId = $itemId;
			$this -> mModule = $module;
			$this -> mSiteId = $siteId;
			$this -> mUserId = $userId;

			$item_query = $this -> mrAmpDb -> Execute('SELECT itemfile,validator,siteid,userid FROM cacheditems WHERE module='.$this -> mrAmpDb -> Format_Text($this -> mModule).' AND itemid='.$this -> mrAmpDb -> Format_Text($this -> mItemId). ($siteId ? ' AND siteid='.$siteId : ''). ($userId ? ' AND userid='.$userId : ''));

			if ($item_query -> NumRows()) {
				$this -> mValidator = $item_query -> Fields('validator');
				$this -> mItemFile = $this -> cachePath.$item_query -> Fields('itemfile');
				$this -> mSiteId = $item_query -> Fields('siteid');
				$this -> mUserId = $item_query -> Fields('userid');
				$this -> mResult = AMPOLIROS_CACHE_ITEM_FOUND;
			} else
				$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_FOUND;
		}
	}

	/*!
	 @function Store
	 @abstract Stores the item in the cache.
	 @discussion Stores the item in the cache.
	 @param $content string - Item content.
	 @param $validator string - Optional validator.
	 @result TRUE if the item has been stored.
	 */
	public function store($content, $validator = '') {
		$result = false;
		$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_STORED;
		$goon = false;
		$sem = new Semaphore('cache', $this -> mItemFile);
		$sem -> WaitGreen();
		$sem -> SetRed();

		if (strlen($this -> mItemFile) and file_exists($this -> mItemFile)) {
			if ($fh = @ fopen($this -> mItemFile, 'w')) {
				if (@ fwrite($fh, $content)) {
					$name = $this -> mItemFile;
					$goon = true;
				}

				fclose($fh);
			}
		} else {
			$name = $this -> cachePath.date('Ymd').'_cacheditem_'.rand();

			if ($fh = @ fopen($name, 'w')) {
				if (@ fwrite($fh, $content)) {
					$goon = true;
				}
				@ fclose($fh);
			}
		}

		if ($goon) {
			$item_query = $this -> mrAmpDb -> Execute('SELECT itemid FROM cacheditems WHERE itemid='.$this -> mrAmpDb -> Format_Text($this -> mItemId).' AND module='.$this -> mrAmpDb -> Format_Text($this -> mModule). ($this -> mSiteId ? ' AND siteid='.$this -> mSiteId : ''). ($this -> mUserId ? ' AND userid='.$this -> mUserId : ''));
			if ($item_query -> NumRows()) {
				if ($this -> mrAmpDb -> Execute('UPDATE cacheditems SET validator='.$this -> mrAmpDb -> Format_Text($validator).',itemfile='.$this -> mrAmpDb -> Format_Text(basename($name)).',siteid='.$this -> mSiteId.',userid='.$this -> mUserId.' WHERE itemid='.$this -> mrAmpDb -> Format_Text($this -> mItemId).' AND module='.$this -> mrAmpDb -> Format_Text($this -> mModule))) {
					$result = true;
				}
			} else {
				if ($this -> mrAmpDb -> Execute('INSERT INTO cacheditems VALUES ('.$this -> mrAmpDb -> Format_Text($this -> mModule).','.$this -> mrAmpDb -> Format_Text($this -> mItemId).','.$this -> mrAmpDb -> Format_Text(basename($name)).','.$this -> mrAmpDb -> Format_Text($validator).','.$this -> mSiteId.','.$this -> mUserId.')')) {
					$result = true;
				}
			}

			if ($result) {
				$this -> mItemFile = $name;
				$this -> mValidator = $validator;
				$this -> mResult = AMPOLIROS_CACHE_ITEM_STORED;
			}
			$sem -> SetGreen();
		}
		return $result;
	}

	/*!
	 @function Retrieve
	 @abstract Retrieves the item from the cache.
	 @discussion Retrieves the item from the cache.
	 @param md5 string - Optional md5 hash to be checked with the cached item one.
	 @result The item content.
	 */
	public function retrieve($md5 = '') {
		$result = false;
		$sem = new Semaphore('cache', $this -> mItemFile);
		$sem -> WaitGreen();

		if (strlen($this -> mItemFile) and file_exists($this -> mItemFile)) {
			$sem -> SetRed();
			$goon = true;
			if (strlen($md5)) {
				if ($this -> GetItemMd5() == $md5)
					$goon = true;
				else
					$goon = false;
			}

			if ($goon) {
				if (file_exists($this -> mItemFile)) {
					$result = file_get_contents($this -> mItemFile);
				}
			} else
				$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_EQUAL;
			$sem -> SetGreen();
		} else
			$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_FOUND;

		return $result;
	}

	/*!
	 @function CheckValidator
	 @abstract Checks if the optional validator is equal to a given one.
	 @discussion Checks if the optional validator is equal to a given one.
	 @param validator string - Validator to be checked.
	 @result TRUE if the validators are equals.
	 */
	public function checkValidator($validator) {
		$result = false;
		if (strlen($this -> mItemFile) and file_exists($this -> mItemFile)) {
			if ($validator == $this -> mValidator)
				$result = true;
		}
		return $result;
	}

	/*!
	 @function Destroy
	 @abstract Destroys the item from the cache.
	 @discussion Destroys the item from the cache.
	 @result TRUE if the item has been destroyed.
	 */
	public function destroy() {
		$result = false;
		$sem = new Semaphore('cache', $this -> mItemFile);
		$sem -> WaitGreen();
		$sem -> SetRed();
		if (strlen($this -> mItemFile) and file_exists($this -> mItemFile)) {
			$result = @ unlink($this -> mItemFile);
		} else
			$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_FOUND;
		if ($result)
			$result = $this -> mrAmpDb -> Execute('DELETE FROM cacheditems WHERE module='.$this -> mrAmpDb -> Format_Text($this -> mModule).' AND itemid='.$this -> mrAmpDb -> Format_Text($this -> mItemId));
		$sem -> SetGreen();
		return $result;
	}

	/*!
	 @function CompareMd5
	 @abstract Checks if the md5 of the cached item is equal to the md5 of a given item.
	 @discussion Checks if the md5 of the cached item is equal to the md5 of a given item.
	 @param itemContent string - Content of the item to be checked.
	 @result TRUE if the md5 of the items are equals.
	 */
	public function compareMd5($itemContent) {
		$result = false;
		if (strlen($this -> mItemFile) and file_exists($this -> mItemFile)) {
			if (md5($itemContent) == $this -> GetItemMd5()) {
				$this -> mResult = AMPOLIROS_CACHE_ITEM_EQUAL;
				$result = true;
			} else
				$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_EQUAL;
		} else
			$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_FOUND;
		return $result;
	}

	/*!
	 @function GetItemMd5
	 @abstract Gets the md5 hash of the file, in order to compare it with the original item.
	 @discussion Gets the md5 hash of the file, in order to compare it with the original item.
	 @result The md5 hash.
	 */
	public function getItemMd5() {
		$result = false;
		if (strlen($this -> mItemFile) and file_exists($this -> mItemFile)) {
			if (function_exists('md5_file'))
				$result = md5_file($this -> mItemFile);
			else {
				$result = md5(file_get_contents($this -> mItemFile));
			}
			$this -> mResult = AMPOLIROS_CACHE_ITEM_FOUND;
		} else
			$this -> mResult = AMPOLIROS_CACHE_ITEM_NOT_FOUND;
		return $result;
	}

	public function getCachePath() {
		return $this -> cachePath;
	}
}

?>