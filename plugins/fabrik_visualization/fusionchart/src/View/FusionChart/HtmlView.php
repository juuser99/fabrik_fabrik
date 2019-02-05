<?php
/**
 * Fabrik Fusion Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\FusionChart\View\FusionChart;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Helpers\Html;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Fusion Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @since       4.0
 */
class HtmlView extends BaseView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		/** @var CMSApplication $app */
		$app                    = Factory::getApplication();
		$input                  = $app->input;
		$srcs                   = Html::framework();
		$srcs['FbListFilter']   = 'media/com_fabrik/js/listfilter.js';
		$srcs['AdvancedSearch'] = 'media/com_fabrik/js/advanced-search.js';
		$model                  = $this->getModel();
		$usersConfig            = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->requiredFiltersFound = $this->get('RequiredFiltersFound');

		if ($this->requiredFiltersFound)
		{
			$this->chart = $this->get('Fusionchart');
		}
		else
		{
			$this->chart = '';
		}

		$params       = $model->getParams();
		$this->params = $params;
		$viewName     = $this->getName();
		/** @var PluginManagerModel $pluginManager */
		$pluginManager       = FabrikModel::getInstance(PluginManagerModel::class);
		$plugin              = $pluginManager->getPlugIn('calendar', 'visualization');
		$this->containerId   = $this->get('ContainerId');
		$this->filters       = $this->get('Filters');
		$this->showFilters   = $model->showFilters();
		$this->filterFormURL = $this->get('FilterFormURL');
		$tpl                 = $params->get('fusionchart_layout', 'bootstrap');
		$this->_setPath('template', JPATH_ROOT . '/plugins/fabrik_visualization/fusionchart/tmpl/fusionchart/' . $tpl);

		Html::stylesheetFromPath('plugins/fabrik_visualization/fusionchart/tmpl/fusionchart/' . $tpl . '/template.css');

		// Assign something to Fabrik.blocks to ensure we can clear filters
		$ref = $model->getJSRenderContext();
		$js  = "$ref = {};";
		$js  .= "\n" . "Fabrik.addBlock('$ref', $ref);";
		$js  .= $model->getFilterJs();
		Html::iniRequireJs($model->getShim());
		Html::script($srcs, $js);
		$text = $this->loadTemplate();
		Html::runContentPlugins($text, true);
		echo $text;
	}
}
