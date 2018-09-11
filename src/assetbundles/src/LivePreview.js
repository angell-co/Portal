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
        if (!this.$toolbar) {

            Craft.livePreview.$iframe.addClass('portal-lp-iframe');

            this.$toolbar = $('<header class="portal-lp-toolbar header" />');

            this.$breakpointButtons = $('<div class="btngroup" />').appendTo(this.$toolbar);

            $('<div class="btn" data-width="" data-height="">Desktop</div>').appendTo(this.$breakpointButtons);
            $('<div class="btn" data-width="1024" data-height="768">Tablet</div>').appendTo(this.$breakpointButtons);
            $('<div class="btn" data-width="375" data-height="667">Mobile</div>').appendTo(this.$breakpointButtons);

            this.$toolbar.prependTo(Craft.livePreview.$iframeContainer);

            this.addListener($('.portal-lp-toolbar .btn'), 'activate', 'changeBreakpoint');

        }
    },

    changeBreakpoint: function(ev)
    {
        var $btn = $(ev.target),
            w = $btn.data('width'),
            h = $btn.data('height');

        if (w !== '' && h !== '') {
            Craft.livePreview.$iframe.addClass('portal-lp-iframe--resized');
            Craft.livePreview.$iframe.css({
                width: w + 'px',
                height: h + 'px',
                left: '50%',
                marginLeft: '-' + (w / 2) + 'px'
            });
        } else {
            Craft.livePreview.$iframe.removeClass('portal-lp-iframe--resized');
            Craft.livePreview.$iframe.css({
                width: '100%',
                height: '100%',
                left: 0,
                marginLeft: 0
            });
        }
    }
});