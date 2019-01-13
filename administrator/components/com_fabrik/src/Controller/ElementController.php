<?php
/**
 * Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

defined('_JEXEC') or die('Restricted access');

/**
 * Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ElementController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENT';

	/**
	 * Set a URL for browser redirection.
	 *
	 * @param   string $url  URL to redirect to.
	 * @param   string $msg  Message to display on redirect. Optional, defaults to value set internally by controller, if any.
	 * @param   string $type Message type. Optional, defaults to 'message'.
	 *
	 * @return    $this    This object to support chaining.
	 *
	 * @since 4.0
	 */
	public function setRedirect($url, $msg = null, $type = null)
	{
		$app           = Factory::getApplication();
		$confirmUpdate = $app->getUserState('com_fabrik.confirmUpdate');

		// @TODO override the redirect url if confirm update is needed and task appropriate
		if ($confirmUpdate == true)
		{
			// Odd nes where redirect url was blank - caused blank pages when editing an element
			$testUrl = $app->getUserState('com_fabrik.redirect', '');

			if ($testUrl !== '')
			{
				$url = $testUrl;
			}
		}

		$this->redirect = $url;

		if ($msg !== null)
		{
			// Controller may have set this directly
			$this->message = $msg;
		}
		// Ensure the type is not overwritten by a previous call to setMessage.
		$this->messageType = ($type === null || empty($this->messageType)) ? 'message' : $type;

		return $this;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @param   int    $recordId record id
	 * @param   string $urlVar   url var
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   11.1
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$gid    = $input->getInt('filter_groupId', 0);

		if ($gid !== 0)
		{
			$append .= '&filter_groupId=' . $gid;
		}

		return $append;
	}

	/**
	 * ask if the user really wants to update element field name/type
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function updateStructure()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabModel::getInstance(PluginManagerModel::class);
		$model         = $pluginManager->getPlugIn('field', 'element');
		$id            = $input->getInt('id');
		$model->setId($id);
		$db      = $model->getListModel()->getDb();
		$oldName = str_replace('`', '', $app->getUserState('com_fabrik.oldname'));
		$newName = $app->getUserState('com_fabrik.newname');
		$model->updateJoinedPks($oldName, $newName);
		$db->setQuery($app->getUserState('com_fabrik.q'));

		if (!$db->execute())
		{
			Log::add($db->stderr(true), Log::WARNING, 'jerror');
			$msg = '';
		}
		else
		{
			$msg = Text::_('COM_FABRIK_STRUCTURE_UPDATED');
		}

		if ($input->get('origtask') == 'save')
		{
			$this->setRedirect('index.php?option=com_fabrik&view=elements', $msg);
		}
		else
		{
			$this->setRedirect('index.php?option=com_fabrik&task=element.edit&id=' . $id, $msg);
		}
	}

	/**
	 * User decided to cancel update
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function cancelUpdateStructure()
	{
		Session::checkToken() or die('Invalid Token');
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabModel::getInstance(PluginManagerModel::class);
		$app           = Factory::getApplication();
		$input         = $app->input;
		$model         = $pluginManager->getPlugIn('field', 'element');
		$model->setId($input->getInt('id'));
		$element         = $model->getElement();
		$element->name   = $input->getWord('oldname');
		$element->plugin = $input->getWord('origplugin');
		$element->store();

		if ($input->get('origtask') == 'save')
		{
			$this->setRedirect('index.php?option=com_fabrik&view=elements');
		}
		else
		{
			$this->setRedirect('index.php?option=com_fabrik&task=element.edit&id=' . $element->id);
		}
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since 4.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$ok  = parent::save();
		$app = Factory::getApplication();

		if (!is_null($app->getUserState('com_fabrik.redirect')))
		{
			$this->setRedirect($app->getUserState('com_fabrik.redirect'));
			$app->setUserState('com_fabrik.redirect', null);
		}

		return $ok;
	}

	/**
	 * When you go from a child to parent element, check in child before redirect
	 *
	 * @deprecated - don't think its used?
	 *
	 * @return  void
	 *
	 * @since      4.0
	 */
	public function parentredirect()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$jForm = $input->get('jform', array(), 'array');
		$id    = (int) FArrayHelper::getValue($jForm, 'id', 0);
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabModel::getInstance(PluginManagerModel::class);
		$className     = $input->post->get('plugin', 'field');
		$elementModel  = $pluginManager->getPlugIn($className, 'element');
		$elementModel->setId($id);
		$row = $elementModel->getElement();
		$row->checkin();
		$to = $input->getInt('redirectto');
		$this->setRedirect('index.php?option=com_fabrik&task=element.edit&id=' . $to);
	}
}
