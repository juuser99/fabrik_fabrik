<?php
/**
 * Fabrik Fusion Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Fusionchart\Views;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html as HtmlHelper;
use Fabrik\Helpers\Text;

use \JFactory;
use \JHtml;
use \JViewLegacy;
use \JComponentHelper;

/**
 * Fabrik Fusion Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @since       3.0
 */
class Html extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */
	public function display($tpl = 'default')
	{
		$app                    = JFactory::getApplication();
		$input                  = $app->input;
		$srcs                   = HtmlHelper::framework();
		$srcs['FbListFilter']   = 'media/com_fabrik/js/listfilter.js';
		$srcs['AdvancedSearch'] = 'media/com_fabrik/js/advanced-search.js';
		$model                  = $this->getModel();
		$usersConfig            = JComponentHelper::getParams('com_fabrik');
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

		$params              = $model->getParams();
		$this->params        = $params;
		$pluginManager       = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$plugin              = $pluginManager->getPlugIn('calendar', 'visualization');
		$this->containerId   = $this->get('ContainerId');
		$this->filters       = $this->get('Filters');
		$this->showFilters   = $model->showFilters();
		$this->filterFormURL = $this->get('FilterFormURL');
		$tpl                 = $params->get('fusionchart_layout', 'bootstrap');
		$this->_setPath('template', JPATH_ROOT . '/plugins/fabrik_visualization/Fusionchart/Views/Fusionchart/tmpl/' . $tpl);

		HtmlHelper::stylesheetFromPath('plugins/fabrik_visualization/Fusionchart/Views/Fusionchart/tmpl/' . $tpl . '/template.css');

		// Assign something to Fabrik.blocks to ensure we can clear filters
		$ref = $model->getJSRenderContext();
		$js  = "$ref = {};";
		$js .= "\n" . "Fabrik.addBlock('$ref', $ref);";
		$js .= $model->getFilterJs();
		HtmlHelper::iniRequireJs($model->getShim());
		HtmlHelper::script($srcs, $js);
		$text = $this->loadTemplate();
		HtmlHelper::runContentPlugins($text);
		echo $text;
	}
}
