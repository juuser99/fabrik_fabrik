<?php
/**
 * Fabrik Form Session Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Site\Model;

// No direct access
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Crypt\Key;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Form Session Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormSessionModel extends FabModel
{
	/**
	 * User id
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $userId = null;

	/**
	 * Unique reference for the form session
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $hash = null;

	/**
	 * Form id
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $formId = null;

	/**
	 * Row id
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $rowId = null;

	/**
	 * Status message
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $status = null;

	/**
	 * Status id
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $statusId = null;

	/**
	 * Formsession row
	 *
	 * @var Table
	 *
	 * @since 4.0
	 */
	public $row = null;

	/**
	 * Should the form store a cookie with
	 * a reference to the incomplete form data
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	protected $useCookie = true;

	/**
	 * cryptor
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	protected $crypt = null;

	/**
	 * Constructor
	 *
	 * @since 4.0
	 */
	public function __construct()
	{
		if (!defined('_FABRIKFORMSESSION_LOADED_FROM_COOKIE'))
		{
			define('_FABRIKFORMSESSION_LOADED_FROM_COOKIE', 1);
			define('_FABRIKFORMSESSION_LOADED_FROM_TABLE', 2);
		}

		parent::__construct();
	}

	/**
	 * Save the form data to #__{package}_form_session
	 *
	 * @param   object  &$formModel  form model
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function savePage(&$formModel)
	{
		// Need to check for encrypted vars, unencrypt them and place them back in the array
		$post = $formModel->setFormData();
		$input = $this->app->input;
		$formModel->copyToRaw($post);
		$formModel->addEncrytedVarsToArray($post);

		$pluginManager = Worker::getPluginManager();
		if (in_array(false, $pluginManager->runPlugins('onSavePage', $formModel)))
		{
			return false;
		}

		if (array_key_exists('fabrik_vars', $post))
		{
			unset($post['fabrik_vars']);
		}

		$data = serialize($post);
		$hash = $this->getHash();
		$row = $this->load();
		$row->hash = $hash;
		$row->user_id = (int) $this->user->get('id');
		$row->form_id = $this->getFormId();
		$row->row_id = $this->getRowId();
		$row->last_page = $input->get('page');
		$row->referring_url = $input->server->get('HTTP_REFERER', '', 'string');
		$row->data = $data;
		$this->setCookie($hash);

		if (!$row->store())
		{
			echo $row->getError();
		}

		// $$$ hugh - if we're saving the formdata in the session, we should set 'session.on'
		// as per The New Way we're doing redirects, etc.
		$this->session->set('com_' . $this->package . '.form.' . $this->getFormId() . '.session.on', true);
	}

	/**
	 * Set the form session cookie
	 *
	 * @param   string  $hash  the actual key that is stored in the db table's hash field
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function setCookie($hash)
	{
		if ($this->canUseCookie() === false)
		{
			return;
		}

		$crypt = $this->getCrypt();
		$lifetime = time() + 365 * 24 * 60 * 60;
		$key = (int) $this->user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();
		$rcookie = $crypt->encrypt($hash);
		setcookie($key, $rcookie, $lifetime, '/');
	}

	/**
	 * Remove the form session cookie
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	protected function removeCookie()
	{
		$lifetime = time() - 99986400;
		$key = (int) $this->user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();
		setcookie($key, false, $lifetime, '/');
	}

	/**
	 * @todo - migrate to J4.0
	 * Create the crypt class object
	 *
	 * @return  JSimpleCrypt
	 *
	 * @since 4.0
	 */
	protected function getCrypt()
	{
		/**
		 * $$$ hugh - might want to alter this to use Worker::getCrypt()
		 * as we now use that everywhere else.
		 */
		if (!isset($this->crypt))
		{
			jimport('joomla.utilities.simplecrypt');
			jimport('joomla.utilities.utility');

			// Create the encryption key, apply extra hardening using the user agent string

			$key = ApplicationHelper::getHash($this->app->input->server->get('HTTP_USER_AGENT'));
			$key = new Key('simple', $key, $key);
			$this->crypt = new JCrypt(new JCryptCipherSimple, $key);
		}

		return $this->crypt;
	}

	/**
	 * Set use cookie
	 *
	 * @param   bool  $bol  set use cookie true/false
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function useCookie($bol)
	{
		$this->useCookie = $bol;
	}

	/**
	 * Load in the saved session
	 *
	 * @return object session table row
	 *
	 * @since 4.0
	 */
	public function load()
	{
		$row = $this->getTable('Formsession', 'FabrikTable');
		$row->data = '';
		$hash = '';

		if ((int) $this->user->get('id') !== 0)
		{
			$hash = $this->getHash();
			$this->status = Text::_('LOADING FROM DATABASE');
			$this->statusId = _FABRIKFORMSESSION_LOADED_FROM_TABLE;
		}
		else
		{
			if ($this->canUseCookie())
			{
				$crypt = $this->getCrypt();
				$cookieKey = $this->getCookieKey();
				$cookieVal = FArrayHelper::getValue($_COOKIE, $cookieKey, '');

				if ($cookieVal !== '')
				{
					$this->status = Text::_('COM_FABRIK_LOADING_FROM_COOKIE');
					$this->statusId = _FABRIKFORMSESSION_LOADED_FROM_COOKIE;
					$hash = $crypt->decrypt($cookieVal);
				}
			}
		}

		if ($hash !== '')
		{
			// No point loading it if the hash is empty
			$row->load(array('hash' => $hash));
		}

		if (is_null($row->id))
		{
			$row->last_page = 0;
			$row->data = '';
		}

		$this->last_page = $row->last_page;
		$this->row = $row;

		return $row;
	}

	/**
	 * Get the cookie name
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function getCookieKey()
	{
		$key = (int) $this->user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();

		return $key;
	}

	/**
	 * If a plug has set a session var com_fabrik.form.X.session.on then we should be
	 * using the session cookie, see form confirmation plugin for this in use
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function canUseCookie()
	{
		$formId = $this->getFormId();

		if ($this->session->get('com_' . $this->package . '.form.' . $formId . '.session.on'))
		{
			return true;
		}

		return $this->useCookie;
	}

	/**
	 * Remove the saved session
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */

	public function remove()
	{
		// $$$ hugh - need to clear the 'session.on'.  If we're zapping the stored
		// session form data, doesn't matter who or what set 'session.on' ... it ain't there any more.
		$this->session->clear('com_' . $this->package . '.form.' . $this->getFormId() . '.session.on');
		$row = $this->getTable('Formsession', 'FabrikTable');
		$hash = '';

		if ((int) $this->user->get('id') !== 0)
		{
			$hash = $this->getHash();
		}
		else
		{
			if ($this->useCookie)
			{
				$crypt = $this->getCrypt();
				$cookieKey = (int) $this->user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();
				$cookieVal = FArrayHelper::getValue($_COOKIE, $cookieKey, '');

				if ($cookieVal !== '')
				{
					$hash = $crypt->decrypt($cookieVal);
				}
			}
		}

		$db = $row->getDbo();
		$row->hash = $hash;
		$query = $db->getQuery(true);
		$query->delete($db->qn($row->getTableName()))->where('hash = ' . $db->q($hash));
		$db->setQuery($query);
		$this->removeCookie();
		$this->row = $row;

		if ($db->execute())
		{
			return true;
		}
		else
		{
			$row->setError($db->getErrorMsg());

			return false;
		}
	}

	/**
	 * Get the hash identifier
	 * format userid:formid:rowid
	 *
	 * @return  string  hash
	 *
	 * @since 4.0
	 */
	public function getHash()
	{
		$userId = $this->getUserId();

		if (is_null($this->hash))
		{
			$this->hash = $userId . ':' . $this->getFormId() . ':' . $this->getRowId();
		}

		return $this->hash;
	}

	/**
	 * Get a the user id
	 *
	 * @return  mixed  user id if logged in, unique id if not
	 *
	 * @since 4.0
	 */
	protected function getUserId()
	{
		if ($this->user->get('id') == 0)
		{
			return uniqid();
		}

		return $this->user->get('id');
	}

	/**
	 * Det the form id whose record is being edited
	 *
	 * @param   int  $id  form id
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function setFormId($id)
	{
		$this->formId = $id;
	}

	/**
	 * Set the row id that is being edited or saved
	 *
	 * @param   int  $id  Row id
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function setRowId($id)
	{
		$this->rowId = (int) $id;
	}

	/**
	 * Gets the row id - if not set uses request 'rowid' var
	 *
	 * @return  int
	 *
	 * @since 4.0
	 */
	protected function getRowId()
	{
		if (is_null($this->rowId))
		{
			$this->rowId = $this->app->input->getString('rowid', '', 'string');
		}

		return (int) $this->rowId;
	}

	/**
	 * Gets the row id - if not set uses request 'rowId' var
	 *
	 * @return int  form id
	 *
	 * @since 4.0
	 */
	public function getFormId()
	{
		if (is_null($this->formId))
		{
			$this->formId = $this->app->input->getInt('formid');
		}

		return $this->formId;
	}
}
