<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\controllers;

use angellco\portal\models\Target_SiteSettings;
use angellco\portal\Portal;
use angellco\portal\models\Target;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Targets Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class TargetsController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * Targets index page.
     *
     * @return Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex(): Response
    {
        $this->requireAdmin();

        $targets = Portal::$plugin->targets->getAllTargets();

        return $this->renderTemplate('portal/targets/_index', [
            'targets' => $targets
        ]);
    }

    /**
     * Edit a target.
     *
     * @param int|null $targetId The target’s ID, if editing an existing target.
     * @param Target|null $target The target being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested category group cannot be found
     */
    public function actionEditTarget(int $targetId = null, Target $target = null): Response
    {
        $this->requireAdmin();

        $variables = [];


        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('portal', 'Portal'),
                'url' => UrlHelper::url('portal')
            ],
            [
                'label' => Craft::t('app', 'Live Preview'),
                'url' => UrlHelper::url('portal')
            ],
            [
                'label' => Craft::t('portal', 'Targets'),
                'url' => UrlHelper::url('portal/targets')
            ]
        ];


        // Context options
        $variables['contextOptions'] = Portal::$plugin->targets->getContextOptions();


        // Set up the model
        $variables['brandNewTarget'] = false;

        if ($targetId !== null) {
            if ($target === null) {
                $target = Portal::$plugin->targets->getTargetById($targetId);

                if (!$target) {
                    throw new NotFoundHttpException('Target not found');
                }
            }

            $variables['title'] = $target->name;
        } else {
            if ($target === null) {
                $target = new Target();
                $variables['brandNewTarget'] = true;
            }

            $variables['title'] = Craft::t('portal', 'Create a new target');
        }

        $variables['targetId'] = $targetId;
        $variables['target'] = $target;

        return $this->renderTemplate('portal/targets/_edit', $variables);
    }

    /**
     * Save a target.
     *
     * @return Response|null
     */
    public function actionSaveTarget()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $request = Craft::$app->getRequest();

        $target = new Target();

        // Main group settings
        $target->id = $request->getBodyParam('targetId');
        $target->name = $request->getBodyParam('name');
        $target->context = $request->getBodyParam('context');

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $request->getBodyParam('sites.' . $site->handle);

            $siteSettings = new Target_SiteSettings();
            $siteSettings->siteId = $site->id;
            $siteSettings->template = $postedSettings['template'];

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $target->setSiteSettings($allSiteSettings);

        // Save it
        if (!Portal::$plugin->targets->saveTarget($target)) {
            Craft::$app->getSession()->setError(Craft::t('portal', 'Couldn’t save the target.'));

            // Send the target back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'target' => $target
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('portal', 'Target saved.'));

        return $this->redirectToPostedUrl($target);
    }

    /**
     * Deletes a target.
     *
     * @return Response
     */
    public function actionDeleteTarget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $targetId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Portal::$plugin->targets->deleteTargetById($targetId);

        return $this->asJson(['success' => true]);
    }

}
