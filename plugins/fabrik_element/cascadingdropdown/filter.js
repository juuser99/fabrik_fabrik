/**
 * Cascading Dropdown Element Filter
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var CascadeFilter = my.Class({
	constructor: function (observerid, opts) {
		var self = this,
				v;
		this.options = opts;
		this.observer = $('#' + observerid);
		// autocompletes don't have an id on the hidden value field, but have it as class
		if (this.observer.length === 0) {
			this.observer = $('.' + observerid);
			if (this.observer) {
				this.observer = this.observer[0];
			}
		}
		if (this.observer) {
			$(document.createElement('img')).attr({
				'id' : this.options.filterid + '_loading',
				'src': Fabrik.liveSite + 'media/com_fabrik/images/ajax-loader.gif',
				'alt': 'loading...'
			}).
			css({'opacity': '0'}).inject(this.observer, 'before');
			v = this.observer.get('value');

			this.ajaxData = {
				'option'                    : 'com_fabrik',
				'format'                    : 'raw',
				'task'                      : 'plugin.pluginAjax',
				'plugin'                    : 'cascadingdropdown',
				'method'                    : 'ajax_getOptions',
				'element_id'                : this.options.elid,
				'v'                         : v,
				'formid'                    : this.options.formid,
				'fabrik_cascade_ajax_update': 1,
				'filterview'                : 'table'
			};

			this.observer.on('change', function () {
				self.update();
			});

			this.periodical = setInterval(function () {
				this.update.call(this, [cb]);
			}, 500);
			this.periodcount = 0;
		} else {
			fconsole('observer not found ', observerid);
		}
	},

    update: function () {
        var self = this,
            filterData = eval(this.options.filterobj).getFilterData();

        if (this.observer) {
            // $$$ hugh - added this so we fake out submitted form data for use as placeholders in query filter
            Object.append(this.ajaxData, filterData, {v: this.observer.val()});

            this.myAjax = $.ajax({
                url       : '',
                method: 'post',
                'data'    : this.ajaxData
            }).done(function (e) {
                self.ajaxComplete(e);
            });
        }
    },

    ajaxComplete: function (json) {
        var self = this;
        json = JSON.decode(json);

        if (($('#' + this.options.filterid)).length === 0) {
            fconsole('filterid not found: ', this.options.filterid);
            this.endAjax();
            return;
        }

        $('#' + this.options.filterid).empty();
        json.each(function (item) {
            $(document.createElement('option')).attr({'value': item.value}).text(item.text)
                .inject($('#' + self.options.filterid));
            $('#' + self.options.filterid).val(self.options.def);
        });

        this.endAjax();
    },

    endAjax: function () {
        $('#' + this.options.filterid + '_loading').css('opacity', '0');
	    $('#' + this.options.filterid).val(this.options.def);
	    if (this.options.advanced)
	    {
		    $('#' + this.options.filterid).trigger('liszt:updated');
	    }
    }
});