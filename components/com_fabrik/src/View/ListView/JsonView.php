<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Site\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * List JSON view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class JsonView extends BaseView
{
	/**
	 * Display a json object representing the table data.
	 * Not used for updating fabrik list, use raw view for that, here in case you want to export the data to another application
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$model = $this->getModel();
		$model->setId($this->app->input->getInt('listid'));

		if (!parent::access($model))
		{
			exit;
		}

		$data = $model->getData();
		echo json_encode($data);
	}
}
