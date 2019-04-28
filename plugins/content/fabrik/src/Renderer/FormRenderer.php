<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Controller\FormController;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Fabrik\Component\Fabrik\Site\Model\FormModel;
use Joomla\CMS\MVC\View\HtmlView;

class FormRenderer extends AbstractRenderer
{
	/**
	 * @param AbstractSiteController         $controller
	 * @param FabrikSiteModel|FormModel|null $model
	 * @param HtmlView                       $view
	 *
	 * @return string
	 *
	 * @since version
	 */
	protected function renderContent(AbstractSiteController $controller, ?FabrikSiteModel $model, HtmlView $view): string
	{
		$id = $this->bag->getId();

		if ($id === 0)
		{
			$this->app->enqueueMessage('No id set in fabrik plugin declaration', 'warning');

			return '';
		}

		$model->ajax = $this->bag->isAjax();
		$model->setId($id);

		unset($model->groups);

		ob_start();
		$controller->display($model);
		$result = ob_get_contents();
		ob_end_clean();

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
		// $$$ rob - flayout is used in form/details view when _isMamot = true
		$this->setControllerInputParameter('flayout', $this->input->get('layout'));
		$this->setControllerInputParameter('rowid', $this->bag->getRowId());
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
		return FormController::class;
	}
}