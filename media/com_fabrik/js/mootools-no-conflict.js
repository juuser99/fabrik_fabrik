// Prevent mootools from causing labels with tooltips and tabs from disappearing on loss of focus
window.addEvent('domready', function(){
    if (typeof jQuery != 'undefined' && typeof MooTools != 'undefined' ) {
        Element.implement({
            slide: function(how, mode){ // fix for carousels
                return this;
            },
            hide: function () { // helped me with tooltip applied on labels
                return this;
            },
            show: function (v) {// helped me with tooltip applied on labels
                return this;
            }
        });
    }
});