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

class FormCssRenderer extends AbstractRenderer
{
	/**
	 * @param AbstractSiteController $controller
	 * @param FabrikSiteModel|FormModel|null   $model
	 * @param HtmlView               $view
	 *
	 * @return string
	 *
	 * @since version
	 */
	protected function renderContent(AbstractSiteController $controller, ?FabrikSiteModel $model, HtmlView $view): string
	{
		if (!$model)
		{
			return '';
		}

		$model->setId($this->bag->getId());
		$model->setEditable(false);
		$layout = !empty($layout) ? $layout : 'default';
		$view->setModel($model, true);
		$model->getFormCss($layout);

		return '';
	}

	/**
	 * @param AbstractSiteController $controller
	 * @param string                 $viewName
	 * @param string                 $cacheId
	 *
	 * @return FabrikSiteModel
	 *
	 * @since 4.0
	 */
	protected function getModel(AbstractSiteController $controller, string $viewName, string $cacheId): FabrikSiteModel
	{
		return parent::getModel($controller, 'form', $cacheId);
	}

	/**
	 * @param AbstractSiteController $controller
	 * @param string                 $viewName
	 * @param string                 $cacheId
	 *
	 * @return HtmlView
	 *
	 * @since 4.0
	 */
	protected function getView(AbstractSiteController $controller, string $viewName, string $cacheId): HtmlView
	{
		return parent::getView($controller, 'form', $cacheId);
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