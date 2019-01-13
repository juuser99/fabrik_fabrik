<?php
/**
 * Raw Visualization controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Administrator\Table\VisualizationTable;

/**
 * Raw Visualization controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class VisualizationRawController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_VISUALIZATION';

	/**
	 * @var string
	 *
	 * @since since 4.0
	 */
	protected $context = 'visualization';

	/**
	 * Called via ajax to perform viz ajax task (defined by plugintask method)
	 *
	 * @param   boolean $cachable  If true, the view output will be cached
	 * @param   boolean $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$id    = $input->getInt('visualizationid');
		$viz   = FabrikTable::getInstance(VisualizationTable::class);
		$viz->load($id);
		FabrikModel::addIncludePath(JPATH_SITE . '/plugins/fabrik_visualization/' . $viz->plugin . '/models');
		$model = $this->getModel($viz->plugin);
		$model->setId($id);
		$pluginTask = $input->get('plugintask', '', 'request');

		if ($pluginTask !== '')
		{
			echo $model->$pluginTask();
		}
		else
		{
			$task = $input->get('task');

			$path = JPATH_SITE . '/plugins/fabrik_visualization/' . $viz->plugin . '/controllers/' . $viz->plugin . '.php';

			if (file_exists($path))
			{
				require_once $path;
			}
			else
			{
				throw new \RuntimeException('Could not load visualization: ' . $viz->plugin);
			}

			$controllerName = 'FabrikControllerVisualization' . $viz->plugin;
			$controller     = new $controllerName;
			$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_visualization/' . $viz->plugin . '/views');
			$controller->addViewPath(COM_FABRIK_FRONTEND . '/views');

			// Add the model path
			FabrikModel::addIncludePath(JPATH_SITE . '/plugins/fabrik_visualization/' . $viz->plugin . '/models');
			FabrikModel::addIncludePath(COM_FABRIK_FRONTEND . '/models');

			$input->set('visualizationid', $id);
			$controller->$task();
		}

		return $this;
	}

	/**
	 * Get html for viz plugin
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function getPluginHTML()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$plugin = $input->get('plugin');
		$model  = $this->getModel();
		$model->getForm();
		echo $model->getPluginHTML($plugin);
	}
}
