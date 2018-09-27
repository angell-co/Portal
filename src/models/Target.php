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
use angellco\portal\records\Target as TargetRecord;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\validators\UniqueValidator;

/**
 * Target Model
 *
 * @property int|null $id ID
 * @property string|null $name Name
 * @property string|null $context Context
 * @property Target_SiteSettings[] $siteSettings Site settings
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class Target extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Context
     */
    public $context;

    /**
     * @var array|null Site Settings
     */
    public $siteSettings;


    // Private Properties
    // =========================================================================

    /**
     * @var array|null Context Options
     */
    private $_contextOptions;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [ [ 'id' ], 'number', 'integerOnly' => true ],
            [ [ 'name' ], UniqueValidator::class, 'targetClass' => TargetRecord::class ],
            [ [ 'name', 'context', 'siteSettings' ], 'required' ],
            [ [ 'name' ], 'string', 'max' => 255 ],
            [ [ 'siteSettings' ], 'validateSiteSettings' ],
        ];
    }

    /**
     * Validates the targets site settings.
     */
    public function validateTargetSite()
    {
        foreach ($this->getSiteSettings() as $i => $siteSetting) {
            if (!$siteSetting->validate()) {
                $this->addModelErrors($siteSetting, "siteSetting[{$i}]");
            }
        }
    }

    /**
     * Use the translated target's name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Returns the targets's site-specific settings.
     *
     * @return Target_SiteSettings[]
     */
    public function getSiteSettings(): array
    {
        if ($this->siteSettings !== null) {
            return $this->siteSettings;
        }

        if (!$this->id) {
            return [ ];
        }

        // Set them with setSiteSettings() so setTarget() gets called on them
        $this->setSiteSettings(ArrayHelper::index(Portal::$plugin->targets->getTargetSiteSettings($this->id), 'siteId'));

        return $this->siteSettings;
    }

    /**
     * Sets the target's site-specific settings.
     *
     * @param Target_SiteSettings[] $siteSettings
     */
    public function setSiteSettings(array $siteSettings)
    {
        $this->siteSettings = $siteSettings;

        foreach ($this->siteSettings as $settings) {
            $settings->setTarget($this);
        }
    }

    /**
     * Returns the name of the Target’s context.
     *
     * @return string|bool
     */
    public function getContextName()
    {

        if (!$this->_contextOptions) {
            $this->_contextOptions = Portal::$plugin->targets->getContextOptions();
        }

        if (isset($this->_contextOptions[ $this->context ])) {
            return $this->_contextOptions[ $this->context ][ 'label' ];
        }

        return false;

    }

}
