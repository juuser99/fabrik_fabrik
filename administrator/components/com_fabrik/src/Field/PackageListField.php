<?php
/**
 * Renders a repeating drop down list of packages
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
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Renders a repeating drop down list of packages
 *
 * @package     Fabrik
 * @subpackage  Form
 * @since       4.0
 */
class PackageListField extends ListField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'packagelist';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Packagelist';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$db    = Worker::getDbo();
		$query = $db->getQuery(true);
		$query->select("id AS value, CONCAT(label, '(', version , ')') AS " . $db->quote('text'));
		$query->from('#__{package}_packages');
		$query->order('value DESC');
		$db->setQuery($query);
		$rows     = $db->loadObjectList();
		$o        = new \stdClass;
		$o->value = 0;
		$o->text  = Text::_('COM_FABRIK_NO_PACKAGE');
		array_unshift($rows, $o);

		return $rows;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		if ($this->element['active'] == 1)
		{
			$this->element['readonly'] = '';
		}
		else
		{
			$this->element['readonly'] = 'true';
		}

		return parent::getInput();
	}
}
