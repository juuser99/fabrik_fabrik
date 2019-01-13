<?php
/**
 * Connections controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Fabrik\Administrator\Model\ConnectionModel;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * Connections list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ConnectionsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTIONS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'connections';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see		JController
	 *
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unsetDefault', 'setDefault');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @since	1.6
	 *
	 * @return  ConnectionModel
	 */
	public function getModel($name = ConnectionModel::class, $prefix = '')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to set the home property for a list of items
	 *
	 * @since	1.6
	 *
	 * @return null
	 */
	public function setDefault()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		$app = Factory::getApplication();
		$input = $app->input;

		// Get items to publish from the request.
		$cid = $input->get('cid', array(), 'array');
		$data = array('setDefault' => 1, 'unsetDefault' => 0);
		$task = $this->getTask();
		$value = FArrayHelper::getValue($data, $task, 0, 'int');

		if ($value == 0)
		{
			$this->setMessage(Text::_('COM_FABRIK_CONNECTION_CANT_UNSET_DEFAULT'));
		}

		if (empty($cid))
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			if ($value != 0)
			{
				$cid = $cid[0];

				// Get the model.
				$model = $this->getModel();

				// Publish the items.
				if (!$model->setDefault($cid, $value))
				{
					Log::add($model->getError(), Log::WARNING, 'jerror');
				}
				else
				{
					$this->setMessage(Text::_('COM_FABRIK_CONNECTION_SET_DEFAULT'));
				}
			}
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
}
