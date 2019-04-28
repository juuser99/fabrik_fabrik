<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Joomla\CMS\MVC\View\HtmlView;

class ViewRenderer extends AbstractRenderer
{
	/**
	 * @param AbstractSiteController $controller
	 * @param FabrikSiteModel|null   $model
	 * @param HtmlView               $view
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function renderContent(AbstractSiteController $controller, ?FabrikSiteModel $model, HtmlView $view): string
	{
		ob_start();
		$controller->display();
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
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
		return AbstractSiteController::class;
	}
}