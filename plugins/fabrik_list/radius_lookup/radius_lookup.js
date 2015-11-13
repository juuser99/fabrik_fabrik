/**
 * List Radius Lookup
 *
 * @copyright: Copyright (C) 2005-2014, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


var FbListRadiusLookup = my.Class(FbListPlugin, {

	options: {

	},

	constructor : function (options) {
		var self = this;
		FbListRadiusLookup.Super.call(this, options);

		if (this.options.value === undefined) {
			this.options.value = 0;
		}

		var clear = this.listform.find('.clearFilters');
		console.log('clear = ', clear);
		clear.on('mouseup', function () {
			self.clearFilter();
		});

		if (typeOf(this.listform) !== 'null') {
			this.listform = this.listform.find('#radius_lookup' + this.options.renderOrder);
			if (typeOf(this.listform) === 'null') {
				fconsole('didnt find element #radius_lookup' + this.options.renderOrder);
				return;
			}
		}

		if (typeOf(this.listform) === 'null') {
			return;
		}

		if (geo_position_js.init()) {
			geo_position_js.getCurrentPosition(function (p) {
				this.setGeoCenter(p);
			}.bind(this),
			function (e) {
				this.geoCenterErr(e);
			}.bind(this), {
				enableHighAccuracy : true
			});
		}
	},

	setGeoCenter: function (p) {
		this.geocenterpoint = p;
		this.geoCenter(p);
	},

	geoCenter: function (p) {
		if (p === undefined) {
			window.alert(Joomla.JText._('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE'));
		} else {
			this.listform.find('input[name=radius_search_lat' + this.options.renderOrder + ']').val(p.coords.latitude.toFixed(2));
			this.listform.find('input[name=radius_search_lon' + this.options.renderOrder + ']').val(p.coords.longitude.toFixed(2));
		}
	},

	clearFilter: function () {
		this.listform.getElements('select').set('value', '');
		return true;
	}
});
