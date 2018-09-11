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
        init: function(settings)
        {
            console.log('Portal LivePreview loaded');
            console.log(settings);

            Garnish.on(Craft.LivePreview, 'enter', function() {
                console.log('enter');
                console.log(Craft.livePreview);
            });
            Garnish.on(Craft.LivePreview, 'slideIn', function() {
                console.log('slideIn');
            });
            Garnish.on(Craft.LivePreview, 'exit', function() {
                console.log('exit');
            });
        }
    });

    Portal.LivePreview.init = function(settings) {
        Portal.livePreview = new Portal.LivePreview(settings);
    };

})(jQuery);