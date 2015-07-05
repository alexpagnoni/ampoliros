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
// $Id: LocaleCountry.php,v 1.8 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.locale');

define ('COUNTRY_SIGNPOSITION_PARENS_AROUND', 0);
define ('COUNTRY_SIGNPOSITION_BEFORE_QUANTITY_MONEY', 1);
define ('COUNTRY_SIGNPOSITION_AFTER_QUANTITY_MONEY', 2);
define ('COUNTRY_SIGNPOSITION_BEFORE_MONEY', 3);
define ('COUNTRY_SIGNPOSITION_AFTER_MONEY', 4);

/*!
 @abstract Country locale settings.
 @discussion Defines:
 COUNTRY_SIGNPOSITION_PARENS_AROUND
 COUNTRY_SIGNPOSITION_BEFORE_QUANTITY_MONEY
 COUNTRY_SIGNPOSITION_AFTER_QUANTITY_MONEY
 COUNTRY_SIGNPOSITION_BEFORE_MONEY
 COUNTRY_SIGNPOSITION_AFTER_MONEY
 */
class LocaleCountry extends Object {
	/*! @var mCountry string - Country name. */
	private $mCountry;
	/*! @var mCountryShort string - Country short name. */
	private $mCountryShort;
	/*! @var mLanguage string - Default country language. */
	private $mLanguage;
	/*! @var mDecimalSeparator string - Numbers decimal separator. */
	private  $mDecimalSeparator;
	/*! @var mThousandsSeparator string - Numbers thousands separator. */
	private $mThousandsSeparator;
	/*! @var mPositiveSign string - Positive numbers sign. */
	private $mPositiveSign;
	/*! @var mNegativeSign string - Negative numbers sign. */
	private $mNegativeSign;
	/*! @var mCurrencySymbol string - Currency symbol. */
	private $mCurrencySymbol;
	/*! @var mMoneyDecimalSeparator string - Money decimal separator. */
	private $mMoneyDecimalSeparator;
	/*! @var mMoneyThousandsSeparator string - Money thousands separator symbol. */
	private $mMoneyThousandsSeparator;
	/*! @var mFractDigits int - Value decimals. */
	private $mFractDigits;
	/*! @var mPositivePrefixCurrency bool. */
	private $mPositivePrefixCurrency;
	/*! @var mPositiveSignPosition int - How to represent positive sign, refer to COUNTRY_SIGNPOSITION_* defines. */
	private $mPositiveSignPosition;
	/*! @var mNegativePrefixCurrencty bool. */
	private $mNegativePrefixCurrency;
	/*! @var mPositiveSignPosition int - How to represent negative sign, refer to COUNTRY_SIGNPOSITION_* defines. */
	private $mNegativeSignPosition;
	/*! @var mTimeFormat string - Time formatting string. */
	private $mTimeFormat;
	/*! @var mDateFormat string - Date formatting string. */
	private $mDateFormat;
	/*! @var mShortDateFormat string - Short date formatting string. */
	private $mShortDateFormat;
	/*! @var mStartWeekOnMonday bool - True if week starts on Monday. */
	private $mStartWeekOnMonday;
	private $mDateSeparator;
	private $mDateOrder;
	private $mCharSet;

	/*!
	 @function LocaleCountry
	
	 @abstract Class constructor.
	
	 @param countryName string - Country name in English language.
	 */
	public function LocaleCountry($countryName) {
		if (strlen($countryName)) {
			$this -> mCountry = $countryName;
			$this -> open();
		} else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
			$log -> LogEvent('ampoliros.locale_library.country_class.country', 'Empty country name', LOGGER_ERROR);
        }
	}

	/*!
	 @abstract Opens the country file and reads the definitions.
	 @result True if the country file exists and has been read
	 */
	public function open() {
		$result = false;

        import('carthag.core.Registry');
        $reg = Registry :: instance();
        OpenLibrary('configman.library');
		$country_file = new ConfigFile($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> mCountry.'.country');
		if ($country_file -> Opened()) {
			$this -> mCountryShort = $country_file -> Value('COUNTRYSHORT');
			$this -> mLanguage = $country_file -> Value('LANGUAGE');
			$this -> mDecimalSeparator = $country_file -> Value('DECIMALSEPARATOR');
			$this -> mThousandsSeparator = $country_file -> Value('THOUSANDSSEPARATOR');
			$this -> mPositiveSign = $country_file -> Value('POSITIVESIGN');
			$this -> mNegativeSign = $country_file -> Value('NEGATIVESIGN');
			$this -> mCurrencySymbol = $country_file -> Value('CURRENCYSYMBOL');
			$this -> mMoneyDecimalSeparator = $country_file -> Value('MONEYDECIMALSEPARATOR');
			$this -> mMoneyThousandsSeparator = $country_file -> Value('MONEYTHOUSANDSSEPARATOR');
			$this -> mFractDigits = $country_file -> Value('FRACTDIGITS');
			$this -> mPositivePrefixCurrency = $country_file -> Value('POSITIVEPREFIXCURRENCY');
			$this -> mPositiveSignPosition = $country_file -> Value('POSITIVESIGNPOSITION');
			$this -> mNegativePrefixCurrency = $country_file -> Value('NEGATIVEPREFIXCURRENCY');
			$this -> mNegativeSignPosition = $country_file -> Value('NEGATIVESIGNPOSITION');
			$this -> mTimeFormat = $country_file -> Value('TIMEFORMAT');
			$this -> mDateFormat = $country_file -> Value('DATEFORMAT');
			$this -> mShortDateFormat = $country_file -> Value('SHORTDATEFORMAT');
			$this -> mStartWeekOnMonday = $country_file -> Value('STARTWEEKONMONDAY');
			$this -> mDateSeparator = $country_file -> Value('DATESEPARATOR');
			$this -> mDateOrder = $country_file -> Value('DATEORDER');
			$this -> mCharSet = $country_file -> Value('CHARSET');

			$result = true;
		} else {
                        import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
			$log -> LogEvent('ampoliros.locale_library.country_class.open', 'Unable to open country file '.$reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/locale/'.$this -> mCountry.'.country', LOGGER_ERROR);
        }

		return $result;
	}

	/*!
	 @abstract Formats a money amount.
	 @result Formatted money amount.
	 */
	public function formatMoney($amount) {
		// Formats with decimal and thousands separators and currency fract digits
		//
		$formatted_amount = number_format(abs($amount), $this -> mFractDigits, $this -> mMoneyDecimalSeparator, $this -> mMoneyThousandsSeparator);
		$formatted_currsymbol = $this -> mCurrencySymbol;

		if ($amount >= 0) {
			$sign_position = $this -> mPositiveSignPosition;
			$prefix_currency = $this -> mPositivePrefixCurrency;
			$sign = $this -> mPositiveSign;
		} else {
			$sign_position = $this -> mNegativeSignPosition;
			$prefix_currency = $this -> mNegativePrefixCurrency;
			$sign = $this -> mNegativeSign;
		}

		// Formats with the given sign position
		//
		switch ($sign_position) {
			case COUNTRY_SIGNPOSITION_PARENS_AROUND :
				$formatted_amount = '('.$formatted_amount.')';
				break;

			case COUNTRY_SIGNPOSITION_BEFORE_QUANTITY_MONEY :
				$formatted_amount = $sign.$formatted_amount;
				break;

			case COUNTRY_SIGNPOSITION_AFTER_QUANTITY_MONEY :
				$formatted_amount = $formatted_amount.$sign;
				break;

			case COUNTRY_SIGNPOSITION_BEFORE_MONEY :
				$formatted_currsymbol = $sign.$formatted_currsymbol;
				break;

			case COUNTRY_SIGNPOSITION_AFTER_MONEY :
				$formatted_currsymbol = $formatted_currsymbol.$sign;
				break;
		}

		// Formats amount and currency symbol in the right order
		//
		if ($prefix_currency)
			$result = $formatted_currsymbol.' '.$formatted_amount;
		else
			$result = $formatted_amount.' '.$formatted_currsymbol;

		return $result;
	}

	/*!
	 @function FormatNumber
	 @abstract Formats a number.
	 @result Formatted number.
	 */
	public function formatNumber($number, $decimals = 0) {
			// Formats with decimal and thousands separators
		//
	$result = number_format($number, $decimals, $this -> mDecimalSeparator, $this -> mThousandsSeparator);

		// Formats with sign symbol
		//
		return $number >= 0 ? $this -> mPositiveSign.$result : $this -> NegativeSign.$result;
	}

	/*** DATE AND TIME ***/

	/* Getters */

	/*!
	@function TimeFormat
	*/
	public function timeFormat() {
		return $this -> mTimeFormat;
	}

	/*!
	@function DateFormat
	*/
	public function dateFormat() {
		return $this -> mDateFormat;
	}

	/*!
	@function ShortDateFormat
	*/
	public function shortDateFormat() {
		return $this -> mShortDateFormat;
	}

	/*!
	@function StartWeekOnMonday
	*/
	public function startWeekOnMonday() {
		return $this -> mStartWeekOnMonday;
	}

	/*!
	@function DateSeparator
	*/
	public function dateSeparator() {
		return $this -> mDateSeparator;
	}

	/*!
	@function DateOrder
	*/
	public function dateOrder() {
		return $this -> mDateOrder;
	}

	/*!
	 @function FormatDate
	 @abstract Formats a date string.
	 @param date int - UNIX timestamp.
	 @result Formatted date.
	 */
	public function formatDate($date) {
		return date($this -> mDateFormat, $date);
	}

	/*!
	 @function FormatArrayDate
	 @abstract Formats a date array.
	 @param dateArray array - Array of date parameters.
	 @result Formatted date.
	 */
	public function formatArrayDate($dateArray) {
		return $this -> formatDate(mktime($dateArray['hours'], $dateArray['minutes'], $dateArray['seconds'], $dateArray['mon'], $dateArray['mday'], $this -> NormalizeYear($dateArray['year'])));
	}

	/*!
	 @function FormatShortDate
	 @abstract Formats a date string in short format.
	 @param date int - UNIX timestamp.
	 @result Formatted date.
	 */
	public function formatShortDate($date) {
		return date($this -> mShortDateFormat, $date);
	}

	/*!
	 @function FormatShortArrayDate
	 @abstract Formats a date array in short format.
	 @param dateArray array - Array of date parameters.
	 @result Formatted date.
	 */
	public function formatShortArrayDate($dateArray, $format = '') {
		if (!strlen($format))
			$date = $this -> mShortDateFormat;
		else
			$date = $format;

		if (!isset($dateArray['hours']))
			$dateArray['hours'] = '';
		if (!isset($dateArray['minutes']))
			$dateArray['minutes'] = '';
		if (!isset($dateArray['seconds']))
			$dateArray['seconds'] = '';
		if (!isset($dateArray['year']))
			$dateArray['year'] = '';
		if (!isset($dateArray['mon']))
			$dateArray['mon'] = '';
		if (!isset($dateArray['mday']))
			$dateArray['mday'] = '';

		$dateArray['year'] = $this -> NormalizeYear($dateArray['year']);

		$date = str_replace('a', $dateArray['hours'] > 12 ? 'pm' : 'am', $date);
		$date = str_replace('A', $dateArray['hours'] > 12 ? 'PM' : 'AM', $date);
		$date = str_replace('d', str_pad($dateArray['mday'], 2, '0', STR_PAD_LEFT), $date);
		$date = str_replace('g', $dateArray['hours'] > 12 ? $dateArray['hours'] - 12 : $dateArray['hours'], $date);
		$date = str_replace('G', $dateArray['hours'], $date);
		$date = str_replace('h', str_pad($dateArray['hours'] > 12 ? $dateArray['hours'] - 12 : $dateArray['hours'], 2, '0', STR_PAD_LEFT), $date);
		$date = str_replace('H', str_pad($dateArray['hours'], 2, '0', STR_PAD_LEFT), $date);
		$date = str_replace('i', str_pad($dateArray['minutes'], 2, '0', STR_PAD_LEFT), $date);
		$date = str_replace('j', $dateArray['mday'], $date);
		$date = str_replace('m', str_pad($dateArray['mon'], 2, '0', STR_PAD_LEFT), $date);
		$date = str_replace('n', $dateArray['mon'], $date);
		$date = str_replace('s', str_pad($dateArray['seconds'], 2, '0', STR_PAD_LEFT), $date);
		$date = str_replace('Y', $dateArray['year'], $date);
		$date = str_replace('y', substr($dateArray['year'], -2), $date);

		return $date;
	}

	/*!
	 @function SafeFormatTimestamp
	 @abstract Safely formats an Unix timestamp to a localized datestamp.
	 @param unixTimestamp integer - Unix timestamp. Defaults to current timestamp.
	 @result A safe datestamp.
	 */
	public function safeFormatTimestamp($unixTimestamp = '') {
		return date(AMPOLIROS_LOCALE_SAFE_TIMESTAMP, strlen($unixTimestamp) ? $unixTimestamp : time());
	}

	/*!
	 @function FormatTime
	 @abstract Formats a time string.
	 @param time int - UNIX timestamp.
	 @result Formatted time.
	 */
	public function formatTime($time) {
		return date($this -> mTimeFormat, $time);
	}

	/*!
	 @function FormatArrayTime
	 @abstract Formats a time array in short format.
	 @param dateArray array - Array of date parameters.
	 @result Formatted time.
	 */
	public function formatArrayTime($dateArray) {
		return $this -> FormatShortArrayDate($dateArray, $this -> mTimeFormat);

		/*$timestamp = mktime(
		    $dateArray['hours'],
		    $dateArray['minutes'],
		    $dateArray['seconds'],
		    $dateArray['mon'],
		    $dateArray['mday'],
		    $dateArray['year'] );
		return $this->FormatTime( $timestamp );*/
	}

	/*!
	 @function GetDateArrayFromShortDateStamp
	 @abstract Gets a date array from a localized datestamp in short format, like "12/31/2002".
	 @param datestamp string - Date stamp.
	 @result Array of date parameters.
	 */
	public function getDateArrayFromShortDateStamp($datestamp) {
		$order = $this -> DateOrder();
		$date_elements = explode($this -> DateSeparator(), $datestamp);
		$fmt = "%". (strpos($order, 'y') + 1)."\$s-%". (strpos($order, 'm') + 1)."\$s-%". (strpos($order, 'd') + 1)."\$s";

		list ($date['year'], $date['mon'], $date['mday']) = explode('-', sprintf($fmt, $date_elements[0], $date_elements[1], $date_elements[2]));
		$date['year'] = $this -> NormalizeYear($date['year']);

		return $date;
	}

	/*!
	 @function GetDateArrayFromSafeTimestamp
	 @abstract Gets a date array from a safe time stamp, like the one returned by SafeFormatTimestamp().
	 @param timestamp string - Safe timestamp.
	 @result A safe date array.
	 */
	public function getDateArrayFromSafeTimestamp($timestamp) {
		$timestamp = str_replace(',', '', $timestamp);
		$date_elements = explode(' ', $timestamp);
		list ($date['year'], $date['mon'], $date['mday']) = explode('-', $date_elements[0]);
		list ($date['hours'], $date['minutes'], $date['seconds']) = explode(':', $date_elements[1]);

		if (isset($date_elements[2]) and $date_elements[2] == 'PM')
			$date['hours'] += 12;

		return $date;
	}

	public function getDateArrayFromUnixTimestamp($timestamp) {
		$result['year'] = date('Y', $timestamp);
		$result['mon'] = date('m', $timestamp);
		$result['mday'] = date('d', $timestamp);
		$result['hours'] = date('H', $timestamp);
		$result['minutes'] = date('i', $timestamp);
		$result['seconds'] = date('s', $timestamp);
		return $result;
	}

	public function normalizeYear($year) {
		$result = $year;

		switch (strlen($year)) {
			case '0' :
				$result = '2000';
				break;
			case '1' :
				$result = '200'.$year;
				break;
			case '2' :
				$result = '20'.$year;
				break;
			case '3' :
				$result = '2'.$year;
				break;
		}

		return $result;
	}

	/*!
	 @function Language
	 @abstract Returns default country language.
	 */
	public function language() {
		return $this -> mLanguage;
	}

	/*!
	 @function Country
	 @abstract Returns country name.
	 @result Country name.
	 */
	public function country() {
		return $this -> mCountry;
	}

	/*!
	 @function CountryShort
	 @abstract Returns country short name.
	 @result Country short name.
	 */
	public function countryShort() {
		return $this -> mCountryShort;
	}

	/*!
	 @function DecimalSeparator
	 @abstract Returns decimal separator.
	 @result Decimal separator.
	 */
	public function decimalSeparator() {
		return $this -> mDecimalSeparator;
	}

	/*!
	 @function ThousandsSeparator
	 @abstract Returns thousands separator.
	 @result Thousands separator.
	 */
	public function thousandsSeparator() {
		return $this -> mThousandsSeparator;
	}

	/*!
	 @function PositiveSign
	 @abstract Returns positive sign.
	 @result Positive sign.
	 */
	public function positiveSign() {
		return $this -> mPositiveSign;
	}

	/*!
	 @function NegativeSign
	 @abstract Returns negative sign.
	 @result Negative sign.
	 */
	public function negativeSign() {
		return $this -> mNegativeSign;
	}

	/*!
	 @function CurrencySymbol
	 @abstract Returns currency symbol.
	 @result Currency symbol.
	 */
	public function currencySymbol() {
		return $this -> mCurrencySymbol;
	}

	/*!
	 @function MoneyDecimalSeparator
	 @abstract Returns money decimal separator.
	 @result Money decimal separator.
	 */
	public function moneyDecimalSeparator() {
		return $this -> mMoneyDecimalSeparator;
	}

	/*!
	 @function MoneyThousandsSeparator
	 @abstract Returns money thousands separator.
	 @result Money thousands separator.
	 */
	public function moneyThousandsSeparator() {
		return $this -> mMoneyThousandsSeparator;
	}

	/*!
	 @function FractDigits
	 @abstract Returns money fract digits.
	 @result Money fract digits.
	 */
	public function fractDigits() {
		return $this -> mFractDigits;
	}

	public function positivePrefixCurrency() {
		return $this -> mPositivePrefixCurrency;
	}

	public function positiveSignPosition() {
		return $this -> mPositiveSignPosition;
	}

	public function negativePrefixCurrency() {
		return $this -> mNegativePrefixCurrency;
	}

	public function negativeSignPosition() {
		return $this -> mNegativeSignPosition;
	}

	public function getCharSet() {
		return $this -> mCharSet;
	}
}

?>