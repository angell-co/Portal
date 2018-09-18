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
 * @since     0.1.0
 */

if (typeof Portal === 'undefined')
{
    Portal = {};
}

Portal.LivePreview = Garnish.Base.extend(
{

    $toolbar: null,
    $deviceMask: null,

    targetMenuBtn: null,
    rotatingTimeout: null,

    init: function(settings)
    {

        // We always want to load with the default page template
        Cookies.remove('portal_template');

        // Bind to the live preview events
        Garnish.on(Craft.LivePreview, 'enter', $.proxy(function(ev)
        {
            this.onEnter(ev)
        }, this));

        Garnish.on(Craft.LivePreview, 'exit', $.proxy(function(ev)
        {
            this.onExit(ev)
        }, this));

    },

    onEnter: function(ev)
    {
        this.addListener(Craft.livePreview.$editor, 'resize', 'toggleToolbar');
        this.toggleToolbar();
    },

    onExit: function(ev)
    {
        this.removeListener(Craft.livePreview.$editor, 'resize');
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

        Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container');

        if (!this.$toolbar) {

            this.$toolbar = $('<header class="header" />');

            // Breakpoints
            var $breakpointButtons = $('<div class="btngroup" />').appendTo(this.$toolbar);

            // TODO make these configurable
            $('<div class="portal-lp-btn portal-lp-btn--desktop" data-width="" data-height="" data-breakpoint="desktop" />').appendTo($breakpointButtons);
            $('<div class="portal-lp-btn portal-lp-btn--tablet" data-width="768" data-height="1006" data-breakpoint="tablet" />').appendTo($breakpointButtons);
            $('<div class="portal-lp-btn portal-lp-btn--mobile" data-width="375" data-height="653" data-breakpoint="mobile" />').appendTo($breakpointButtons);


            // Orientation toggle
            var $orientationToggle = $('<div class="btn portal-lp-orientation-btn" data-icon="refresh"></div>').appendTo(this.$toolbar);
            this.addListener($orientationToggle, 'activate', 'toggleOrientation');


            // Target selector
            var $targetMenuBtn = $('<div class="btn menubtn right no-outline">'+Craft.t('portal', 'Choose Target')+'</div>').appendTo(this.$toolbar),
                $targetMenu = $('<div class="menu" />').appendTo(this.$toolbar),
                $targetMenuUl = $('<ul />').appendTo($targetMenu);

            // TODO load up from the backend with correct context
            $('<li><a data-template="">Primary Page</a></li>').appendTo($targetMenuUl);
            $('<li><a data-template="news/_entry">Something Else</a></li>').appendTo($targetMenuUl);

            this.targetMenuBtn = new Garnish.MenuBtn($targetMenuBtn,
            {
                onOptionSelect: function(option)
                {
                    var template = $(option).data('template');
                    Cookies.set('portal_template', template);
                    Craft.livePreview.forceUpdateIframe();
                }
            });


            // Breakpoint button click handlers
            this.addListener($('.portal-lp-btn', $breakpointButtons), 'activate', 'changeBreakpoint');

            // Set the window to the last breakpoint we have in the cookie
            var currentBreakpoint = Cookies.get('portal_breakpoint');
            if (currentBreakpoint) {
                $breakpointButtons.find('.portal-lp-btn[data-breakpoint="'+currentBreakpoint+'"]').click();
            }

        }


        // Device mask
        if (!this.$deviceMask) {
            this.$deviceMask = $('<div class="portal-device-mask" />');
        }


        // Add to DOM
        this.$toolbar.prependTo(Craft.livePreview.$iframeContainer);
        this.$deviceMask.appendTo(Craft.livePreview.$iframeContainer);


        // Set current state
        if (currentBreakpoint && currentBreakpoint === 'tablet') {
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--tablet');
        }

        var currentOrientation = Cookies.get('portal_orientation');
        if ((currentBreakpoint && currentBreakpoint !== 'desktop') && (currentOrientation && currentOrientation === 'landscape')) {
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--landscape');
        }

    },

    detachToolbar: function()
    {
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--landscape');
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
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--landscape');
        }


        // Change the size of the iframe if we can
        if (w !== '' && h !== '') {

            // Toggle classes
            this.targetMenuBtn.menu.$container.addClass('dark');
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--resized');

            if (bp === 'tablet') {
                Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--tablet');
            } else {
                Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--tablet');
            }

            // Make the size change
            Craft.livePreview.$iframe.css({
                width: w + 'px',
                height: h + 'px',
                left: '50%',
                marginLeft: '-' + (w / 2) + 'px'
            });

        } else {

            // Desktop
            this.resetIframe();

        }


        // Force live preview to update
        Craft.livePreview.forceUpdateIframe();

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
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--landscape');
        } else {
            orientation = 'portrait';
            Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--landscape');
        }

        Cookies.set('portal_orientation', orientation);


        // Make the switch
        var bp = Cookies.get('portal_breakpoint');

        if (bp && bp !== 'desktop') {

            clearTimeout(this.rotatingTimeout);
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--rotating');

            this.rotatingTimeout = setTimeout(function() {

                var w = Craft.livePreview.$iframe.outerWidth(),
                    h = Craft.livePreview.$iframe.outerHeight();

                // Check actual and intended orientation line up, if not then invert
                if ((orientation === 'portrait' && w > h) || orientation === 'landscape' && w < h) {
                    Craft.livePreview.$iframe.css({
                        width: h + 'px',
                        height: w + 'px',
                        marginLeft: '-' + (h / 2) + 'px'
                    });
                }

                Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--rotating');

                $btn.data('portal-working', false);

            }, 350);

        } else {
            $btn.data('portal-working', false);
        }

    },

    resetIframe: function()
    {
        this.targetMenuBtn.menu.$container.removeClass('dark');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--resized');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--tablet');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--landscape');
        Craft.livePreview.$iframe.css({
            width: '100%',
            height: '100%',
            left: 0,
            marginLeft: 0
        });
    }
});