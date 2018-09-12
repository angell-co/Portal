<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal;

use angellco\portal\assetbundles\livepreview\LivePreviewAsset;
use angellco\portal\services\PortalService as PortalServiceService;
use angellco\portal\services\Targets as TargetsService;

use Craft;
use craft\base\Plugin;
use craft\events\TemplateEvent;
use craft\helpers\Json;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

use craft\web\View;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 *
 * @property  PortalServiceService $portalService
 * @property  TargetsService $targets
 */
class Portal extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Portal::$plugin
     *
     * @var Portal
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '0.1.0';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Portal::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register global CP resources after all plugins have loaded
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                if ($this->isInstalled && !Craft::$app->plugins->doesPluginRequireDatabaseUpdate($this)) {
                    $this->_loadGlobalCpResources();
                }
            }
        );

        // If its a Live Preview request then we need to do stuff
        if (Craft::$app->request->isLivePreview) {

            // First we hijack the UA
            $this->_fakeUserAgent();

            // Then we hijack the rendering template, if we need to
            Event::on(
                View::class,
                View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
                function (TemplateEvent $event) {
                    if (isset($_COOKIE['spoon_template'])) {

                        $newTemplate = $_COOKIE['spoon_template'];

                        if ($newTemplate !== "" && $event->template !== $newTemplate) {
                            $event->output = Craft::$app->view->renderPageTemplate($newTemplate, $event->variables);
                        }
                    }
                }
            );

        }



//        // Register our site routes
//        Event::on(
//            UrlManager::class,
//            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
//            function (RegisterUrlRulesEvent $event) {
//                $event->rules['siteActionTrigger1'] = 'portal/targets';
//            }
//        );
//
//        // Register our CP routes
//        Event::on(
//            UrlManager::class,
//            UrlManager::EVENT_REGISTER_CP_URL_RULES,
//            function (RegisterUrlRulesEvent $event) {
//                $event->rules['cpActionTrigger1'] = 'portal/targets/do-something';
//            }
//        );
//
//        // Do something after we're installed
//        Event::on(
//            Plugins::class,
//            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
//            function (PluginEvent $event) {
//                if ($event->plugin === $this) {
//                    // We were just installed
//                }
//            }
//        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'portal',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Private Methods
    // =========================================================================

    /**
     * Loads up the CP resources we need everywhere.
     *
     * @throws \yii\base\InvalidConfigException
     */
    private function _loadGlobalCpResources()
    {
        // Check the conditions are right to run
        if (Craft::$app->request->isCpRequest && !Craft::$app->request->getAcceptsJson())
        {
            $view = Craft::$app->getView();

            $view->registerAssetBundle(LivePreviewAsset::class);

            $settings = [];

            $view->registerJs('new Portal.LivePreview('.Json::encode($settings, JSON_UNESCAPED_UNICODE).');');
        }

    }

    /**
     * Fakes the User Agent string for the request based on a cookie
     */
    private function _fakeUserAgent()
    {
        if (isset($_COOKIE['spoon_breakpoint'])) {

            $breakpoint = $_COOKIE['spoon_breakpoint'];
            $headers = Craft::$app->request->getHeaders();

            if ($breakpoint === 'tablet') {
                $headers->set('user-agent', 'Mozilla/5.0 (iPad; CPU OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
            }

            if ($breakpoint === 'mobile') {
                $headers->set('user-agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
            }
        }
    }

}
