/**
 * Admin Element Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/* global fconsole:true, FabrikAdmin:true, Fabrik:true, PluginManager:true, Joomla:true */

var fabrikAdminElement = my.Class(PluginManager, {

    options: {
        id          : 0,
        parentid    : 0,
        jsevents    : [],
        jsTotal     : 0,
        deleteButton: 'removeButton'
    },

    jsCounter: -1,
    jsAjaxed : 0,

    constructor: function (plugins, options, id) {
        var self = this;
        if (Fabrik.debug) {
            fconsole('Fabrik adminelement.js: Initialising', plugins, options, id);
        }
        fabrikAdminElement.Super.call(this, plugins, id, 'validationrule');
        this.options = $.extend(this.options, options);
        this.setParentViz();

       /* this.jsAccordion = new Fx.Accordion([], [], {
            alwaysHide: true,
            display   : -1,
            duration  : 'short'
        });*/
        $(document).ready(function () {
            if ($('#addJavascript').length === 0) {
                fconsole('Fabrik adminelement.js: javascript tab Add button not found');
            } else {
                $('#addJavascript').on('click', function (e) {
                    e.stopPropagation();
                    //self.jsAccordion.display(-1);
                    self.addJavascript();
                });
            }

            self.watchLabel();
            self.watchGroup();
            self.options.jsevents.each(function (opt) {
                self.addJavascript(opt);
            });

          /*  self.jsPeriodical = setInterval(function () {
                self.iniJsAccordion.call(self, true);
            }, 500);*/

            $('#jform_plugin').on('change', function (e) {
                self.changePlugin(e);
            });

            $('#javascriptActions').on('click', 'a[data-button=removeButton]', function (e) {
                e.stopPropagation();
                self.deleteJS(self);
            });

            $('#javascriptActions').on('change', 'select[id^="jform_action-"],select[id^="jform_js_e_event-"],select[id^="jform_js_e_trigger-"],select[id^="jform_js_e_condition-"],input[id^="jform_js_e_value-"])', function () {
                self.setAccordionHeader($(this).closest('.actionContainer'));
            });

            var pluginArea = $('#plugins');
            pluginArea.on('click', 'h3.title', function () {
                var target = this;
                $('#plugins').find('h3.title').each(function () {
                    if (this !== target) {
                        $(this).removeClass('pane-toggler-down');
                    }
                });
                $(target).toggleClass('pane-toggler-down');
            });
        });
    },

    /**
     * Automatically fill in the db table name from the label if no
     * db table name existed when the form loaded and when the user has not
     * edited the db table name.
     */
    watchLabel: function () {
        var self = this;
        this.autoChangeDbName = $('#jform_name').val() === '';
        $('#jform_label').on('keyup', function () {
            if (self.autoChangeDbName) {
                var label = $('#jform_label').val().trim().toLowerCase();
                label = label.replace(/\W+/g, '_');
                $('#jform_name').val(label);
            }
        });

        $('#jform_name').on('keyup', function () {
            self.autoChangeDbName = false;
        });
    },

    /**
     * Set the last selected group as a cookie value.
     * Then on page load if no group set, set to the cookie value.
     */
    watchGroup: function () {
        var cookieName = 'fabrik_element_group';
        if ($('#jform_group_id').val() === '') {
            var keyValue = document.cookie.match('(^|;) ?' + cookieName + '=([^;]*)(;|$)');
            var val = keyValue ? keyValue[2] : null;
            $('#jform_group_id').val(val);
        }

        $('#jform_group_id').on('change', function () {
            var value = $('#jform_group_id').val();
            var date = new Date();
            date.setTime(date.getTime() + (1 * 24 * 60 * 60 * 1000));
            var expires = '; expires=' + date.toGMTString();
            document.cookie = cookieName + '=' + encodeURIComponent(value) + expires;
        });
    },

    /*iniJsAccordion: function () {
        if (this.jsAjaxed === this.options.jsevents.length) {
            if (this.options.jsevents.length === 1) {
                this.jsAccordion.display(0);
            } else {
                this.jsAccordion.display(-1);
            }
            clearInterval(this.jsPeriodical);
        }
    },*/

    changePlugin: function (e) {
        var self = this;
        $('#plugin-container').empty().append($(document.createElement('span'))
                .text(Joomla.JText._('COM_FABRIK_LOADING'))
        );
        var myAjax = $.ajax({
            url           : 'index.php',
            'evalResponse': false,
            'evalScripts' : function (script, text) {
                this.script = script;
            }.bind(this),
            'data'        : {
                'option': 'com_fabrik',
                'id'    : this.options.id,
                'task'  : 'element.getPluginHTML',
                'format': 'raw',
                'plugin': $(e.target).val()
            },
        }).done(function (r) {
            $('#plugin-container').html(r);
            Browser.exec(self.script);
            self.updateBootStrap();
            FabrikAdmin.reTip();
        });
        Fabrik.requestQueue.add(myAjax);
    },

    deleteJS: function (target) {
        var c = target.closest('div.actionContainer');
        if (Fabrik.debug) {
            fconsole('Fabrik adminelement.js: Deleting JS entry: ', c.id);
        }
        c.remove();
        this.jsAjaxed--;
    },

    addJavascript: function (opt) {
        var jsId = opt && opt.id ? opt.id : 0,
            self = this,
        // Ajax request to load the first part of the plugin form
        // (do[plugin] in, on)
            div = $(document.createElement('div')).addClass('actionContainer panel accordion-group'),
            a = $(document.createElement('a')).addClass('accordion-toggle').attr({
                'href': '#'
            });
        a.append($(document.createElement('span')).addClass('pluginTitle').text(Joomla.JText._('COM_FABRIK_LOADING')));
        var toggler = $(document.createElement('div')).addClass('title pane-toggler accordion-heading')
            .append($(document.createElement('strong')).append(a));
        var body = $(document.createElement('div')).addClass('accordion-body');

        div.append(toggler);
        div.append(body);
        div.appendTo($('javascriptActions'));
        var c = this.jsCounter;
        var request = new $.ajax({
            url : 'index.php',
            data: {
                'option'          : 'com_fabrik',
                'view'            : 'plugin',
                'task'            : 'top',
                'format'          : 'raw',
                'type'            : 'elementjavascript',
                'plugin'          : null,
                'plugin_published': true,
                'c'               : c,
                'id'              : jsId,
                'elementid'       : this.id
            }
        }).always(function () {
                if (Fabrik.debug) {
                    fconsole('Fabrik adminelement.js: Adding JS entry', (c + 1).toString());
                }
            }).done(function (res) {
                body.append(res);
                body.find('textarea[id^="jform_code-"]').on('change', function () {
                    self.setAccordionHeader($(this).closest('.actionContainer'));
                });
                self.setAccordionHeader(div);
                self.jsAjaxed++;
                self.updateBootStrap();
                FabrikAdmin.reTip();
            }).fail(function (jqxhr, textStatus, error) {
                fconsole('Fabrik adminelement.js addJavascript: ajax failure: ', textStatus, error);
            });
        this.jsCounter++;
        Fabrik.requestQueue.add(request);
        this.updateBootStrap();
        FabrikAdmin.reTip();
    },

    setAccordionHeader: function (c) {
        /**
         * Sets accordion header as follows:
         *
         * 1. If action is '' use COM_FABRIK_PLEASE_SELECT, otherwise use "On"
         * followed by action text
         *
         * 2. If code is set, append either comment text from first line if it
         * exists or "Javascript Inline Code"
         *
         * 3. If code is NOT set, append the event, trigger, condition and value
         * fields
         **/
        if (typeOf(c) === 'null') {
            return;
        }
        var header = c.find('span.pluginTitle');
        var action = c.find('select[id^="jform_action-"]');
        if (action.value === '') {
            header.html('<span style="color:red;">' + Joomla.JText._('COM_FABRIK_JS_SELECT_EVENT') + '</span>');
            return;
        }
        var s = 'on ' + action.getSelected()[0].text + ' : ';
        var code = c.find('textarea[id^="jform_code-"]');
        var event = c.find('select[id^="jform_js_e_event-"]');
        var trigger = c.find('select[id^="jform_js_e_trigger-"]');
        var name = document.id('jform_name');
        var value = c.find('input[id^="jform_js_e_value-"]');
        var condition = c.find('select[id^="jform_js_e_condition-"]');
        var t = '';
        if (code.value.clean() !== '') {
            var first = code.value.split('\n')[0].trim();
            var comment = first.match(/^\/\*(.*)\*\//);
            if (comment) {
                t = comment[1];
            } else {
                t = Joomla.JText._('COM_FABRIK_JS_INLINE_JS_CODE');
            }
            if (code.value.replace(/(['"]).*?[^\\]\1/g, '').test('//')) {
                t += ' &nbsp; <span style="color:red;font-weight:bold;">';
                t += Joomla.JText._('COM_FABRIK_JS_INLINE_COMMENT_WARNING').replace(/ /g, '&nbsp;');
                t += '</span>';
            }
        } else if (event.value && trigger.value && name.value) {
            t = Joomla.JText._('COM_FABRIK_JS_WHEN_ELEMENT') + ' "' + name.value + '" ';
            if (condition.getSelected()[0].text.test(/hidden|shown/)) {
                t += Joomla.JText._('COM_FABRIK_JS_IS') + ' ';
                t += condition.getSelected()[0].text + ', ';
            } else {
                t += condition.getSelected()[0].text + ' "' + value.value.trim() + '", ';
            }
            var trigtype = trigger.getSelected().closest('optgroup').prop('label').toLowerCase();
            t += event.getSelected()[0].text + ' ' + trigtype.substring(0, trigtype.length - 1);
            t += ' "' + trigger.getSelected()[0].text + '"';
        } else {
            s += '<span style="color:red;">' + Joomla.JText._('COM_FABRIK_JS_NO_ACTION') + '</span>';
        }
        if (t !== '') {
            s += '<span style="font-weight:normal">' + t + '</span>';
        }
        header.html(s);
    },

    setParentViz: function () {
        if (parseInt(this.options.parentid, 10) !== 0) {
            $('#unlink').on('click', function () {
                if (this.checked) {
                    $('elementFormTable').fadeIn();
                } else {
                    $('elementFormTable').fadeOut();
                }
            });
        }
        $('#swapToParent').on('click', function () {
            var f = document.adminForm;
            f.task.value = 'element.parentredirect';
            var to = this.className.replace('element_', '');
            f.redirectto.value = to;
            f.submit();
        });
    }
});
