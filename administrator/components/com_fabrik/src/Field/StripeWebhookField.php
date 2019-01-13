<?php
/**
 * Renders a Fabrik Help link
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0.9
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * Renders a Fabrik Help link
 *
 * @package  Fabrik
 * @since    4.0
 */
class StripeWebhookField extends FormField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'stripewebhook';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Stripewebhook';

	/**
	 * Get the input - a read only link
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getInput()
	{
		$formId = $this->form->model->getId();

		if (empty($formId))
		{
			$url = Text::_('Available once form saved');
		}
		else
		{
			$plugin = (string) $this->getAttribute('plugin', 'stripe');
			$url    = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&c=plugin&task=plugin.pluginAjax';
			$url    .= '&formid=' . $formId;
			$url    .= '&g=form&plugin=' . $plugin;
			$url    .= '&method=webhook';
			$url    .= '&renderOrder=' . $this->form->repeatCounter;
		}

		return '<div>' . $url . '</div>';
	}
}
