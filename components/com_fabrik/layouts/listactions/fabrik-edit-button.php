<?php
/**
 * Layout: list row buttons - rendered as a Bootstrap dropdown
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;

$d        = $displayData;
$btnClass = $d->action !== 'dropdown' ? 'btn btn-default' : '';
$class    = $btnClass . 'fabrik_edit fabrik__rowlink';
$editText = $d->action == 'dropdown' ? $d->editLabel : '<span class="hidden">' . $d->editLabel . '</span>';

?>
<a data-loadmethod="<?php echo $d->loadMethod; ?>"
	class="<?php echo $class;?> btn-default" <?php echo $d->editAttributes;?>
	data-list="<?php echo $d->dataList;?>"
	data-isajax="<?php echo $d->isAjax; ?>"
	data-rowid="<?php echo $d->rowId; ?>"
	href="<?php echo $d->editLink;?>"
	title="<?php echo $d->editLabel;?>">
	<?php echo Html::image('edit.png', 'list', '', array('alt' => $d->editLabel)); ?><?php echo $editText; ?>
</a>