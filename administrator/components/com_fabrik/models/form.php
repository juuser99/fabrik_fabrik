<?php
/**
 * Fabrik Admin Form Model
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
use \JForm as JForm;
use Fabrik\Helpers\Worker;
use \Joomla\Registry\Registry as JRegistry;
use \JComponentHelper as JComponentHelper;
use \JFactory as JFactory;
use \JFolder as JFolder;

interface ModelFormFormInterface
{
	/**
	 * Save the form
	 *
	 * @param   array  $data  posted jform data
	 *
	 * @return  bool
	 */
	public function save($data);

}

/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Form extends View implements ModelFormFormInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * The plugin type?
	 *
	 * @deprecated - don't think this is used
	 *
	 * @var  string
	 */
	protected $pluginType = 'Form';

	/**
	 * If editable if 0 then show view only version of form
	 *
	 * @var bol true
	 */
	public $editable = true;

	/**
	 * Parameters
	 *
	 * @var JRegistry
	 */
	protected $params = null;

	/**
	 * Form errors
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * The form running as a mambot or module(true)
	 *
	 * @var bool
	 */
	public $isMambot = false;

	/**
	 * Save the form
	 *
	 * @param   array $post The jform part of the request data pertaining to the list.
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function save($post)
	{
		$view = ArrayHelper::getValue($post, 'view');
		$this->set('id', $view);
		$item = $this->getItem();
		$groups = $item->get('form.groups');

		$post = $this->prepareSave($post, 'form');
		$selectedGroups = ArrayHelper::fromObject($post->get('form.current_groups'));

		$newGroups = new \stdClass;

		foreach ($groups as $group)
		{
			if (in_array($group->id, $selectedGroups))
			{
				$name = $group->name;
				$newGroups->$name = $group;
			}
		}

		$post->set('form.groups', $newGroups);

		return parent::save($post);
	}

	/**
	 * Get JS
	 *
	 * @return string
	 */
	public function getJs()
	{
		$js[] = "\twindow.addEvent('domready', function () {";
		$plugins = json_encode($this->getPlugins());
		$js[] = "\t\tFabrik.controller = new PluginManager($plugins, '" . $this->getItem()->get('id') . "', 'form');";
		$js[] = "\t})";

		return implode("\n", $js);
	}

	/**
	 * Reinsert the groups ids into formgroup rows
	 *
	 * @param   array  $data           jform post data
	 * @param   array  $currentGroups  group ids
	 *
	 * @return  void
	 */
	protected function _makeFormGroups($data, $currentGroups)
	{
		// FIXME for json view
		echo "_makeFormGroups not workee ";exit;
		$formId = $this->get($this->getName() . '.id');
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		ArrayHelper::toInteger($currentGroups);
		$query->delete('#__fabrik_formgroup')->where('form_id = ' . (int) $formId);

		if (!empty($currentGroups))
		{
			$query->where('group_id NOT IN (' . implode($currentGroups, ', ') . ')');
		}

		$db->setQuery($query);

		// Delete the old form groups
		$db->execute();

		// Get previously saved form groups
		$query->clear()->select('id, group_id')->from('#__fabrik_formgroup')->where('form_id = ' . (int) $formId);
		$db->setQuery($query);
		$groupIds = $db->loadObjectList('group_id');
		$orderId = 1;
		$currentGroups = array_unique($currentGroups);

		foreach ($currentGroups as $group_id)
		{
			if ($group_id != '')
			{
				$group_id = (int) $group_id;
				$query->clear();

				if (array_key_exists($group_id, $groupIds))
				{
					$query->update('#__fabrik_formgroup')
					->set('ordering = ' . $orderId)->where('id =' . $groupIds[$group_id]->id);
				}
				else
				{
					$query->insert('#__fabrik_formgroup')
					->set(array('form_id =' . (int) $formId, 'group_id = ' . $group_id, 'ordering = ' . $orderId));
				}

				$db->setQuery($query);
				$db->execute();
				$orderId++;
			}
		}
	}

	/**
	 * Validate the form
	 *
	 * @param   array   $data   The data to validate.
	 *
	 * @return mixed  false or data
	 */

	public function validate($data)
	{
		$params = $data['params'];
		$ok = parent::validate($data);

		// Standard jform validation failed so we shouldn't test further as we can't be sure of the data
		if (!$ok)
		{
			return false;
		}

		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		$data['params'] = $params;

		return $data;
	}

	/**
	 * Delete form and form groups
	 *
	 * @param   array  &$cids  to delete
	 *
	 * @return  bool
	 */
/*	public function delete(&$cids)
	{
		$res = parent::delete($cids);

		if ($res)
		{
			foreach ($cids as $cid)
			{
				$item = FabTable::getInstance('FormGroup', 'FabrikTable');
				$item->load(array('form_id' => $cid));
				$item->delete();
			}
		}

		return $res;
	}*/

	/**
	 * Are we creating a new record or editing an existing one?
	 * Put here to ensure compat when we go from 3.0 where rowid = 0 = new, to row id '' = new
	 *
	 * @since   3.0.9
	 *
	 * @return  boolean
	 */
	public function isNewRecord()
	{
		return $this->getRowId() === '';
	}

	/**
	 * Get the current records row id
	 * setting a rowid of -1 will load in the current users record (used in
	 * conjunction with usekey variable
	 *
	 * setting a rowid of -2 will load in the last created record
	 *
	 * @return  string  rowid
	 */

	public function getRowId()
	{
		if (isset($this->rowId))
		{
			return $this->rowId;
		}

		$input = $this->app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$user = $this->user;
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		// $$$rob if we show a form module when in a fabrik form component view - we shouldn't use
		// the request rowid for the mambot as that value is destined for the component
		if ($this->isMambot && $input->get('option') == 'com_' . $package)
		{
			$this->rowId = $usersConfig->get('rowid');
		}
		else
		{
			$this->rowId = Worker::getMenuOrRequestVar('rowid', $usersConfig->get('rowid'), $this->isMambot);

			if ($this->rowId == -2)
			{
				// If the default was set to -2 (load last row) then a pagination form plugin's row id should override menu settings
				$this->rowId = Worker::getMenuOrRequestVar('rowid', $usersConfig->get('rowid'), $this->isMambot, 'request');
			}
		}

		if ($this->getListModel()->getParams()->get('sef-slug', '') !== '')
		{
			$this->rowId = explode(':', $this->rowId);
			$this->rowId = array_shift($this->rowId);
		}
		// $$$ hugh - for some screwed up reason, when using SEF, rowid=-1 ends up as :1
		// $$$ rob === compare as otherwise 0 == ":1" which meant that the users record was loaded
		if ($this->isUserRowId())
		{
			$this->rowId = '-1';
		}
		// Set rowid to -1 to load in the current users record
		switch ($this->rowId)
		{
			case '-1':
				// New rows (no logged in user) should be ''
				$this->rowId = $user->get('id') == 0 ? '' : $user->get('id');
				break;
			case '-2':
				// Set rowid to -2 to load in the last recorded record
				$this->rowId = $this->getMaxRowId();
				break;
		}

		/**
		 * $$$ hugh - added this as a Hail Mary sanity check, make sure
		 * rowId is an empty string if for whatever reason it's still null,
		 * as we have code in various place that checks for $this->rowId === ''
		 * to detect adding new form.  So if at this point rowid is null, we have
		 * to assume it's a new form, and set rowid to empty string.
		 */
		if (is_null($this->rowId))
		{
			$this->rowId = '';
		}

		/**
		 * $$$ hugh - there's a couple of places, like calendar viz, that add &rowid=0 to
		 * query string for new form, so check for that and set to empty string.
		 */
		if ($this->rowId === '0')
		{
			$this->rowId = '';
		}

		Worker::getPluginManager()->runPlugins('onSetRowId', $this);

		return $this->rowId;
	}

	/**
	 * Should the form load up rowid=-1 usekey=foo
	 *
	 * @param   string  $priority  Request priority menu or request
	 *
	 * @return boolean
	 */

	protected function isUserRowId($priority = 'menu')
	{
		$rowId = Worker::getMenuOrRequestVar('rowid', '', $this->isMambot, $priority);

		return $rowId === '-1' || $rowId === ':1';
	}

	/**
	 * Checks if the params object has been created and if not creates and returns it
	 *
	 * @return  object  params
	 */

	public function getParams()
	{
		if (!isset($this->params))
		{
			$item = $this->getItem();
			$this->params = new JRegistry($item->get('form.params'));
		}

		return $this->params;
	}

	/**
	 * Does the form contain user errors
	 *
	 * @return  bool
	 */
	public function hasErrors()
	{
		$errorsFound = false;

		foreach ($this->errors as $field => $errors)
		{
			if (!empty($errors))
			{
				foreach ($errors as $error)
				{
					if (!empty($error[0]))
					{
						$errorsFound = true;
					}
				}
			}
		}

		if ($this->saveMultiPage(false))
		{
			$sessionRow = $this->getSessionData();
			/*
			 * Test if its a resumed paged form
			 * if so _arErrors will be filled so check all elements had no errors
			 */
			$multiPageErrors = false;

			if ($sessionRow->data != '')
			{
				foreach ($this->errors as $err)
				{
					if (!empty($err[0]))
					{
						$multiPageErrors = true;
					}
				}

				if (!$multiPageErrors)
				{
					$errorsFound = false;
				}
			}
		}

		return $errorsFound;
	}

	/**
	 * Checks if user is logged in and form multipage settings to determine
	 * if the form saves to the session table on multipage navigation
	 *
	 * @param   bool  $useSessionOn  Return true if JSession contains session.on - used in confirmation
	 * plugin to re-show the previously entered form data. Not used in $this->hasErrors() otherwise logged in users
	 * can not get the confirmation plugin to work
	 *
	 * @return  bool
	 */

	public function saveMultiPage($useSessionOn = true)
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$session = JFactory::getSession();

		// Set in plugins such as confirmation plugin
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('usesSession', $this, 'form');

		if (in_array(true, $pluginManager->data))
		{
			if ($session->get('com_' . $package . '.form.' . $this->getId() . '.' . $this->getRowId() . '.session.on') == true && $useSessionOn)
			{
				return true;
			}
		}

		$save = (int) $params->get('multipage_save', 0);

		if ($this->user->get('id') !== 0)
		{
			return $save === 0 ? false : true;
		}
		else
		{
			return $save === 2 ? true : false;
		}
	}

	/**
	 * Get the template name
	 *
	 * @since 3.0
	 *
	 * @return string tmpl name
	 */
	public function getTmpl()
	{
		$input = $this->app->input;
		$params = $this->getParams();
		$item = $this->getForm();
		$tmpl = '';
		$default = 'bootstrap';
		$document = JFactory::getDocument();

		if ($document->getType() === 'pdf')
		{
			$tmpl = $params->get('pdf_template', '') !== '' ? $params->get('pdf_template') : $default;
		}
		else
		{
			if ($this->app->isAdmin())
			{
				$tmpl = $this->isEditable() ? $params->get('admin_form_template') : $params->get('admin_details_template');
				$tmpl = $tmpl == '' ? $default : $tmpl;
			}

			if ($tmpl == '')
			{
				if ($this->isEditable())
				{
					$tmpl = $item->form_template == '' ? $default : $item->form_template;
				}
				else
				{
					$tmpl = $item->view_only_template == '' ? $default : $item->view_only_template;
				}
			}
		}

		$tmpl = Worker::getMenuOrRequestVar('fabriklayout', $tmpl, $this->isMambot);

		// Finally see if the options are overridden by a querystring var
		$baseTmpl = $tmpl;
		$tmpl = $input->get('layout', $tmpl);

		// Test it exists - otherwise revert to baseTmpl tmpl
		$folder = $this->isEditable() ? 'form' : 'details';

		if (!JFolder::exists(JPATH_SITE . '/components/com_fabrik/views/' . $folder . '/tmpl/' . $tmpl))
		{
			$tmpl = $baseTmpl;
		}

		$this->isEditable() ? $item->form_template = $tmpl : $item->view_only_template = $tmpl;

		return $tmpl;
	}

	/**
	 * Is the form editable
	 *
	 * @return  bool
	 */
	public function isEditable()
	{
		return $this->editable;
	}

}
