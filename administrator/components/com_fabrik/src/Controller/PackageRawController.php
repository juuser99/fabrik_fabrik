<?php
/**
 * Raw Package controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Raw Package controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class PackageRawController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_PACKAGE';

	/**
	 * @var string
	 *
	 * @since since 4.0
	 */
	protected $context = 'package';

	/**
	 * list of items
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function dolist()
	{
		$app      = Factory::getApplication();
		$input    = $app->input;
		$db       = Worker::getDbo(true);
		$query    = $db->getQuery(true);
		$list     = $input->get('list', 'form');
		$selected = $input->get('selected');
		$query->select('id, label')->from('#__fabrik_' . $list . 's');

		if ($selected != '')
		{
			// $query->where('id NOT IN ('.$selected.')');
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();
		echo "<ul id=\"$list-additems\">";

		if (empty($rows))
		{
			echo "<li>" . Text::sprintf('COM_FABRIK_NO_FREE_ITEMS_FOUND') . "</li>";
		}
		else
		{
			foreach ($rows as $row)
			{
				echo "<li><a href=\"#\" id=\"$row->id\">$row->label</a>";
			}
		}

		echo "</ul>";
		$script = "$('$list-additems').getElements('a').addEvent('click', function(e){
			Fabrik.fireEvent('fabrik.package.item.selected', [e]);
		});";
		Html::addScriptDeclaration($script);
	}
}
