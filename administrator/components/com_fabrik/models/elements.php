<?php
/**
 * Fabrik Admin Elements Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use \JComponentHelper as JComponentHelper;
use \JHtml as JHtml;
use Fabrik\Helpers\Text;
use Fabrik\Helpers\Worker;
use Joomla\Registry\Registry as JRegistry;

use \stdClass as stdClass;

interface ModelElementsInterface
{
}

/**
 * Fabrik Admin Elements Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Elements extends Base implements ModelElementsInterface
{
	/**
	 * State prefix
	 *
	 * @var string
	 */
	protected $context = 'fabrik.elements';

	/**
	 * Constructor.
	 *
	 * @param   Registry  $state  Optional configuration settings.
	 *
	 * @since	3.5
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);

		if (!$this->state->exists('filter_fields'))
		{
			$this->state->set('filter_fields', array('e.id', 'e.name', 'e.label', 'e.show_in_list_summary', 'e.published', 'e.ordering', 'g.label',
				'e.plugin', 'g.name'));
		}
	}
	/**
	 * Get list view items.
	 *
	 * @return array
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$elements = array();

		foreach ($items as $item)
		{
			$item = new JRegistry($item);
			$itemGroups = (array) $item->get('form.groups');

			foreach ($itemGroups as $itemGroup)
			{
				$groupElements = (array) $itemGroup->fields;

				foreach ($groupElements as $i => &$element)
				{
					$element->full_element_name = $element->name;
					$element->numValidations = 'todo';
					$element->numJs = 'todo';
					$element->validationTip = array('todo');
					$element->group_name = $itemGroup->name;
					$element->ordering = $i;
					$element->tip = 'todo';
					$element->view = $item->get('view');
				}

				$elements = $elements + $groupElements;
			}
		}

		$elements = parent::filterItems($elements);

		return $elements;
	}

	public function getPagination()
	{
		// FIXME
		return new \JPagination(0, 0, 0);
	}

	public function getGroupOptions()
	{
		$items = parent::getItems();
		$options = array();

		foreach ($items as $item)
		{
			$item = new JRegistry($item);
			$groups = $item->get('form.groups');

			foreach ($groups as $group)
			{

				$option = new stdClass;
				$option->value = $item->get('view');
				$option->text = $group->name;
				$options[] = $option;
			}
		}

		return $options;
	}

	/**
	 * Unpublish items
	 *
	 * @param array $ids
	 */
	public function unpublish($ids = array())
	{
		$this->doPublish($ids, 0);
	}

	/**
	 * Publish items
	 *
	 * @param array $ids
	 */
	public function publish($ids = array())
	{
		$this->doPublish($ids, 1);
	}

	/**
	 * Trash items
	 *
	 * @param   array $ids Ids
	 *
	 * @return  void
	 */
	public function trash($ids)
	{
		$this->doPublish($ids, -2);
	}

	/**
	 * Toggle publish state
	 *
	 * @param array $ids
	 * @param int   $state 1|0
	 *
	 * @throws RuntimeException
	 *
	 */
	protected function doPublish($ids = array(), $state = 1)
	{
		$this->changeState($ids, 'published', $state);
	}

	public function changeState($ids = array(), $property = '', $state = 1)
	{
		$items = $this->getViews();

		foreach ($items as &$item)
		{
			$item   = new JRegistry($item);
			$groups = $item->get('form.groups');

			foreach ($groups as &$group)
			{
				foreach ($group->fields as &$field)
				{
					if (in_array($field->id, $ids))
					{
						if (isset($field->$property))
						{
							$field->$property = $state;
						}
					}
				}
			}

			$row = $this->prepareSave($item->toObject());
			$this->save($row);
		}
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @since	1.6
	 *
	 * @return  null
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$published = $this->app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->set('filter.published', $published);

		$search = $this->app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->set('filter.search', $search);

		// Load the form state
		$form = $this->app->getUserStateFromRequest($this->context . '.filter.form', 'filter_form', '');
		$this->set('filter.form', $form);

		// Load the group state
		$group = $this->app->getUserStateFromRequest($this->context . '.filter.group', 'filter_group', '');
		$this->set('filter.group', $group);

		// Load the show in list state
		$showInList = $this->app->getUserStateFromRequest($this->context . '.filter.show_in_list_summary', 'filter_showinlist', '');
		$this->set('filter.show_in_list_summary', $showInList);

		// Load the plug-in state
		$plugin = $this->app->getUserStateFromRequest($this->context . '.filter.plugin', 'filter_plugin', '');
		$this->set('filter.plugin', $plugin);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Get show in list options
	 *
	 * @return  array  of Jhtml select.options
	 */

	public function getShowInListOptions()
	{
		return array(JHtml::_('select.option', 0, Text::_('JNO')), JHtml::_('select.option', 1, Text::_('JYES')));
	}

	/**
	 * Get a list of plugin types to filter on
	 *
	 * @return  array  of select.options
	 */

	public function getPluginOptions()
	{
		$db = Worker::getDbo(true);
		$user = \JFactory::getUser();
		$levels = implode(',', $user->getAuthorisedViewLevels());
		$query = $db->getQuery(true);
		$query->select('element AS value, element AS text')->from('#__extensions')->where('enabled >= 1')->where('type =' . $db->q('plugin'))
			->where('state >= 0')->where('access IN (' . $levels . ')')->where('folder = ' . $db->q('fabrik_element'))->order('text');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Batch process element properties
	 *
	 * @param   array  $ids    element ids
	 * @param   array  $batch  element properties to set to
	 *
	 * @since   3.0.7
	 *
	 * @return  bool
	 */

	public function batch($ids, $batch)
	{
		ArrayHelper::toInteger($ids);

		foreach ($ids as $id)
		{
			$item = $this->getTable('Element');
			$item->load($id);
			$item->batch($batch);
		}
	}

	/**
	 * Stops internal id from being unpublished
	 *
	 * @param   array  $ids  Ids wanting to be unpublished
	 *
	 * @return  array  allowed ids
	 */
	public function canUnpublish($ids)
	{
		ArrayHelper::toInteger($ids);
		$blocked = array();

		foreach ($ids as $id)
		{
			$item = $this->getTable('Element');
			$item->load($id);

			if ($item->plugin == 'internalid')
			{
				$blocked[] = $id;
			}
		}

		if (!empty($blocked))
		{
			$this->app->enqueueMessage(Text::_('COM_FABRIK_CANT_UNPUBLISHED_PK_ELEMENT'), 'warning');
		}

		return array_diff($ids, $blocked);
	}
}
