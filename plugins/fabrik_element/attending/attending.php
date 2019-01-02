<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.attending
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
use Joomla\CMS\Factory;
use Joomla\Component\Fabrik\Site\Plugin\AbstractElementPlugin;

defined('_JEXEC') or die();

/**
 * Plugin element to allow user to attend events, join groups etc.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.attending
 * @since       3.0
 */
class PlgFabrik_ElementAttending extends AbstractElementPlugin
{
	/**
	 * Db table field type
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $fieldDesc = 'TINYINT(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $fieldSize = '1';

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 *
	 * @since 4.0
	 */
	public function render($data, $repeatCounter = 0)
	{
		$id = $this->getHTMLId($repeatCounter);

		$layout                 = $this->getLayout('form');
		$displayData            = new \stdClass;
		$displayData->attendees = $this->getAttendees();
		$displayData->id        = $id;

		return $layout->render($displayData);
	}

	/**
	 * Get attendees
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	protected function getAttendees()
	{
		$input     = $this->app->input;
		$listModel = $this->getListModel();
		$list      = $listModel->getTable();
		$listId    = $list->id;
		$formId    = $listModel->getFormModel()->getId();
		$db        = $listModel->getDb();
		$query     = $db->getQuery(true);
		$rowId     = $input->get('row_id');

		$query->select('*')->from('#__fabrik_attending')->where('list_id = ' . (int) $listId)
			->where('form_id = ' . (int) $formId)
			->where('row_id = ' . $db->q($rowId))
			->where('element_id = ' . (int) $this->getId());

		$attending = $db->setQuery($query)->loadObjectList();

		foreach ($attending as $attend)
		{
			$attend->user = Factory::getUser($attend->user_id);
		}

		return $attending;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function elementJavascript($repeatCounter)
	{
		$input  = $this->app->input;
		$user   = $this->user;
		$params = $this->getParams();

		$id      = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$data    = $this->getFormModel()->data;
		$listid  = $this->getlistModel()->getTable()->id;
		$formid  = $input->getInt('formid');
		$row_id  = $input->get('rowid', '', 'string');

		$opts         = new \stdClass;
		$opts->row_id = $row_id;
		$opts->elid   = $this->getElement()->id;
		$opts->userid = (int) $user->get('id');
		$opts->view   = $input->get('view');

		return array('FbAttending', $id, $opts);
	}
}
