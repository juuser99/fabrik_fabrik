<?php
/**
 * Forms list controller class.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Fabrik\Component\Fabrik\Administrator\Model\FormModel;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Forms list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_FORMS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'forms';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string $name   Model name
	 * @param   string $prefix Model prefix
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @since    4.0
	 *
	 * @return  FormModel
	 */
	public function getModel($name = FormModel::class, $prefix = '', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Attempt to alter the db structure to match the form's current status
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function updateDatabase()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&view=forms');
		$this->getModel()->updateDatabase();
		$this->setMessage(Text::_('COM_FABRIK_DATABASE_UPDATED'));
	}

	/**
	 * View the list data
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function listview()
	{
		$input = $this->input;
		$cid   = $input->get('cid', array(0), 'array');
		$cid   = $cid[0];
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_lists')->where('form_id = ' . (int) $cid);
		$db->setQuery($query);
		$listId = $db->loadResult();
		$this->setRedirect('index.php?option=com_fabrik&task=list.view&listid=' . $listId);
	}
}
