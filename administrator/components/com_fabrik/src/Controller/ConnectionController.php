<?php
/**
 * Connection controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;
use Joomla\Component\Fabrik\Site\Model\ConnectionModel as SiteConnectionModel;

/**
 * Connection controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ConnectionController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTION';

	/**
	 * Tries to connection to the database
	 *
	 * @return string connection message
	 *
	 * @since 4.0
	 */
	public function test()
	{
		Session::checkToken() or die('Invalid Token');
		$app   = Factory::getApplication();
		$input = $app->input;
		$cid   = $input->get('cid', array(), 'array');
		$cid   = array((int) $cid[0]);
		$link  = 'index.php?option=com_fabrik&view=connections';

		foreach ($cid as $id)
		{
			/** @var SiteConnectionModel $model */
			$model = FabrikModel::getInstance(SiteConnectionModel::class);
			$model->setId($id);

			if ($model->testConnection() == false)
			{
				Log::add(Text::_('COM_FABRIK_UNABLE_TO_CONNECT'), Log::WARNING, 'jerror');

				$this->setRedirect($link);

				return;
			}
		}

		$this->setRedirect($link, Text::_('COM_FABRIK_CONNECTION_SUCESSFUL'));
	}
}
