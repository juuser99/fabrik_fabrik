<?php
/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validation;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;
use \JUserHelper;

/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @since       3.5
 */
class UserExists extends Validation
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'userexists';

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
		$params = $this->getParams();
		$elementModel = $this->elementModel;

		// As ornot is a radio button it gets json encoded/decoded as an object
		$orNot = $params->get('userexists_or_not', 'fail_if_exists');
		jimport('joomla.user.helper');
		$result = JUserHelper::getUserId($data);

		if ($this->user->get('guest'))
		{
			if (!$result)
			{
				if ($orNot == 'fail_if_exists')
				{
					return true;
				}
			}
			else
			{
				if ($orNot == 'fail_if_not_exists')
				{
					return true;
				}
			}

			return false;
		}
		else
		{
			if (!$result)
			{
				if ($orNot == 'fail_if_exists')
				{
					return true;
				}
			}
			else
			{
				$user_field = $params->get('userexists_user_field');
				$user_id = 0;

				if ((int) $user_field !== 0)
				{
					$user_elementModel = Worker::getPluginManager()->getElementPlugin($user_field);
					$user_fullName = $user_elementModel->getFullName(true, false);
					$user_field = $user_elementModel->getFullName(false, false);
				}

				if (!empty($user_field))
				{
					// $$$ the array thing needs fixing, for now just grab 0
					$formData = $elementModel->getFormModel()->formData;
					$user_id = ArrayHelper::getValue($formData, $user_fullName . '_raw', ArrayHelper::getValue($formData, $user_fullName, ''));

					if (is_array($user_id))
					{
						$user_id = ArrayHelper::getValue($user_id, 0, '');
					}
				}

				if ($user_id != 0)
				{
					if ($result == $user_id)
					{
						return ($orNot == 'fail_if_exists') ? true : false;
					}

					return false;
				}
				else
				{
					// The connected user is editing his own data
					if ($result == $this->user->get('id'))
					{
						return ($orNot == 'fail_if_exists') ? true : false;
					}

					return false;
				}
			}

			return false;
		}
	}
}
