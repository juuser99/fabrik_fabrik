<?php
/**
 * Fabrik Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Fabrik\Component\Fabrik\Administrator\Model\TableTrait;
use Joomla\Registry\Registry;
use Joomla\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * @package     Fabrik\Component\Fabrik\Site\Model
 *
 * @since       4.0
 */
class FabSiteModel extends BaseDatabaseModel
{
	use TableTrait;

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
	 *
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
}
