<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Controller\VisualizationController;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Fabrik\Helpers\Worker;
use Joomla\CMS\MVC\View\HtmlView;

class VisualizationRenderer extends AbstractRenderer
{
	/**
	 * @var array
	 * @since 4.0
	 */
	private $pluginVizName;

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
		$viewName = $this->getPluginVizName($cacheId);

		return parent::getModel($controller, $viewName, $cacheId);
	}

	/**
	 * Get the viz plugin name
	 *
	 * @param string $cacheId viz id
	 *
	 * @return  string    viz plugin name
	 *
	 * @since 4.0
	 */
	protected function getPluginVizName($cacheId)
	{
		$id = (int) $cacheId;
		if (!isset($this->pluginVizName))
		{
			$this->pluginVizName = array();
		}

		if (!array_key_exists($id, $this->pluginVizName))
		{
			$db    = Worker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('plugin')->from('#__{package}_visualizations')->where('id = ' . (int) $id);
			$db->setQuery($query);
			$this->pluginVizName[$id] = $db->loadResult();
		}

		return $this->pluginVizName[$id];
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
		return VisualizationController::class;
	}
}