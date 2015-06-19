<?php
/**
 * Email Already Registered Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.emailexists
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validation;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Text;

/**
 * Email Already Registered Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.emailexists
 * @since       3.5
 */
class EmailExists extends Validation
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'emailexists';

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
		if (empty($data))
		{
			return false;
		}

		if (is_array($data))
		{
			$data = $data[0];
		}

		$params = $this->getParams();
		$elementModel = $this->elementModel;
		$orNot = $params->get('emailexists_or_not', 'fail_if_exists');
		$user_field = $params->get('emailexists_user_field');
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

		jimport('joomla.user.helper');
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__users')->where('email = ' . $db->q($data));
		$db->setQuery($query);
		$result = $db->loadResult();

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
				return ($orNot == 'fail_if_exists') ? true : false;
			}
			else
			{
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
					if ($result == $this->user->get('id')) // The connected user is editing his own data
					{
						return ($orNot == 'fail_if_exists') ? true : false;
					}

					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @return	string	label
	 */
	protected function getLabel()
	{
		$params = $this->getParams();
		$cond = $params->get('emailexists_or_not');

		if ($cond == 'fail_if_not_exists')
		{
			return Text::_('PLG_VALIDATIONRULE_EMAILEXISTS_LABEL_NOT');
		}
		else
		{
			return parent::getLabel();
		}
	}
}
