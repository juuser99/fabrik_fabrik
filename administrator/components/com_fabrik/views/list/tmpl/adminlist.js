/**
 * Admin List Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var ListPluginManager = my.Class(PluginManager, {

    type: 'list',

    constructor: function (plugins, id) {
        this.parent(plugins, id);
    }

});

var ListForm = my.Class({

        autoChangeDbName: true,

        options: {
            j3: true
        },

        constructor: function (options) {
            var rows,
                self = this;
            $(document).domready(function () {
                self.options = $.append(self.options, options);
                self.watchTableDd();
                self.watchLabel();
                $('#addAJoin').on('click', function (e) {
                    e.stop();
                    self.addJoin();
                });
                if ($('table.linkedLists')) {
                    rows = $('#table.linkedLists').find('tbody');
                    new Sortables(rows, {
                        'handle': '.handle',
                        'onSort': function (element, clone) {
                            var s = this.serialize(1, function (item) {
                                if (item.find('input')) {
                                    return item.find('input').name.split('][').getLast().replace(']', '');
                                }
                                return '';
                            });
                            var actual = [];
                            s.each(function (i) {
                                if (i !== '') {
                                    actual.push(i);
                                }
                            });
                            document.find('input[name*=faceted_list_order]').value = JSON.stringify(actual);
                        }
                    });
                }

                if (document.find('table.linkedForms')) {
                    rows = document.find('table.linkedForms').find('tbody');
                    new Sortables(rows, {
                        'handle': '.handle',
                        'onSort': function (element, clone) {
                            var s = this.serialize(1, function (item) {
                                if (item.find('input')) {
                                    return item.find('input').name.split('][').getLast().replace(']', '');
                                }
                                return '';
                            });
                            var actual = [];
                            s.each(function (i) {
                                if (i !== '') {
                                    actual.push(i);
                                }
                            });
                            document.find('input[name*=faceted_form_order]').value = JSON.stringify(actual);
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
            jQuery('#jform_label').on('keyup', function (e) {
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
            var self = this;
            $('.addOrder').removeEvents('click');
            $('.deleteOrder').removeEvents('click');
            $('.addOrder').on('click', function (e) {
                e.stop();
                self.addOrderBy();
            });
            $('.deleteOrder').on('click', function (e) {
                e.stop();
                self.deleteOrderBy(e);
            });
        },

        addOrderBy: function (e) {
            var t;
            if (e) {
                t = e.target.closest('.orderby_container');
            } else {
                t = document.find('.orderby_container');
            }
            t.clone().inject(t, 'after');
            this.watchOrderButtons();
        },

        deleteOrderBy: function (e) {
            if (document.find('.orderby_container').length > 1) {
                e.target.closest('.orderby_container').dispose();
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
                    url       : url,
                    method    : 'post',
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
            var t = $('.join_from').combine($('.join_to'));
            t.each(function (sel) {
                var v = sel.val();
                if (this.options.activetableOpts.indexOf(v) === -1) {
                    this.options.activetableOpts.push(v);
                }
            }.bind(this));
            this.options.activetableOpts.sort();
        },

        addJoin: function (groupId, joinId, joinType, joinToTable, thisKey, joinKey, joinFromTable, joinFromFields, joinToFields, repeat) {
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

            joinType = new Element('select', {
                'name' : 'jform[params][join_type][]',
                'class': 'inputbox input-mini'
            }).adopt(this._buildOptions(this.options.joinOpts, joinType));
            var joinFrom = new Element('select', {
                'name' : 'jform[params][join_from_table][]',
                'class': 'inputbox join_from input-medium'
            }).adopt(this._buildOptions(this.options.activetableOpts, joinFromTable));
            groupId = new Element('input', {'type': 'hidden', 'name': 'group_id[]', 'value': groupId});
            var tableJoin = new Element('select', {
                'name' : 'jform[params][table_join][]',
                'class': 'inputbox join_to input-medium'
            }).adopt(this._buildOptions(this.options.tableOpts, joinToTable));
            var tableKey = new Element('select', {
                'name' : 'jform[params][table_key][]',
                'class': 'table_key inputbox input-medium'
            }).adopt(this._buildOptions(joinFromFields, thisKey));
            joinKey = new Element('select', {
                'name' : 'jform[params][table_join_key][]',
                'class': 'table_join_key inputbox input-medium'
            }).adopt(this._buildOptions(joinToFields, joinKey));
            var repeatRadio =
                "<fieldset class=\"radio\">" +
                "<input type=\"radio\" id=\"joinrepeat" + this.joinCounter + "\" value=\"1\" name=\"jform[params][join_repeat][" + this.joinCounter + "][]\" " + repeaton + "/><label for=\"joinrepeat" + this.joinCounter + "\">" + Joomla.JText._('JYES') + "</label>" +
                "<input type=\"radio\" id=\"joinrepeatno" + this.joinCounter + "\" value=\"0\" name=\"jform[params][join_repeat][" + this.joinCounter + "][]\" " + repeatoff + "/><label for=\"joinrepeatno" + this.joinCounter + "\">" + Joomla.JText._('JNO') + "</label>" +
                "</fieldset>";

            headings = $(document.createElement('thead')).adopt(
                $(document.createElement('tr')).adopt([
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

            row = $(document.createElement('tr')).attr({'id': 'join' + this.joinCounter}).adopt([
                $(document.createElement('td')).adopt(ii),
                $(document.createElement('td')).adopt([groupId, joinType]),
                $(document.createElement('td')).adopt(joinFrom),
                $(document.createElement('td')).adopt(tableJoin),
                $(document.createElement('td.table_key')).adopt(tableKey),
                $(document.createElement('td.table_join_key')).adopt(joinKey),
                $(document.createElement('td')).html(repeatRadio),
                $(document.createElement('td')).adopt(delButton)
            ]);

            var sContent = $(document.createElement('table')).addClass('table-striped table')
                .adopt([
                    headings,
                    tbody.adopt(row)
                ]);
            if (this.joinCounter === 0) {
                sContent.inject($('#joindtd'));
            } else {
                var tb = $('#joindtd').find('tbody');
                row.inject(tb);
            }
            this.joinCounter++;
        },

        deleteJoin: function (e) {
            var tbl, t;
            e.stop();
            t = $(e.target).closest('tr');
            tbl = $(e.target).closest('table');
            t.dispose();
            if (tbl.find('tbody tr').length === 0) {
                tbl.dispose();
            }
        },

        watchJoins: function () {
            var self = this;
            $('div[id^=table-sliders-data]').on('change', '.join_from', function () {
                var row = $(this).closest('tr');
                var activeJoinCounter = row.prop('id').replace('join', '');
                self.updateJoinStatement(activeJoinCounter);
                var table = $(this).val();
                var conn = $('input[name*=connection_id]').val();

                var update = row.find('td.table_key');
                var url = 'index.php?option=com_fabrik&format=raw&task=list.ajax_loadTableDropDown&table=' + table + '&conn=' + conn;
                var myAjax = new Request.HTML({
                    url   : url,
                    method: 'post',
                    update: update
                }).send();
            });

            $('div[id^=table-sliders-data]').on('change', '.join_to', function (e, target) {
                var row = $(this).closest(rowContainer);
                var activeJoinCounter = row.prop('id').replace('join', '');
                this.updateJoinStatement(activeJoinCounter);
                var table = $(this).val();
                var conn = $('input[name*=connection_id]').val();
                var url = 'index.php?name=jform[params][table_join_key][]&option=com_fabrik&format=raw&task=list.ajax_loadTableDropDown&table=' +
                    table + '&conn=' + conn;

                var update = row.find('td.table_join_key');
                var myAjax = new Request.HTML({
                    url   : url,
                    method: 'post',
                    update: update
                }).send();
            });
            this.watchFieldList('join_type');
            this.watchFieldList('table_join_key');
            this.watchFieldList('table_key');
        },

        updateJoinStatement: function (activeJoinCounter) {
            var fields = $('#join' + activeJoinCounter + ' .inputbox');
            fields = Array.from(fields);
            var type = fields[0].val();
            var fromTable = fields[1].val();
            var toTable = fields[2].val();
            var fromKey = fields[3].val();
            var toKey = fields[4].val();
            var str = type + ' JOIN ' + toTable + ' ON ' + fromTable + '.' + fromKey + ' = ' + toTable + '.' + toKey;
            var desc = $('#join-desc-' + activeJoinCounter);
            if (typeOf(desc) !== 'null') {
                desc.html(str);
            }

        }

    })
    ;

////////////////////////////////////////////

var adminFilters = my.Class({

    options: {
        j3: false
    },

    constructor: function (el, fields, options) {
        this.el = $('#' + el);
        this.fields = fields;
        this.options = $.append(this.options, options);
        this.filters = [];
        this.counter = 0;
    },

    addHeadings: function () {
        var thead = $(document.createElement('thead'))
            .adopt($(document.createElement('tr')).attr({'id': 'filterTh', 'class': 'title'}).adopt(
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_JOIN')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_FIELD')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_CONDITION')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_VALUE')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_TYPE')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_APPLY_FILTER_TO')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_GROUPED')),
                $(document.createElement('th')).text(Joomla.JText._('COM_FABRIK_DELETE'))
            ));
        thead.inject($('#filterContainer'), 'before');
    },

    deleteFilterOption: function (e) {
        this.counter--;
        var tbl, t;
        e.stop();
        var row = parseInt(e.target.id.replace('filterContainer-del-', ''), 10);

        t = $(e.target).closest('tr');
        tbl = $(e.target).closest('table');

        if (this.counter === 0) {
            tbl.hide();
        }
        // in 3.1 we have to hide the rows rather than destroy otherwise the form doesn't submit!!!
        t.find('input, select, textarea').dispose();
        t.hide();
    },

    _makeSel: function (c, name, pairs, sel, showSelect) {
        var opts = [];
        showSelect = showSelect === true ? true : false;
        if (showSelect) {
            opts.push($(document.createElement('option')).attr({'value': ''}).text(Joomla.JText._('COM_FABRIK_PLEASE_SELECT')));
        }
        pairs.each(function (pair) {
            if (pair.value === sel) {
                opts.push($(document.createElement('option'))
                    .attr({'value': pair.value, 'selected': 'selected'}).text(pair.label));
            } else {
                opts.push($(document.createElement('option')).attr({'value': pair.value}).text(pair.label));
            }
        });
        return $(document.createElement('select')).addClass(c + ' input-medium').attr('name', name).adopt(opts);
    },

    addFilterOption: function (selJoin, selFilter, selCondition, selValue, selAccess, evaluate, grouped) {
        var and, or, joinDd, groupedNo, groupedYes, i, sels,
            self = this;
        if (this.counter <= 0) {
            if (this.el.closest('table').find('thead')) {
                // We've already added the thead - in 3.1 we have to hide the rows rather than destroy otherwise the form doesn't submit!!!
            } else {
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
        var tr = new Element('tr');
        if (this.counter > 0) {
            var opts = {'type': 'radio', 'name': 'jform[params][filter-grouped][' + this.counter + ']', 'value': '1'};
            opts.checked = (grouped === '1') ? 'checked' : '';
            groupedYes = $(document.createElement('label')).text(Joomla.JText._('JYES')).adopt(
                $(document.createElement('input')).attr(opts)
            );
            // Need to redeclare opts for ie8 otherwise it renders a field!
            opts = {
                'type' : 'radio',
                'name' : 'jform[params][filter-grouped][' + this.counter + ']',
                'value': '0'
            };
            opts.checked = (grouped !== '1') ? 'checked' : '';

            groupedNo = $(document.createElement('label')).text(Joomla.JText._('JNO')).adopt(
                $(document.createElement('input')).attr(opts)
            );

        }
        if (this.counter === 0) {
            joinDd = $(document.createElement('span').text('WHERE').adopt(
                $(document.createElement('input'))
                    .addClass('inputbox')
                    .attr({
                        'type' : 'hidden',
                        'id'   : 'paramsfilter-join',
                        'name' : 'jform[params][filter-join][]',
                        'value': selJoin
                    })));
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
                }).adopt(
                [and, or]);
        }

        var tdGrouped = new Element('td');
        var td = new Element('td');

        if (this.counter <= 0) {
            tdGrouped.appendChild($(document.createElement('input')).attr({
                'type' : 'hidden',
                'name' : 'jform[params][filter-grouped][' + this.counter + ']',
                'value': '0'
            }));
            tdGrouped.appendChild($(document.createElement('span')).text('n/a'));

        } else {
            tdGrouped.appendChild(groupedNo);
            tdGrouped.appendChild(groupedYes);
        }
        td.appendChild(joinDd);

        var td1 = new Element('td');
        td1.innerHTML = this.fields;
        var td2 = new Element('td');
        td2.innerHTML = conditionsDd;
        var td3 = new Element('td');
        var td4 = new Element('td');
        td4.innerHTML = this.options.filterAccess;
        var td5 = new Element('td');

        var textArea = $(document.createElement('textarea')).attr({
            'name': 'jform[params][filter-value][]',
            'cols': 17,
            'rows': 4
        }).text(selValue);
        td3.appendChild(textArea);
        td3.appendChild(new Element('br'));

        var evalopts = [
            {'value': 0, 'label': Joomla.JText._('COM_FABRIK_TEXT')},
            {'value': 1, 'label': Joomla.JText._('COM_FABRIK_EVAL')},
            {'value': 2, 'label': Joomla.JText._('COM_FABRIK_QUERY')},
            {'value': 3, 'label': Joomla.JText._('COM_FABRIK_NO_QUOTES')}
        ];

        var tdType = new Element('td').adopt(this._makeSel('inputbox elementtype', 'jform[params][filter-eval][]', evalopts, evaluate, false));

        var checked = (selJoin !== '' || selFilter !== '' || selCondition !== '' || selValue !== '') ? true : false;
        var delId = this.el.id + '-del-' + this.counter;

        var a = '<button id="' + delId + '" class="btn btn-danger"><i class="icon-minus"></i> </button>';
        td5.html(a);
        tr.appendChild(td);

        tr.appendChild(td1);
        tr.appendChild(td2);
        tr.appendChild(td3);
        tr.appendChild(tdType);
        tr.appendChild(td4);
        tr.appendChild(tdGrouped);
        tr.appendChild(td5);

        this.el.appendChild(tr);

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