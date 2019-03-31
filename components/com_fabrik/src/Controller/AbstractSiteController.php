<?php
/**
 * Abstract Site Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Controller\ModelTrait;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerParser;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class AbstractSiteController extends BaseController
{
	use ModelTrait;

	/**
	 * Is the controller inside a content plug-in
	 *
	 * @var  bool
	 *
	 * @since 4.0
	 */
	public $isMambot = false;

	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var  int
	 *
	 * @since 4.0
	 */
	public $cacheId = 0;

	/**
	 * @var User
	 *
	 * @since 4.0
	 */
	protected $user;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $package;

	/**
	 * @var Session
	 *
	 * @since 4.0
	 */
	protected $session;

	/**
	 * @var Document
	 *
	 * @since 4.0
	 */
	protected $doc;

	/**
	 * @var DatabaseDriver
	 *
	 * @since 4.0
	 */
	protected $db;

	/**
	 * @var Registry
	 *
	 * @since 4.0
	 */
	protected $config;

	/**
	 * @var CMSApplication
	 * @since 4.0
	 */
	protected $app;

	/**
	 * AbstractSiteController constructor.
	 *
	 * @param array                    $config
	 * @param MVCFactoryInterface|null $factory
	 * @param null                     $app
	 * @param null                     $input
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		$this->app     = ArrayHelper::getValue($config, 'app', $app ?? Factory::getApplication());
		$this->user    = ArrayHelper::getValue($config, 'user', Factory::getUser());
		$this->package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$this->session = ArrayHelper::getValue($config, 'session', $this->app->getSession());
		$this->doc     = ArrayHelper::getValue($config, 'doc', $this->app->getDocument());
		$this->db      = ArrayHelper::getValue($config, 'db', Factory::getContainer()->get('DatabaseDriver'));
		$this->config  = ArrayHelper::getValue($config, 'config', $this->app->getConfig());

		parent::__construct($config, $factory, $this->app, $input);
	}

	/**
	 * Display the view
	 *
	 * @param bool  $cachable  If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param array $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  null
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		/** @var CMSApplication $app */
		$app     = Factory::getApplication();
		$input   = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// Menu links use fabriklayout parameters rather than layout
		$flayout = $input->get('fabriklayout');

		if ($flayout != '')
		{
			$input->set('layout', $flayout);
		}

		$document = $app->getDocument();

		$viewName  = $input->get('view', 'form');
		$modelName = $viewName;

		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		if ($viewName == 'details')
		{
			$viewName  = 'form';
			$modelName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($modelName))
		{
			$view->setModel($model, true);
		}

		// Display the view

		$view->error = $this->getError();

		if (Worker::useCache() && !$this->isMambot)
		{
			$user    = Factory::getUser();
			$uri     = Uri::getInstance();
			$uri     = $uri->toString(array('path', 'query'));
			$cacheid = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache   = Factory::getCache('com_' . $package, 'view');
			Html::addToSessionCacheIds($this->cacheId);
			echo $cache->get($view, 'display', $cacheid);
		}
		else
		{
			return $view->display();
		}
	}

	/**
	 * Plugin controller's use /Fabrik/Plugin namespace but view namespaces are generated with the Application's name
	 * which is either Site or Administrator by default which will create a namespace like
	 * /Fabrik/Plugin/FabrikList/Administrator/Email/Controller/EmailController which is bad. So we have to pass in
	 * the plugin as the $prefix so that the namespace is generated correctly.
	 *
	 * @param string $name
	 * @param string $prefix
	 * @param string $type
	 * @param array  $config
	 *
	 * @return \Joomla\CMS\MVC\View\AbstractView|null
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	protected function createView($name, $prefix = '', $type = '', $config = array())
	{
		$controllerClass = get_class($this);

		if (PluginControllerParser::isPluginController($controllerClass)) {
			$prefix = PluginControllerParser::getPluginFromControllerClass($controllerClass);

			return parent::createView($name, $prefix, $type, $config);
		}

		return parent::createView($name, $prefix, $type, $config);
	}

	protected function getError()
	{
		@trigger_error(
			sprintf(
				'%1$s::getError() is deprecated and no longer used.',
				self::class
			),
			E_USER_DEPRECATED
		);

	}
}