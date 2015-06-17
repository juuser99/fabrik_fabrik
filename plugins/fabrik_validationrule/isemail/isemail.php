<?php
/**
 * Is Email Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isemail
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validation;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;

/**
 * Is Email Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isemail
 * @since       3.5
 */
class IsEmail extends Validation
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isemail';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		$email = $data;

		// Could be a dropdown with multivalues
		if (is_array($email))
		{
			$email = implode('', $email);
		}

		// Decode as it can be posted via ajax
		$email = urldecode($email);
		$params = $this->getParams();
		$allow_empty = $params->get('isemail-allow_empty');

		if ($allow_empty == '1' and empty($email))
		{
			return true;
		}

		// $$$ hugh - let's try using new helper func instead of rolling our own.
		return Worker::isEmail($email);
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overridden on per-validation basis (such as isnumeric)
	 *
	 * @return  bool
	 */
	protected function allowEmpty()
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isemail-allow_empty');

		return $allow_empty == '1';
	}
}
