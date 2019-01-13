<?php
/**
 * Renders a list of Bootstrap field class sizes
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
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

/**
 * Renders a list of Bootstrap field class sizes
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.5
 */
class BootstrapFieldClassField extends ListField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'bootstrapfieldclass';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$sizes   = array();
		$sizes[] = HTMLHelper::_('select.option', 'input-mini');
		$sizes[] = HTMLHelper::_('select.option', 'input-small');
		$sizes[] = HTMLHelper::_('select.option', 'input-medium');
		$sizes[] = HTMLHelper::_('select.option', 'input-large');
		$sizes[] = HTMLHelper::_('select.option', 'input-xlarge');
		$sizes[] = HTMLHelper::_('select.option', 'input-xxlarge');
		$sizes[] = HTMLHelper::_('select.option', 'input-block-level');
		$sizes[] = HTMLHelper::_('select.option', 'span1');
		$sizes[] = HTMLHelper::_('select.option', 'span2');
		$sizes[] = HTMLHelper::_('select.option', 'span3');
		$sizes[] = HTMLHelper::_('select.option', 'span4');
		$sizes[] = HTMLHelper::_('select.option', 'span5');
		$sizes[] = HTMLHelper::_('select.option', 'span6');
		$sizes[] = HTMLHelper::_('select.option', 'span7');
		$sizes[] = HTMLHelper::_('select.option', 'span8');
		$sizes[] = HTMLHelper::_('select.option', 'span9');
		$sizes[] = HTMLHelper::_('select.option', 'span10');
		$sizes[] = HTMLHelper::_('select.option', 'span11');
		$sizes[] = HTMLHelper::_('select.option', 'span12');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-1');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-2');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-3');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-4');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-5');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-6');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-7');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-8');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-9');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-10');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-11');
		$sizes[] = HTMLHelper::_('select.option', 'col-md-12');

		return $sizes;
	}
}
