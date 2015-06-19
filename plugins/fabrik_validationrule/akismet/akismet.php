<?php
/**
 * Akismet Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.akismet
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validation;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \RuntimeException;
use \JUri;


/**
 * Akismet Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.akismet
 * @since       3.5
 */
class Akismet extends Validation
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'akismet';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @throws RuntimeException
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		$params = $this->getParams();

		if ($params->get('akismet-key') != '')
		{
			$username = $this->user->get('username') != '' ? $this->user->get('username') : $this->_randomString();
			require_once JPATH_SITE . '/plugins/fabrik_validationrule/akismet/libs/akismet.class.php';
			$comment = array('author' => $username, 'email' => $this->user->get('email'), 'website' => JUri::base(), 'body' => $data);
			$validator = new \Akismet(JURI::base(), $params->get('akismet-key'), $comment);

			if ($validator->errorsExist())
			{
				throw new RuntimeException("Couldn't connected to Akismet server!");
			}
			else
			{
				if ($validator->isSpam())
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Create a random string
	 *
	 * @return string
	 */
	protected function _randomString()
	{
		return preg_replace('/([ ])/e', 'chr(rand(97,122))', '     ');
	}
}
