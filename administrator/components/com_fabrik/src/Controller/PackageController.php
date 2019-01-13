<?php
/**
 * Package controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Fabrik\Component\Fabrik\Site\Model\FormModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;

/**
 * Package controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class PackageController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_PACKAGE';

	/**
	 * Export the package
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function export()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$model = $this->getModel();
		$model->export($cid);
		$nText = $this->text_prefix . '_N_ITEMS_EXPORTED';
		$this->setMessage(JText::plural($nText, count($cid)));
		$this->setRedirect('index.php?option=com_fabrik&view=packages');
	}

	/**
	 * View the package
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function view()
	{
		$document = Factory::getDocument();
		$app = Factory::getApplication();
		$input = $app->input;
		$viewType = $document->getType();
		// @todo refactor to j4
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = $input->get('layout', 'default');
		$view = $this->getView('form', $viewType, '');
		$view->isMambot = $this->isMambot;

		// Set the layout
		$view->setLayout($viewLayout);

		// If the view is a package create and assign the table and form views
		$listView = $this->getView('list', $viewType);
		$listModel = $this->getModel(ListModel::class);
		$listView->setModel($listModel, true);
		$view->tableView = $listView;

		$view->formView = $this->getView('Form', $viewType);
		$formModel = $this->getModel(FormModel::class);
		$formModel->setDbo(Worker::getDbo());
		$view->formView->setModel($formModel, true);

		// Push a model into the view
		if ($model = $this->getModel($view->getName()))
		{
			$view->setModel($model, true);
		}

		$model->setDbo(Worker::getDbo());

		// @TODO check for cached version
		$view->display();
	}

	/**
	 * List form
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function listform()
	{
		$document = Factory::getDocument();
		$this->Form = $this->get('PackageListForm');
		$viewType = $document->getType();
		$view = $this->getView('package', $viewType, '');

		// Push a model into the view
		if ($model = $this->getModel())
		{
			$view->setModel($model, true);
			$db = Worker::getDbo();
			$model->setDbo($db);
		}

		$view->listform();
	}
}
