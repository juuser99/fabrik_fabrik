<?php
/**
 * Layout: list edit button
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.3
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;

$d = $displayData;

$btnClass = $d->action !== 'dropdown' ? 'btn btn-default' : '';
$class    = $btnClass . 'fabrik_view fabrik__rowlink';
$viewText = $d->action === 'dropdown' ? $d->viewLabel : '<span class="hidden">' . $d->viewLabel . '</span>';

?>
<a data-loadmethod="<?php echo $d->loadMethod; ?>"
	class="<?php echo $class; ?>" <?php echo $d->detailsAttributes; ?>
	data-list="<?php echo $d->dataList; ?>"
	href="<?php echo $d->link; ?>"
	title="<?php echo $d->viewLabel; ?>"
	target="<?php echo $d->viewLinkTarget; ?>">
	<?php echo Html::image($d->list_detail_link_icon, 'list', '', array('alt' => $d->viewLabel)); ?>
	<?php echo $viewText; ?>
</a>