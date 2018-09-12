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
    $breakpointButtons: null,

    init: function(settings)
    {
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

        Craft.livePreview.$iframe.addClass('portal-lp-iframe');

        if (!this.$toolbar) {

            this.$toolbar = $('<header class="portal-lp-toolbar header" />');

            this.$breakpointButtons = $('<div class="btngroup" />').appendTo(this.$toolbar);

            $('<div class="btn" data-width="" data-height="" data-breakpoint="desktop">Desktop</div>').appendTo(this.$breakpointButtons);
            $('<div class="btn" data-width="1024" data-height="768" data-breakpoint="tablet">Tablet</div>').appendTo(this.$breakpointButtons);
            $('<div class="btn" data-width="375" data-height="667" data-breakpoint="mobile">Mobile</div>').appendTo(this.$breakpointButtons);

            this.addListener($('.btn', this.$breakpointButtons), 'activate', 'changeBreakpoint');
        }

        this.$toolbar.prependTo(Craft.livePreview.$iframeContainer);

    },

    detachToolbar: function()
    {
        Craft.livePreview.$iframe.removeClass('portal-lp-iframe');
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

        Cookies.set('spoon_breakpoint', bp);

        if (w !== '' && h !== '') {
            Craft.livePreview.$iframe.addClass('portal-lp-iframe--resized');
            Craft.livePreview.$iframe.css({
                width: w + 'px',
                height: h + 'px',
                left: '50%',
                marginLeft: '-' + (w / 2) + 'px'
            });
        } else {
            this.resetIframe();
        }
    },

    resetIframe: function()
    {
        Craft.livePreview.$iframe.removeClass('portal-lp-iframe--resized');
        Craft.livePreview.$iframe.css({
            width: '100%',
            height: '100%',
            left: 0,
            marginLeft: 0
        });
    }
});