<?php
/**
 * Admin Lists List Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('script','system/multiselect.js', false, true);

$user	   = Factory::getUser();
$userId	   = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');

?>
<form action="<?php echo Route::_('index.php?option=com_fabrik&view=lists'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div id="j-sidebar-container" class="col-md-2">
			<?php echo $this->sidebar; ?>
        </div>

        <div class="col-md-10">
            <div id="j-main-container" class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
                <div class="clearfix"> </div>

                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th width="2%">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'l.id', $listDirn, $listOrder); ?>
                        </th>
                        <th width="1%">
	                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </th>
                        <th width="16%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LIST_NAME', 'label', $listDirn, $listOrder); ?>
                        </th>
                        <th width="17%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_FABRIK_DB_TABLE_NAME', 'db_table_name', $listDirn, $listOrder); ?>
                        </th>
                        <th width="14%">
							<?php echo Text::_('COM_FABRIK_ELEMENT');?>
                        </th>
                        <th width="14%">
							<?php echo Text::_('COM_FABRIK_FORM'); ?>
                        </th>
                        <th width="16%">
							<?php echo Text::_('COM_FABRIK_VIEW_DATA');?>
                        </th>
                        <th width="20%">
							<?php echo Text::_('COM_FABRIK_VIEW_DETAILS'); ?>
                        </th>
                        <th width="5%">
							<?php echo HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'published', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td colspan="9">
							<?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                    </tfoot>
                    <tbody>
					<?php foreach ($this->items as $i => $item) :
						$ordering = ($listOrder == 'ordering');
						$link = Route::_('index.php?option=com_fabrik&task=list.edit&id=' . $item->id);
						$params = new Registry($item->params);
						$elementLink = Route::_('index.php?option=com_fabrik&task=element.edit&id=0&filter_groupId=' . $this->tableGroups[$item->id]->group_id);
						$formLink = Route::_('index.php?option=com_fabrik&task=form.edit&id=' . $item->form_id);
						$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
						$canChange = true;
						?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td>
								<?php echo $item->id; ?>
                            </td>
                            <td><?php echo HTMLHelper::_('searchtools.id', $i, $item->id); ?></td>
                            <td>
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jsearchtools.checkedout', $i, $item->editor, $item->checked_out_time, 'lists.', $canCheckin); ?>
								<?php endif; ?>
								<?php
								if ($item->checked_out && ( $item->checked_out != $user->get('id'))) : ?>
                                    <span class="editlinktip hasTip"
                                          title="foo <?php echo Text::_($item->label) . "::" . $params->get('note'); ?>"> <?php echo $item->label; ?>
                                </span>
								<?php else : ?>
                                    <a href="<?php echo $link;?>">
                                    <span class="editlinktip hasTip" title="<?php echo $item->label . "::" . $params->get('note'); ?>">
                                        <?php echo Text::_($item->label); ?>
                                    </span>
                                    </a>
								<?php endif; ?>
                            </td>
                            <td>
								<?php echo $item->db_table_name;?>
                            </td>
                            <td>
                                <a href="<?php echo $elementLink?>">
                                    <i class="icon-plus"></i> <?php echo Text::_('COM_FABRIK_ADD');?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo $formLink; ?>">
                                    <i class="icon-pencil"></i> <?php echo Text::_('COM_FABRIK_EDIT'); ?>
                                </a>
                            </td>
                            <td>
                                <a href="index.php?option=com_fabrik&task=list.view&listid=<?php echo $item->id;?>">
                                    <i class="icon-list-view"></i> <?php echo Text::_('COM_FABRIK_VIEW_DATA');?>
                                </a>
                            </td>
                            <td>
                                <a href="#showlinkedelements" onclick="return listItemTask('cb<?php echo $i;?>','list.showLinkedElements');">
									<?php echo Text::_('COM_FABRIK_VIEW_DETAILS');?>
                                </a>
                            </td>
                            <td class="center">
								<?php echo HTMLHelper::_('jsearchtools.published', $item->published, $i, 'lists.', $canChange);?>
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
    </div>
</form>
