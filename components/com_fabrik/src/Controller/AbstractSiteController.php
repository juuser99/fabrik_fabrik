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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class AbstractSiteController extends BaseController
{
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
	 * Constructor
	 *
	 * @param   array $config A named configuration array for object construction.
	 *
	 * @since 4.0
	 *
	 * @throws \Exception
	 */
	public function __construct($config = array())
	{
		$this->app     = ArrayHelper::getValue($config, 'app', Factory::getApplication());
		$this->user    = ArrayHelper::getValue($config, 'user', Factory::getUser());
		$this->package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$this->session = ArrayHelper::getValue($config, 'session', $this->app->getSession());
		$this->doc     = ArrayHelper::getValue($config, 'doc', $this->app->getDocument());
		$this->db      = ArrayHelper::getValue($config, 'db', Factory::getContainer()->get('DatabaseDriver'));
		$this->config  = ArrayHelper::getValue($config, 'config', $this->app->getConfig());

		parent::__construct($config);
	}
}