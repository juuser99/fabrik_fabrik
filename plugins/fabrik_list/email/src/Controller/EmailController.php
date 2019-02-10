<?php
/**
 * Email list plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikList\Email\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

/**
 * Email list plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @since       4.0
 */
class EmailController extends AbstractSiteController
{
	/**
	 * Path of uploaded file
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $filepath = null;

	/**
	 * default display mode
	 *
	 * @param   bool  $cachable  Cacheable
	 * @param   array $urlparams Params
	 *
	 * @return null
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		echo "display";
	}

	/**
	 * set up the popup window containing the form to create the
	 * email message
	 *
	 * @return string html
	 *
	 * @since 4.0
	 */
	public function popupwin()
	{
		/** @var CMSApplication $app */
		$app      = Factory::getApplication();
		$input    = $app->input;
		$document = $app->getDocument();
		$viewName = 'popupwin';
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		/** @var ListModel $listModel */
		$listModel = $this->getModel(ListModel::class);
		// if SEF'ed, router will have changed 'id' to 'listid'
		$listModel->setId($input->getInt('id', $input->getInt('listid')));
		$formModel = $listModel->getFormModel();

		// Push a model into the view
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabrikModel::getInstance(PluginManagerModel::class);
		$model         = $pluginManager->getPlugIn('email', 'list');

		$model->formModel = $formModel;
		$model->listModel = $listModel;
		$listParams       = $listModel->getParams();
		$model->setParams($listParams, $input->getInt('renderOrder'));
		$view->setModel($model, true);
		$view->setModel($listModel);
		$view->setModel($formModel);

		// Display the view
		$view->error = $this->getError();

		return $view->display();
	}

	/**
	 * Send the emails
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function doemail()
	{
		/** @var CMSApplication $app */
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabrikModel::getInstance(PluginManagerModel::class);
		$model         = $pluginManager->getPlugIn('email', 'list');
		/** @var ListModel $listModel */
		$listModel     = $this->getModel(ListModel::class);
		$listModel->setId($input->getInt('id'));
		$listParams = $listModel->getParams();
		$model->setParams($listParams, $input->getInt('renderOrder'));
		$model->listModel = $listModel;
		/*
		 * $$$ hugh - for some reason have to do this here, if we don't, it'll
		 * blow up when it runs later on from within the list model itself.
		 */
		$formModel = $listModel->getFormModel();
		$model->doEmail();
	}
}
