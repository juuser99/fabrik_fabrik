<?php
/**
 * Is Alpha Numeric Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isalphanumeric
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Component\Fabrik\Site\Plugin\AbstractValidationRulePlugin;

/**
 * Is Alpha Numeric Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isalphanumeric
 * @since       3.0
 */
class PlgFabrik_ValidationruleIsalphanumeric extends AbstractValidationRulePlugin
{
	/**
	 * Plugin name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $pluginName = 'isalphanumeric';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 *
	 * @since 4.0
	 */
	public function validate($data, $repeatCounter)
	{
		// Could be a drop-down with multi-values
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		if ($data == '')
		{
			return false;
		}

		// Not a word character
		preg_match('/[^\w\s]/', $data, $matches);

		return empty($matches) ? true : false;
	}
}
