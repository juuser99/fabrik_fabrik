<?php
/**
 * Plugin List Field class for Fabrik.
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Plugin List Field class for Fabrik.
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class PluginListField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'PluginList';

	/**
	 * Cache plugin list options
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private static $cache = array();

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$group    = (string) $this->element['plugin'];
		$key      = $this->element['key'];
		$key      = ($key == 'visualization.plugin') ? "CONCAT('visualization.',element) " : 'element';
		$cacheKey = $group . '.' . $key;

		if (array_key_exists($cacheKey, self::$cache))
		{
			return self::$cache[$cacheKey];
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($key . ' AS value, element AS text');
		$query->from('#__extensions AS p');
		$query->where($db->qn('type') . ' = ' . $db->q('plugin'));
		$query->where($db->qn('enabled') . ' = 1 AND state != -1');
		$query->where($db->qn('folder') . ' = ' . $db->q($group));
		$query->order('text');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();
		array_unshift($options, HTMLHelper::_('select.option', '', Text::_('COM_FABRIK_PLEASE_SELECT')));
		self::$cache[$cacheKey] = $options;

		return $options;
	}
}
