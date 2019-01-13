<?php
/**
 * @package     Joomla\Plugin\FabrikForm\Comment\Table
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\FabrikForm\Comment\Table;

use Joomla\Component\Fabrik\Administrator\Table\FabrikTable;
use Joomla\Database\DatabaseDriver;

/**
 * @package     Joomla\Plugin\FabrikForm\Comment\Table
 *
 * @since       4.0
 */
class CommentObjectsTable extends FabrikTable
{
	/**
	 * CommentTable constructor.
	 *
	 * @param DatabaseDriver $db
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__jcomments_objects', 'id', $db);
	}
}