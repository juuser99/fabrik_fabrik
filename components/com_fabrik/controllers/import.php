<?php
/**
 * Fabrik Import Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require 'controller.php';

/**
 * Fabrik Import Controller
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikControllerImport extends FabrikController
{
	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */

	public function display($cachable = false, $urlparams = array())
	{
		$input = $this->input;
		$model = new CsvImport;
		$model->clearSession();
		$this->listid = $input->getInt('listid', 0);
		$listModel = new Lizt;
		$listModel->setId($this->listid);
		$this->table = $listModel->getTable();
		$document = JFactory::getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$model = new CsvImport;
		$view->setModel($model, true);
		$view->display();
	}

	/**
	 * Perform the file upload and set the session state
	 * Unlike back end import if there are unmatched headings we bail out
	 *
	 * @return null
	 */

	public function doimport()
	{
		$input = $this->input;
		$model = new CsvImport;
		$listModel = $model->getListModel();

		if (!$listModel->canCSVImport())
		{
			throw new RuntimeException('Naughty naughty!', 400);
		}

		if (!$model->checkUpload())
		{
			$this->display();

			return;
		}

		$id = $listModel->getId();
		$document = JFactory::getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$model->import();
		$Itemid = $input->getInt('Itemid');

		if (!empty($model->newHeadings))
		{
			// As opposed to admin you can't alter table structure with a CSV import from the front end
			$this->app->enqueueMessage($model->makeError(), 'notice');
			$this->setRedirect('index.php?option=com_fabrik&view=import&filetype=csv&listid=' . $id . '&Itemid=' . $Itemid);
		}
		else
		{
			$input->set('fabrik_list', $id);
			$model->insertData();
			$msg = $model->updateMessage();
			$this->setRedirect('index.php?option=com_fabrik&view=list&listid=' . $id . "&resetfilters=1&Itemid=" . $Itemid, $msg);
		}
	}
}
