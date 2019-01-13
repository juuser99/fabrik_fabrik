<?php
/**
 * Admin Connection Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Fabrik\Component\Fabrik\Administrator\Table\ConnectionTable;
use Fabrik\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Component\Fabrik\Site\Model\ConnectionModel as SiteConnectionModel;

/**
 * Admin Connection Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class ConnectionModel extends FabAdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTION';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  ConnectionTable  A database object
	 *
	 * @since 4.0
	 */
	public function getTable($type = ConnectionTable::class, $prefix = '', $config = array())
	{
		/**
		 * not sure if we should be loading JTable or FabTable here
		 * issue with using Fabtable is that it will always load the cached version of the cnn
		 * which might cause issues when migrating from test to live sites???
		 */
		$config['dbo'] = Worker::getDbo(true);

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array  $data      Data for the form.
	 * @param   bool   $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed	A JForm object on success, false on failure
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.connection', 'connection', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed	The data for the form.
	 *
	 * @since 4.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.connection.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to set the default item
	 *
	 * @param   int  $id  of connection to set as default
	 *
	 * @return  boolean	 True on success.
	 *
	 * @since 4.0
	 */
	public function setDefault($id)
	{
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->update('#__fabrik_connections')->set($db->quoteName('default') . ' = 0');
		$db->setQuery($query);
		$db->execute();
		$query->clear();
		$query->update('#__fabrik_connections')->set($db->quoteName('default') . ' = 1')->where('id = ' . (int) $id);
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Check if connection is the default and if so reset its values to those of the J db connection
	 *
	 * @param   object  &$item  connection item
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function checkDefault(&$item)
	{
		if ($item->id == 1)
		{
			$this->app->enqueueMessage(Text::_('COM_FABRIK_ORIGINAL_CONNECTION'));

			if (!$this->matchesDefault($item))
			{
				$item->host = $this->config->get('host');
				$item->user = $this->config->get('user');
				$item->password = $this->config->get('password');
				$item->database = $this->config->get('db');

				Log::add(Text::_('COM_FABRIK_YOU_MUST_SAVE_THIS_CNN'), Log::WARNING, 'jerror');
			}
		}
	}

	/**
	 * Do the item details match the J db connection details
	 *
	 * @param   object  $item  connection item
	 *
	 * @return  bool  matches or not
	 *
	 * @since 4.0
	 */
	protected function matchesDefault($item)
	{
		$config = $this->config;
		$crypt = Worker::getCrypt();
		$pwMatch = $config->get('password') === $item->password || $config->get('password') === $crypt->decrypt($item->password);

		return $config->get('host') == $item->host && $config->get('user') == $item->user && $pwMatch
			&& $config->get('db') == $item->database;
	}

	/**
	 * Save the connection- test first if its valid
	 * if it is remove the session instance of the connection then call parent save
	 *
	 * @param   array  $data  connection data
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since 4.0
	 */
	public function save($data)
	{
		$model = FabModel::getInstance(SiteConnectionModel::class);
		$model->setId($data['id']);
		$crypt = Worker::getCrypt();

		$params = new \stdClass;
		$params->encryptedPw = true;
		$data['params'] = json_encode($params);
		$data['password'] = $crypt->encrypt($data['password']);
		$options = $model->getConnectionOptions(ArrayHelper::toObject($data));
		$db = $model->getDriverInstance($options);
		$key = 'fabrik.connection.' . $data['id'];
		$this->session->clear($key);

		return parent::save($data);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @since 4.0
	 */
	public function validate($form, $data, $group = null)
	{
		if ($data['password'] !== $data['passwordConf'])
		{
			$this->setError(Text::_('COM_FABRIK_PASSWORD_MISMATCH'));

			return false;
		}

		return parent::validate($form, $data);
	}
}
