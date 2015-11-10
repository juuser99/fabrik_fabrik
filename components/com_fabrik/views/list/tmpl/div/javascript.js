/**
 * List helper
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

jQuery(window).on('fabrik.loaded', function() {
	$('.fabrikList tr').each(function () {
		$(this).on('mouseover', function(e){
			if ($(this).hasClass('oddRow0') || $(this).hasClass('oddRow1')){
				$(this).addClass('fabrikHover');
			}
		});

		$(this).on('mouseout', function(e){
			$(this).removeClass('fabrikHover');
		});

		$(this).on('click', function(e) {
			if ($(this).hasClass('oddRow0') || $(this).hasClass('oddRow1')){
				$('.fabrikList tr').each(function(){
					$(this).removeClass('fabrikRowClick');
				});
				$(this).addClass('fabrikRowClick');
			}
		});
	});
});
