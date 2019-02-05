<?php
/**
 * Slideshow vizualization: view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Slideshow\View\Slideshow;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Helpers\Html;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Slideshow Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
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
		$app         = Factory::getApplication();
		$input       = $app->input;
		$srcs        = Html::framework();
		$model       = $this->getModel();
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->js     = $this->get('JS');
		$viewName     = $this->getName();
		$params       = $model->getParams();
		$this->params = $params;
		/** @var PluginManagerModel $pluginManager */
		$pluginManager        = FabrikModel::getInstance(PluginManagerModel::class);
		$plugin               = $pluginManager->getPlugIn('slideshow', 'visualization');
		$this->showFilters    = $model->showFilters();
		$this->filters        = $this->get('Filters');
		$this->filterFormURL  = $this->get('FilterFormURL');
		$this->params         = $model->getParams();
		$this->containerId    = $this->get('ContainerId');
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';

		if ($this->get('RequiredFiltersFound'))
		{
			$srcs['Slideshow2'] = 'components/com_fabrik/libs/slideshow2/js/slideshow.js';
			$mode               = $params->get('slideshow_viz_type', 1);

			switch ($mode)
			{
				case 1:
					break;
				case 2:
					$srcs['Kenburns'] = 'components/com_fabrik/libs/slideshow2/js/slideshow.kenburns.js';
					break;
				case 3:
					$srcs['Push'] = 'components/com_fabrik/libs/slideshow2/js/slideshow.push.js';
					break;
				case 4:
					$srcs['Fold'] = 'components/com_fabrik/libs/slideshow2/js/slideshow.fold.js';
					break;
				default:
					break;
			}

			JHTML::stylesheet('components/com_fabrik/libs/slideshow2/css/slideshow.css');
			$srcs['SlideShow'] = 'plugins/fabrik_visualization/slideshow/slideshow.js';
		}

		Html::slimbox();
		Html::iniRequireJs($model->getShim());
		Html::script($srcs, $this->js);

		//Html::slimbox();

		$tpl      = $params->get('slideshow_viz_layout', 'bootstrap');
		$tmplpath = $model->pathBase . 'slideshow/tmpl/slideshow/' . $tpl;
		$this->_setPath('template', $tmplpath);
		Html::stylesheetFromPath('plugins/fabrik_visualization/slideshow/tmpl/slideshow/' . $tpl . '/template.css');
		Html::stylesheetFromPath('plugins/fabrik_visualization/slideshow/tmpl/slideshow/' . $tpl . '/custom.css');
		echo parent::display();
	}
}
