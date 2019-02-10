<?php
/**
 * Fabrik Package Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;

// No direct access
use Fabrik\Component\Fabrik\Site\Model\FormModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Component\Fabrik\Site\Model\PackageModel;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik package controller
 *
 * @package  Fabrik
 * @since    4.0
 */
class PackageController extends AbstractSiteController
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 *          
	 * @since 4.0
	 */
	public $cacheId = 0;

	/**
	 * Display the package view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  $this  A JController object to support chaining.
	 *                 
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$document = $app->getDocument();
		$input = $app->input;
		$viewName = $input->get('view', 'package');

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// If the view is a package create and assign the table and form views
		$tableView = $this->getView('list', $viewType);
		/** @var ListModel $listModel */
		$listModel = $this->getModel(ListModel::class);
		$tableView->setModel($listModel, true);
		$view->tableView = $tableView;

		$view->formView = $this->getView('Form', $viewType);
		/** @var FormModel $formModel */
		$formModel = $this->getModel(FormModel::class);
		$formModel->setDbo(Worker::getDbo());
		$view->formView->setModel($formModel, true);

		// Push a model into the view
		if ($model = $this->getModel(PackageModel::class))
		{
			$model->setDbo(Worker::getDbo());
			$view->setModel($model, true);
		}
		// Display the view
		$view->error = $this->getError();
		$view->display();

		return $this;
	}
}
