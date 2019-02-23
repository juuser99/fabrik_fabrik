<?php
/**
 * Fabrik Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;


use Fabrik\Component\Fabrik\Administrator\Dispatcher\Dispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class FabrikModel extends BaseDatabaseModel
{

	/**
	 * BaseDatabaseModel::getInstance is ugly AF due to requiring a string based $prefix for namespacing.
	 *
	 * @param string $modelClass
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return FabrikModel|BaseDatabaseModel
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public static function getInstance($modelClass, $prefix = '', $config = array())
	{
		if (!class_exists($modelClass)) {
			if (Dispatcher::PREFIX_SITE !== $prefix && Dispatcher::PREFIX_ADMIN !== $prefix)
			{
				// Try Native Joomla
				return parent::getInstance($modelClass, $prefix, $config);
			}

			// Let's see if one of our models exist in case we are dynamically requesting the model
			$modelString = "%s\\%s\\Model\\%sModel";
				$modelClass  = (Dispatcher::PREFIX_SITE === $prefix) ?
				sprintf($modelString, Dispatcher::NAMESPACE, Dispatcher::PREFIX_SITE, ucfirst($modelClass)) :
				sprintf($modelString, Dispatcher::NAMESPACE, Dispatcher::PREFIX_ADMIN, ucfirst($modelClass));

			if (!class_exists($modelClass)) {
				// This is our own code's fault if we get to this point
				throw new \Exception($modelClass.' does not exist!');
			}
		}

		// Check for a possible service from the container otherwise manually instantiate the class
		if (Factory::getContainer()->has($modelClass))
		{
			return Factory::getContainer()->get($modelClass);
		}

		return new $modelClass($config);
	}
}