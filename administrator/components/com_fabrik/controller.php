<?php
/**
 * Main Fabrik administrator controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik master display controller.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminController extends JControllerLegacy
{
	/**
	 * Display the view
	 *
	 * @param   bool   $cachable   If true, the view output will be cached
	 * @param   array  $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  void
	 */

	public function display($cachable = false, $urlparams = false)
	{
		$this->default_view = 'home';
		require_once JPATH_COMPONENT . '/helpers/fabrik.php';
		parent::display();
	}

	/**
	 * Method to get a model object, loading it if required.
	 * 3.5 switch old 'save meta to db tables' model over to 'save meta to json file'
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional. (was defaulting to 'ignore_request' => false
	 *                            but that meant list order bys not being picked up.
	 *
	 * @since   3.5
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = '', $prefix = '', $config = array())
	{
		$jConfig = JComponentHelper::getParams('com_fabrik');
		$nameSuffix = $jConfig->get('meta_storage', 'db');

		if (empty($name))
		{
			$name = $this->getName();
		}

		$name .= $nameSuffix;

		return parent::getModel($name, $prefix, $config);
	}
}
