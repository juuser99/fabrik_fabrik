<?php
/**
 * Renders a list of ascending / descending options
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Renders a list of ascending / descending options
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class AscendingDescendingField extends ListField
{
	use FormFieldNameTrait;

	/**
	 * Element name
	 * @var        string
	 *
	 * @since 4.0
	 */
	protected $name = 'Ascendingdescending';

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'ascendingdescending';

	/**
	 * Method to get the field options.
	 *
	 * @return  array    The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$opts[] = HTMLHelper::_('select.option', 'ASC', Text::_('COM_FABRIK_ASCENDING'));
		$opts[] = HTMLHelper::_('select.option', 'DESC', Text::_('COM_FABRIK_DESCENDING'));

		return $opts;
	}
}
