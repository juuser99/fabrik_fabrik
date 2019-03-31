<?php
/**
 * Abstract Visualization Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Administrator\Table\VisualizationTable;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerParser;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\StringHelper as FStringHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Abstract Visualization Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class VisualizationController extends AbstractSiteController
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 *
	 * @since 4.0
	 */
	public $cacheId = 0;

	/**
	 * Display the view
	 *
	 * @param   boolean $cachable  If true, the view output will be cached - NOTE not actually used to control caching!!
	 * @param   array   $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  VisualizationController  A contoller object to support chaining.
	 *
	 * @since   12.2
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$input    = $this->input;
		$viewName = PluginControllerParser::getPluginFromControllerClass(get_class($this));

		if ($viewName == '')
		{
			/**
			 * if we are using a url like http://localhost/fabrik3.0.x/index.php?option=com_fabrik&view=visualization&id=6
			 * then we need to ascertain which viz to use
			 */
			$viewName = $this->getViewName();
		}

		$this->addViewPath(JPATH_SITE . '/plugins/fabrik_visualization/' . $viewName . '/src/View');

		$viewType = $this->doc->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		$extraQS = Worker::getMenuOrRequestVar('viz_extra_query_string', '', false, 'menu');
		$extraQS = ltrim($extraQS, '&?');
		$extraQS = FStringHelper::encodeqs($extraQS);

		if (!empty($extraQS))
		{
			foreach (explode('&', $extraQS) as $qsStr)
			{
				$parts = explode('=', $qsStr);
				$input->set($parts[0], $parts[1]);
				$_GET[$parts[0]] = $parts[1];
			}
		}

		// Push a model into the view
		if ($model = $this->getModel($viewName))
		{
			$view->setModel($model, true);
		}

		try
		{
			// F3 cache with raw view gives error
			if (!Worker::useCache())
			{
				$view->display();
			}
			else
			{
				// Build unique cache id on url, post and user id
				$user    = Factory::getUser();
				$uri     = Uri::getInstance();
				$uri     = $uri->toString(array('path', 'query'));
				$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
				$cache   = Factory::getCache('com_fabrik', 'view');
				$cache->get($view, 'display', $cacheId);
				Html::addToSessionCacheIds($cacheId);
			}
		}
		catch (\RuntimeException $exception)
		{
			echo 'ERROR: '.$exception->getMessage();
		}

		return $this;
	}

	/**
	 * Override of j!'s getView
	 *
	 * Method to get a reference to the current view and load it if necessary.
	 *
	 * @param   string $name   The view name. Optional, defaults to the controller name.
	 * @param   string $type   The view type. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config Configuration array for view. Optional.
	 *
	 * @return  object  Reference to the view or an error.
	 *
	 * @since 4.0
	 */
	public function getView($name = '', $type = '', $prefix = '', $config = array())
	{
		$viewName = PluginControllerParser::getPluginFromControllerClass(get_class($this));
		$viewName = $viewName === '' ? $this->getViewName() : $name;

		$config['base_path'] = JPATH_PLUGINS.'/plugins/fabrik_visualization/'.strtolower($viewName);

		return parent::getView($viewName, $type, $viewName, $config);
	}

	/**
	 * If loading via id then we want to get the view name and add the plugin view and model paths
	 *
	 * @return   string  view name
	 *
	 * @since 4.0
	 */
	protected function getViewName()
	{
		/** @var VisualizationTable $viz */
		$viz = FabrikTable::getInstance(VisualizationTable::class);
		$viz->load($this->input->getInt('id'));
		$viewName = $viz->plugin;

		return $viewName;
	}

}
