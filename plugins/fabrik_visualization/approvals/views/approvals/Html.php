<?php
/**
 * Approval HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Approvals\Views;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html as HtmlHelper;
use Fabrik\Helpers\Text;

use \JComponentHelper;
use \JFactory;
use \JViewLegacy;

/**
 * Approval HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @since       3.0.6
 */

class Html extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void|mixed  A string if successful, otherwise a JError object.
	 */

	public function display($tpl = 'default')
	{
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = $input->get('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->id = $id;
		$this->row = $this->get('Visualization');
		$this->rows = $this->get('Rows');
		$this->containerId = $this->get('ContainerId');
		$this->calName = $this->get('VizName');
		$this->params = $model->getParams();
		$tpl = 'bootstrap';
		$this->_setPath('template', JPATH_SITE . '/plugins/fabrik_visualization/Approvals/Views/Approvals/tmpl/' . $tpl);

		HtmlHelper::stylesheetFromPath('plugins/fabrik_visualization/Approvals/Views/Approvals/tmpl/' . $tpl . '/template.css');

		$ref = $model->getJSRenderContext();
		$js = "var $ref = new fbVisApprovals('approvals_" . $id . "');\n";
		$js .= "Fabrik.addBlock('" . $ref . "', $ref);\n";
		$js .= $model->getFilterJs();

		$srcs = HtmlHelper::framework();
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';
		$srcs['Approvals'] = 'plugins/fabrik_visualization/approvals/approvals.js';

		HtmlHelper::iniRequireJs($model->getShim());
		HtmlHelper::script($srcs, $js);

		$text = $this->loadTemplate();
		HtmlHelper::runContentPlugins($text);
		echo $text;
	}
}
