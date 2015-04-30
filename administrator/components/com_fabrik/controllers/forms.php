<?php
/**
 * Forms list controller class.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Forms list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Forms extends \JControllerBase
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_FORMS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'forms';

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
	 * @param   string  $name    Model name
	 * @param   string  $prefix  Model prefix
	 *
	 * @since	1.6
	 *
	 * @return  model
	 */

	/*public function &getModel($name = 'Form', $prefix = 'FabrikAdminModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}*/

	/**
	 * Attempt to alter the db structure to match the form's current status
	 *
	 * @return  null
	 */

	/*public function updateDatabase()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&view=forms');
		$this->getModel()->updateDatabase();
		$this->setMessage(FText::_('COM_FABRIK_DATABASE_UPDATED'));
	}*/

	/**
	 * View the list data
	 *
	 * @return  null
	 */

	/*public function listview()
	{
		$input = $this->app->input;
		$cid = $input->get('cid', array(0), 'array');
		$cid = $cid[0];
		$db = JFactory::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_lists')->where('form_id = ' . (int) $cid);
		$db->setQuery($query);
		$listId = $db->loadResult();
		$this->setRedirect('index.php?option=com_fabrik&task=list.view&listid=' . $listId);
	}*/
}
