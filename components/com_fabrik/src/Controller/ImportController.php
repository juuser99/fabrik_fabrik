<?php
/**
 * Fabrik Import Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;

// No direct access
use Fabrik\Component\Fabrik\Site\Model\CsvImportModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Import Controller
 *
 * @package  Fabrik
 * @since    4.0
 */
class ImportController extends AbstractSiteController
{
	/**
	 * Display the view
	 *
	 * @param boolean $cachable    If true, the view output will be cached - NOTE not actually used to control
	 *                             caching!!!
	 * @param array   $urlparams   An array of safe url parameters and their variable types, for valid values see
	 *                             {@link JFilterInput::clean()}.
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$input = $this->app->input;
		/** @var CsvImportModel $csvImportModel */
		$csvImportModel = $this->getModel(CsvImportModel::class);
		$csvImportModel->clearSession();
		$this->listid = $input->getInt('listid', 0);
		/** @var ListModel $listModel */
		$listModel = $this->getModel(ListModel::class);
		$listModel->setId($this->listid);
		$this->table = $listModel->getTable();
		$document    = $this->app->getDocument();
		$viewName    = $input->get('view', 'form');
		$viewType    = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		$view->setModel($csvImportModel, true);
		$view->display();
	}

	/**
	 * Perform the file upload and set the session state
	 * Unlike back end import if there are unmatched headings we bail out
	 *
	 * @return null
	 *
	 * @since 4.0
	 */
	public function doimport()
	{
		$input = $this->app->input;

		/** @var CsvImportModel $model */
		$model     = $this->getModel(CsvImportModel::class);
		$listModel = $model->getListModel();

		if (!$listModel->canCSVImport())
		{
			throw new \RuntimeException('Naughty naughty!', 400);
		}

		$menus  = $this->app->getMenu();
		$itemId = $input->getInt('Itemid', '');

		if (!empty($itemId))
		{
			$menus = $this->app->getMenu();
			$menus->setActive($itemId);
		}

		if (!$model->checkUpload())
		{
			$this->display();

			return;
		}

		$id       = $listModel->getId();
		$document = $this->app->getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();

		// Set the default view name from the Request
		$this->getView($viewName, $viewType);
		$model->import();

		if (!empty($model->newHeadings))
		{
			// As opposed to admin you can't alter table structure with a CSV import from the front end
			$this->app->enqueueMessage($model->makeError(), 'notice');
			$this->setRedirect('index.php?option=com_fabrik&view=import&filetype=csv&listid=' . $id . '&Itemid=' . $itemId);
		}
		else
		{
			$input->set('fabrik_list', $id);
			$model->insertData();
			$msg = $model->updateMessage();
			$this->setRedirect('index.php?option=com_fabrik&view=list&listid=' . $id . "&resetfilters=1&Itemid=" . $itemId, $msg);
		}
	}
}
