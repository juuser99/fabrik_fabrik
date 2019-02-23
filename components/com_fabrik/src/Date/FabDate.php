<?php
/**
 * @package     Joomla.Site
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Site\Date;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\String\StringHelper as FStringHelper;
use Joomla\String\StringHelper;

/**
 * very small override to JDate to stop 500 errors occurring (when Jdebug is on) if $date is not a valid date string
 *
 * @package  Fabrik
 * @since    4.0
 */
class FabDate extends Date
{
	/**
	 * GMT Date
	 *
	 * @var \DateTimeZone
	 *
	 * @since 4.0
	 */
	protected static $gmt;

	/**
	 * Default tz date
	 *
	 * @var \DateTimeZone
	 *
	 * @since 4.0
	 */
	protected static $stz;

	/**
	 * Construct
	 *
	 * @param   string $date Date
	 * @param   mixed  $tz   Timezone
	 *
	 * @since 4.0
	 */
	public function __construct($date = 'now', $tz = null)
	{
		$app  = Factory::getApplication();
		$orig = $date;
		$date = $this->stripDays($date);
		/* not sure if this one needed?
		 * $date = $this->monthToInt($date);
		 */
		$date = $this->removeDashes($date);

		try
		{
			$dt = new \DateTime($date);
		}
		catch (\Exception $e)
		{
			JDEBUG ? $app->enqueueMessage('date format unknown for ' . $orig . ' replacing with today\'s date', 'notice') : '';
			$date = 'now';
			/* catches 'Failed to parse time string (ublingah!) at position 0 (u)' exception.
			 * don't use this object
			 */
		}

		// Create the base GMT and server time zone objects.
		if (empty(self::$gmt) || empty(self::$stz))
		{
			self::$gmt = new \DateTimeZone('GMT');
			self::$stz = new \DateTimeZone(@date_default_timezone_get());
		}

		parent::__construct($date, $tz);
	}

	/**
	 * Remove '-' from string
	 *
	 * @param   string $str String to remove - from
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function removeDashes($str)
	{
		$str = FStringHelper::ltrimword($str, '-');

		return $str;
	}

	/**
	 * Month name to integer
	 *
	 * @param   string $str Month name
	 *
	 * @return  int  month number
	 *
	 * @since 4.0
	 */
	protected function monthToInt($str)
	{
		$abbrs = array(true, false);

		for ($a = 0; $a < count($abbrs); $a++)
		{
			for ($i = 0; $i < 13; $i++)
			{
				$month = $this->monthToString($i, $abbrs[$a]);

				if (StringHelper::stristr($str, $month))
				{
					$monthNum = StringHelper::strlen($i) === 1 ? '0' . $i : $i;
					$str      = StringHelper::str_ireplace($month, $monthNum, $str);
				}
			}
		}

		return $str;
	}

	/**
	 * Converts strftime format into PHP date() format
	 *
	 * @param   string $format  Strftime format
	 *
	 * @since   3.0.7
	 *
	 * @return  string  converted format
	 */
	static public function strftimeFormatToDateFormat($format)
	{
		$app = Factory::getApplication();

		if (strstr($format, '%C'))
		{
			$app->enqueueMessage('Cant convert %C strftime date format to date format, substituted with Y', 'notice');

			return;
		}

		$search = array('%e', '%j', '%u', '%V', '%W', '%h', '%B', '%C', '%g', '%G', '%M', '%P', '%r', '%R', '%T', '%X', '%z', '%Z', '%D', '%F', '%s',
			'%x', '%A', '%Y', '%m', '%d', '%H', '%S');

		$replace = array('j', 'z', 'w', 'W', 'W', 'M', 'F', 'Y', 'y', 'Y', 'i', 'a', '"g:i:s a', 'H:i', 'H:i:s', 'H:i:s', 'O', 'O', 'm/d/y"', 'Y-m-d', 'U',
			'Y-m-d', 'l', 'Y', 'm', 'd', 'H', 's');

		return str_replace($search, $replace, $format);
	}

	/**
	 * Convert strftime to PHP time format
	 *
	 * @param   string  $format Format
	 *
	 * @return  string  converted format
	 *
	 * @since 4.0
	 */
	static public function dateFormatToStrftimeFormat($format)
	{
		$trs = array(
			'd' => '%d',
			'D' => '%a',
			'j' => '%e',
			'l' => '%A',
			'N' => '%u',
			'S' => '',
			'w' => '%w',
			'z' => '%j',
			'W' => '%V',
			'F' => '%B',
			'm' => '%m',
			'M' => '%b',
			'n' => '%m',
			't' => '',
			'L' => '',
			'o' => '%g',
			'Y' => '%Y',
			'y' => '%y',
			'a' => '%P',
			'A' => '%p',
			'B' => '',
			'g' => '%l',
			'G' => '%H',
			'h' => '%I',
			'H' => '%H',
			'i' => '%M',
			's' => '%S',
			'e' => '%z',
			'u' => '',
			'I' => '',
			'O' => '',
			'P' => '',
			'T' => '%z',
			'Z' => '',
			'c' => '%c',
			'r' => '%a, %d %b %Y %H:%M:%S %z',
			'U' => '%s'
		);

		return strtr($format, $trs);
	}

	/**
	 * Strip days
	 *
	 * @param   string $str Date string
	 *
	 * @return  string date without days
	 *
	 * @since 4.0
	 */
	protected function stripDays($str)
	{
		$abbrs = array(true, false);

		for ($a = 0; $a < count($abbrs); $a++)
		{
			for ($i = 0; $i < 7; $i++)
			{
				$day = $this->dayToString($i, $abbrs[$a]);

				if (StringHelper::stristr($str, $day))
				{
					$str = StringHelper::str_ireplace($day, '', $str);
				}
			}
		}

		return $str;
	}
}