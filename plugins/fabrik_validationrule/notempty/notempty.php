<?php
/**
 * Not Empty Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.notempty
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validationrule;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Not Empty Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.notempty
 * @since       3.0
 */
class Notempty extends Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'notempty';

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
		if (method_exists($this->elementModel, 'dataConsideredEmptyForValidation'))
		{
			$ok = $this->elementModel->dataConsideredEmptyForValidation($data, $repeatCounter);
		}
		else
		{
			$ok = $this->elementModel->dataConsideredEmpty($data, $repeatCounter);
		}

		return !$ok;
	}
}
