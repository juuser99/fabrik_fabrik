<?php
/**
 * FabForm controller class.
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

jimport('joomla.application.component.controllerform');

/**
 * FabForm controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabControllerForm extends JControllerForm
{
	/**
	 * Option
	 *
	 * @var string
	 */
	protected $option = 'com_fabrik';

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
		$this->input = $this->app->input;
	}

	/**
	 * Copy items
	 *
	 * @return  null
	 */
	public function copy()
	{
		$model = $this->getModel();
		$input = $this->app->input;
		$cid = $input->get('cid', array(), 'array');

		if (empty($cid))
		{
			$this->app->enqueueMessage(FText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'notice');
		}
		else
		{
			if ($model->copy())
			{
				$text = $this->text_prefix . '_N_ITEMS_COPIED';
				$this->setMessage(JText::plural($text, count($cid)));
			}
		}

		$extension = $input->get('extension');
		$extensionURL = ($extension) ? '&extension=' . $extension : '';
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $extensionURL, false));
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 * (sometimes required to avoid router collisions).
	 *
	 * @since   3.1
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 */
	public function edit($key = null, $urlVar = null)
	{
		$this->option = 'com_fabrik';

		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to get a model object, loading it if required.
	 * 3.5 switch old 'save meta to db tables' model over to 'save meta to json file'
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *                           was array('ignore_request' => true) - testing removing it
	 *
	 * @since   3.5
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = '', $prefix = '', $config = array())
	{
		if (empty($name))
		{
			$name = $this->context;
		}

		$config = JComponentHelper::getParams('com_fabrik');
		$nameSuffix = $config->get('meta_storage', 'db');
		$name .= $nameSuffix;

		return parent::getModel($name, $prefix, $config);
	}
}
