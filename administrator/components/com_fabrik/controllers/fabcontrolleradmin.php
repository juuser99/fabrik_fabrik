<?php
/**
 * Extends JControllerAdmin allowing for confirmation of removal of
 * items, along with call to model to perform additional
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controlleradmin');

/**
 * List controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabControllerAdmin extends JControllerAdmin
{
	/**
	 * Component name
	 *
	 * @var string
	 */
	public $option = 'com_fabrik';

	/**
	 * JApplication object
	 *
	 * @var JApplicationCms
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// DI inject the app
		$this->app = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
	}

	/**
	 * Actually delete the requested items forms etc.
	 *
	 * @return null
	 */
	public function dodelete()
	{
		parent::delete();
	}

	/**
	 * Method to get a model object, loading it if required.
	 * 3.5 switch old 'save meta to db tables' model over to 'save meta to json file'
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @since   3.5
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true))
	{
		$config = JComponentHelper::getParams('com_fabrik');
		$nameSuffix = $config->get('meta_storage', 'db');
		$name .= $nameSuffix;

		return parent::getModel($name, $prefix, $config);
	}
}
