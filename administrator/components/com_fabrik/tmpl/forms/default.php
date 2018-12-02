<?php
/**
 * Admin Forms List Tmpl
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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('script', 'system/multiselect.js', false, true);
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<form action="<?php echo Route::_('index.php?option=com_fabrik&view=forms'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <th width="2%">
                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'f.id', $listDirn, $listOrder); ?>
                    </th>
                    <th width="1%">
	                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                    </th>
                    <th width="35%">
                        <?php echo HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LABEL', 'f.label', $listDirn, $listOrder); ?>
                    </th>
                    <th width="10%">
                        <?php echo Text::_('COM_FABRIK_ELEMENT'); ?>
                    </th>
                    <th width="5%">
                        <?php echo Text::_('COM_FABRIK_CONTENT_TYPE'); ?>
                    </th>
                    <th width="10%">
                        <?php echo Text::_('COM_FABRIK_UPDATE_DATABASE'); ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_FABRIK_VIEW_DATA'); ?>
                    </th>
                    <th width="5%">
                        <?php echo HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'f.published', $listDirn, $listOrder); ?>
                    </th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <td colspan="6">
                        <?php echo $this->pagination->getListFooter(); ?>
                    </td>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach ($this->items as $i => $item) :
                    $ordering    = ($listOrder == 'ordering');
                    $link       = Route::_('index.php?option=com_fabrik&task=form.edit&id=' . (int) $item->id);
                    $canCreate  = $user->authorise('core.create', 'com_fabrik.form.1');
                    $canEdit    = $user->authorise('core.edit', 'com_fabrik.form.1');
                    $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
                    $canChange  = $user->authorise('core.edit.state', 'com_fabrik.form.1') && $canCheckin;
                    $params     = new Registry($item->params);

                    $elementLink = Route::_('index.php?option=com_fabrik&task=element.edit&id=0&filter_groupId=' . $item->group_id);
                    ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td><?php echo $item->id; ?></td>
                        <td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                        <td>
                            <?php if ($item->checked_out) : ?>
                                <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'forms.', $canCheckin); ?>
                            <?php endif; ?>
                            <?php
                            if ($item->checked_out && ($item->checked_out != $user->get('id')))
                            {
                                echo Text::_($item->label);
                            }
                            else
                            {
                                ?>
                                <a href="<?php echo $link; ?>">
                                    <?php echo Text::_($item->label); ?>
                                </a>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="<?php echo $elementLink ?>">
                                <i class="icon-plus"></i> <?php echo Text::_('COM_FABRIK_ADD'); ?>
                            </a>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button data-toggle="dropdown" class="btn btn-micro">
                                    <span class="fa fa-caret-down"></span>
                                    <span class="element-invisible">Actions for: COM_FABRIK_EXPORT_CONTENT_TYPE</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="javascript://" onclick="listItemTask('cb<?php echo $i; ?>', 'form.createContentType')">
                                        <span class="icon-upload"></span> <?php echo JText::_('COM_FABRIK_CONTENT_TYPE_EXPORT'); ?>
                                    </a>
                                    <?php
                                    if ($params->get('content_type_path', '') !== '') :?>
                                    <a class="dropdown-item" href="index.php?option=com_fabrik&task=form.downloadContentType&cid=<?php echo $item->id; ?>">
                                        <span class="icon-download"></span> <?php echo JText::_('COM_FABRIK_CONTENT_TYPE_DOWNLOAD'); ?>
                                    </a>
                                    <?php

                                    endif;
                                    ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','forms.updateDatabase')">
                                <i class="icon-refresh"></i> <?php echo Text::_('COM_FABRIK_UPDATE_DATABASE'); ?>
                            </a>
                        </td>
                        <td>
                            <a href="index.php?option=com_fabrik&task=list.view&listid=<?php echo $item->list_id ?>">
                                <i class="icon-list-view"></i> <?php echo Text::_('COM_FABRIK_VIEW_DATA'); ?>
                            </a>
                        </td>
                        <td class="center">
                            <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'forms.', $canChange); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>
            </table>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>
