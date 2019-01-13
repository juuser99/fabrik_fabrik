<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;


use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;

trait ModelTrait
{
	/**
	 * Try a clean approach first then fall back to native Joomla
	 *
	 * @param string $modelClass
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return FabrikModel
	 *
	 * @since 4.0
	 *
	 * @throws \Exception
	 */
	public function getModel($modelClass = '', $prefix = '', $config = array())
	{
		if (class_exists($modelClass)) {
			return FabrikModel::getInstance($modelClass, '', $config);
		}

		return parent::getModel($modelClass, $prefix, $config);
	}
}