<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\services;

use angellco\portal\Portal;
use angellco\portal\models\Target;
use angellco\portal\models\Target_SiteSettings;
use angellco\portal\records\Target as TargetRecord;
use angellco\portal\records\Target_SiteSettings as Target_SiteSettingsRecord;
use angellco\portal\errors\TargetNotFoundException;

use Craft;
use craft\base\Component;
use craft\db\Query;

/**
 * Targets Service
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class Targets extends Component
{

    // Properties
    // =========================================================================

    /**
     * @var int[]|null
     */
    private $_allTargetIds;

    /**
     * @var Target[]|null
     */
    private $_targetsById;

    /**
     * @var bool
     */
    private $_fetchedAllTargets = false;

    // Public Methods
    // =========================================================================

    /**
     * Returns all of the target IDs.
     *
     * @return int[]
     */
    public function getAllTargetIds(): array
    {
        if ($this->_allTargetIds !== null) {
            return $this->_allTargetIds;
        }

        if ($this->_fetchedAllTargets) {
            return $this->_allTargetIds = array_keys($this->_targetsById);
        }

        return $this->_allTargetIds = (new Query())
            ->select(['id'])
            ->from(['{{%portal_targets}}'])
            ->column();
    }

    /**
     * Returns all targets.
     *
     * @return Target[]
     */
    public function getAllTargets(): array
    {
        if ($this->_fetchedAllTargets) {
            return array_values($this->_targetsById);
        }

        $this->_targetsById = [];

        /** @var TargetRecord[] $targetRecords */
        $targetRecords = TargetRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($targetRecords as $targetRecord) {
            $this->_targetsById[$targetRecord->id] = $this->_createTargetFromRecord($targetRecord);
        }

        $this->_fetchedAllTargets = true;

        return array_values($this->_targetsById);
    }

    /**
     * Gets the total number of targets.
     *
     * @return int
     */
    public function getTotalTargets(): int
    {
        return count($this->getAllTargetIds());
    }

    /**
     * Returns a target by its ID.
     *
     * @param int $targetId
     * @return Target|null
     */
    public function getTargetById(int $targetId)
    {
        if ($this->_targetsById !== null && array_key_exists($targetId, $this->_targetsById)) {
            return $this->_targetsById[$targetId];
        }

        if ($this->_fetchedAllTargets) {
            return null;
        }

        $targetRecord = TargetRecord::find()
            ->where(['id' => $targetId])
            ->one();

        if ($targetRecord === null) {
            return $this->_targetsById[$targetId] = null;
        }

        /** @var TargetRecord $targetRecord */
        return $this->_targetsById[$targetId] = $this->_createTargetFromRecord($targetRecord);
    }

    /**
     * Returns a targets's site settings.
     *
     * @param int $targetId
     * @return Target_SiteSettings[]
     */
    public function getTargetSiteSettings(int $targetId): array
    {
        $results = Target_SiteSettingsRecord::find()
            ->where(['targetId' => $targetId])
            ->all();
        $siteSettings = [];

        foreach ($results as $result) {
            $siteSettings[] = new Target_SiteSettings($result->toArray([
                'id',
                'targetId',
                'siteId',
                'template',
            ]));
        }

        return $siteSettings;

    }

    /**
     * Saves a target.
     *
     * @param Target $target The target to be saved
     *
     * @return bool Whether the target was saved successfully
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function saveTarget(Target $target): bool
    {
        $isNewTarget = !$target->id;

        if (!$isNewTarget) {
            $targetRecord = TargetRecord::find()
                ->where(['id' => $target->id])
                ->one();

            if (!$targetRecord) {
                throw new TargetNotFoundException("No target exists with the ID '{$target->id}'");
            }

            $oldTarget = new Target($targetRecord->toArray([
                'id',
                'name',
                'context',
            ]));
        } else {
            $targetRecord = new TargetRecord();
        }

        $targetRecord->name = $target->name;
        $targetRecord->context = $target->context;

        // Get the site settings
        $allSiteSettings = $target->getSiteSettings();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a target that is missing site settings');
            }
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {

            // Save the category group
            $targetRecord->save(false);

            // Now that we have a target ID, save it on the model
            if (!$target->id) {
                $target->id = $targetRecord->id;
            }

            // Might as well update our cache of the target while we have it.
            $this->_targetsById[$target->id] = $target;

            // Update the site settings
            // -----------------------------------------------------------------

            if (!$isNewTarget) {
                // Get the old target site settings
                $allOldSiteSettingsRecords = Target_SiteSettingsRecord::find()
                    ->where(['targetId' => $target->id])
                    ->indexBy('siteId')
                    ->all();
            }

            foreach ($allSiteSettings as $siteId => $siteSettings) {
                // Was this already selected?
                if (!$isNewTarget && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new Target_SiteSettingsRecord();
                    $siteSettingsRecord->targetId = $target->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                $siteSettingsRecord->template = $siteSettings->template;

                $siteSettingsRecord->save(false);

                // Set the ID on the model
                $siteSettings->id = $siteSettingsRecord->id;
            }

            if (!$isNewTarget) {
                // Drop any site settings that are no longer being used
                $siteIds = array_keys($allSiteSettings);

                /** @noinspection PhpUndefinedVariableInspection */
                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    if (!in_array($siteId, $siteIds, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Deletes a target by its ID.
     *
     * @param int $targetId The target's ID
     * @return bool Whether the target was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteTargetById(int $targetId): bool
    {
        if (!$targetId) {
            return false;
        }

        $target = $this->getTargetById($targetId);

        if (!$target) {
            return false;
        }

        return $this->deleteTarget($target);
    }

    /**
     * Deletes a target.
     *
     * @param Target $target The target
     * @return bool Whether the target was deleted successfully
     */
    public function deleteTarget(Target $target): bool
    {

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {

            Craft::$app->getDb()->createCommand()
                ->delete('{{%portal_targets}}', ['id' => $target->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Returns if the target’s template path is valid.
     *
     * @param Target $target
     * @param int $siteId
     * @return bool
     */
    public function isTargetTemplateValid(Target $target, int $siteId): bool
    {
        $targetSiteSettings = $target->getSiteSettings();

        if (isset($targetSiteSettings[$siteId])) {
            // Set Craft to the site template mode
            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Does the template exist?
            $templateExists = Craft::$app->getView()->doesTemplateExist((string)$targetSiteSettings[$siteId]->template);

            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            if ($templateExists) {
                return true;
            }
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a Target with attributes from a TargetRecord.
     *
     * @param TargetRecord|null $targetRecord
     * @return Target|null
     */
    private function _createTargetFromRecord(TargetRecord $targetRecord = null)
    {
        if (!$targetRecord) {
            return null;
        }

        $target = new Target($targetRecord->toArray([
            'id',
            'name',
            'context',
        ]));

        return $target;
    }

}
