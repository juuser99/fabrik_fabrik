<?php
/**
 * @package     Joomla\Component\Fabrik\Site\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Component\Fabrik\Site\CSV;

/**
 * This class will parse a csv file in either standard or MS Excel format.
 * Two methods are provided to either process a line at a time or return the whole csv file as an array.
 *
 * It can deal with:
 * - Line breaks within quoted fields
 * - Character separator (usually a comma or semicolon) in quoted fields
 * - Can leave or remove leading and trailing \s or \t
 * - Can leave or skip empty rows.
 * - Windows and Unix line breaks dealt with automatically. Care must be taken with Macintosh format.
 *
 * Also, the escape character is automatically removed.
 *
 * NOTICE:
 * - Quote character can be escaped by itself or by using an escape character, within a quoted field (i.e. "" or \"
 * will work)
 *
 * $csv = & new Csv_Bv('test.csv', ';', '"' , '\\');
 * $csv->SkipEmptyRows(TRUE); // Will skip empty rows. TRUE by default. (Shown here for example only).
 * $csv->TrimFields(TRUE); // Remove leading and trailing \s and \t. TRUE by default.
 *
 * while ($row = $csv->NextLine()) {
 *
 *         echo "<br><br>Processing line ". $csv->RowCount() . "<br>";
 *         echo implode(' , ', $row);
 *
 * }
 *
 * echo "<br><br>Number of returned rows: ".$csv->RowCount();
 * echo "<br><br>Number of skipped rows: ".$csv->SkippedRowCount();
 *
 * ----
 * OR using the csv2array function.
 * ----
 *
 * $csv = & new Csv_Bv('test.csv', ';', '"' , '\\');
 * $csv->SkipEmptyRows(TRUE); // Will skip empty rows. TRUE by default. (Shown here for example only).
 * $csv->TrimFields(TRUE); // Remove leading and trailing \s and \t. TRUE by default.
 *
 * $_arr = $csv->csv2Array();
 *
 * echo "<br><br>Number of returned rows: ".$csv->RowCount();
 * echo "<br><br>Number of skipped rows: ".$csv->SkippedRowCount();
 *
 *
 * WARNING:
 * - Macintosh line breaks need to be dealt with carefully. See the PHP help files for the function 'fgetcsv'
 *
 * The coding standards used in this file can be found here: http://www.dagbladet.no/development/phpcodingstandard/
 *
 *    All comments and suggestions are welcomed.
 *
 * SUPPORT: Visit http://vhd.com.au/forum/
 *
 * CHANGELOG:
 *
 * - Fixed skipping of last row if the last row did not have a new line. Thanks to Florian Bruch and Henry Flurry.
 * (2006_05_15)
 * - Changed the class name to Csv_Bv for consistency. (2006_05_15)
 * - Fixed small problem where line breaks at the end of file returned a warning (2005_10_28)
 *
 * @version    Release: 1.2
 * @category   Joomla
 * @package    Fabrik
 * @author     Ben Vautier <classes@vhd.com.au>
 * @copyright  2006 Ben Vautier
 * @since      3.0
 *
 */
class CsvParser
{
	/**
	 * Seperator character
	 *
	 * @var char
	 */
	protected $mFldSeperator;

	/**
	 * Enclose character
	 *
	 * @var char
	 */
	protected $mFldEnclosure;

	/**
	 * Escape character
	 *
	 * @var char
	 */
	protected $mFldEscapor;

	/**
	 * Length of the largest row in bytes.Default is 4096
	 *
	 * @var int
	 */
	protected $mRowSize;

	/**
	 * Holds the file pointer
	 *
	 * @var resource
	 */
	public $mHandle;

	/**
	 * Counts the number of rows that have been returned
	 *
	 * @var int
	 */
	protected $mRowCount;

	/**
	 * Counts the number of empty rows that have been skipped
	 *
	 * @var int
	 */
	protected $mSkippedRowCount;

	/**
	 * Determines whether empty rows should be skipped or not.
	 * By default empty rows are returned.
	 *
	 * @var boolean
	 */
	protected $mSkipEmptyRows;

	/**
	 * Specifies whether the fields leading and trailing \s and \t should be removed
	 * By default it is TRUE.
	 *
	 * @var boolean
	 */
	protected $mTrimFields;

	/**
	 * $$$ rob 15/07/2011
	 *  'excel' or 'csv', if excel then convert 'UTF-16LE' to 'UTF-8' with iconv when reading in lines
	 *
	 * @var string
	 */
	public $inPutFormat = 'csv';

	/**
	 * Constructor
	 *
	 * Only used to initialise variables.
	 *
	 * @param   string $file      file path
	 * @param   string $seperator Only one character is allowed (optional)
	 * @param   string $enclose   Only one character is allowed (optional)
	 * @param   string $escape    Only one character is allowed (optional)
	 */

	public function __construct($file, $seperator = ',', $enclose = '"', $escape = '')
	{
		$this->mFldSeperator    = $seperator;
		$this->mFldEnclosure    = $enclose;
		$this->mFldEscapor      = $escape;
		$this->mSkipEmptyRows   = true;
		$this->mTrimFields      = true;
		$this->htmlentity       = true;
		$this->mRowCount        = 0;
		$this->mSkippedRowCount = 0;
		$this->mRowSize         = 4096;

		// Open file
		$this->mHandle = @fopen($file, "r") or trigger_error('Unable to open csv file', E_USER_ERROR);
	}

	/**
	 * uft 8 decode
	 *
	 * @param   string $string decode strong
	 *
	 * @return unknown|mixed
	 */

	protected function charset_decode_utf_8($string)
	{
		/* Only do the slow convert if there are 8-bit characters */
		/* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
		if (!preg_match("/[\200-\237]/", $string) and !preg_match("/[\241-\377]/", $string))
		{
			return $string;
		}

		// Decode three byte unicode characters
		$pattern = "/([\340-\357])([\200-\277])([\200-\277])/";
		$string  = preg_replace_callback(
			$pattern,
			function ($m)
			{
				return '&#' . ((ord($m[1]) - 224) * 4096 + (ord($m[2]) - 128) * 64 + (ord($m[3]) - 128));
			},
			$string
		);

		// Decode two byte unicode characters
		$string = preg_replace_callback(
			"/([\300-\337])([\200-\277])/",
			function ($m)
			{
				return '&#' . ((ord($m[1]) - 192) * 64 + (ord($m[2]) - 128));
			},
			$string
		);

		return $string;
	}

	/**
	 * csv::NextLine() returns an array of fields from the next csv line.
	 *
	 * The position of the file pointer is stored in PHP internals.
	 *
	 * Empty rows can be skipped
	 * Leading and trailing \s and \t can be removed from each field
	 *
	 * @return  array  of fields
	 */

	public function NextLine()
	{
		if (feof($this->mHandle))
		{
			return false;
		}

		$arr_row = fgetcsv($this->mHandle, $this->mRowSize, $this->mFldSeperator, $this->mFldEnclosure);
		$this->mRowCount++;

		// Skip empty rows if asked to
		if ($this->mSkipEmptyRows)
		{
			/* $$ Phil changed - $arr_row (fgetcsv) could be false (if any errors or EOF)
			 * and so needs to be include in this if condition so as to return false
			 */
			// if ($arr_row[0] === '' && count($arr_row) === 1)
			if (!$arr_row || ($arr_row[0] === '' && count($arr_row) === 1))
			{
				$this->mRowCount--;
				$this->mSkippedRowCount++;
				$arr_row = $this->NextLine();

				// This is to avoid a warning when empty lines are found at the very end of a file.
				if (!is_array($arr_row))
				{
					// This will only happen if we are at the end of a file.
					return false;
				}
			}
		}

		if (is_array($arr_row))
		{
			if ($this->inPutFormat == 'excel' || $this->inPutFormat == 'fabrikexcel')
			{
				$encFrom = $this->inPutFormat == 'fabrikexcel' ? 'UTF-16LE' : 'Windows-1252';

				foreach ($arr_row as $k => $v)
				{
					$arr_row[$k] = trim($arr_row[$k]);

					if ($arr_row[$k] !== '')
					{
						$arr_row[$k] = iconv($encFrom, 'UTF-8', $arr_row[$k]);
						$arr_row[$k] = str_replace('""', '"', $arr_row[$k]);
						$arr_row[$k] = preg_replace("/^\"(.*)\"$/sim", "$1", $arr_row[$k]);
					}
				}
			}
		}
		// Remove leading and trailing spaces \s and \t
		if ($this->mTrimFields && is_array($arr_row))
		{
			array_walk($arr_row, array($this, 'ArrayTrim'));
		}

		/**
		 * Remove escape character if it is not empty and different from the enclose character
		 * otherwise fgetcsv removes it automatically and we don't have to worry about it.
		 */
		if ($this->mFldEscapor !== '' && $this->mFldEscapor !== $this->mFldEnclosure && is_array($arr_row))
		{
			array_walk($arr_row, array($this, 'ArrayRemoveEscapor'));
		}

		// Remove leading and trailing spaces \s and \t
		if ($this->htmlentity && is_array($arr_row))
		{
			array_walk($arr_row, array($this, 'charset_decode_utf_8'));
		}

		return $arr_row;
	}

	/**
	 * csv::Csv2Array will return the whole csv file as 2D array
	 *
	 * @return  array
	 */

	public function Csv2Array()
	{
		$arr_csv = array();

		while ($arr_row = $this->NextLine())
		{
			$arr_csv[] = $arr_row;
		}

		return $arr_csv;
	}

	/**
	 * csv::ArrayTrim will remove \s and \t from an array
	 *
	 * It is called from array_walk.
	 *
	 * @param   string &$item string to trim
	 * @param   string $key   not used
	 *
	 * @return  void
	 */

	protected Function ArrayTrim(&$item, $key)
	{
		// Space and tab
		$item = trim($item, " \t");
	}

	/**
	 * csv::ArrayRemoveEscapor will escape the enclose character
	 * It is called from array_walk.
	 *
	 * @param   string &$item string to trim
	 * @param   string $key   not used
	 *
	 * @return  void
	 */

	protected function ArrayRemoveEscapor(&$item, $key)
	{
		$item = str_replace($this->mFldEscapor . $this->mFldEnclosure, $this->mFldEnclosure, $item);
	}

	/**
	 * Htmlenties a string
	 *
	 * @param   string &$item string to trim
	 * @param   string $key   not used
	 *
	 * @return  void
	 */

	protected function htmlentity(&$item, $key)
	{
		$item = htmlentities($item);
	}

	/**
	 * csv::RowCount return the current row count
	 *
	 * @access public
	 * @return int
	 */
	public function RowCount()
	{
		return $this->mRowCount;
	}

	/**
	 * csv::RowCount return the current skipped row count
	 *
	 * @return int
	 */

	public function SkippedRowCount()
	{
		return $this->mSkippedRowCount;
	}

	/**
	 * csv::SkipEmptyRows, sets whether empty rows should be skipped or not
	 *
	 * @param   bool $bool skip empty rows
	 *
	 * @return void
	 */

	public function SkipEmptyRows($bool = true)
	{
		$this->mSkipEmptyRows = $bool;
	}

	/**
	 * csv::TrimFields, sets whether fields should have their \s and \t removed.
	 *
	 * @param   bool $bool set trim fields state
	 *
	 * @return  null
	 */

	public function TrimFields($bool = true)
	{
		$this->mTrimFields = $bool;
	}
}
