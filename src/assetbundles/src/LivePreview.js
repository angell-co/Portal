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
    targetMenuBtn: null,

    init: function(settings)
    {

        // We always want to load with the default page template
        Cookies.remove('portal_template');

        // Bind to the live preview enter event
        Garnish.on(Craft.LivePreview, 'enter', $.proxy(function(ev)
        {
            this.onEnter(ev)
        }, this));

    },

    onEnter: function(ev)
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
            $('<div class="portal-btn portal-btn--desktop" data-width="" data-height="" data-breakpoint="desktop" />').appendTo($breakpointButtons);
            $('<div class="portal-btn portal-btn--tablet" data-width="768" data-height="1024" data-breakpoint="tablet" />').appendTo($breakpointButtons);
            $('<div class="portal-btn portal-btn--mobile" data-width="375" data-height="667" data-breakpoint="mobile" />').appendTo($breakpointButtons);



            // Target selector
            var $targetMenuBtn = $('<div class="btn menubtn right">'+Craft.t('portal', 'Choose Target')+'</div>').appendTo(this.$toolbar),
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
            this.addListener($('.portal-btn', $breakpointButtons), 'activate', 'changeBreakpoint');

            // Set the window to the last breakpoint we have in the cookie
            var currentBreakpoint = Cookies.get('portal_breakpoint');
            if (currentBreakpoint) {
                $breakpointButtons.find('.portal-btn[data-breakpoint="'+currentBreakpoint+'"]').click();
            }

        }

        this.$toolbar.prependTo(Craft.livePreview.$iframeContainer);

    },

    detachToolbar: function()
    {
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container');
        this.resetIframe();

        if (this.$toolbar) {
            this.$toolbar.detach();
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

        // Change the size of the iframe
        if (w !== '' && h !== '') {
            this.targetMenuBtn.menu.$container.addClass('dark');
            Craft.livePreview.$iframeContainer.addClass('portal-lp-iframe-container--resized');
            Craft.livePreview.$iframe.css({
                width: w + 'px',
                height: h + 'px',
                left: '50%',
                marginLeft: '-' + (w / 2) + 'px'
            });
        } else {
            this.resetIframe();
        }

        // Force live preview to update
        Craft.livePreview.forceUpdateIframe();

    },

    resetIframe: function()
    {
        this.targetMenuBtn.menu.$container.removeClass('dark');
        Craft.livePreview.$iframeContainer.removeClass('portal-lp-iframe-container--resized');
        Craft.livePreview.$iframe.css({
            width: '100%',
            height: '100%',
            left: 0,
            marginLeft: 0
        });
    }
});