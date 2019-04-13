<?php
/**
 * Abstract Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Storage;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Abstract Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
abstract class AbstractStorageAdaptor
{
	/**
	 * Path or url to uploaded file
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $uploadedFilePath = null;

	/**
	 * @var Registry
	 * @since 4.0
	 */
	protected $params;

	/**
	 * AbstractStorageAdaptor constructor.
	 *
	 * @param \Joomla\CMS\HTML\Registry $params
	 * @since 4.0
	 */
	public function __construct(\Joomla\CMS\HTML\Registry $params)
	{
		$this->params = $params;
	}

	/**
	 * Return the value stored in fileupload_storage_type
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public static function getAlias(): string;

	/**
	 * Does a file exist
	 *
	 * @param string $filepath    File path to test
	 * @param bool   $prependRoot also test with root prepended
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	abstract public function exists($filepath, $prependRoot = true);

	/**
	 * Does a folder exist
	 *
	 * @param string $path Folder path to test
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	abstract public function folderExists($path);

	/**
	 * Create a folder
	 *
	 * @param string $path Folder path
	 * @param int    $mode Bitmask Permissions
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	abstract public function createFolder($path, $mode = 0755);

	/**
	 * Write a file
	 *
	 * @param string $file   File name
	 * @param string $buffer The buffer to write
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	abstract public function write($file, $buffer);

	/**
	 * Read a file
	 *
	 * @param string $filepath File path
	 *
	 * @return  mixed  Returns file contents or boolean False if failed
	 */
	abstract public function read($filepath);

	/**
	 * Stream a file
	 *
	 * @param string $filepath  File path
	 * @param int    $chunkSize chunk size
	 *
	 * @return  bool  returns false if error
	 *
	 * @since 4.0
	 */
	abstract public function stream($filepath, $chunkSize = 1024 * 1024);

	/**
	 * Clean the file path
	 *
	 * @param string $path Path to clean
	 *
	 * @return  string  cleaned path
	 *
	 * @since 4.0
	 */
	abstract public function clean($path);

	/**
	 * @param $file
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function getFileUrl($file);

	/**
	 * Clean a file name
	 *
	 * @param string $filename      File name to clean
	 * @param int    $repeatCounter Repeat group counter
	 *
	 * @return  string  cleaned name
	 *
	 * @since 4.0
	 */
	abstract public function cleanName($filename, $repeatCounter);

	/**
	 * Delete a file
	 *
	 * @param string $filepath    File to delete
	 * @param bool   $prependRoot also test with root prepended
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	abstract public function delete($filepath, $prependRoot = true);

	/**
	 * Moves an uploaded file to a destination folder
	 *
	 * @param string $tmpFile  The name of the php (temporary) uploaded file
	 * @param string $filepath The path (including filename) to move the uploaded file to
	 *
	 * @return  boolean True on success
	 *
	 * @since 4.0
	 */
	abstract public function upload($tmpFile, $filepath);

	/**
	 * Set a file's permissions
	 *
	 * @param string $filepath File to set permissions for
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	abstract public function setPermissions($filepath);

	/**
	 * Get the complete folder path, including the server root
	 *
	 * @param string $filepath The file path
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	abstract public function getFullPath($filepath);

	/**
	 * Check for snooping
	 *
	 * @param string $filepath The file path
	 *
	 * @return  boolean
	 *
	 * @since 4.0
	 */
	abstract public function checkPath($folder);

	/**
	 * @param $file
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function getThumb($file);

	/**
	 * Get the cropped file for the file given
	 *
	 * @param string $file main image file path
	 *
	 * @return  string  cropped image
	 *
	 * @since 4.0
	 */
	abstract public function getCropped($file);

	/**
	 * Return the directory separator - can't use DIRECTORY_SEPARATOR by default, as s3 uses /
	 *
	 * @return string
	 *
	 * @since 3.8
	 */
	abstract public function getDS();

	/**
	 * Get the uploaded file path
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function getUploadedFilePath()
	{
		return $this->uploadedFilePath;
	}

	/**
	 * Convert a full url into a full server path
	 *
	 * @param string $url URL
	 *
	 * @return string  path
	 *
	 * @since 4.0
	 */
	public function urlToPath($url)
	{
		return $url;
	}

	/**
	 * Do a final transform on the path name
	 *
	 * @param string  &$filepath Path to parse
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function finalFilePathParse(&$filepath)
	{
	}

	/**
	 * Convert a full server path into a full url
	 *
	 * @param string $path Server path
	 *
	 * @return  string  url
	 *
	 * @since 4.0
	 */
	public function pathToURL($path)
	{
		$path = str_replace(COM_FABRIK_BASE, '', $path);
		$path = FStringHelper::ltrimiword($path, '/');
		$path = COM_FABRIK_LIVESITE . $path;
		$path = str_replace('\\', '/', $path);

		// Some servers do not like double slashes in the URL.
		$path = str_replace('\/\/', '/', $path);

		return $path;
	}

	/**
	 * Make recursive folders
	 *
	 * @param string $folderPath Path to folder - e.g. /images/stories
	 * @param int    $mode       Bitmask Permissions
	 *
	 * @return  mixed JError|void
	 *
	 * @since 4.0
	 */
	public function makeRecursiveFolders($folderPath, $mode = 0755)
	{
		if (!Folder::exists($folderPath))
		{
			if (!Folder::create($folderPath, $mode))
			{
				throw new \RuntimeException("Could not make dir $folderPath ", 21);
			}
		}
	}

	/**
	 * Allows storage model to modify pathname just before it is rendered.  For instance,
	 * if using Amazon S3 with 'Authenticated URL' option.
	 *
	 * @param string $filepath Path to file
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function preRenderPath($filepath)
	{
		return $filepath;
	}

	/**
	 * When creating file paths, do we need to append them with JPATH_SITE
	 *
	 * @return  bool
	 * @since  3.0.6.2
	 *
	 */
	public function appendServerPath()
	{
		return true;
	}

	/**
	 * Randomize file name
	 *
	 * @param string  &$filename File name
	 *
	 * @return void
	 * @since 3.0.8
	 *
	 */
	protected function randomizeName(&$filename)
	{
		$params = $this->getParams();

		if ($params->get('random_filename') == 1)
		{
			$length = (int) $params->get('length_random_filename');

			if ($length < 6)
			{
				$length = 6;
			}

			$key      = "";
			$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRTVWXYZ";
			$i        = 0;

			while ($i < $length)
			{
				$char = StringHelper::substr($possible, mt_rand(0, StringHelper::strlen($possible) - 1), 1);
				$key  .= $char;
				$i++;
			}

			$ext      = File::getExt($filename);
			$filename = $key . '.' . $ext;
		}
	}

	/**
	 * Get params
	 *
	 * @return  Registry
	 *
	 * @since 4.0
	 */
	public function getParams()
	{
		return $this->params;
	}
}
