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

(function($){

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
            Garnish.on(Craft.LivePreview, 'enter', this.onEnter);
        },

        onEnter: function()
        {
            if (!this.$toolbar) {

                this.$toolbar = $('<header class="header" />');

                this.$breakpointButtons = $('<div class="btngroup" />').appendTo(this.$toolbar);

                $('<div class="btn">Desktop</div>').appendTo(this.$breakpointButtons);
                $('<div class="btn">Tablet</div>').appendTo(this.$breakpointButtons);
                $('<div class="btn">Mobile</div>').appendTo(this.$breakpointButtons);

                this.$toolbar.prependTo(Craft.livePreview.$iframeContainer);
            }
        }
    });

    Portal.LivePreview.init = function(settings) {
        Portal.livePreview = new Portal.LivePreview(settings);
    };

})(jQuery);