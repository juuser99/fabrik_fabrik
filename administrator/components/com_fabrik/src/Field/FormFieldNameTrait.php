<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

use Joomla\CMS\Version;

/**
 * @package     Joomla\Component\Fabrik\Administrator\Field
 *
 * @since       4.0
 */
trait FormFieldNameTrait
{
	/**
	 * Override Joomla's default without maintaining the whole class
	 *
	 * @param $fieldName
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function getName($fieldName)
	{
		// Check if there is special handling needed for a specific version
		// If we need this, add a new method such as getName40()
		$getter = 'getName' . JVERSION;
		if (method_exists($this, $getter))
		{
			return $this->$getter($fieldName);
		}

		$name = parent::getName($fieldName);

		// Field is not repeatable so return the default
		if (!$this->repeat)
		{
			return $name;
		}

		// To support repeated element, extensions can set this in plugin->onRenderSettings
		$repeatCounter = empty($this->form->repeatCounter) ? 0 : $this->form->repeatCounter;

		// Handle multiple fields
		if (substr($name, -2) === '[]')
		{
			$name = substr($name, 0, -2);
			$name .= '[' . $repeatCounter . '][]';

			return $name;
		}

		$name .= '[' . $repeatCounter . ']';

		return $name;
	}
}
