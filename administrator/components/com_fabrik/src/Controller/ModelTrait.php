<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;


use Fabrik\Helpers\Worker;
use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;

trait ModelTrait
{
	/**
	 * Try Joomla Factory first then fall back to manually creating
	 *
	 * @param string $name
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return FabrikModel|bool
	 *
	 * @since 4.0
	 *
	 * @throws \Exception
	 */
	protected function createModel($name, $prefix = '', $config = array())
	{
		/*
		 * Use true so that we always use the Joomla db when in admin.
		 * otherwise if alt cnn set to default that is loaded and the fabrik tables are not found
		 */
		$db = Worker::getDbo(true);
		$config['dbo'] = $db;

		if ($factoryModel = parent::createModel($name, $prefix, $config)) {
			return $factoryModel;
		}

		if (class_exists($name)) {
			return FabrikModel::getInstance($name, '', $config);
		}

		return false;
	}
}