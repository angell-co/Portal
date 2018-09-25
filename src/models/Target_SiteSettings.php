<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\models;

use angellco\portal\Portal;

use Craft;
use craft\base\Model;
use craft\models\Site;
use craft\validators\SiteIdValidator;

use yii\base\InvalidConfigException;

/**
 * Target Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class Target_SiteSettings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Target ID
     */
    public $targetId;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var string
     */
    public $template;

    /**
     * @var Target|null
     */
    private $_target;

    // Public Methods
    // =========================================================================

    /**
     * Returns the target.
     *
     * @return Target
     * @throws InvalidConfigException if [[targetId]] is missing or invalid
     */
    public function getTarget(): Target
    {
        if ($this->_target !== null) {
            return $this->_target;
        }

        if (!$this->targetId) {
            throw new InvalidConfigException('Target site settings model is missing its target ID');
        }

        if (($this->_target = Portal::$plugin->targets->getTargetById($this->targetId)) === null) {
            throw new InvalidConfigException('Invalid target ID: '.$this->targetId);
        }

        return $this->_target;
    }

    /**
     * Sets the target.
     *
     * @param Target $target
     */
    public function setTarget(Target $target)
    {
        $this->_target = $target;
    }

    /**
     * Returns the site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if (!$this->siteId) {
            throw new InvalidConfigException('Target site settings model is missing its site ID');
        }

        if (($site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: '.$this->siteId);
        }

        return $site;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'template' => Craft::t('app', 'Template'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [ [ 'id', 'targetId', 'siteId' ], 'number', 'integerOnly' => true ],
            [ [ 'siteId' ], SiteIdValidator::class ],
            [ [ 'template' ], 'string', 'max' => 500 ]
        ];

        return $rules;
    }
}
