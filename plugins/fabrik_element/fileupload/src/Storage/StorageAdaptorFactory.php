<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Adaptor;


use Fabrik\Plugin\FabrikElement\Fileupload\Storage\AbstractStorageAdaptor;
use Fabrik\Plugin\FabrikElement\Fileupload\Storage\Exception\StorageAdaptorNotFoundException;
use Joomla\CMS\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;

class StorageAdaptorFactory
{
	/**
	 * @var array
	 * @since 4.0
	 */
	private $adaptors = [];

	/**
	 * StorageAdaptorFactory constructor.
	 *
	 * @since 4.0
	 */
	public function __construct()
	{
		$adaptors = Folder::files(__DIR__.'/Adaptor');

		foreach ($adaptors as $adaptor) {
			$info = pathinfo($adaptor);

			/** @var AbstractStorageAdaptor $class */
			$class = sprintf('Fabrik\\Plugin\\FabrikElement\\Fileupload\\Storage\\Adaptor\\%s', $info['filename']);

			$adaptors[$class::getAlias()] = $class;
		}
	}

	/**
	 * @param string   $alias
	 * @param Registry $params
	 *
	 * @return mixed
	 *
	 * @throws StorageAdaptorNotFoundException
	 * @since 4.0
	 */
	public function getStorageAdaptor(string $alias, Registry $params)
	{
		if (!isset($this->adaptors[$alias])) {
			throw new StorageAdaptorNotFoundException($alias);
		}

		return new $this->adaptors[$alias]($params);
	}
}