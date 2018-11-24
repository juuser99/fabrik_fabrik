<?php
/**
 * Fabrik Admin Import Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Table\Table;
use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Component\Fabrik\Administrator\Table\ListTable;

/**
 * Fabrik Admin Import Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ImportModel extends FabAdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_IMPORT';

	/**
	 * JTables to import
	 *
	 * @var Table[]
	 *
	 * @since 4.0
	 */
	protected $tables = array();

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  FabTable    A database object
	 *
	 * @since 4.0
	 */
	public function getTable($type = ListTable::class, $prefix = '', $config = array())
	{
		$sig = $type . $prefix . implode('.', $config);

		if (!array_key_exists($sig, $this->tables))
		{
			$config['dbo']      = Worker::getDbo(true);
			$this->tables[$sig] = FabTable::getInstance($type, $prefix, $config);
		}

		return $this->tables[$sig];
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.import', 'import', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$form->model = $this;

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed    The data for the form.
	 *
	 * @since 4.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.import.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
}
