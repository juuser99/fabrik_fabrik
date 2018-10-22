<?php
/**
 * Abstract Fabrik Admin model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Joomla\Component\Fabrik\Administrator\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Joomla\Component\Fabrik\Site\Model\PluginManagerModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * Abstract Fabrik Admin model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
abstract class FabAdminModel extends \Joomla\CMS\MVC\Model\AdminModel
{
	/**
	 * @var CMSApplication
	 *
	 * @since 4.0
	 */
	protected $app;

	/**
	 * @var User
	 *
	 * @since 4.0
	 */
	protected $user;

	/**
	 * @var Registry
	 *
	 * @since 4.0
	 */
	protected $config;

	/**
	 * @var Session
	 *
	 * @since 4.0
	 */
	protected $session;

	/**
	 * @var DatabaseDriver
	 *
	 * @since 4.0
	 */
	protected $db;

	/**
	 * @var PluginManagerModel
	 *
	 * @since 4.0
	 */
	protected $pluginManager;

	/**
	 * Component name
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $option = 'com_fabrik';

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   4.0
	 */
	public function __construct($config = array())
	{
		$this->app           = ArrayHelper::getValue($config, 'app', Factory::getApplication());
		$this->user          = ArrayHelper::getValue($config, 'user', Factory::getUser());
		$this->config        = ArrayHelper::getValue($config, 'config', Factory::getConfig());
		$this->session       = ArrayHelper::getValue($config, 'session', Factory::getSession());
		$this->db            = ArrayHelper::getValue($config, 'db', Factory::getDbo());
		$this->pluginManager = ArrayHelper::getValue($config, 'pluginManager',
			BaseDatabaseModel::getInstance(PluginManagerModel::class));

		parent::__construct($config);
	}

	/**
	 * Get the list's active/selected plug-ins
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function getPlugins()
	{
		$item = $this->getItem();

		// Load up the active plug-ins
		$plugins = FArrayHelper::getValue($item->params, 'plugins', array());

		return $plugins;
	}
}
