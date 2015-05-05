<?php
/**
 * Fabrik Admin Groups Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

use \Joomla\Registry\Registry as JRegistry;

// No direct access
defined('_JEXEC') or die('Restricted access');

interface ModelGroupsInterface
{
}

/**
 * Fabrik Admin Groups Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Groups extends Base implements ModelGroupsInterface
{
	/**
	 * State prefix
	 *
	 * @var string
	 */
	protected $context = 'fabrik.groups';

	/**
	 * Constructor.
	 *
	 * @param   Registry $state Optional configuration settings.
	 *
	 * @since    3.5
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);

		if (!$this->state->exists('filter_fields'))
		{
			$this->state->set('filter_fields', array('g.id', 'g.name', 'g.label', 'f.label', 'g.published'));
		}
	}

	/**
	 * Get list view items.
	 *
	 * @return array
	 */
	public function getItems()
	{
		$items  = parent::getItems();
		$groups = array();

		foreach ($items as $item)
		{
			$item       = new JRegistry($item);
			$itemGroups = (array) $item->get('form.groups');

			foreach ($itemGroups as &$itemGroup)
			{
				$itemGroup->form_id       = $item->get('view');
				$itemGroup->flabel        = $item->get('form.label');
				$itemGroup->view          = $item->get('view');
				$itemGroup->_elementCount = count((array) $itemGroup->fields);
			}

			$groups = $groups + $itemGroups;
		}

		return $groups;
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
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

		// List state information.
		parent::populateState('name', 'asc');
	}

	/**
	 * Unpublish items
	 *
	 * @param array $ids
	 */
	public function unpublish($ids = array())
	{
		$this->doPublish($ids, $state = 0);
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
		$items = $this->getViews();

		foreach ($items as &$item)
		{
			$item   = new JRegistry($item);
			$groups = $item->get('form.groups');

			foreach ($groups as &$group)
			{
				$group = new JRegistry($group);

				if (in_array($group->get('id'), $ids))

				{
					$group->set('published', $state);
				}

				$group = $group->toObject();
			}

			$row = $this->prepareSave($item->toObject());
			$this->save($row);
		}
	}

	/**
	 * @param  array|object $post
	 *
	 * @return JRegistry
	 */
	protected function prepareSave($post, $view = null)
	{
		if (is_array($post))
		{
			echo "tod oprepare group sae for array data";
			exit;

		}
		else
		{
			$data = $post;
		}

		return new JRegistry($data);
	}
}
