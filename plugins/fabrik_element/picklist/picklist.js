/**
 * PickList Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbPicklist = my.Class(FbElement, {
    constructor: function (element, options) {
        this.plugin = 'fabrikpicklist';
        this.parent(element, options);
        if (this.options.allowadd === true) {
            this.watchAddToggle();
            this.watchAdd();
        }
        this.makeSortable();
    },

    /**
     * Ini the sortable object
     */
    makeSortable: function () {
        if (this.options.editable) {
            var c = this.getContainer();
            var from = c.find('.fromList'),
                to = c.find('.toList'),
                dropcolour = from.css('background-color'),
                that = this;
            this.sortable = new Sortables([from, to], {
                clone     : true,
                revert    : true,
                opacity   : 0.7,
                hovercolor: '#ffddff',
                onComplete: function (element) {
                    this.setData();
                    this.showNotices(element);
                    that.fadeOut(from, dropcolour);
                    that.fadeOut(to, dropcolour);
                }.bind(this),
                onSort    : function (element, clone) {
                    this.showNotices(element, clone);

                }.bind(this),


				onStart : function (element, clone) {
					this.drag.addEvent('onEnter', function (element, droppable) {
						if (this.lists.contains(droppable)) {
							that.fadeOut(droppable, this.options.hovercolor);
							if (this.lists.contains(this.drag.overed)) {
								this.drag.overed.addEvent('mouseleave', function () {
									that.fadeOut(from, dropcolour);
									that.fadeOut(to, dropcolour);
								}.bind(this));
							}
						}
					}.bind(this));
				}
			});
			var notices = [from.find('li.emptypicklist'), to.find('li.emptypicklist')];
			this.sortable.removeItems(notices);
			this.showNotices();
		}
	},

    fadeOut: function (droppable, colour) {
        var hoverFx = new Fx.Tween(droppable, {
            wait    : false,
            duration: 600
        });
        hoverFx.start('background-color', colour);
    },

	/**
	 * Show empty notices
	 *
	 * @param  DOMNode  element  Li being dragged
	 *
	 */
	showNotices: function (element, clone) {
		if (element) {
			// Get list
			element = element.closest('ul');
		}
		var c = this.getContainer(),
		limit, to, i;
		var lists = [c.find('.fromList'), c.find('.toList')];
		for (i = 0; i < lists.length; i++) {
			to = lists[i];
			limit = (to === element || typeOf(element) === 'null') ? 1 : 2;
			var notice = to.find('li.emptypicklist');
			var lis = to.find('li');
			lis.length > limit ? notice.hide() : notice.show();
		}
	},

	setData: function () {
		var c = this.getContainer(),
		to = c.find('.toList'),
		lis = to.find('li[class!=emptypicklist]'),
		v = lis.map(
				function (item, index) {
					return item.id
							.replace(this.options.element + '_value_', '');
				}.bind(this));
		this.element.value = JSON.encode(v);
	},

    watchAdd: function () {
        var id = this.element.id,
            c = this.getContainer(),
            to = c.find('.toList'),
            btn = c.find('input[type=button]');

        if (typeOf(btn) === 'null') {
            return;
        }
        btn.on(
            'click',
            function (e) {
                var val,
                    value = c.find('input[name=addPicklistValue]'),
                    labelEl = c.find('input[name=addPicklistLabel]'),
                    label = labelEl.get('value');
                if (value.length > 0) {
                    val = value.val();
                } else {
                    val = label;
                }
                if (val === '' || label === '') {
                    window.alert(Joomla.JText._('PLG_ELEMENT_PICKLIST_ENTER_VALUE_LABEL'));
                } else {

                    var li = $(document.createElement('li')).addClass('picklist').attr({
                        'id'   : this.element.id + '_value_' + val
                    }).text(label);

                    to.adopt(li);
                    this.sortable.addItems(li);

                    e.stop();
                    if (typeOf(value) === 'element') {
                        value.value = '';
                    }
                    labelEl.value = '';
                    this.setData();
                    this.addNewOption(val, label);
                    this.showNotices();
                }
            }.bind(this));
    },

    unclonableProperties: function () {
        return ['form', 'sortable'];
    },

    watchAddToggle: function () {
        var c = this.getContainer(),
            self = this,
            d = c.find('div.addoption'),
            a = c.find('.toggle-addoption'),
            clone, fe;
        if (this.mySlider) {
            // Copied in repeating group so need to remove old slider html first
            clone = d.clone();
            fe = c.find('.fabrikElement');
            d.parent().destroy();
            fe.adopt(clone);
            d = c.find('div.addoption');
            d.css('margin', 0);
        }
        $(d).slideUp(500);
        a.on('click', function (e) {
            e.stop();
            $(d).slideToggle();
        });
    },

    cloned: function (c) {
        delete this.sortable;
        if (this.options.allowadd === true) {
            this.watchAddToggle();
            this.watchAdd();
        }
        this.makeSortable();
        this.parent(c);
    }
});