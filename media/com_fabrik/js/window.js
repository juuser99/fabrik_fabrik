/**
 * Fabrik Window
 *
 * @copyright: Copyright (C) 2005-2014, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Window factory
 *
 * @param   object  opts  Options
 *
 * @return  Fabrik.Window
 */
Fabrik.getWindow = function (opts) {
    if (Fabrik.Windows[opts.id]) {
        if (opts.visible !== false) {
            Fabrik.Windows[opts.id].open();
        }
        Fabrik.Windows[opts.id].setOptions(opts);
        // Fabrik.Windows[opts.id].loadContent();
    } else {
        var type = opts.type ? opts.type : '';
        switch (type) {
            case 'redirect':
                Fabrik.Windows[opts.id] = new Fabrik.RedirectWindow(opts);
                break;
            case 'modal':
                Fabrik.Windows[opts.id] = new Fabrik.Modal(opts);
                break;
            case '':
            /* falls through */
            default:
                Fabrik.Windows[opts.id] = new Fabrik.Window(opts);
                break;
        }
    }
    return Fabrik.Windows[opts.id];
};


Fabrik.Window = my.Class({

    options: {
        id               : 'FabrikWindow',
        title            : '&nbsp;',
        container        : false,
        loadMethod       : 'html',
        contentURL       : '',
        createShowOverLay: false,
        width            : 300,
        height           : 300,
        loadHeight       : 100,
        expandable       : true,
        offset_x         : null,
        offset_y         : null,
        visible          : true,
        onClose          : function () {
        },
        onOpen           : function () {
        },
        onContentLoaded  : function () {
            this.fitToContent(false);
        },
        destroy          : true
    },

    modal: false,

    classSuffix: '',

    expanded: false,

    constructor: function (options) {
        this.options = $.extend(this.options, options);
        this.makeWindow();
    },

    /**
     * Tabs can resize content area
     */
    watchTabs: function () {
        var self = this;
        $('.nav-tabs a').on('mouseup', function () {
            self.fitToWidth();
            self.drawWindow();
        });
    },

    deleteButton: function () {
        var self = this,
            delClick = function (e) {
                self.close(e);
            };
        var del = $(Fabrik.jLayouts['modal-close']);
        del.on('click', delClick);

        return del;
    },

    center: function () {
        var pxWidth = this.windowWidthInPx(),
            w = this.window.css('width'),
            h = this.window.css('height');
        w = (w === null || w === 'auto') ? pxWidth : this.window.css('width');
        w = w.toInt();
        h = (h === null || h === 'auto') ? this.options.height + 10 : this.window.css('height');
        h = h.toInt();
        var d = {'width': w + 'px', 'height': h + 'px'};
        this.window.css(d);

        if (!(this.modal)) {
            var yy = window.getSize().y / 2 + window.getScroll().y - (h / 2);
            d.top = typeOf(this.options.offset_y) !== 'null' ? window.getScroll().y + this.options.offset_y : yy;

            var xx = window.getSize().x / 2 + window.getScroll().x - w / 2;
            d.left = typeOf(this.options.offset_x) !== 'null' ? window.getScroll().x + this.options.offset_x : xx;

        } else {
            // Fileupload crop uses this
            var offset = (window.getSize().y - h) / 2;
            var xoffset = (window.getSize().x - w) / 2;
            d.top = offset < 0 ? window.getScroll().y : window.getScroll().y + offset;
            d.left = xoffset < 0 ? window.getScroll().x : window.getScroll().x + xoffset;
        }
        // Prototype J template css puts margin left on .modals
        d['margin-left'] = 0;
        this.window.css(d);
    },

    /**
     * Work out the window width either from px or % variable
     *
     * @deprecated use this.windowDimenionInPx('width') instead
     *
     * @return  int  Px width of window
     */

    windowWidthInPx: function () {
        return this.windowDimenionInPx('width');
    },

    /**
     * Work out the window width or height either from px or % variable
     *
     * @param   string  dir  Width or height.
     *
     * @return  int  Px width of window
     */
    windowDimenionInPx: function (dir) {
        var coord = dir === 'height' ? 'y' : 'x';
        var dim = this.options[dir] + '';
        if (dim.indexOf('%') !== -1) {
            return Math.floor(window.getSize()[coord] * (dim.toFloat() / 100));
        }
        return dim.toInt();
    },

    /**
     * Build the window HTML
     */
    makeWindow: function () {
        var draggerC, dragger, expandButton, expandIcon,
            self = this, resizeIcon, label, cw, ch, handleParts = [];
        this.window = $('<div />').addClass('fabrikWindow ' + this.classSuffix + ' modal').attr({
            'id': this.options.id
        });

        // Set window dimensions before center - needed for fileupload crop
        this.window.css('width', this.options.width);
        this.window.css('height', this.options.height);
        this.center();
        this.contentWrapperEl = this.window;
        var del = this.deleteButton();

        var hclass = 'handlelabel';
        if (!this.modal) {
            hclass += ' draggable';
            draggerC = $('<div />').addClass('bottomBar modal-footer');
            dragger = $('<div />').addClass('dragger');
            resizeIcon = $(Fabrik.jLayouts['icon-expand']);
            resizeIcon.prependTo(dragger);
            draggerC.append(dragger);
        }

        expandIcon = jQuery(Fabrik.jLayouts['icon-full-screen']);
        label = $('<h3 />').addClass(hclass).text(this.options.title);

        handleParts.push(label);
        if (this.options.expandable && this.modal === false) {
            expandButton = $('<a />').addClass('expand').attr({
                'href': '#'
            }).on('click', function (e) {
                self.expand(e);
            }).append(expandIcon);
            handleParts.push(expandButton);
        }

        handleParts.push(del);
        this.handle = this.getHandle().append(handleParts);

        var bottomBarHeight = 15;
        var topBarHeight = 15;
        var contentHeight = this.options.height - bottomBarHeight - topBarHeight;
        if (contentHeight < this.options.loadHeight) {
            contentHeight = this.options.loadHeight;
        }
        this.contentWrapperEl = $('<div />').addClass('contentWrapper').css({
            'height': contentHeight + 'px'
        });
        var itemContent = $('<div />').addClass('itemContent');
        this.contentEl = $('<div />').addClass('itemContentPadder');
        itemContent.append(this.contentEl);
        this.contentWrapperEl.append(itemContent);
        cw = this.windowWidthInPx();
        ch = this.windowDimenionInPx('height');
        this.contentWrapperEl.css({'height': ch, 'width': cw + 'px'});
        if (this.modal) {
            this.window.append([this.handle, this.contentWrapperEl]);
        } else {
            this.window.append([this.handle, this.contentWrapperEl, draggerC]);
            this.window.draggable(
                {
                    'handle': dragger,
                    drag    : function () {
                        Fabrik.trigger('fabrik.window.resized', this.window);
                        this.drawWindow();
                    }.bind(this)
                }
            ).resizable();
            var dragOpts = {'handle': this.handle};
            dragOpts.onComplete = function () {
                Fabrik.trigger('fabrik.window.moved', this.window);
                this.drawWindow();
            }.bind(this);
            dragOpts.container = this.options.container ? document.id(this.options.container) : null;
            this.window.makeDraggable(dragOpts);
        }
        if (!this.options.visible) {
            this.window.fade('hide');
        }
        $(document.body).append(this.window);
        this.loadContent();
        this.center();
    },

    /**
     * toggle the window full screen
     */
    expand: function (e) {
        e.stopPropagation();
        if (!this.expanded) {
            this.expanded = true;
            var w = window.getSize();
            this.unexpanded = this.window.getCoordinates();
            var scroll = window.getScroll();
            this.window.setPosition({'x': scroll.x, 'y': scroll.y}).css({'width': w.x, 'height': w.y});
        } else {
            this.window.setPosition({
                'x': this.unexpanded.left,
                'y': this.unexpanded.top
            }).css({'width': this.unexpanded.width, 'height': this.unexpanded.height});
            this.expanded = false;
        }
        this.drawWindow();
    },

    getHandle: function () {
        var c = this.handleClass();
        return $('<div />').addClass('draggable ' + c);
    },

    handleClass: function () {
        return 'modal-header';
    },

    loadContent: function () {
        var u, self = this;
        $(window).trigger('tips.hideall');
        switch (this.options.loadMethod) {

            case 'html':
                if (this.options.content === undefined) {
                    fconsole('no content option set for window.html');
                    this.close();
                    return;
                }
                if (typeOf(this.options.content) === 'element') {
                    this.options.content.inject(this.contentEl.empty());
                } else {
                    this.contentEl.html(this.options.content);
                }
                this.options.onContentLoaded.apply(this);
                this.watchTabs();
                break;
            case 'xhr':
                u = this.window.find('.itemContent');
                u = this.contentEl;
                Fabrik.loader.start(u);
                new $.ajax({
                    'url'   : this.options.contentURL,
                    'data'  : {'fabrik_window_id': this.options.id},
                    'method': 'post',
                }).success(function (r) {
                        u.append(r);
                        Fabrik.loader.stop(u);
                        self.options.onContentLoaded.apply(this);
                        self.watchTabs();

                        // Needed for IE11
                        self.center();
                        // Ini any Fabrik JS code that was loaded with the ajax request
                        // window.trigger('fabrik.loaded');
                    });
                break;
            case 'iframe':
                var h = this.options.height - 40,
                    winWidth = window.getWidth(),
                    scrollSize = this.contentEl.getScrollSize();
                var w = scrollSize.x + 40 < winWidth ? scrollSize.x + 40 : winWidth;
                u = this.window.find('.itemContent');
                Fabrik.loader.start(u);

                if (this.iframeEl) {
                    this.iframeEl.dispose();
                }
                this.iframeEl = $('<iframe />').addClass('fabrikWindowIframe').attr({
                    'id'          : this.options.id + '_iframe',
                    'name'        : this.options.id + '_iframe',
                    'src'         : this.options.contentURL,
                    'marginwidth' : 0,
                    'marginheight': 0,
                    'frameBorder' : 0,
                    'scrolling'   : 'auto',
                }).css({
                    'height': h + 'px',
                    'width' : w
                }).inject(this.window.find('.itemContent'));
                this.iframeEl.hide();
                this.iframeEl.on('load', function () {
                    Fabrik.loader.stop(self.window.find('.itemContent'));
                    self.iframeEl.show();
                    self.trigger('onContentLoaded', [this]);
                    self.watchTabs();
                });
                break;
        }
    },

    drawWindow: function () {
        var titleHeight = this.window.find('.' + this.handleClass());
        titleHeight = titleHeight ? titleHeight.getSize().y : 25;
        var footer = this.window.find('.bottomBar').getSize().y;
        this.contentWrapperEl.css('height', this.window.getDimensions().height - (titleHeight + footer));
        this.contentWrapperEl.css('width', this.window.getDimensions().width - 2);

        // Resize iframe when window is resized
        if (this.options.loadMethod === 'iframe') {
            this.iframeEl.css('height', this.contentWrapperEl.offsetHeight - 40);
            this.iframeEl.css('width', this.contentWrapperEl.offsetWidth - 10);
        }
    },

    fitToContent: function (scroll, center) {
        scroll = scroll === undefined ? true : scroll;
        center = center === undefined ? true : center;

        if (this.options.loadMethod !== 'iframe') {
            // As iframe content may not be on the same domain we CAN'T
            // guarantee access to its body element to work out its dimensions
            this.fitToHeight();
            this.fitToWidth();
        }
        this.drawWindow();
        if (center) {
            this.center();
        }
        if (!this.options.offset_y && scroll) {
            new Fx.Scroll(window).toElement(this.window);
        }
    },

    /**
     * Fit the window height to the min of either its content height or the window height
     */
    fitToHeight: function () {
        // Add the top and bottom bars to the content size
        var titleHeight = this.window.find('.' + this.handleClass());
        titleHeight = titleHeight ? titleHeight.getSize().y : 25;
        var footer = this.window.find('.bottomBar').getSize().y;
        var contentEl = this.window.find('.itemContent');
        var testH = contentEl.getScrollSize().y + titleHeight + footer;
        var h = testH < window.getHeight() ? testH : window.getHeight();
        this.window.css('height', h);
    },

    /**
     * Fit the window width to the min of either its content width or the window width
     */
    fitToWidth: function () {
        var contentEl = this.window.find('.itemContent'),
            winWidth = window.getWidth();
        var w = contentEl.getScrollSize().x + 25 < winWidth ? contentEl.getScrollSize().x + 25 : winWidth;
        this.window.css('width', w);
    },

    close: function (e) {

        if (e) {
            e.stopPropagation();
        }
        //this.options.destroy = true;

        // By default cant destroy as we want to be able to reuse them (see crop in fileupload element)
        if (this.options.destroy) {

            // However db join add (in repeating group) has a fit if we don't remove its content
            this.window.destroy();
            delete(Fabrik.Windows[this.options.id]);
        } else {
            this.window.fade('hide');
        }
        this.trigger('onClose', [this]);
    },

    open: function (e) {
        if (e) {
            e.stopPropagation();
        }
        this.window.fade('show');
        this.trigger('onOpen', [this]);
    }

});

Fabrik.Modal = my.Class(Fabrik.Window, {

    modal: true,

    classSuffix: 'fabrikWindow-modal',

    getHandle: function () {
        return $('<div />').addClass(this.handleClass());
    }
});

Fabrik.RedirectWindow = my.Class(Fabrik.Window, {
    constructor: function (opts) {
        var opts2 = {
            'id'         : 'redirect',
            'title'      : opts.title ? opts.title : '',
            loadMethod   : loadMethod,
            'width'      : opts.width ? opts.width : 300,
            'height'     : opts.height ? opts.height : 320,
            'minimizable': false,
            'collapsible': true

        };
        opts2.id = 'redirect';
        opts = $.merge(opts2, opts);
        var loadMethod, url = opts.contentURL;
        //if its a site page load via xhr otherwise load as iframe
        opts.loadMethod = 'xhr';
        if (!url.contains(Fabrik.liveSite) && (url.contains('http://') || url.contains('https://'))) {
            opts.loadMethod = 'iframe';
        } else {
            if (!url.contains('tmpl=component')) {
                opts.contentURL += url.contains('?') ? '&tmpl=component' : '?tmpl=component';
            }
        }
        this.options = $.extend(this.options, opts);
        this.makeWindow();
    }
});
