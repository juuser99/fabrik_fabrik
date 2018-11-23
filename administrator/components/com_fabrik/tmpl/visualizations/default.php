<?php
/**
 * Admin Visualization List Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('script','system/multiselect.js',false,true);

$user = Factory::getUser();
$userId	= $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
?>
<form action="<?php echo Route::_('index.php?option=com_fabrik&view=visualizations'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div id="j-sidebar-container" class="col-2">
			<?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="col-10">
			<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

            <div class="clearfix"></div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th width="2%"><?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_ID', 'v.id', $listDirn, $listOrder); ?></th>
                        <th width="1%"> <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" /> </th>
                        <th width="35%" >
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LABEL', 'v.label', $listDirn, $listOrder); ?>
                        </th>
                        <th width="35%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_FABRIK_TYPE', 'v.plugin', $listDirn, $listOrder); ?>
                        </th>
                        <th width="5%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'v.published', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                <?php foreach ($this->items as $i => $item) :
                    $ordering = ($listOrder == 'ordering');
                    $link = Route::_('index.php?option=com_fabrik&task=visualization.edit&id=' . (int) $item->id);
                    $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                    $canChange = true;
                    ?>

                    <tr class="row<?php echo $i % 2; ?>">
                            <td>
                                <?php echo $item->id; ?>
                            </td>
                            <td>
                                <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                            </td>
                            <td>
                                <?php if ($item->checked_out) : ?>
                                    <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'visualizations.', $canCheckin); ?>
                                <?php endif; ?>
                                <?php
                                if ($item->checked_out && ( $item->checked_out != $user->get('id'))) :
                                    echo $item->label;
                                else:
                                ?>
                                <a href="<?php echo $link; ?>">
                                    <?php echo $item->label; ?>
                                </a>
                            <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $item->plugin; ?>
                            </td>
                            <td class="center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'visualizations.', $canChange); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
