<?php
/**
 * Fabrik Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Registry\Registry;
use Joomla\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * Fabrik Element List Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class FabModel extends BaseDatabaseModel
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
	 * @var Date
	 *
	 * @since 4.0
	 */
	protected $date;

	/**
	 * App name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $package = 'fabrik';

	/**
	 * @var Registry
	 *
	 * @since 4.0
	 */
	protected $config;

	/**
	 * @var Language
	 *
	 * @since 4.0
	 */
	protected $lang;

	/**
	 * @var Session
	 * @since 4.0
	 */
	protected $session;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   4.0
	 * @throws  \Exception
	 */
	public function __construct($config = array())
	{
		$this->app     = ArrayHelper::getValue($config, 'app', Factory::getApplication());
		$this->user    = ArrayHelper::getValue($config, 'user', Factory::getUser());
		$this->config  = ArrayHelper::getValue($config, 'config', $this->app->getConfig());
		$this->session = ArrayHelper::getValue($config, 'session', $this->app->getSession());
		$this->date    = ArrayHelper::getValue($config, 'date', Factory::getDate());
		$this->lang    = ArrayHelper::getValue($config, 'lang', Factory::getLanguage());
		$this->package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		parent::__construct($config);
	}

	/**
	 * Method to load and return a model object.
	 *
	 * @param   string  $name    The name of the view
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  configuration
	 *
	 * @return	FabTable|false	Model object or boolean false if failed
	 *
	 * @since 4.0
	 */
	protected function _createTable($name, $prefix = 'Table', $config = array())
	{
		// Clean the model name
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		// Make sure we are returning a DBO object
		if (!array_key_exists('dbo', $config))
		{
			$config['dbo'] = $this->getDbo();
		}

		return FabTable::getInstance($name, $prefix, $config);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return	Table	The table
	 *
	 * @since 4.0
	 *
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if (empty($name))
		{
			$name = $this->getName();
		}

		if ($table = $this->_createTable($name, $prefix, $options))
		{
			return $table;
		}

		throw new \RuntimeException(Text::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name));
	}
}
