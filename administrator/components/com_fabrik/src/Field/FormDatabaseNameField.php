<?php
/**
 * Renders the form's database name or a field to create one
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Form\Field\TextField;

/**
 * Renders the form's database name or a field to create one
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class FormDatabaseNameField extends TextField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'formdatabasename';

	/**
	 * Element name
	 * @var        string
	 *
	 * @since 4.0
	 */
	protected $name = 'FormDatabaseName';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		if ($this->form->getValue('record_in_database'))
		{
			$db    = Worker::getDbo(true);
			$query = $db->getQuery(true);
			$id    = (int) $this->form->getValue('id');
			$query->select('db_table_name')->from('#__{package}_lists')->where('form_id = ' . $id);
			$db->setQuery($query);
			$this->element['readonly'] == true;
			$this->element['class'] = 'readonly';
			$this->value            = $db->loadResult();
		}

		return parent::getInput();
	}
}
