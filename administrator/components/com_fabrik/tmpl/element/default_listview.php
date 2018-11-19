<?php
/**
 * Admin Element Edit - List view Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>
<?php echo HtmlHelper::_('bootstrap.startTabSet', 'listviewTabs', array('active' => 'listview-details')); ?>
<?php echo HtmlHelper::_('bootstrap.addTab', 'listviewTabs', 'listview-details', Text::_('COM_FABRIK_ELEMENT_LABEL_LIST_SETTINGS_DETAILS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
                <?php foreach ($this->form->getFieldset('listsettings') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
                <?php foreach ($this->form->getFieldset('listsettings2') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
            </fieldset>
        </div>
    </div>
<?php echo HtmlHelper::_('bootstrap.endTab'); ?>
<?php echo HtmlHelper::_('bootstrap.addTab', 'listviewTabs', 'listview-icons', Text::_('COM_FABRIK_ELEMENT_LABEL_ICONS_SETTINGS_DETAILS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
                <?php foreach ($this->form->getFieldset('icons') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
            </fieldset>

        </div>
    </div>
<?php echo HtmlHelper::_('bootstrap.endTab'); ?>
<?php echo HtmlHelper::_('bootstrap.addTab', 'listviewTabs', 'listview-filters', Text::_('COM_FABRIK_ELEMENT_LABEL_FILTERS_DETAILS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
                <?php foreach ($this->form->getFieldset('filters') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
                <?php foreach ($this->form->getFieldset('filters2') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
            </fieldset>
        </div>
    </div>
<?php echo HtmlHelper::_('bootstrap.endTab'); ?>
<?php echo HtmlHelper::_('bootstrap.addTab', 'listviewTabs', 'listview-css', Text::_('COM_FABRIK_ELEMENT_LABEL_CSS_DETAILS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
                <?php foreach ($this->form->getFieldset('viewcss') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
            </fieldset>
        </div>
    </div>
<?php echo HtmlHelper::_('bootstrap.endTab'); ?>
<?php echo HtmlHelper::_('bootstrap.addTab', 'listviewTabs', 'listview-calculations', Text::_('COM_FABRIK_ELEMENT_LABEL_CALCULATIONS_DETAILS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
                <div class="row">
                    <div class="col-md-6">
                        <?php
                        $fieldsets = $this->form->getFieldsets();
                        $cals = array('calculations-sum', 'calculations-avg', 'calculations-median');
                        foreach ($cals as $cal) :?>
                            <legend><?php echo Text::_($fieldsets[$cal]->label); ?></legend>
                            <?php foreach ($this->form->getFieldset($cal) as $this->field) :
                                echo $this->loadTemplate('control_group');
                            endforeach;
                        endforeach;
                        ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $cals = array('calculations-count', 'calculations-custom');
                        foreach ($cals as $cal) :?>
                            <legend><?php echo Text::_($fieldsets[$cal]->label); ?></legend>
                            <?php foreach ($this->form->getFieldset($cal) as $this->field) :
                                echo $this->loadTemplate('control_group');
                            endforeach;
                        endforeach;
                        ?>
                    </div>
                </div>
            </fieldset>

        </div>
    </div>
<?php echo HtmlHelper::_('bootstrap.endTab'); ?>
<?php echo HtmlHelper::_('bootstrap.endTabSet'); ?>