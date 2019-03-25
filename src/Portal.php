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
use angellco\portal\services\Targets as TargetsService;
use angellco\portal\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\events\TemplateEvent;
use craft\helpers\Json;
use craft\services\Plugins;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\web\View;

use craft\commerce\Plugin as CommercePlugin;

use yii\base\Event;
use yii\web\NotFoundHttpException;

/**
 * @author    Angell & Co
 * @package   Portal
 * @since     1.0.0
 *
 * @property  TargetsService $targets
 * @method    Settings getSettings()
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

    /**
     * Set to true if Craft Commerce is installed
     *
     * @var bool
     */
    public static $commerceInstalled;


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

        // Check if Commerce is installed
        self::$commerceInstalled = class_exists(CommercePlugin::class);

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules[ 'portal/targets' ] = 'portal/targets/index';
                $event->rules[ 'portal/targets/new' ] = 'portal/targets/edit-target';
                $event->rules[ 'portal/targets/<targetId:\d+>' ] = 'portal/targets/edit-target';
            }
        );

        // Register global CP resources after all plugins have loaded
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function() {
                if ($this->isInstalled && !Craft::$app->plugins->doesPluginRequireDatabaseUpdate($this)) {
                    $this->_loadLivePreviewCpResources();
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
                function(TemplateEvent $event) {
                    $this->_switchTemplate($event);
                }
            );

        }

        // Log that the plugin is loaded
        Craft::info(
            Craft::t(
                'portal',
                '{name} plugin loaded',
                [ 'name' => $this->name ]
            ),
            __METHOD__
        );
    }

    // Private Methods
    // =========================================================================

    /**
     * Loads up the CP resources we need for Live Preview.
     *
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    private function _loadLivePreviewCpResources()
    {
        // Check the conditions are right to run
        if (Craft::$app->request->isCpRequest && !Craft::$app->request->getAcceptsJson())
        {

            // First of all, check we’re in a valid context
            $segments = Craft::$app->request->getSegments();

            $context = false;

            // Entries
            if (count($segments) >= 3 && $segments[ 0 ] === 'entries')
            {

                if ($segments[ 2 ] === 'new')
                {
                    $section = Craft::$app->sections->getSectionByHandle($segments[ 1 ]);
                }
                else
                {
                    $entryId = (integer) explode('-', $segments[ 2 ])[ 0 ];
                    $entry = Craft::$app->entries->getEntryById($entryId);

                    if ($entry)
                    {
                        $section = $entry->getSection();
                    }
                }

                if (isset($section) && $section) {
                    $context = 'section:'.$section->id;
                }



            }
            // Category groups
            else if (count($segments) >= 3 && $segments[ 0 ] === 'categories')
            {
                $group = Craft::$app->categories->getGroupByHandle($segments[ 1 ]);
                if ($group)
                {
                    $context = 'categoryGroup:'.$group->id;
                }
            }
            // Product Types
            else if ($this::$commerceInstalled && count($segments) >= 4 && $segments[ 0 ] === 'commerce' && $segments[ 1 ] === 'products')
            {
                $productType = CommercePlugin::getInstance()->productTypes->getProductTypeByHandle($segments[ 2 ]);
                if ($productType)
                {
                    $context = 'productType:'.$productType->id;
                }
            }


            // Then, if we are we can get the data we need and run
            if ($context) {

                // Work out the Site
                $siteHandle = null;
                if (count($segments) === 4 && $segments[ 3 ] !== 'new') {
                    $siteHandle = $segments[ 3 ];
                }

                // Deal with commerce routes
                if ($segments[ 0 ] === 'commerce'
                    && $segments[ 1 ] === 'products'
                    && $segments[ 3 ] === 'new'
                    && isset($segments[ 4 ])
                ) {
                    $siteHandle = $segments[ 4 ];
                }

                if ($siteHandle) {
                    $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

                    if (!$site) {
                        $site = Craft::$app->getSites()->getPrimarySite();
                    }
                } else {
                    $site = Craft::$app->getSites()->getPrimarySite();
                }

                // Make the settings the JS needs
                $settings = [
                    'siteId' => $site->id,
                    'context' => $context,
                    'targets' => $this->targets->getAllTargetsForLivePreview(),
                    'showBreakpoints' => $this->getSettings()->showLivePreviewDeviceEmulator
                ];

                // Register the AssetBundle
                $view = Craft::$app->getView();

                $view->registerAssetBundle(LivePreviewAsset::class);

                $view->registerJs('new Portal.LivePreview('.Json::encode($settings, JSON_UNESCAPED_UNICODE).');');
            }

        }

    }

    /**
     * Fakes the User Agent string for the request based on a cookie
     */
    private function _fakeUserAgent()
    {
        if (isset($_COOKIE[ 'portal_breakpoint' ])) {

            $breakpoint = $_COOKIE[ 'portal_breakpoint' ];
            $headers = Craft::$app->request->getHeaders();

            if ($breakpoint === 'tablet') {
                $headers->set('user-agent', 'Mozilla/5.0 (iPad; CPU OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
            }

            if ($breakpoint === 'mobile') {
                $headers->set('user-agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
            }
        }
    }

    /**
     * Changes the templte of the rendering page to whatever is in the cookie
     */
    private function _switchTemplate($event)
    {
        if (isset($_COOKIE[ 'portal_template' ])) {

            $newTemplate = $_COOKIE[ 'portal_template' ];

            if ($newTemplate !== "" && $event->template !== $newTemplate) {
                $event->output = Craft::$app->view->renderPageTemplate($newTemplate, $event->variables);
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

}
