<?php
/**
 * Groups list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Groups list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Groups extends \JControllerBase
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_GROUPS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'groups';

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   12.1
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		// Get the application
		$app = $this->getApplication();

		// Get the document object.
		$document = \JFactory::getDocument();

		$viewName   = $app->input->getWord('view', 'lists');
		$viewFormat = $document->getType();
		$layoutName = $app->input->getWord('layout', 'bootstrap');
		$app->input->set('view', $viewName);

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');

		$viewClass  = 'Fabrik\Admin\Views\\' . ucfirst($viewName) . '\\' . ucfirst($viewFormat);
		$modelClass = 'Fabrik\Admin\Models\\' . ucfirst($viewName);

		$view = new $viewClass(new $modelClass, $paths);
		$view->setLayout($layoutName);
		// Render our view.
		echo $view->render();

		return true;
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @return  J model
	 */

	/*public function &getModel($name = 'Group', $prefix = 'FabrikAdminModel')
	{
		//$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		$model = parent::getModel($name, $prefix, array('ignore_request' => false));

		return $model;
	}*/
}
