<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * List JSON view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class JSON extends Base
{
	/**
	 * Display a json object representing the table data.
	 * Not used for updating fabrik list, use raw view for that, here in case you want to export the data to another application
	 *
	 * @return  void
	 */
	public function render()
	{
		$app = $this->app;
		$model = $this->getModel();
		$model->setId($app->input->getString('listid'));

		if (!parent::access($model))
		{
			exit;
		}

		$data = $model->getData();
		echo json_encode($data);
	}
}
