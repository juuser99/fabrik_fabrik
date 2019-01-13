<?php
/**
 * Fabrik Admin Home Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;

use Fabrik\Helpers\StringHelper;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Fabrik\Component\Fabrik\Site\Model\ConnectionModel;
use Fabrik\Component\Fabrik\Site\Model\FormModel;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Admin Home Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class HomeModel extends FabrikAdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_HOME';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since 4.0
	 */
	public function getTable($type = 'Cron', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = Worker::getDbo(true);

		return parent::getTable($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}

	/**
	 * Get fabrikar.com rss feed
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getRSSFeed()
	{
		$data = new \SimpleXMLElement('http://feeds.feedburner.com/fabrik', LIBXML_NOCDATA, true);

		if (!$data)
		{
			$output = Text::_('Error: Feed not retrieved');
		}
		else
		{
			$data = json_decode(json_encode($data), true);
			$title = $data['channel']['title'];
			$link  = $data['channel']['link'];

			$output = '<table class="adminlist">';
			$output .= '<tr><th colspan="3"><a href="' . $link . '" target="_blank">' . Text::_($title) . '</th></tr>';

			$items    = $data['channel']['item'];
			$numItems = count($items);

			if ($numItems == 0)
			{
				$output .= '<tr><th>' . Text::_('No news items found') . '</th></tr>';
			}
			else
			{
				$k = 0;

				foreach ($items as $item)
				{
					$date = new \DateTime($item['pubDate']);
					$output .= '<tr><td class="row' . $k . '">';
					$output .= '<a href="' . $item['link'] . '" target="_blank">' . $item['title'] . '</a>';
					$output .= '<br />' . $date->format('Y-m-d');
					$description = StringHelper::truncate($item['description'], array('wordcount' => 50));
					$output .= '<br />' . $description;
					$output .= '</td></tr>';
					$k = 1 - $k;
				}
			}

			$output .= '</table>';
		}

		return $output;
	}

	/**
	 * Install sample data
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function installSampleData()
	{
		$cnn       = Worker::getConnection();
		$defaultDb = $cnn->getDb();
		$db        = Worker::getDbo(true);
		$group     = $this->getTable('Group');
		$config    = $this->config;

		$dbTableName = $config->get('dbprefix') . "fb_contact_sample";
		echo "<div style='text-align:left;border:1px dotted #cccccc;padding:10px;'>" . "<h3>Installing data...</h3><ol>";

		$group->name      = "Contact Details";
		$group->label     = "Contact Details";
		$group->published = 1;

		if (!$group->store())
		{
			Log::add($group->getError(), Log::WARNING, 'jerror');
		}

		$groupId = $db->insertid();

		$defaultDb->dropTable($dbTableName);

		echo "<li>Group 'Contact Details' created</li>";
		echo "<li>Element 'Email' added to group 'Contact Details'</li>";

		$group            = $this->getTable('Group');
		$group->name      = "Your Enquiry";
		$group->label     = "Your Enquiry";
		$group->published = 1;

		$group->store();

		$group2Id = $db->insertid();
		echo "<li>Group 'Your Enquiry' created</li>";

		echo "<li>Element 'Message' added to group 'Your Enquiry'</li>";

		$form                     = $this->getTable('Form');
		$form->label              = "Contact Us";
		$form->record_in_database = 1;
		$form->intro              = "This is a sample contact us form, that is stored in a database table";

		$form->submit_button_label = "Submit";
		$form->published           = 1;

		$form->form_template      = "bootstrap";
		$form->view_only_template = "bootstrap";

		$form->store();

		echo "<li>Form 'Contact Us' created</li>";
		$formId = $db->insertid();

		$query = $db->getQuery(true);
		$query->insert('#__{package}_formgroup')->set(array('form_id=' . (int) $formId, 'group_id=' . (int) $groupId, 'ordering=0'));
		$db->setQuery($query);

		$db->execute();

		$query = $db->getQuery(true);
		$query->insert('#__{package}_formgroup')->set(array('form_id=' . (int) $formId, 'group_id=' . (int) $group2Id, 'ordering=1'));
		$db->setQuery($query);

		$db->execute();

		echo "<li>Groups added to 'Contact Us' form</li>";
		$listModel           = FabrikModel::getInstance(ListModel::class);
		$list                = $this->getTable('List');
		$list->label         = "Contact Us Data";
		$list->introduction  = "This table stores the data submitted in the contact us form";
		$list->form_id       = $formId;
		$list->connection_id = $cnn->getConnection()->id;
		$list->db_table_name = $dbTableName;

		// Store without name quotes as that's db specific
		$list->db_primary_key = $dbTableName . '.id';
		$list->auto_inc       = 1;
		$list->published      = 1;
		$list->rows_per_page  = 10;
		$list->params         = $listModel->getDefaultParams();
		$list->template       = 'bootstrap';

		$list->store();
		echo "<li>Table for 'Contact Us' created</li></div>";
		$form->store();
		$formModel = FabrikModel::getInstance(FormModel::class);
		$formModel->setId($form->id);
		$formModel->form = $form;

		$listModel->setState('list.id', $list->id);
		$listModel->getItem();

		$elements = array('id' => array('plugin' => 'internalid', 'label' => 'id', 'group_id' => $groupId),
			'first_name' => array('plugin' => 'field', 'label' => 'First Name', 'group_id' => $groupId),
			'last_name' => array('plugin' => 'field', 'label' => 'Last Name', 'group_id' => $groupId),
			'email' => array('plugin' => 'field', 'label' => 'Email', 'group_id' => $groupId),
			'message' => array('plugin' => 'textarea', 'group_id' => $group2Id));

		return $listModel->createDBTable($list->db_table_name, $elements);
	}

	/**
	 * Empty all fabrik db tables of their data
	 *
	 * @return  void or JError
	 *
	 * @since 4.0
	 */
	public function reset()
	{
		$db     = Worker::getDbo(true);
		$prefix = '#__{package}_';
		$tables = array('cron', 'elements', 'formgroup', 'forms', 'form_sessions', 'groups', 'joins', 'jsactions', 'packages', 'lists',
			'validations', 'visualizations');

		foreach ($tables as $table)
		{
			$db->setQuery('TRUNCATE TABLE ' . $prefix . $table);
			$db->execute();
		}
	}

	/**
	 * Drop all the lists db tables
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function dropData()
	{
		/** @var ConnectionModel $connModel */
		$connModel = FabrikModel::getInstance(ConnectionModel::class);
		$connModel->setId(null); // was $item->connection_id but this doesn't exist
		$db    = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select("connection_id, db_table_name")->from('#__{package}_lists');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$connModel->setId($row->connection_id);
			$c        = $connModel->getConnection($row->connection_id);
			$fabrikDb = $connModel->getDb();
			$fabrikDb->dropTable($row->db_table_name);
		}
	}
}
