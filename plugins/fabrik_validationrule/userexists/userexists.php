<?php
/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\User\UserHelper;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractValidationRulePlugin;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @since       3.0
 */
class PlgFabrik_ValidationruleUserExists extends AbstractValidationRulePlugin
{
	/**
	 * Plugin name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $pluginName = 'userexists';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string $data          To check
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 *
	 * @since 4.0
	 */
	public function validate($data, $repeatCounter)
	{
		$params        = $this->getParams();
		$elementPlugin = $this->elementPlugin;

		// As ornot is a radio button it gets json encoded/decoded as an object
		$orNot  = $params->get('userexists_or_not', 'fail_if_exists');
		$result = UserHelper::getUserId($data);

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
				$userField = $params->get('userexists_user_field');
				$userId    = 0;

				if ((int) $userField !== 0)
				{
					$userElementModel = Worker::getPluginManager()->getElementPlugin($userField);
					$userFullName     = $userElementModel->getFullName(true, false);
					$userField        = $userElementModel->getFullName(false, false);
				}

				if (!empty($userField))
				{
					// $$$ the array thing needs fixing, for now just grab 0
					$formData = $elementPlugin->getForm()->formData;
					$userId   = FArrayHelper::getValue($formData, $userFullName . '_raw', FArrayHelper::getValue($formData, $userFullName, ''));

					if (is_array($userId))
					{
						$userId = FArrayHelper::getValue($userId, 0, '');
					}
				}

				if ($userId != 0)
				{
					if ($result == $userId)
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
