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
use \JProfiler as JProfiler;
use \JFilterInput as JFilterInput;
use \FabrikString as FabrikString;
use \FabrikHelperHTML as FabrikHelperHTML;

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

	/**
	 * Get an list of elements that aren't shown in the table view
	 *
	 * @return  array  of element table objects
	 */
	public function getElementsNotInTable()
	{
		if (!isset($this->elementsNotInList))
		{
			$this->elementsNotInList = array();
			$groups = $this->getGroupsHiarachy();

			foreach ($groups as $group)
			{
				$elements = $group->getPublishedElements();

				foreach ($elements as $elementModel)
				{
					if ($elementModel->canView() || $elementModel->canUse())
					{
						$element = $elementModel->getElement();

						if (!isset($element->show_in_list_summary) || !$element->show_in_list_summary)
						{
							$this->elementsNotInList[] = $element;
						}
					}
				}
			}
		}

		return $this->elementsNotInList;
	}

	/**
	 * Collates data to write out the form
	 *
	 * @return  mixed  bool
	 */
	public function render()
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('formmodel render: start') : null;

		// $$$rob required in paolo's site when rendering modules with ajax option turned on
		$this->listModel = null;
		$this->setRowId($this->getRowId());

		/*
		 * $$$ hugh - need to call this here as we set $this->editable here, which is needed by some plugins
		 * , this means that getData() is being called from checkAccessFromListSettings(),
		 * so plugins running onBeforeLoad will have to unset($formModel->_data) if they want to
		 * do something funky like change the rowid being loaded.  Not a huge problem, but caught me out
		 * when a custom PHP onBeforeLoad plugin I'd written for a client suddenly broke.
		 */
		$this->checkAccessFromListSettings();
		$pluginManager = Worker::getPluginManager();
		$res = $pluginManager->runPlugins('onBeforeLoad', $this);

		if (in_array(false, $res))
		{
			return false;
		}

		JDEBUG ? $profiler->mark('form model render: getData start') : null;
		$data = $this->getData();
		JDEBUG ? $profiler->mark('form model render: getData end') : null;
		$res = $pluginManager->runPlugins('onLoad', $this);

		if (in_array(false, $res))
		{
			return false;
		}

		JDEBUG ? $profiler->mark('formmodel render end') : null;

		$session = JFactory::getSession();
		$session->set('com_' . $package . '.form.' . $this->getId() . '.data', $this->data);

		// $$$ rob return res - if its false the the form will not load
		return $res;
	}

	/**
	 * Set row id
	 *
	 * @param   string  $id  primary key value
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */
	public function setRowId($id)
	{
		$this->rowId = $id;
	}

	/**
	 * Makes sure that the form is not viewable based on the list's access settings
	 *
	 * Also sets the form's editable state, if it can record in to a db table
	 *
	 * @return  int  0 = no access, 1 = view only , 2 = full form view, 3 = add record only
	 */
	public function checkAccessFromListSettings()
	{
		$item = $this->getItem();

		if ($item->get('list.record_in_database') == 0)
		{
			return 2;
		}

		$listModel = $this->getListModel();

		if (!is_object($listModel))
		{
			return 2;
		}

		$data = $this->getData();
		$ret = 0;

		if ($listModel->canViewDetails())
		{
			$ret = 1;
		}

		$isUserRowId = $this->isUserRowId();

		// New form can we add?
		if ($this->getRowId() === '' || $isUserRowId)
		{
			// If they can edit can they also add
			if ($listModel->canAdd())
			{
				$ret = 3;
			}
			// $$$ hugh - corner case for rowid=-1, where they DON'T have add perms, but DO have edit perms
			elseif ($isUserRowId && $listModel->canEdit($data))
			{
				$ret = 2;
			}
		}
		else
		{
			// Editing from - can we edit
			if ($listModel->canEdit($data))
			{
				$ret = 2;
			}
		}
		// If no access (0) or read only access (1) set the form to not be editable
		$editable = ($ret <= 1) ? false : true;
		$this->setEditable($editable);

		if ($this->app->input->get('view', 'form') == 'details')
		{
			$this->setEditable(false);
		}

		return $ret;
	}

	/**
	 * Populate the Model state
	 *
	 * @return  void
	 */
	protected function populateState()
	{
		$input = $this->app->input;

		if (!$this->app->isAdmin())
		{
			// Load the menu item / component parameters.
			$params = $this->app->getParams();
			$this->setState('params', $params);

			// Load state from the request.
			$pk = $input->getString('id', $params->get('id'));
		}
		else
		{
			$pk = $input->getString('id');
		}

		$this->set('id', $pk);
	}

	/**
	 * Main method to get the data to insert into the form
	 *
	 * @return  array  Form's data
	 */
	public function getData()
	{
		// If already set return it. If not was causing issues with the juser form plugin
		// when it tried to modify the form->data info, from within its onLoad method, when sync user option turned on.

		if (isset($this->data))
		{
			return $this->data;
		}

		$this->getRowId();
		$input = $this->app->input;
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('formmodel getData: start') : null;
		$this->data = array();
		$f = JFilterInput::getInstance();

		/*
		 * $$$ hugh - we need to remove any elements from the query string,
		 * if the user doesn't have access, otherwise ACL's on elements can
		 * be bypassed by just setting value on form load query string!
		 */

		$clean_request = $f->clean($_REQUEST, 'array');

		foreach ($clean_request as $key => $value)
		{
			$test_key = FabrikString::rtrimword($key, '_raw');
			$elementModel = $this->getElement($test_key, false, false);

			if ($elementModel !== false)
			{
				if (!$elementModel->canUse())
				{
					unset($clean_request[$key]);
				}
			}
		}

		$data = $clean_request;
		$item = $this->getItem();
		$aGroups = $this->getGroupsHiarachy();
		JDEBUG ? $profiler->mark('formmodel getData: groups loaded') : null;

		if (!$item->get('list.record_in_database'))
		{
			FabrikHelperHTML::debug($data, 'form:getData from $_REQUEST');
			$data = $f->clean($_REQUEST, 'array');
		}
		else
		{
			JDEBUG ? $profiler->mark('formmodel getData: start get list model') : null;
			$listModel = $this->getListModel();
			JDEBUG ? $profiler->mark('formmodel getData: end get list model') : null;
			$fabrikDb = $listModel->getDb();
			JDEBUG ? $profiler->mark('formmodel getData: db created') : null;
			$this->aJoinObjs = $listModel->getJoins();
			JDEBUG ? $profiler->mark('formmodel getData: joins loaded') : null;

			if ($this->hasErrors())
			{
				// $$$ hugh - if we're a mambot, reload the form session state we saved in
				// process() when it banged out.
				if ($this->isMambot)
				{
					$sessionRow = $this->getSessionData();
					$this->sessionModel->last_page = 0;

					if ($sessionRow->data != '')
					{
						$data = ArrayHelper::toObject(unserialize($sessionRow->data), 'stdClass', false);
						JFilterOutput::objectHTMLSafe($data);
						$data = array($data);
						FabrikHelperHTML::debug($data, 'form:getData from session (form in Mambot and errors)');
					}
				}
				else
				{
					// $$$ rob - use setFormData rather than $_GET
					// as it applies correct input filtering to data as defined in article manager parameters
					$data = $this->setFormData();
					$data = ArrayHelper::toObject($data, 'stdClass', false);

					// $$$rob ensure "<tags>text</tags>" that are entered into plain text areas are shown correctly
					JFilterOutput::objectHTMLSafe($data);
					$data = ArrayHelper::fromObject($data);
					FabrikHelperHTML::debug($data, 'form:getData from POST (form not in Mambot and errors)');
				}
			}
			else
			{
				$sessionLoaded = false;

				// Test if its a resumed paged form
				if ($this->saveMultiPage())
				{
					$sessionRow = $this->getSessionData();
					JDEBUG ? $profiler->mark('formmodel getData: session data loaded') : null;

					if ($sessionRow->data != '')
					{
						$sessionLoaded = true;
						/*
						 * $$$ hugh - this chunk should probably go in setFormData, but don't want to risk any side effects just now
						 * problem is that later failed validation, non-repeat join element data is not formatted as arrays,
						 * but from this point on, code is expecting even non-repeat join data to be arrays.
						 */
						$tmp_data = unserialize($sessionRow->data);
						$groups = $this->getGroupsHiarachy();

						foreach ($groups as $groupModel)
						{
							if ($groupModel->isJoin() && !$groupModel->canRepeat())
							{
								foreach ($tmp_data['join'][$groupModel->getJoinId()] as &$el)
								{
									$el = array($el);
								}
							}
						}

						$bits = $data;
						$bits = array_merge($tmp_data, $bits);
						$data = array(ArrayHelper::toObject($bits));
						FabrikHelperHTML::debug($data, 'form:getData from session (form not in Mambot and no errors');
					}
				}

				if (!$sessionLoaded)
				{
					/* Only try and get the row data if its an active record
					 * use !== '' as rowid may be alphanumeric.
					 * Unlike 3.0 rowId does equal '' if using rowid=-1 and user not logged in
					 */
					$usekey = Worker::getMenuOrRequestVar('usekey', '', $this->isMambot);

					if (!empty($usekey) || $this->rowId !== '')
					{
						// $$$ hugh - once we have a few join elements, our select statements are
						// getting big enough to hit default select length max in MySQL.
						$listModel->setBigSelects();

						// Otherwise lets get the table record
						$opts = $input->get('task') == 'form.inlineedit' ? array('ignoreOrder' => true) : array();
						$sql = $this->buildQuery($opts);
						$fabrikDb->setQuery($sql);
						FabrikHelperHTML::debug($fabrikDb->getQuery(), 'form:render');
						$rows = $fabrikDb->loadObjectList();

						if (is_null($rows))
						{
							JError::raiseWarning(500, $fabrikDb->getErrorMsg());
						}

						JDEBUG ? $profiler->mark('formmodel getData: rows data loaded') : null;

						// $$$ rob Ack above didn't work for joined data where there would be n rows returned for "this rowid = $this->rowId  \n";
						if (!empty($rows))
						{
							// Only do this if the query returned some rows (it wont if usekey on and userid = 0 for example)
							$data = array();

							foreach ($rows as &$row)
							{
								if (empty($data))
								{
									// If loading in a rowid=-1 set the row id to the actual row id
									$this->rowId = isset($row->__pk_val) ? $row->__pk_val : $this->rowId;
								}

								$row = empty($row) ? array() : ArrayHelper::fromObject($row);
								$request = $clean_request;
								$request = array_merge($row, $request);
								$data[] = ArrayHelper::toObject($request);
							}
						}

						FabrikHelperHTML::debug($data, 'form:getData from querying rowid= ' . $this->rowId . ' (form not in Mambot and no errors)');

						// If empty data return and trying to edit a record then show error
						JDEBUG ? $profiler->mark('formmodel getData: empty test') : null;

						// Was empty($data) but that is never empty. Had issue where list prefilter meant record was not loaded, but no message shown in form
						if (empty($rows) && $this->rowId != '')
						{
							// $$$ hugh - special case when using -1, if user doesn't have a record yet
							if ($this->isUserRowId())
							{
								return;
							}
							else
							{
								// If no key found set rowid to 0 so we can insert a new record.
								if (empty($usekey) && !$this->isMambot && in_array($input->get('view'), array('form', 'details')))
								{
									$this->rowId = '';
									/**
									 * runtime exception is a little obtuse for people getting here from legitimate links,
									 * like from an email, but aren't logged in so run afoul of a pre-filter, etc
									 * So do the 3.0 thing, and raise a warning
									 */
									//throw new RuntimeException(FText::_('COM_FABRIK_COULD_NOT_FIND_RECORD_IN_DATABASE'));
									JError::raiseWarning(500, FText::_('COM_FABRIK_COULD_NOT_FIND_RECORD_IN_DATABASE'));
								}
								else
								{
									// If we are using usekey then there's a good possibility that the record
									// won't yet exist - so in this case suppress this error message
									$this->rowId = '';
								}
							}
						}
					}
				}
				// No need to setJoinData if you are correcting a failed validation
				if (!empty($data))
				{
					$this->setJoinData($data);
				}
			}
		}

		$this->data = $data;
		FabrikHelperHTML::debug($data, 'form:data');
		JDEBUG ? $profiler->mark('queryselect: getData() end') : null;

		return $this->data;
	}

	/**
	 * Is the page a multipage form?
	 *
	 * @return  bool
	 */
	public function isMultiPage()
	{
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$params = $groupModel->getParams();

			if ($params->get('split_page'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Query all active form plugins to see if they inject custom html into the top
	 * or bottom of the form
	 *
	 * @return  array  plugin top html, plugin bottom html (inside <form>) plugin end (after form)
	 */

	public function getFormPluginHTML()
	{
		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('form');

		$pluginManager->runPlugins('getBottomContent', $this, 'form');
		$pluginBottom = implode("<br />", array_filter($pluginManager->data));

		$pluginManager->runPlugins('getTopContent', $this, 'form');
		$pluginTop = implode("<br />", array_filter($pluginManager->data));

		// Inserted after the form's closing </form> tag
		$pluginManager->runPlugins('getEndContent', $this, 'form');
		$pluginEnd = implode("<br />", array_filter($pluginManager->data));

		return array($pluginTop, $pluginBottom, $pluginEnd);
	}

	/**
	 * Determines if the form can be published
	 *
	 * @return  bool  true if publish dates are ok
	 */
	public function canPublish()
	{
		$db = Worker::getDbo();
		$item = $this->getItem();
		$nullDate = $db->getNullDate();
		$up = $item->get('form.publish_up');
		$down = $item->get('form.publish_down');
		$publishUp = JFactory::getDate($up)->toUnix();
		$publishDown = JFactory::getDate($down)->toUnix();
		$now = JFactory::getDate()->toUnix();

		if ($item->get('form.published') == '1')
		{
			if ($now >= $publishUp || $up == '' || $up == $nullDate)
			{
				if ($now <= $publishDown ||$down == '' || $down == $nullDate)
				{
					return true;
				}
			}
		}

		return false;
	}

}
