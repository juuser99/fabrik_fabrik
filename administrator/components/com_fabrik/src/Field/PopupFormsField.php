<?php
/**
 * Renders a list related forms that a db join element can be populated from
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Renders a list related forms that a db join element can be populated from
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class PopupFormsField extends ListField
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 *
	 * @since 4.0
	 */
	protected $name = 'Connections';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('f.id AS value, f.label AS text, l.id AS listid')->from('#__{package}_forms AS f')
			->join('LEFT', '#__{package}_lists As l ON f.id = l.form_id')
			->where('f.published = 1 AND l.db_table_name = ' . $db->quote($this->form->getValue('params.join_db_name')))->order('f.label');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList('value');

		if (empty($options))
		{
			$options[] = HTMLHelper::_('select.option', '', Text::_('COM_FABRIK_NO_POPUP_FORMS_AVAILABLE'));
		}

		return $options;
	}
}
