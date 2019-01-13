<?php
/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.php
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Joomla\CMS\Language\Text;

/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.php
 * @since       3.0
 */

class PlgFabrik_ListLink extends AbstractListPlugin
{
	/**
	 * @var string
	 * @since 4.0
	 */
	protected $buttonPrefix = 'link';

	/**
	 * @var null
	 * @since 4.0
	 */
	protected $msg = null;

	/**
	 * @var bool
	 * @since 4.0
	 */
	protected $heading = false;

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args Arguments
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function button(&$args)
	{
		if (is_array($args) && array_key_exists(0, $args))
		{
			$this->heading = FArrayHelper::getValue($args[0], 'heading', false);
		}
		else
		{
			$this->heading = false;
		}

		parent::button($args);

		return true;
	}

	/**
	 * Get button image
	 *
	 * @since   3.1b
	 *
	 * @return   string  image
	 */
	protected function getImageName()
	{
		$img = parent::getImageName();

		return $img;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function buttonLabel()
	{
		return $this->getParams()->get('table_link_button_label', parent::buttonLabel());
	}

	/**
	 * Build the HTML for the plug-in button
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function button_result()
	{
		if ($this->heading)
		{
			return '&nbsp;';
		}
		else
		{
			return parent::button_result();
		}
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function getAclParam()
	{
		return 'table_link_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array $args Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts              = $this->getElementJSOptions();
		$params            = $this->getParams();
		$opts->link        = $params->get('table_link_link', '');
		$opts->fabrikLink  = $params->get('table_link_isfabrik', '0') === '1';
		$opts->windowTitle = Text::_($params->get('table_link_fabrik_window_title', ''));
		$opts              = json_encode($opts);
		$this->jsInstance  = "new FbListLink($opts)";

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListLink';
	}
}
