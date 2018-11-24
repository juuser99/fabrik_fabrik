<?php
/**
 * Determine whether automatically to create a group when a form or list is created
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\RadioField;

/**
 * Determine whether automatically to create a group when a form or list is created
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class AutocreateGroupField extends RadioField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'autocreategroup';

	/**
	 * Element name
	 * @var        string
	 *
	 * @since 4.0
	 */
	protected $name = 'AutoCreateGroup';

	/**
	 * Method to get the radio button field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		$this->value = $this->form->getValue('id') == 0 ? 1 : 0;

		return parent::getInput();
	}
}
