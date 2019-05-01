<?php
/**
 * Visualization View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\Visualization;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Component\Fabrik\Site\Model\VisualizationModel;
use Fabrik\Component\Fabrik\Site\View\AbstractView;
use Fabrik\Helpers\Html;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * HTML Partial Fabrik Visualization view class. Renders HTML without <head> or wrapped in <body>
 * Any Ajax request requiring HTML should add "&foramt=partial" to the URL. This avoids us
 * potentially reloading jQuery in the <head> which is problematic as that replaces the main page's
 * jQuery object and removes any additional functions that had previously been assigned
 * such as JQuery UI, or fullcalendar
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class PartialView extends AbstractView
{
	/**
	 * Display
	 *
	 * @param string $tmpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		$srcs  = Html::framework();
		$input = $this->app->input;
		Html::script($srcs);
		/** @var VisualizationModel $model */
		$model       = $this->getModel();
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->get('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$visualization = $model->getVisualization();
		$params        = $model->getParams();
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabrikModel::getInstance(PluginManagerModel::class);
		$plugin        = $pluginManager->getPlugIn($visualization->plugin, 'visualization');
		$plugin->setRow($visualization);

		if ($visualization->published == 0)
		{
			$this->app->enqueueMessage(Text::_('COM_FABRIK_SORRY_THIS_VISUALIZATION_IS_UNPUBLISHED'), 'error');

			return;
		}

		// Plugin is basically a model
		$pluginTask = $input->get('plugintask', 'render', 'request');

		// @FIXME cant set params directly like this, but I think plugin model setParams() is not right
		$plugin->params = $params;
		$tmpl           = $plugin->getParams()->get('calendar_layout', $tmpl);
		$plugin->$pluginTask($this);
		$this->plugin = $plugin;
		$this->addTemplatePath($this->_basePath . '/plugins/' . $this->_name . '/' . $plugin->getName() . '/tmpl/' . $tmpl);

		$root = $this->app->isClient('administrator') ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$this->addTemplatePath($root . '/templates/' . $this->app->getTemplate() . '/html/com_fabrik/visualization/' . $plugin->getName() . '/' . $tmpl);
		$ab_css_file = JPATH_SITE . '/plugins/fabrik_visualization/' . $plugin->getName() . '/tmpl/' . $tmpl . '/template.css';

		if (File::exists($ab_css_file))
		{
			HTMLHelper::stylesheet('template.css', 'plugins/fabrik_visualization/' . $plugin->getName() . '/tmpl/' . $tmpl . '/', true);
		}

		echo parent::display();
	}

	/**
	 * Just for plugin
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setId()
	{
	}
}
