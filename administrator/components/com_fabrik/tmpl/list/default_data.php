<?php
/**
 * Admin List Tmpl
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
use Joomla\CMS\Language\Text;

$doc = \Joomla\CMS\Factory::getDocument();
$rtlDir = $doc->direction === 'rtl' ? 'left' : 'right';
$rtlDirInv = $doc->direction === 'rtl' ? 'right' : 'left';
?>
<?php echo HTMLHelper::_('bootstrap.startTabSet', 'dataTabs', array('active' => 'data-data')); ?>

<?php echo HTMLHelper::_('bootstrap.addTab', 'dataTabs', 'data-data', Text::_('COM_FABRIK_DATA')); ?>
<div class="row">
    <div class="col-12">
        <fieldset class="form-horizontal">
		    <?php
		    $this->field = $this->form->getField('connection_id');
		    echo $this->loadTemplate('control_group');
		    if ($this->item->id == 0) :
			    $this->field = $this->form->getField('_database_name');
			    echo $this->loadTemplate('control_group');
			    echo $this->form->getLabel('or');
		    endif;
		    $this->field = $this->form->getField('db_table_name');
		    echo $this->loadTemplate('control_group');
		    $this->field = $this->form->getField('db_primary_key');
		    echo $this->loadTemplate('control_group');
		    $this->field = $this->form->getField('auto_inc');
		    echo $this->loadTemplate('control_group');
		    ?>

            <label for="order_by"><?php echo Text::_('COM_FABRIK_FIELD_ORDER_BY_LABEL'); ?></label>
            <div id="orderByTd" style="margin:4px 0 0 2px">
			    <?php
			    for ($o = 0; $o < count($this->order_by); $o++) : ?>
                    <div class="orderby_container" style="margin-bottom:3px;clear:left;float:<?php echo $rtlDirInv; ?>">
					    <?php
					    echo FArrayHelper::getValue($this->order_by, $o, $this->order_by[0]);
					    if ((int) $this->item->id !== 0) :
						    echo FArrayHelper::getValue($this->order_dir, $o)?>
                            <div class="btn-group pull-<?php echo $rtlDir; ?>">
                                <a class="btn btn-success addOrder" href="#"><i class="icon-plus"></i> </a>
                                <a class="btn btn-danger deleteOrder" href="#"><i class="icon-minus"></i> </a>
                            </div>
					    <?php endif; ?>
                    </div>
			    <?php endfor; ?>
            </div>
        </fieldset>

    </div>
</div>
<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

<?php echo HTMLHelper::_('bootstrap.addTab', 'dataTabs', 'data-groupby', Text::_('COM_FABRIK_GROUP_BY')); ?>
<div class="row">
    <div class="col-12">
        <fieldset class="form-horizontal">
		    <?php
		    foreach ($this->form->getFieldset('grouping') as $this->field):
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    foreach ($this->form->getFieldset('grouping2') as $this->field):
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

<?php echo HTMLHelper::_('bootstrap.addTab', 'dataTabs', 'data-prefilter', Text::_('COM_FABRIK_PREFILTER')); ?>
<div class="row">
    <div class="col-12">
        <fieldset class="form-horizontal">
            <legend><?php echo Text::_('COM_FABRIK_PREFILTERS')?></legend>

            <a class="btn" href="#" onclick="oAdminFilters.addFilterOption(); return false;">
                <i class="icon-plus"></i> <?php echo Text::_('COM_FABRIK_ADD'); ?>
            </a>
            <div id="prefilters" style="padding-top:20px">
                <table class="table table-striped" width="100%">
                    <tbody id="filterContainer">
                    </tbody>
                </table>
            </div>
		    <?php foreach ($this->form->getFieldset('prefilter') as $this->field):
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

<?php echo HTMLHelper::_('bootstrap.addTab', 'dataTabs', 'table-sliders-data-joins', Text::_('COM_FABRIK_JOINS')); ?>
<div class="row">
    <div class="col-12">
        <fieldset>
            <legend>
			    <?php echo Text::_('COM_FABRIK_JOINS');?>
            </legend>
		    <?php if ($this->item->id != 0) { ?>
                <a href="#" id="addAJoin" class="btn">
                    <i class="icon-plus"></i>  <?php echo Text::_('COM_FABRIK_ADD'); ?>
                </a>
                <div id="joindtd" style="margin-top:20px"></div>
			    <?php
			    foreach ($this->form->getFieldset('joins') as $this->field):
				    echo $this->loadTemplate('control_group');
			    endforeach;
			    ?>
			    <?php
		    } else {
			    echo Text::_('COM_FABRIK_AVAILABLE_ONCE_SAVED');
		    }
		    ?>
        </fieldset>

    </div>
</div>
<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>