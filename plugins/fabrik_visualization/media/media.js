/**
 * Media Visualization
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbMediaViz = my.Class({

	options: {
		which_player: 'jw',
		width: 600,
		height: 450
	},

	constructor: function (el, options) {
		this.el = el;
		this.options = $.append(this.options, options);
		this.render();
	},

	render: function () {
		if (this.options.which_player === 'jw') {
			jwplayer("jw_player").setup({
				//'flashplayer': this.options.jw_swf_url,
				'width': this.options.width,
				'height': this.options.height,
				'playlistfile': this.options.jw_playlist_url,
				'playlist.position': 'right',
				//'playlist.size': this.options.height,
				'skin': this.options.jw_skin,
				'modes': [
					{type: 'flash', src: this.options.jw_swf_url},
					{type: 'html5'},
					{type: 'download'}
				]
			});
		}
	}
});