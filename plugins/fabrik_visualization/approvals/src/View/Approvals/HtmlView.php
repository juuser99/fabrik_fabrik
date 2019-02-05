<?php
/**
 * Approval HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Approvals\View\Approvals;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Plugin\FabrikVisualization\Approvals\Model\ApprovalsModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Approval HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
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
		/** @var ApprovalsModel $model */
		$model       = $this->getModel();
		$app         = Factory::getApplication();
		$input       = $app->input;
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$id          = $input->get('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->id          = $id;
		$this->row         = $this->get('Visualization');
		$this->rows        = $this->get('Rows');
		$this->containerId = $this->get('ContainerId');
		$this->calName     = $this->get('VizName');
		$this->params      = $model->getParams();
		$tpl               = 'bootstrap';
		$this->_setPath('template', JPATH_SITE . '/plugins/fabrik_visualization/approvals/tmpl/' . $tpl);

		Html::stylesheetFromPath('plugins/fabrik_visualization/approvals/tmpl/' . $tpl . '/template.css');

		$ref = $model->getJSRenderContext();
		$js  = "var $ref = new fbVisApprovals('approvals_" . $id . "');\n";
		$js  .= "Fabrik.addBlock('" . $ref . "', $ref);\n";
		$js  .= $model->getFilterJs();

		$srcs                 = Html::framework();
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';
		$srcs['Approvals']    = 'plugins/fabrik_visualization/approvals/approvals.js';

		Html::iniRequireJs($model->getShim());
		Html::script($srcs, $js);

		$text = $this->loadTemplate();
		Html::runContentPlugins($text, true);
		echo $text;
	}
}
