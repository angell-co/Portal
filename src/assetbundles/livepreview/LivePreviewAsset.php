<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\assetbundles\livepreview;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use yii\web\View;

/**
 * LivePreviewAsset AssetBundle
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class LivePreviewAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@angellco/portal/assetbundles/livepreview/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/LivePreview.min.js',
        ];
        $this->css = [
            'css/LivePreview.min.css',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('portal', [
                'Desktop',
                'Tablet',
                'Mobile',
                'Choose Target'
            ]);
        }

    }

}
