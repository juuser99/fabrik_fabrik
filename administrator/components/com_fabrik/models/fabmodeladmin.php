<?php
/**
 * Abstract Fabrik Admin model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.modeladmin');

/**
 * Abstract Fabrik Admin model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

abstract class FabModelAdmin extends JModelAdmin
{
	/**
	 * Component name
	 *
	 * @var  string
	 */
	protected $option = 'com_fabrik';

	/**
	 * @var JDatabaseDriver
	 */
	protected $db;

	/**
	 * Di Injected JApplication
	 *
	 * @var JApplicationCMS
	 */
	protected $app;

	/**
	 * Joomla config
	 *
	 * @var Registry
	 */
	protected $config;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// DI Injection
		$this->db = ArrayHelper::getValue($config, 'db', JFactory::getDbo());
		$this->app = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
		$this->config = ArrayHelper::getValue($config, 'config', JFactory::getConfig());
	}

	/**
	 * Get the list's active/selected plug-ins
	 *
	 * @return array
	 */
	public function getPlugins()
	{
		$item = $this->getItem();

		// Load up the active plug-ins
		$plugins = FArrayHelper::getValue($item->params, 'plugins', array());

		return $plugins;
	}
}
