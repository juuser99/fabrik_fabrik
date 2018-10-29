<?php
/**
 * @package     Joomla\Component\Fabrik\Administrator\Controller
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;


use Joomla\Component\Fabrik\Administrator\Model\FabModel;

trait ModelTrait
{
	/**
	 * Why Joomla? Why?
	 *
	 * @param        $name
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	protected function createModel($name = '', $prefix = '', $config = array())
	{
		if (empty($name))
		{
			$name = $this->context;
		}

		$modelClass = "Joomla\\Component\\Fabrik\\Administrator\\Model\\".ucfirst($name)."Model";

		if (class_exists($modelClass)) {
			return FabModel::getInstance($modelClass, '', $config);
		}

		return parent::createModel($name, $prefix, $config);
	}
}