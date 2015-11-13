/**
 * Admin List Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var ListPluginManager = my.Class(PluginManager, {

    type: 'list',

    constructor: function (plugins, id) {
        ListPluginManager.Super.call(this, plugins, id);
    }

});

var ListForm = my.Class({

        autoChangeDbName: true,

        options: {
            j3: true,
            activetableOpts: []
        },

        constructor: function (options) {
            var rows,
                self = this;
            $(document).ready(function () {
                self.options = $.extend(self.options, options);
                self.watchTableDd();
                self.watchLabel();
                $('#addAJoin').on('click', function (e) {
                    e.stopPropagation();
                    self.addJoin();
                });
                if ($('table.linkedLists')) {
                    rows = $('#table.linkedLists').find('tbody');
                    new Sortables(rows, {
                        'handle': '.handle',
                        'onSort': function (element, clone) {
                            var s = this.serialize(1, function (item) {
                                return item.find('input').prop('name').split('][').pop().replace(']', '');
                            });
                            var actual = [];
                            s.each(function (i) {
                                if (i !== '') {
                                    actual.push(i);
                                }
                            });
                            $('input[name*=faceted_list_order]').value = JSON.stringify(actual);
                        }
                    });
                }

                if ($('table.linkedForms')) {
                    rows = $('table.linkedForms').find('tbody');
                    new Sortables(rows, {
                        'handle': '.handle',
                        'onSort': function (element, clone) {
                            var s = this.serialize(1, function (item) {
                                return item.find('input').prop('name').split('][').pop().replace(']', '');
                            });
                            var actual = [];
                            s.each(function (i) {
                                if (i !== '') {
                                    actual.push(i);
                                }
                            });
                            $('input[name*=faceted_form_order]').value = JSON.stringify(actual);
                        }
                    });
                }

                self.joinCounter = 0;
                self.watchOrderButtons();
                self.watchDbName();
                self.watchJoins();
            });
        },

        /**
         * Automatically fill in the db table name from the label if no
         * db table name existed when the form loaded and when the user has not
         * edited the db table name.
         */
        watchLabel: function () {
            this.autoChangeDbName = jQuery('#jform__database_name').val() === '';
            jQuery('#jform_label').on('keyup', function () {
                if (this.autoChangeDbName) {
                    var label = jQuery('#jform_label').val().trim().toLowerCase();
                    label = label.replace(/\W+/g, '_');
                    jQuery('#jform__database_name').val(label);
                }
            }.bind(this));

            jQuery('#jform__database_name').on('keyup', function () {
                this.autoChangeDbName = false;
            }.bind(this));
        },

        watchOrderButtons: function () {
            var self = this,
                add = $('.addOrder'),
                del = $('.deleteOrder');
            add.off('click');
            del.off('click');
            add.on('click', function (e) {
                e.stopPropagation();
                self.addOrderBy();
            });
            del.on('click', function (e) {
                e.stopPropagation();
                self.deleteOrderBy(e);
            });
        },

        addOrderBy: function (e) {
            var t;
            if (e) {
                t = $(e.target).closest('.orderby_container');
            } else {
                t = $('.orderby_container');
            }
            t.after(t.clone());
            this.watchOrderButtons();
        },

        deleteOrderBy: function (e) {
            if ($('.orderby_container').length > 1) {
                $(e.target).closest('.orderby_container').remove();
                this.watchOrderButtons();
            }
        },

        watchDbName: function () {
            var db = $('#database_name');
            db.on('blur', function () {
                if (db.val() === '') {
                    db.prop('disabled', false);
                } else {
                    db.prop('disabled', true);
                }
            });
        },

        _buildOptions: function (data, sel) {
            var opts = [];
            if (data.length > 0) {
                if (typeof(data[0]) === 'object') {
                    data.each(function (o) {
                        if (o[0] === sel) {
                            opts.push($(document.createElement('option')).attr({'value': o[0], 'selected': 'selected'})
                                .text(o[1]));
                        } else {
                            opts.push($(document.createElement('option')).attr({'value': o[0]}).text(o[1]));
                        }
                    });
                } else {
                    data.each(function (o) {
                        if (o === sel) {
                            opts.push($(document.createElement('option')).attr({'value': o, 'selected': 'selected'})
                                .text(o));
                        } else {
                            opts.push($(document.createElement('option')).attr({'value': o}).text(o));
                        }
                    });
                }
            }
            return opts;
        },

        watchTableDd: function () {
            var tbl = $('#tablename');
            tbl.on('change', function () {
                var cid = $('#input[name*=connection_id]').val();
                var table = tbl.val();
                var url = 'index.php?option=com_fabrik&format=raw&task=list.ajax_updateColumDropDowns&cid=' +
                    cid + '&table=' + table;
                $.ajax({
                    url   : url,
                    method: 'post',
                }).done(function (r) {
                    eval(r);
                });
            });
        },

        watchFieldList: function (name) {
            var self = this;
            $('div[id^=table-sliders-data]').on('change:', 'select[name*=' + name + ']', function () {
                self.updateJoinStatement($(this).closest('tr').prop('id').replace('join', ''));
            });
        },

        _findActiveTables: function () {
            var t = $.extend($('.join_from'), $('.join_to')),
                self = this;
            t.each(function () {
                var v = $(this).val();
                if (self.options.activetableOpts.indexOf(v) === -1) {
                    self.options.activetableOpts.push(v);
                }
            });
            this.options.activetableOpts.sort();
        },

        addJoin: function (groupId, joinId, joinType, joinToTable, thisKey, joinKey,
                           joinFromTable, joinFromFields, joinToFields, repeat) {
            var repeaton, repeatoff, headings, row,
                self = this;
            joinType = joinType ? joinType : 'left';
            joinFromTable = joinFromTable ? joinFromTable : '';
            joinToTable = joinToTable ? joinToTable : '';
            thisKey = thisKey ? thisKey : '';
            joinKey = joinKey ? joinKey : '';
            groupId = groupId ? groupId : '';
            joinId = joinId ? joinId : '';
            repeat = repeat ? repeat : false;
            if (repeat) {
                repeaton = 'checked="checked"';
                repeatoff = '';
            } else {
                repeatoff = 'checked="checked"';
                repeaton = '';
            }
            this._findActiveTables();
            joinFromFields = joinFromFields ? joinFromFields : [['-', '']];
            joinToFields = joinToFields ? joinToFields : [['-', '']];

            var tbody = $(document.createElement('tbody'));

            var ii = $(document.createElement('input'))
                .addClass('disabled readonly input-mini')
                .attr({
                    'readonly': 'readonly',
                    'size'    : '2',
                    'name'    : 'jform[params][join_id][]',
                    'value'   : joinId
                });

            var delButton = $(document.createElement('a')).attr({
                'href' : '#',
                'class': 'btn btn-danger'
            }).on('click', function (e) {
                self.deleteJoin(e);
                return false;
            });

            var delHtml = '<i class="icon-minus"></i> ';
            delButton.html(delHtml);

            joinType = $(document.createElement('select')).addClass('inputbox input-mini').attr({
                'name': 'jform[params][join_type][]'
            }).append(this._buildOptions(this.options.joinOpts, joinType));
            var joinFrom = $(document.createElement('select')).addClass('inputbox join_from input-medium').attr({
                'name': 'jform[params][join_from_table][]'
            }).append(this._buildOptions(this.options.activetableOpts, joinFromTable));
            groupId = $(document.createElement('input'))
                .attr({'type': 'hidden', 'name': 'group_id[]', 'value': groupId});
            var tableJoin = $(document.createElement('select')).addClass('inputbox join_to input-medium').attr({
                'name': 'jform[params][table_join][]'
            }).append(this._buildOptions(this.options.tableOpts, joinToTable));
            var tableKey = $(document.createElement('select')).addClass('table_key inputbox input-medium').attr({
                'name': 'jform[params][table_key][]'
            }).append(this._buildOptions(joinFromFields, thisKey));
            joinKey = $(document.createElement('select')).addClass('table_join_key inputbox input-medium').attr({
                'name': 'jform[params][table_join_key][]'
            }).append(this._buildOptions(joinToFields, joinKey));
            var repeatRadio =
                '<fieldset class="radio">' +
                '<input type="radio" id="joinrepeat' + this.joinCounter +
                '" value="1" name="jform[params][join_repeat][' +
                this.joinCounter + '][]" ' + repeaton + '/><label for="joinrepeat' + this.joinCounter + '">' +
                Joomla.JText._('JYES') + '</label>' +
                '<input type="radio" id="joinrepeatno' + this.joinCounter +
                '" value="0" name="jform[params][join_repeat][' + this.joinCounter + '][]" ' + repeatoff +
                '/><label for="joinrepeatno' + this.joinCounter + '">' + Joomla.JText._('JNO') + '</label>' +
                '</fieldset>';

            headings = $(document.createElement('thead')).append(
                $(document.createElement('tr')).append([
                    $(document.createElement('th')).text('id'),
                    $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_JOIN_TYPE')),
                    $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_FROM')),
                    $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_TO')),
                    $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_FROM_COLUMN')),
                    $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_TO_COLUMN')),
                    $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_REPEAT_GROUP_BUTTON_LABEL')),
                    $(document.createElement('th'))
                ])
            );

            row = $(document.createElement('tr')).attr({'id': 'join' + this.joinCounter}).append([
                $(document.createElement('td')).append(ii),
                $(document.createElement('td')).append([groupId, joinType]),
                $(document.createElement('td')).append(joinFrom),
                $(document.createElement('td')).append(tableJoin),
                $(document.createElement('td')).addClass('table_key').append(tableKey),
                $(document.createElement('td')).addClass('table_join_key').append(joinKey),
                $(document.createElement('td')).html(repeatRadio),
                $(document.createElement('td')).append(delButton)
            ]);

            var sContent = $(document.createElement('table')).addClass('table-striped table')
                .append([
                    headings,
                    tbody.append(row)
                ]);
            if (this.joinCounter === 0) {
                sContent.appendTo($('#joindtd'));
            } else {
                var tb = $('#joindtd').find('tbody');
                row.appendTo(tb);
            }
            this.joinCounter++;
        },

        deleteJoin: function (e) {
            var tbl, t;
            e.stopPropagation();
            t = $(e.target).closest('tr');
            tbl = $(e.target).closest('table');
            t.remove();
            if (tbl.find('tbody tr').length === 0) {
                tbl.remove();
            }
        },

        watchJoins: function () {
            var self = this, url, conn, table, row, activeJoinCounter,
                sliders = $('div[id^=table-sliders-data]');
            sliders.on('change', '.join_from', function () {
                row = $(this).closest('tr');
                activeJoinCounter = row.prop('id').replace('join', '');
                self.updateJoinStatement(activeJoinCounter);
                table = $(this).val();
                conn = $('input[name*=connection_id]').val();

                url = 'index.php?option=com_fabrik&format=raw&' +
                    'task=list.ajax_loadTableDropDown&table=' + table + '&conn=' + conn;

                $.ajax({
                    url   : url,
                    method: 'post'
                }).complete(function (r) {
                    row.find('td.table_key').html(r.responseText);
                });
            });

            sliders.on('change', '.join_to', function () {
                row = $(this).closest('tr');
                activeJoinCounter = row.prop('id').replace('join', '');
                self.updateJoinStatement(activeJoinCounter);
                table = $(this).val();
                conn = $('input[name*=connection_id]').val();
                url = 'index.php?name=jform[params][table_join_key][]' +
                    '&option=com_fabrik&format=raw&task=list.ajax_loadTableDropDown&table=' +
                    table + '&conn=' + conn;

                $.ajax({
                    url   : url,
                    method: 'post'
                }).complete(function (r) {
                    row.find('td.table_join_key').html(r.responseText);
                });
            });
            this.watchFieldList('join_type');
            this.watchFieldList('table_join_key');
            this.watchFieldList('table_key');
        },

        updateJoinStatement: function (activeJoinCounter) {
            var fields = $('#join' + activeJoinCounter + ' .inputbox');
            var type = $(fields[0]).val();
            var fromTable = $(fields[1]).val();
            var toTable = $(fields[2]).val();
            var fromKey = $(fields[3]).val();
            var toKey = $(fields[4]).val();
            var str = type + ' JOIN ' + toTable + ' ON ' + fromTable + '.' + fromKey + ' = ' + toTable + '.' + toKey;
            $('#join-desc-' + activeJoinCounter).html(str);
        }
    });

////////////////////////////////////////////

var adminFilters = my.Class({

    options: {
        j3: false
    },

    constructor: function (el, fields, options) {
        this.el = $('#' + el);
        this.fields = fields;
        this.options = $.extend(this.options, options);
        this.filters = [];
        this.counter = 0;
    },

    addHeadings: function () {
        var thead = $(document.createElement('thead'))
            .append($(document.createElement('tr')).attr({'id': 'filterTh', 'class': 'title'}).append(
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_JOIN')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_FIELD')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_CONDITION')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_VALUE')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_TYPE')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_APPLY_FILTER_TO')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_GROUPED')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_DELETE'))
            ));
        thead.insertBefore($('#filterContainer'));
    },

    deleteFilterOption: function (e) {
        this.counter--;
        var tbl, t;
        e.stopPropagation();

        t = $(e.target).closest('tr');
        tbl = $(e.target).closest('table');

        if (this.counter === 0) {
            tbl.hide();
        }
        // in 3.1 we have to hide the rows rather than destroy otherwise the form doesn't submit!!!
        t.find('input, select, textarea').remove();
        t.hide();
    },

    _makeSel: function (c, name, pairs, sel, showSelect) {
        var opts = [];
        showSelect = showSelect === true ? true : false;
        if (showSelect) {
            opts.push($(document.createElement('option')).attr({'value': ''})
                .text(Joomla.JText._('COM_FABRIK_PLEASE_SELECT')));
        }
        pairs.each(function (pair) {
            if (pair.value === sel) {
                opts.push($(document.createElement('option'))
                    .attr({'value': pair.value, 'selected': 'selected'}).text(pair.label));
            } else {
                opts.push($(document.createElement('option')).attr({'value': pair.value}).text(pair.label));
            }
        });
        return $(document.createElement('select')).addClass(c + ' input-medium').attr('name', name).append(opts);
    },

    addFilterOption: function (selJoin, selFilter, selCondition, selValue, selAccess, evaluate, grouped) {
        var and, or, joinDd, groupedNo, groupedYes, i, sels,
            self = this;
        if (this.counter <= 0) {
            if (this.el.closest('table').find('thead').length === 0) {
                this.addHeadings();
            }
        }
        selJoin = selJoin ? selJoin : '';
        selFilter = selFilter ? selFilter : '';
        selCondition = selCondition ? selCondition : '';
        selValue = selValue ? selValue : '';
        selAccess = selAccess ? selAccess : '';
        grouped = grouped ? grouped : '';
        var conditionsDd = this.options.filterCondDd;
        var tr = $(document.createElement('tr'));
        if (this.counter > 0) {
            var opts = {'type': 'radio', 'name': 'jform[params][filter-grouped][' + this.counter + ']', 'value': '1'};
            opts.checked = (grouped === '1') ? 'checked' : '';
            groupedYes = $(document.createElement('label')).text(Joomla.JText._('JYES')).append(
                $(document.createElement('input')).attr(opts)
            );
            // Need to redeclare opts for ie8 otherwise it renders a field!
            opts = {
                'type' : 'radio',
                'name' : 'jform[params][filter-grouped][' + this.counter + ']',
                'value': '0'
            };
            opts.checked = (grouped !== '1') ? 'checked' : '';

            groupedNo = $(document.createElement('label')).text(Joomla.JText._('JNO')).append(
                $(document.createElement('input')).attr(opts)
            );

        }
        if (this.counter === 0) {
            joinDd = $(document.createElement('span')).text('WHERE').append(
                $(document.createElement('input'))
                    .addClass('inputbox')
                    .attr({
                        'type' : 'hidden',
                        'id'   : 'paramsfilter-join',
                        'name' : 'jform[params][filter-join][]',
                        'value': selJoin
                    }));
        } else {
            if (selJoin === 'AND') {
                and = $(document.createElement('option')).attr({'value': 'AND', 'selected': 'selected'}).text('AND');
                or = $(document.createElement('option')).attr({'value': 'OR'}).text('OR');
            } else {
                and = $(document.createElement('option')).attr({'value': 'AND'}).text('AND');
                or = $(document.createElement('option')).attr({'value': 'OR', 'selected': 'selected'}).text('OR');
            }
            joinDd = $(document.createElement('select'))
                .addClass('inputbox input-medium')
                .attr({
                    'id'  : 'paramsfilter-join',
                    'name': 'jform[params][filter-join][]'
                }).append(
                [and, or]);
        }

        var tdGrouped = $('<td>');
        var td = $('<td>');

        if (this.counter <= 0) {
            tdGrouped.append($(document.createElement('input')).attr({
                'type' : 'hidden',
                'name' : 'jform[params][filter-grouped][' + this.counter + ']',
                'value': '0'
            }));
            tdGrouped.append($(document.createElement('span')).text('n/a'));

        } else {
            tdGrouped.append(groupedNo);
            tdGrouped.append(groupedYes);
        }
        td.append(joinDd);

        var td1 = $('<td>');
        td1.html(this.fields);
        var td2 = $('<td>');
        td2.html(conditionsDd);
        var td3 = $('<td>');
        var td4 = $('<td>');
        td4.html(this.options.filterAccess);
        var td5 = $('<td>');

        var textArea = $(document.createElement('textarea')).attr({
            'name': 'jform[params][filter-value][]',
            'cols': 17,
            'rows': 4
        }).text(selValue);
        td3.append(textArea);
        td3.append($('<br />'));

        var evalopts = [
            {'value': 0, 'label': Joomla.JText._('COM_FABRIK_TEXT')},
            {'value': 1, 'label': Joomla.JText._('COM_FABRIK_EVAL')},
            {'value': 2, 'label': Joomla.JText._('COM_FABRIK_QUERY')},
            {'value': 3, 'label': Joomla.JText._('COM_FABRIK_NO_QUOTES')}
        ];

        var sel = this._makeSel('inputbox elementtype', 'jform[params][filter-eval][]', evalopts, evaluate, false);
        var tdType = $('<td>').append(sel);

        var checked = (selJoin !== '' || selFilter !== '' || selCondition !== '' || selValue !== '') ? true : false;
        var delId = this.el.id + '-del-' + this.counter;

        var a = '<button id="' + delId + '" class="btn btn-danger"><i class="icon-minus"></i> </button>';
        td5.html(a);
        tr.append(td);

        tr.append(td1);
        tr.append(td2);
        tr.append(td3);
        tr.append(tdType);
        tr.append(td4);
        tr.append(tdGrouped);
        tr.append(td5);

        this.el.append(tr);

        this.el.closest('table').show();
        $('#' + delId).on('click', function (e) {
            self.deleteFilterOption(e);
        });

        $('#' + this.el.id + '-del-' + this.counter).click = function (e) {
            self.deleteFilterOption(e);
        };

        /*set default values*/
        if (selJoin !== '') {
            sels = Array.from(td.getElementsByTagName('SELECT'));
            if (sels.length >= 1) {
                for (i = 0; i < sels[0].length; i++) {
                    if (sels[0][i].value === selJoin) {
                        sels[0].options.selectedIndex = i;
                    }
                }
            }
        }
        if (selFilter !== '') {
            sels = Array.from(td1.getElementsByTagName('SELECT'));
            if (sels.length >= 1) {
                for (i = 0; i < sels[0].length; i++) {
                    if (sels[0][i].value === selFilter) {
                        sels[0].options.selectedIndex = i;
                    }
                }
            }
        }

        if (selCondition !== '') {
            sels = Array.from(td2.getElementsByTagName('SELECT'));
            if (sels.length >= 1) {
                for (i = 0; i < sels[0].length; i++) {
                    if (sels[0][i].value === selCondition) {
                        sels[0].options.selectedIndex = i;
                    }
                }
            }
        }

        if (selAccess !== '') {
            sels = Array.from(td4.getElementsByTagName('SELECT'));
            if (sels.length >= 1) {
                for (i = 0; i < sels[0].length; i++) {
                    if (sels[0][i].value === selAccess) {
                        sels[0].options.selectedIndex = i;
                    }
                }
            }
        }
        this.counter++;
    }

});