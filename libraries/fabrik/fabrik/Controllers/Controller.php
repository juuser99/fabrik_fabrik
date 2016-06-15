<?php
/**
 * Created by PhpStorm.
 * User: rob
 * Date: 24/05/2016
 * Time: 09:56
 */

namespace Fabrik\Controllers;

use \Joomla\Utilities\ArrayHelper;
use \JFactory;

// @todo - remove when we namespace the list model and place in library
require_once JPATH_COMPONENT . '/models/list.php';

class Controller extends \JControllerLegacy
{
	/**
	 * @var JApplicationCMS
	 */
	protected $app;

	/**
	 * @var JUser
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $package;

	/**
	 * @var JSession
	 */
	protected $session;

	/**
	 * @var JDocument
	 */
	protected $doc;

	/**
	 * @var JDatabaseDriver
	 */
	protected $db;

	/**
	 * @var Registry
	 */
	protected $config;

	/**
	 * Constructor
	 *
	 * @param   array $config A named configuration array for object construction.
	 *
	 */
	public function __construct($config = array())
	{
		$this->app     = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
		$this->user    = ArrayHelper::getValue($config, 'user', JFactory::getUser());
		$this->package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$this->session = ArrayHelper::getValue($config, 'session', JFactory::getSession());
		$this->doc     = ArrayHelper::getValue($config, 'doc', JFactory::getDocument());
		$this->db      = ArrayHelper::getValue($config, 'db', JFactory::getDbo());
		$this->config  = ArrayHelper::getValue($config, 'config', JFactory::getConfig());
		parent::__construct($config);
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   12.2
	 */
	public function getModel($name = '', $prefix = '', $config = array())
	{
		return parent::getModel($name, $prefix, $config);

		// For now any controller that actually has a Fabrik\Model\Foo class
		// should override getModel() as follows:
		//$class = 'Fabrik\\Models\\' . $name;
		//return new $class;
	}
}