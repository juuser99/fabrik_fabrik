<?php
/**
 * Fabrik Cron View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\CronModel;
use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractCronPlugin;
use Fabrik\Component\Fabrik\Site\View\AbstractView;
use Fabrik\Helpers\Html;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Cron view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends AbstractView
{
	/**
	 * Display
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 *              
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		// Not sure this is even used because it has code for visualizations
		die('is this used?');

		/*
		$srcs  = Html::framework();
		$input = $this->app->input;
		Html::script($srcs);
		$model       = $this->getModel();
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$visualization = $model->getVisualization();
		$pluginParams  = $model->getPluginParams();

		$pluginManager = FabrikModel::getInstance(PluginManagerModel::class);
		$plugin        = $pluginManager->getPlugIn($visualization->plugin, 'visualization');
		$plugin->_row  = $visualization;

		if ($visualization->published == 0)
		{
			$this->app->enqueueMessage(Text::_('COM_FABRIK_SORRY_THIS_VISUALIZATION_IS_UNPUBLISHED'), 'warning');

			return '';
		}

		// Plugin is basically a model
		$pluginTask = $input->get('plugintask', 'render', 'request');

		// @FIXME cant set params directly like this, but I think plugin model setParams() is not right
		$plugin->_params = $pluginParams;
		$tmpl            = $plugin->getParams()->get('calendar_layout', $tmpl);
		$plugin->$pluginTask($this);
		$this->plugin = $plugin;
		$this->addTemplatePath($this->_basePath . '/plugins/' . $this->_name . '/' . $plugin->_name . '/tmpl/' . $tmpl);
		$root = $this->app->isClient('administrator') ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$this->addTemplatePath($root . '/templates/' . $this->app->getTemplate() . '/html/com_fabrik/visualization/' . $plugin->_name . '/' . $tmpl);
		$ab_css_file = JPATH_SITE . '/plugins/fabrik_visualization/' . $plugin->_name . '/tmpl/' . $tmpl . '/template.css';

		if (File::exists($ab_css_file))
		{
			HTMLHelper::stylesheet('template.css', 'plugins/fabrik_visualization/' . $plugin->_name . '/tmpl/' . $tmpl . '/', true);
		}

		echo parent::display();
		*/
	}

	/**
	 * Just for plugin
	 *
	 * @return  void
	 */
	public function setId()
	{
	}
}
