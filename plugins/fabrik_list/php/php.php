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
use Fabrik\Helpers\Html;
use Joomla\CMS\Filter\InputFilter;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Language\Text;

/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.php
 * @since       3.0
 */
class PlgFabrik_ListPhp extends AbstractListPlugin
{
	/**
	 * @var string
	 * @since 4.0
	 */
	protected $buttonPrefix = 'php';

	/**
	 * @var null
	 * @since 4.0
	 */
	protected $msg = null;

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
		parent::button($args);
		$heading = false;

		if (!empty($args))
		{
			$heading = FArrayHelper::getValue($args[0], 'heading');
		}

		if ($heading)
		{
			return true;
		}

		$params = $this->getParams();

		return (bool) $params->get('button_in_row', true);
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

		if ($img === 'php.png')
		{
			$img = 'lightning';
		}

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
		return $this->getParams()->get('table_php_button_label', parent::buttonLabel());
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
		return 'table_php_access';
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
		return true;
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array $opts Custom options
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function process($opts = array())
	{
		// We don't use $model, but user code may as it used to be an arg, so fetch it
		$model  = $this->getModel();
		$params = $this->getParams();
		$f      = InputFilter::getInstance();
		$file   = $f->clean($params->get('table_php_file'), 'CMD');

		if ($file == -1 || $file == '')
		{
			$code = $params->get('table_php_code');
			@trigger_error('');
			Html::isDebug() ? eval($code) : @eval($code);
			Worker::logEval(false, 'Eval exception : list php plugin : %s');
		}
		else
		{
			require_once JPATH_ROOT . '/plugins/fabrik_list/php/scripts/' . $file;
		}

		if (isset($statusMsg) && !empty($statusMsg))
		{
			$this->msg = $statusMsg;
		}

		return true;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int $c plugin render order
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function process_result($c)
	{
		if (isset($this->msg))
		{
			return $this->msg;
		}
		else
		{
			$params = $this->getParams();
			$msg    = $params->get('table_php_msg', Text::_('PLG_LIST_PHP_CODE_RUN'));

			return $msg;
		}
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
		$opts                 = $this->getElementJSOptions();
		$params               = $this->getParams();
		$opts->js_code        = $params->get('table_php_js_code', '');
		$opts->requireChecked = (bool) $params->get('table_php_require_checked', '1');
		$opts                 = json_encode($opts);
		$this->jsInstance     = "new FbListPhp($opts)";

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
		return 'FbListPHP';
	}
}
