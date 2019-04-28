<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Controller\ListController;
use Fabrik\Component\Fabrik\Site\Helper\ControllerHelper;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\ArrayHelper;
use Joomla\CMS\MVC\View\HtmlView;

class ListRenderer extends AbstractRenderer
{
	/**
	 * @param AbstractSiteController         $controller
	 * @param FabrikSiteModel|ListModel|null $model
	 * @param HtmlView                       $view
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function renderContent(AbstractSiteController $controller, ?FabrikSiteModel $model, HtmlView $view): string
	{
		$id = $this->bag->getId();

		if ($id === 0)
		{
			$this->app->enqueueMessage('No id set in fabrik plugin declaration', 'warning');

			return '';
		}

		$model->setId($id);
		$model->isMambot = true;

		$model->ajax = $this->bag->isAjax();
		$task        = $this->input->get('task');

		if (method_exists($controller, $task) && $this->input->getInt('activetableid') == $id)
		{
			/*
			 * Enable delete() of rows
			 */
			ob_start();
			$controller->$task();
			$result = ob_get_contents();
			ob_end_clean();
		}

		$model->setOrderByAndDir();
		$model->getFormModel();

		return $result;
	}

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	protected function prepareControllerParameters(string $viewName, string $cacheId): void
	{
		/* $$$ rob 15/02/2011 added this as otherwise when you filtered on a table
				  * with multiple filter set up subsequent tables were showing
				  * the first tables data
				  */
		if ($this->input->get('activelistid') === '')
		{
			$this->setControllerInputParameter('activelistid', $this->input->getId('listid'));
		}

		$id = $this->bag->getId();
		$this->setControllerInputParameter('listid', $id);

		// Allow for simple limit=2 in plugin declaration
		if ($limit = $this->bag->getLimit())
		{
			$limitKey = 'limit' . $id;
			$this->setControllerInputParameter($limitKey, $limit);
		}

		$this->setControllerInputParameter('showfilters', $this->bag->getShowFilters())
			->setControllerInputParameter('clearfilters', $this->bag->getClearFilters())
			->setControllerInputParameter('resetfilters', $this->bag->getResetFilters());

		/**
		 *
		 * Reset this otherwise embedding a list in a list menu page, the embedded list takes the show in list fields from the menu list
		 *
		 * $$$ hugh - nasty little hack to reduce 'emptyish' array, 'cos if no 'elements' in the request, the following ends up setting
		 * returning an array with a single empty string.  This ends up meaning that we render a list with no
		 * elements in it.  We've run across this before, so we have a ArrayHelper:;emptyish() to detect it.
		 */
		$show_in_list = explode('|', $this->input->getString('elements', ''));

		if (ArrayHelper::emptyIsh($show_in_list, true))
		{
			$show_in_list = array();
		}

		$this->setControllerInputParameter('fabrik_show_in_list', $show_in_list);
	}

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function getControllerClass(string $viewName, string $cacheId): string
	{
		return ListController::class;
	}
}