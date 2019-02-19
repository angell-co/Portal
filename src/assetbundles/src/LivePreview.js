/** global: Craft */
/** global: Garnish */
/** global: Portal */
/** global: Cookies */
/**
 * Portal plugin for Craft CMS
 *
 * Portal.LivePreview
 *
 * All the code for adjusting how the Live Preview window works
 *
 * @author    Angell & Co
 * @copyright Copyright (c) 2018 Angell & Co
 * @link      https://angell.io
 * @package   Portal
 * @since     1.0.0
 */

if (typeof Portal === 'undefined')
{
    Portal = {};
}

Portal.LivePreview = Garnish.Base.extend(
{

    $toolbar: null,
    $deviceMask: null,
    $zoomMenu: null,
    $breakpointButtons: null,
    $iframe: null,

    zoomMenuBtn: null,
    targetMenuBtn: null,
    targetOptions: [],
    rotatingTimeout: null,

    init: function(settings)
    {

        this.setSettings(settings, Portal.LivePreview.defaults);


        // Work out which targets we have available, if any
        if (this.settings.context) {

            $.each(this.settings.targets['global'], $.proxy(function(key, target) {
                this.targetOptions.push(target);
            }, this));

            $.each(this.settings.targets[this.settings.context], $.proxy(function(key, target) {
                this.targetOptions.push(target);
            }, this));

        }

        // We always want to load with the default page template
        Cookies.remove('portal_template');


        // Before we go any futher check we actually have something to do
        if (!this.settings.showBreakpoints && this.targetOptions.length === 0) {
            return;
        }


        // Bind to the live preview events
        Garnish.on(Craft.LivePreview, 'enter', $.proxy(function(ev)
        {
            this.onEnter(ev);
        }, this));

        Garnish.on(Craft.LivePreview, 'exit', $.proxy(function(ev)
        {
            this.onExit(ev)
        }, this));

    },

    onEnter: function(ev)
    {
        // Bind arrive
        Craft.livePreview.$iframeContainer.arrive(".lp-iframe", $.proxy(function(newIframe) {

            // Cache the iframe so we can reference it at will
            this.$iframe = $(newIframe);

            if (this.settings.showBreakpoints) {
                this.addListener(Craft.livePreview.$editor, 'resize', 'toggleToolbar');
            }

            this.attachToolbar();

            // Ping the breakpoint buttons for when the iframe is re-loaded
            $('.portal-lp-btn--active', this.$breakpointButtons).click();
        }, this));
    },

    onExit: function(ev)
    {
        if (this.settings.showBreakpoints) {
            this.removeListener(Craft.livePreview.$editor, 'resize');
        }

        // Unbind arrive
        Craft.livePreview.$iframeContainer.unbindArrive(".lp-iframe");
    },

    toggleToolbar: function()
    {

        if (Craft.livePreview.$iframeContainer.outerWidth() > 1024) {

            this.attachToolbar();

        } else {

            this.detachToolbar();

        }
    },

    attachToolbar: function()
    {
        Craft.livePreview.$iframeContainer.addClass('portal-lp');

        if (!this.$toolbar) {

            this.$toolbar = $('<header class="header" />');

            var $btnGroup = $('<div class="btngroup portal-lp-btngroup no-outline" />').appendTo(this.$toolbar);

            // Breakpoints
            if (this.settings.showBreakpoints) {

                // Breakpoint buttons
                this.$breakpointButtons = $('<div class="btngroup portal-lp-breakpoints" />').appendTo(this.$toolbar);
                $('<div class="portal-lp-btn portal-lp-btn--desktop" data-width="" data-height="" data-breakpoint="desktop" title="' + Craft.t('portal', 'Desktop') + '" />').appendTo(this.$breakpointButtons);
                $('<div class="portal-lp-btn portal-lp-btn--tablet" data-width="768" data-height="1006" data-breakpoint="tablet" title="' + Craft.t('portal', 'Tablet') + '" />').appendTo(this.$breakpointButtons);
                $('<div class="portal-lp-btn portal-lp-btn--mobile" data-width="375" data-height="653" data-breakpoint="mobile" title="' + Craft.t('portal', 'Mobile') + '" />').appendTo(this.$breakpointButtons);


                // Zoom
                var $zoomMenuBtn = $('<div class="btn portal-lp-zoom-btn menubtn no-outline">' + Craft.t('portal', 'Zoom') + '</div>').appendTo($btnGroup);
                this.$zoomMenu = $('<div class="menu portal-lp-menu" />').appendTo($btnGroup);
                var $zoomMenuUl = $('<ul />').appendTo(this.$zoomMenu);

                $('<li><a data-zoom="full">100%</a></li>').appendTo($zoomMenuUl);
                $('<li><a data-zoom="threequarters" class="sel">75%</a></li>').appendTo($zoomMenuUl);
                $('<li><a data-zoom="half">50%</a></li>').appendTo($zoomMenuUl);

                this.zoomMenuBtn = new Garnish.MenuBtn($zoomMenuBtn,
                {
                    onOptionSelect: $.proxy(this, 'onZoom')
                });


                // Orientation toggle
                var $orientationToggle = $('<div class="btn portal-lp-orientation-btn" data-icon="refresh"></div>').appendTo($btnGroup);
                this.addListener($orientationToggle, 'activate', 'toggleOrientation');
            }


            // Targets
            if (this.targetOptions.length > 0) {

                // Target select menu
                var $targetMenu = $('<div class="menu portal-lp-menu" />').prependTo($btnGroup),
                    $targetMenuBtn = $('<div class="btn menubtn no-outline">' + Craft.t('portal', 'Choose Target') + '</div>').prependTo($btnGroup),
                    $targetMenuUl = $('<ul />').appendTo($targetMenu);

                // TODO translate
                $('<li><a data-template="" class="sel">Primary Page</a></li>').appendTo($targetMenuUl);

                $.each(this.targetOptions, $.proxy(function(key, target) {

                    var targetSite = null;

                    $.each(target.siteSettings, $.proxy(function(key, siteSetting) {

                        if (siteSetting.siteId == this.settings.siteId) {
                            targetSite = siteSetting;
                        }

                    }, this));

                    if (targetSite !== null) {
                        $('<li><a data-template="' + targetSite.template + '">' + target.name + '</a></li>').appendTo($targetMenuUl);
                    }

                }, this));

                this.targetMenuBtn = new Garnish.MenuBtn($targetMenuBtn,
                {
                    onOptionSelect: $.proxy(this, 'onChangeTarget')
                });
            }

            if (this.settings.showBreakpoints) {
                // Breakpoint button click handlers
                this.addListener($('.portal-lp-btn', this.$breakpointButtons), 'activate', 'changeBreakpoint');


                // Set the window to the last breakpoint we have in the cookie
                var currentBreakpoint = Cookies.get('portal_breakpoint');
                if (currentBreakpoint) {
                    this.$breakpointButtons.find('.portal-lp-btn[data-breakpoint="' + currentBreakpoint + '"]').click();
                }
            }

        }


        // Device mask
        if (this.settings.showBreakpoints && !this.$deviceMask) {
            this.$deviceMask = $('<div class="portal-lp-device-mask" />');
        }


        // Add to DOM
        this.$toolbar.prependTo(Craft.livePreview.$iframeContainer);
        if (this.$deviceMask) {
            this.$deviceMask.appendTo(Craft.livePreview.$iframeContainer);
        }


        // Set current state
        if (this.settings.showBreakpoints) {
            if (currentBreakpoint && currentBreakpoint === 'tablet') {
                Craft.livePreview.$iframeContainer.addClass('portal-lp--tablet');
            }

            var currentOrientation = Cookies.get('portal_orientation');
            if ((currentBreakpoint && currentBreakpoint !== 'desktop') && (currentOrientation && currentOrientation === 'landscape')) {
                Craft.livePreview.$iframeContainer.addClass('portal-lp--landscape');
            }

            var currentZoom = Cookies.get('portal_zoom');
            if (currentZoom && currentZoom !== 'threequarters') {
                Craft.livePreview.$iframeContainer.addClass('portal-lp--zoom-'+currentZoom);
                this.$zoomMenu.find('a.sel').removeClass('sel');
                this.$zoomMenu.find('a[data-zoom='+currentZoom+']').addClass('sel');
            }
        }

    },

    detachToolbar: function()
    {
        Craft.livePreview.$iframeContainer.removeClass('portal-lp');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp--landscape');
        this.resetIframe();

        if (this.$toolbar) {
            this.$toolbar.detach();
        }
        if (this.$deviceMask) {
            this.$deviceMask.detach();
        }
    },

    changeBreakpoint: function(ev)
    {

        var $btn = $(ev.target),
            w = $btn.data('width'),
            h = $btn.data('height'),
            bp = $btn.data('breakpoint');


        // Set the breakpoint cookie
        Cookies.set('portal_breakpoint', bp);


        // Active state on the button
        $('.portal-lp-btn', this.$toolbar).removeClass('portal-lp-btn--active');
        $btn.addClass('portal-lp-btn--active');


        // Check the orientation and switch if needed
        var orientation = Cookies.get('portal_orientation');
        if (orientation && orientation === 'landscape') {
            w = $btn.data('height');
            h = $btn.data('width');
            Craft.livePreview.$iframeContainer.addClass('portal-lp--landscape');
        }


        // Change the size of the iframe if we can
        if (w !== '' && h !== '') {

            // Toggle classes
            if (this.targetMenuBtn) this.targetMenuBtn.menu.$container.addClass('dark');
            if (this.zoomMenuBtn) this.zoomMenuBtn.menu.$container.addClass('dark');

            Craft.livePreview.$iframeContainer.addClass('portal-lp--resized');

            if (bp === 'tablet') {
                Craft.livePreview.$iframeContainer.addClass('portal-lp--tablet');
            } else {
                Craft.livePreview.$iframeContainer.removeClass('portal-lp--tablet');
            }

            // Make the size change
            this.$iframe.css({
                width: w + 'px',
                height: h + 'px',
                marginLeft: '-'+(w/2)+'px'
            });

        } else {

            // Desktop
            this.resetIframe();

        }

    },

    toggleOrientation: function(ev)
    {

        var $btn = $(ev.target);

        if ($btn.data('portal-working')) {
            return;
        }

        $btn.data('portal-working', true);


        // Track it in a cookie and toggle state classes
        var orientation = Cookies.get('portal_orientation');

        if (!orientation || orientation === 'portrait') {
            orientation = 'landscape';
            Craft.livePreview.$iframeContainer.addClass('portal-lp--landscape');
        } else {
            orientation = 'portrait';
            Craft.livePreview.$iframeContainer.removeClass('portal-lp--landscape');
        }

        Cookies.set('portal_orientation', orientation);


        // Make the switch
        var bp = Cookies.get('portal_breakpoint');

        if (bp && bp !== 'desktop') {

            clearTimeout(this.rotatingTimeout);
            Craft.livePreview.$iframeContainer.addClass('portal-lp--rotating');

            this.rotatingTimeout = setTimeout($.proxy(function() {

                var w = this.$iframe.outerWidth(),
                    h = this.$iframe.outerHeight();

                // Check actual and intended orientation line up, if not then invert
                if ((orientation === 'portrait' && w > h) || orientation === 'landscape' && w < h) {
                    this.$iframe.css({
                        width: h + 'px',
                        height: w + 'px',
                        marginLeft: '-'+(h/2)+'px'
                    });
                }

                Craft.livePreview.$iframeContainer.addClass('portal-lp--rotating-done');
                Craft.livePreview.$iframeContainer.removeClass('portal-lp--rotating');
                setTimeout(function() {
                    Craft.livePreview.$iframeContainer.removeClass('portal-lp--rotating-done');
                    $btn.data('portal-working', false);
                }, 50);

            }, this), 350);

        } else {
            $btn.data('portal-working', false);
        }

    },

    resetIframe: function()
    {
        if (this.targetMenuBtn) this.targetMenuBtn.menu.$container.removeClass('dark');
        if (this.zoomMenuBtn) this.zoomMenuBtn.menu.$container.removeClass('dark');

        Craft.livePreview.$iframeContainer.removeClass('portal-lp--resized');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp--tablet');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp--landscape');
        this.$iframe.css({
            width: '100%',
            height: '100%',
            marginLeft: '0'
        });
    },

    onChangeTarget: function(menuOption)
    {
        var $menuOption = $(menuOption),
            template = $menuOption.data('template');

        // Update menu sel class
        $menuOption.parent().siblings().find('a.sel').removeClass('sel');
        $menuOption.addClass('sel');

        // Store the template in a cookie
        Cookies.set('portal_template', template);

        // Force the iframe to refresh
        Craft.livePreview.forceUpdateIframe();
    },

    onZoom: function(menuOption)
    {

        var $menuOption = $(menuOption),
            zoom = $menuOption.data('zoom');

        // Update menu sel class
        $menuOption.parent().siblings().find('a.sel').removeClass('sel');
        $menuOption.addClass('sel');

        // Store the zoom in a cookie
        Cookies.set('portal_zoom', zoom);

        // Toggle the container class
        if (zoom === 'full') {
            Craft.livePreview.$iframeContainer.removeClass('portal-lp--zoom-half');
            Craft.livePreview.$iframeContainer.addClass('portal-lp--zoom-full');
        }
        if (zoom === 'threequarters') {
            Craft.livePreview.$iframeContainer.removeClass('portal-lp--zoom-full');
            Craft.livePreview.$iframeContainer.removeClass('portal-lp--zoom-half');
        }
        if (zoom === 'half') {
            Craft.livePreview.$iframeContainer.removeClass('portal-lp--zoom-full');
            Craft.livePreview.$iframeContainer.addClass('portal-lp--zoom-half');
        }

    }

},
{
    defaults: {
        siteId: null,
        targets: null,
        context: null,
        showBreakpoints: true
    }
});