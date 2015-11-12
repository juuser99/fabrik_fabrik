/**
 * Admin Plugin Manager
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/*
 * global Fabrik:true, Joomla:true, fconsole:true, FabrikAdmin:true,
 * fabrikAdminPlugin:true
 */

var PluginManager = my.Class({

    pluginTotal: 0,

    topTotal: -1,

    constructor: function (plugins, id, type) {
        var self = this, i;
        if (typeof(plugins) === 'string') {
            plugins = [plugins];
        }
        this.id = id;
        this.plugins = plugins;
        this.type = type;
        $(document).ready(function () {
            i;
            /*self.accordion = new Fx.Accordion([], [], {
             alwaysHide: true,
             display   : -1,
             duration  : 'short'
             });*/
            for (i = 0; i < plugins.length; i++) {
                self.addTop(plugins[i]);
            }
            /*  this.periodical = setInterval(function () {
             this.iniAccordion.call(this, true);
             }, 500);*/

            self.watchPluginSelect();
            self.watchDelete();
            self.watchAdd();

            var pluginArea = $('#plugins');
            pluginArea.on('click', 'h3.title', function () {
                var target = $(this);
                pluginArea.find('h3.title').each(function () {
                    if ($(this) !== target) {
                        $(this).removeClass('pane-toggler-down');
                    }
                });
                target.toggleClass('pane-toggler-down');
            });

            self.watchDescriptions(pluginArea);
        });
    },

    /**
     *
     * @param {jQuery} pluginArea
     */
    watchDescriptions: function (pluginArea) {
        pluginArea.on('keyup', 'input[name*=plugin_description]', function () {
            var container = $(this).closest('.actionContainer'),
                title = container.find('.pluginTitle'),
                plugin = container.find('select[name*=plugin]').val(),
                desc = $(this).val();
            title.text(plugin + ': ' + desc);
        });
    },

    /*iniAccordion: function () {
     if (this.pluginTotal === this.plugins.length) {
     if (this.plugins.length === 1) {
     this.accordion.display(0);
     } else {
     this.accordion.display(-1);
     }
     clearInterval(this.periodical);
     }
     },*/

    /**
     * Has the form finished loading and are there any outstanding ajax requests
     *
     * @return bool
     */
    canSaveForm: function () {
        if (document.readyState !== 'complete') {
            return false;
        }
        return Fabrik.requestQueue.empty();
    },

    watchDelete: function () {
        var self = this;
        $('#adminForm').on('click', 'a.removeButton, a[data-button=removeButton]', function (event) {
            event.preventDefault();
            self.pluginTotal--;
            self.topTotal--;
            self.deletePlugin(event);
        });
    },

    watchAdd: function () {
        var self = this;
        $('#addPlugin').on('click', function (e) {
            e.stopPropagation();
            // self.accordion.display(-1);
            self.addTop();
        });
    },

    addTop: function (plugin) {
        var published, show_icon, validate_in, validation_on,
            self = this;
        if (typeof(plugin) === 'string') {
            published = 1;
            show_icon = false;
            plugin = plugin ? plugin : '';
            validate_in = '';
            validation_on = '';
        } else {
            // Validation plugins
            published = plugin ? plugin.published : 1;
            show_icon = plugin ? plugin.show_icon : 1;
            validate_in = plugin ? plugin.validate_in : 'both';
            validation_on = plugin ? plugin.validation_on : 'both';
            plugin = plugin ? plugin.plugin : '';
        }

        var div = $(document.createElement('div')).addClass('actionContainer panel accordion-group'),
            a = $(document.createElement('a')).addClass('accordion-toggle').attr({
                'href': '#'
            }),
            loading = Joomla.JText._('COM_FABRIK_LOADING').toLowerCase(),
            txt = plugin !== '' ? plugin + ' ' + loading : loading;

        a.append($(document.createElement('span')).addClass('pluginTitle').text(txt));
        var toggler = $(document.createElement('div')).addClass('title pane-toggler accordion-heading')
            .append($(document.createElement('strong')).append(a));
        var body = $(document.createElement('div')).addClass('accordion-body');

        div.append(toggler);
        div.append(body);
        div.appendTo($('#plugins'));
        //this.accordion.addSection(toggler, body);
        var tt_temp = this.topTotal + 1; //added temp variable

        // Ajax request to load the first part of the plugin form (do[plugin]
        // in, on)

        var d = {
            'option'          : 'com_fabrik',
            'view'            : 'plugin',
            'task'            : 'top',
            'format'          : 'raw',
            'type'            : this.type,
            'plugin'          : plugin,
            'plugin_published': published,
            'show_icon'       : show_icon,
            'validate_in'     : validate_in,
            'validation_on'   : validation_on,
            'c'               : this.topTotal,
            'id'              : this.id
        };

        $.ajax({
            url       : 'index.php',
            data      : d,
            beforeSend: function () {
                if (Fabrik.debug) {
                    fconsole('Fabrik pluginmanager: Adding', self.type, 'entry', tt_temp.toString());
                }
            }
        }).done(function (r) {
            body.append(r);
            if (plugin !== '') {
                // Sent temp variable as c to addPlugin, so they are aligned properly
                self.addPlugin(plugin, tt_temp);
            } else {
                toggler.find('span.pluginTitle').text(Joomla.JText._('COM_FABRIK_PLEASE_SELECT'));
            }
            self.updateBootStrap();
            FabrikAdmin.reTip();
        }).error(function (xhr, error) {
            fconsole('Fabrik pluginmanager addTop ajax exception:', error);
        });
        this.topTotal++;

        //Fabrik.requestQueue.add(request);
    },

    // Bootstrap specific

    updateBootStrap: function () {
        $('.radio.btn-group label').addClass('btn');

        $('.btn-group input[checked=checked]').each(function () {
            if ($(this).val() === '') {
                $('label[for=' + $(this).prop('id') + ']').addClass('active btn-primary');
            } else if ($(this).val() === '0') {
                $('label[for=' + $(this).prop('id') + ']').addClass('active btn-danger');
            } else {
                $('label[for=' + $(this).prop('id') + ']').addClass('active btn-success');
            }
            $('*[rel=tooltip]').tooltip();
        });

        $('.hasTip').each(function () {
            var title = $(this).prop('title');
            var parts = title.split('::', 2);
            $(this).data('tip:title', parts[0]);
            $(this).data('tip:text', parts[1]);
        });
        var JTooltips = new Tips($('.hasTip'), {
            maxTitleChars: 50,
            fixed        : false
        });
    },

    /**
     * Watch the plugin select list
     */

    watchPluginSelect: function () {
        var self = this;
        $('#adminForm').on('change', 'select.elementtype', function (event) {
            event.preventDefault();
            var plugin = $(this).val();
            var container = $(this).closest('.pluginContainer'),
                select = Joomla.JText._('COM_FABRIK_LOADING').toLowerCase(),
                pluginName = plugin !== '' ? plugin + ' ' + select : select;
            $(this).closest('.actionContainer').find('span.pluginTitle').text(pluginName);
            var c = parseInt(container.prop('id').replace('formAction_', ''), 10);
            self.addPlugin(plugin, c);
        });
    },

    addPlugin: function (plugin, c) {
        var self = this;
        c = typeOf(c) === 'number' ? c : this.pluginTotal;
        if (plugin === '') {
            $($('#plugins').find('.actionContainer')[c]).find('.pluginOpts').empty();
            return;
        }

        // Ajax request to load the plugin content
        var request = new $.ajax({
            url       : 'index.php',
            data      : {
                'option': 'com_fabrik',
                'view'  : 'plugin',
                'format': 'raw',
                'type'  : this.type,
                'plugin': plugin,
                'c'     : c,
                'id'    : this.id
            },
            beforeSend: function () {
                if (Fabrik.debug) {
                    fconsole('Fabrik pluginmanager: Loading', self.type, 'type', plugin, 'for entry', c.toString());
                }
            }
        }).fail(function (jqXHR, textStatus) {
                fconsole('Fabrik pluginmanager addPlugin ajax exception:', textStatus);
            }).success(function (r) {

                var container = $($('#plugins').find('.actionContainer')[c]),
                    title = container.find('span.pluginTitle'),
                    heading = plugin,
                    desc = container.find('input[name*=plugin_description]');
                if (desc) {
                    heading += ': ' + desc.val();
                }
                container.find('.pluginOpts').html(r);
                title.text(heading);
                self.pluginTotal++;
                self.updateBootStrap();
                FabrikAdmin.reTip();
            });
        //Fabrik.requestQueue.add(request);
    },

    deletePlugin: function (e) {
        var c = $(e.target).closest('fieldset.pluginContainer');
        if (c.length === 0) {
            return;
        }
        if (Fabrik.debug) {
            fconsole('Fabrik pluginmanager: Deleting', this.type, 'entry', c.id, 'and renaming later entries');
        }
        /**
         * The following code reduces the index in ids, names and <label for=id>
         * for all entries after the entry that is being deleted. Paul 20131102
         * Extended to handle more field types and ids in all tags not just
         * fieldset This code handles the following tags:
         * fieldset.pluginContainer id='formAction_x' label id='id-x(stuff)-lbl'
         * for='name-x(stuff)-lbl' select id='id-x' name='name[x]' fieldset
         * id='id-x-' class='radio btn-group' input type='radio'
         * id='id-x(stuff)' label for='name-x(stuff)-lbl' class='btn' input
         * type='text' id='id-x' name='name[x]' textarea id='id-x'
         * name='name[x]'
         */
        if (c.id.match(/_\d+$/)) {
            var x = parseInt(c.id.match(/_(\d+)$/)[1], 10),
                plugins = $('#plugins');
            plugins.find('input, select, textarea, label, fieldset').each(function () {
                // Get index from name or id
                var s = this.name ? this.name.match(/\[(\d+)\]/) : null;
                if (!s && this.id) {
                    s = this.id.match(/-(\d+)/);
                }
                if (!s && $(this).prop('tagName').toLowerCase() === 'label' && $(this).prop('for')) {
                    s = $(this).prop('for').match(/-(\d+)/);
                }
                if (s) {
                    var c = parseInt(s[1], 10);
                    if (c > x) {
                        c--;
                        if (this.name) {
                            this.name = this.name.replace(/(\[)(\d+)(\])/, '[' + c + ']');
                        }
                        if (this.id) {
                            this.id = this.id.replace(/(-)(\d+)/, '-' + c);
                        }
                        if ($(this).prop('tagName').toLowerCase() === 'label' && $(this).prop('for')) {
                            $(this).prop('for', $(this).prop('for').replace(/(-)(\d+)/, '-' + c));
                        }
                    }
                }
            });
            plugins.find('fieldset.pluginContainer').each(function () {
                if (this.id.match(/formAction_\d+$/)) {
                    var c = parseInt(this.id.match(/formAction_(\d+)$/)[1], 10);
                    if (c > x) {
                        c = c - 1;
                        this.id = this.id.replace(/(formAction_)(\d+)$/, '$1' + c);
                    }
                }
            });
        }
        e.stopPropagation();
        $(e.target).closest('.actionContainer').remove();
    }

});

var fabrikAdminPlugin = my.Class({

    options    : {},
    constructor: function (name, label, options) {
        this.name = name;
        this.label = label;
        this.options = $.extend(this.options, options);
    },

    cloned: function () {

    }

});