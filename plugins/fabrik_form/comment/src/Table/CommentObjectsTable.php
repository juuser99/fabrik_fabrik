<?php
/**
 * @package     Fabrik\Plugin\FabrikForm\Comment\Table
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Fabrik\Plugin\FabrikForm\Comment\Table;

use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Database\DatabaseDriver;

/**
 * @package     Fabrik\Plugin\FabrikForm\Comment\Table
 *
 * @since       4.0
 */
class CommentObjectsTable extends FabTable
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