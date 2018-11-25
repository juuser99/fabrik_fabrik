<?php
/**
 * Fabrik Import Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\Component\Fabrik\Administrator\Model\ListModel;
use Joomla\Component\Fabrik\Administrator\Table\ElementTable;
use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Component\Fabrik\Site\Model\ImportCsvModel;
use Joomla\Component\Fabrik\Site\Model\ListModel as SiteListModel;
use Joomla\Component\Fabrik\Site\Model\PluginManagerModel;
use Joomla\String\StringHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Fabrik Import Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ImportController extends AbstractFormController
{
	/**
	 * If new elements found in the CSV file and user decided to
	 * add them to the table then do it here
	 *
	 * Any data from elements not selected to be added will be removed
	 *
	 * @param   object $model    Import model
	 * @param   array  $headings Existing headings
	 *
	 * @return  array  All headings (previously found and newly added)
	 *
	 * @since 4.0
	 */
	protected function addElements($model, $headings)
	{
		$app         = Factory::getApplication();
		$dataRemoved = false;
		$input       = $app->input;
		$user        = Factory::getUser();
		$c           = 0;

		/** @var SiteListModel $listModel */
		$listModel = $this->getModel(SiteListModel::class);
		$listModel->setId($input->getInt('listid'));
		$item = $listModel->getTable();

		/** @var ListModel $adminListModel */
		$adminListModel = $this->getModel(ListModel::class);
		$adminListModel->loadFromFormId($item->form_id);

		$formModel = $listModel->getFormModel();
		$adminListModel->setFormModel($formModel);
		$groupId       = current(array_keys($formModel->getGroupsHiarachy()));
		$plugins       = $input->get('plugin', array(), 'array');
		$pluginManager = Worker::getPluginManager();
		$elementModel  = $pluginManager->getPlugIn('field', 'element');
		/** @var ElementTable $element */
		$element     = FabTable::getInstance(ElementTable::class);
		$newElements = $input->get('createElements', array(), 'array');

		// @TODO use actual element plugin getDefaultProperties()
		foreach ($newElements as $elName => $add)
		{
			if ($add)
			{
				$element->id                   = 0;
				$element->name                 = FStringHelper::dbFieldName($elName);
				$element->label                = StringHelper::strtolower($elName);
				$element->plugin               = $plugins[$c];
				$element->group_id             = $groupId;
				$element->eval                 = 0;
				$element->published            = 1;
				$element->width                = 255;
				$element->created              = date('Y-m-d H:i:s');
				$element->created_by           = $user->get('id');
				$element->created_by_alias     = $user->get('username');
				$element->checked_out          = 0;
				$element->show_in_list_summary = 1;
				$element->ordering             = 0;
				$element->params               = $elementModel->getDefaultAttribs();
				$headingKey                    = $item->db_table_name . '___' . $element->name;
				$headings[$headingKey]         = $element->name;
				$element->store();
				$where = " group_id = '" . $element->group_id . "'";
				$element->move(1, $where);
			}
			else
			{
				// Need to remove none selected element's (that don't already appear in the table structure
				// data from the csv data
				$session     = $this->app->getSession();
				$allHeadings = (array) $session->get('com_fabrik.csvheadings');
				$index       = array_search($elName, $allHeadings);

				if ($index !== false)
				{
					$dataRemoved = true;

					foreach ($model->data as &$d)
					{
						unset($d[$index]);
					}
				}
			}

			$c++;
		}

		$adminListModel->ammendTable();

		if ($dataRemoved)
		{
			// Reindex data array
			foreach ($model->data as $k => $d)
			{
				$model->data[$k] = array_reverse(array_reverse($d));
			}
		}

		return $headings;
	}

	/**
	 * Method to cancel an import.
	 *
	 * @param   string $key The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since 4.0
	 */
	public function cancel($key = null)
	{
		$this->setRedirect('index.php?option=com_fabrik&view=lists');

		return true;
	}

	/**
	 * Make or update the list from the CSV file
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function makeTableFromCSV()
	{
		// Called when creating new elements from csv import into existing list
		$app     = Factory::getApplication();
		$session = $app->getSession();
		$input   = $app->input;
		$jform   = $input->get('jform', null, 'array');

		/** @var ImportCsvModel $model */
		$model  = $this->getModel(ImportCsvModel::class);
		$listId = (int) $input->getInt('fabrik_list', $input->get('listid'));

		if ($listId === 0)
		{
			$plugins                = $input->get('plugin', array(), 'array');
			$createElements         = $input->get('createElements', array(), 'array');
			$newElements            = array();
			$c                      = 0;
			$dbName                 = $input->get('db_table_name', '', 'string');
			$model->matchedHeadings = array();

			foreach ($createElements as $elName => $add)
			{
				if ($add)
				{
					$name                                          = FStringHelper::dbFieldName($elName);
					$plugin                                        = $plugins[$c];
					$newElements[$name]                            = $plugin;
					$model->matchedHeadings[$dbName . '.' . $name] = $name;
				}

				$c++;
			}

			// Stop id and date_time being added to the table and instead use $newElements
			$input->set('defaultfields', $newElements);

			/** @var ListModel $listModel */
			$listModel = $this->getModel(ListModel::class);

			/**
			 * Create db
			 *
			 * @TODO should probably add an ACL option to the import options, as we now have to set 'access'
			 * to something for the elementtype import.  Defaulting to 1 for now.
			 */
			$data = array(
				'id'             => 0,
				'_database_name' => $dbName,
				'connection_id'  => $input->getInt('connection_id'),
				'rows_per_page'  => 10,
				'template'       => 'default',
				'published'      => 1,
				'access'         => 1,
				'label'          => $input->getString('label'),
				'jform'          => array(
					'id'             => 0,
					'_database_name' => $dbName,
					'db_table_name'  => '',
					'contenttype'    => null
				)
			);

			$input->set('jform', $data['jform']);
			$listModel->save($data);
			$model->listModel = null;
			$input->set('listid', $listModel->getItem()->id);

			// Reset back to import CSV jForm data.
			$input->set('jform', $jform);
		}
		else
		{
			$headings               = $session->get('com_fabrik.matchedHeadings');
			$model->matchedHeadings = $this->addElements($model, $headings);
			$model->listModel       = null;
			$input->set('listid', $listId);
		}

		$model->readCSV($model->getCSVFileName());
		$model->insertData();
		$msg = $model->updateMessage();
		$this->setRedirect('index.php?option=com_fabrik&view=lists', $msg);
	}

	/**
	 * Display the import CSV file form
	 *
	 * @param   boolean $cachable  If true, the view output will be cached
	 * @param   array   $urlparams An array of safe url parameters and their variable types, for valid values see
	 *                             {@link JFilterInput::clean()}.
	 *
	 * @return  $this  A JControllerLegacy object to support chaining.
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$viewType = Factory::getDocument()->getType();
		$view     = $this->getView('import', $viewType);
		$this->getModel(ImportCsvModel::class)->clearSession();

		if ($model = $this->getModel())
		{
			$view->setModel($model, true);
		}

		$view->display();

		return $this;
	}

	/**
	 * Perform the file upload and set the session state
	 * Unlike front end import if there are unmatched heading we take the user to
	 * a form asking if they want to import those new headings (creating new elements for them)
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function doimport()
	{
		/** @var ImportCsvModel $model */
		$model = $this->getModel(ImportCsvModel::class);
		$app   = Factory::getApplication();
		$input = $app->input;

		if (!$model->checkUpload())
		{
			$this->display();

			return;
		}

		$id       = $model->getListModel()->getId();
		$document = Factory::getDocument();
		$viewName = 'import';
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$model->import();

		if (!empty($model->newHeadings))
		{
			$view->setModel($model, true);
			$view->setModel($this->getModel(PluginManagerModel::class));
			$view->chooseElementTypes();
		}
		else
		{
			$input->set('fabrik_list', $id);
			$model->insertData();
			$msg = $model->updateMessage();
			$model->removeCSVFile();
			$this->setRedirect('index.php?option=com_fabrik&task=list.view&cid=' . $id, $msg);
		}
	}
}
