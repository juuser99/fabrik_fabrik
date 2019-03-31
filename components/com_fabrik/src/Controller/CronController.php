<?php
/**
 * Cron Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Table\CronTable;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerParser;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;


/**
 * Cron Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class CronController extends AbstractSiteController
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
	 * View name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $viewName = null;

	/**
	 * Display the view
	 *
	 * @param boolean       $cachable  If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param array|boolean $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  $this  A JController object to support chaining.
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$document = $this->app->getDocument();
		$viewName = $this->getViewName();
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($viewName))
		{
			$view->setModel($model, true);
		}
		// Display the view
		$view->error = $this->getError();

		$input = Factory::getApplication()->input;
		$task  = $input->getCmd('task');

		if (!strstr($task, '.'))
		{
			$task = 'display';
		}
		else
		{
			$task = explode('.', $task);
			$task = array_pop($task);
		}

		// F3 cache with raw view gives error
		if (!Worker::useCache())
		{
			$view->$task();
		}
		else
		{
			$post = $input->get('post');

			// Build unique cache id on url, post and user id
			$user = Factory::getUser();

			$uri     = Uri::getInstance();
			$uri     = $uri->toString(array('path', 'query'));
			$cacheId = serialize(array($uri, $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache   = Factory::getCache('com_fabrik', 'view');
			$cache->get($view, 'display', $cacheId);
			Html::addToSessionCacheIds($cacheId);
		}
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
		if (!isset($this->viewName))
		{
			$app   = Factory::getApplication();
			$input = $app->input;
			/** @var CronTable $item */
			$item  = FabrikTable::getInstance(CronTable::class);
			$item->load($input->getInt('id'));
			$this->viewName = $item->plugin;
		}

		return $this->viewName;
	}

	/**
	 * Override of j!'s getView
	 *
	 * Method to get a reference to the current view and load it if necessary.
	 *
	 * @param string $name   The view name. Optional, defaults to the controller name.
	 * @param string $type   The view type. Optional.
	 * @param string $prefix The class prefix. Optional.
	 * @param array  $config Configuration array for view. Optional.
	 *
	 * @return  object  Reference to the view or an error.
	 *
	 * @since 4.0
	 */
	public function getView($name = '', $type = '', $prefix = '', $config = array())
	{
		$viewName = PluginControllerParser::getPluginFromControllerClass(get_class($this));
		$viewName = $viewName === '' ? $this->getViewName() : $name;

		$config['base_path'] = JPATH_PLUGINS . '/plugins/fabrik_cron/' . strtolower($viewName);

		return parent::getView($viewName, $type, $prefix, $config);
	}
}
