<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\records;

use angellco\portal\Portal;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class Target record.
 *
 * @property int $id ID
 * @property string $name Name
 * @property string $context Context
 * @property Target_SiteSettings[] $siteSettings Site settings
 * @property Target[] $targets Targets
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     1.0.0
 */
class Target extends ActiveRecord
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%portal_targets}}';
    }

    /**
     * Returns the target’s site settings.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSiteSettings(): ActiveQueryInterface
    {
        return $this->hasMany(Target_SiteSettings::class, [ 'targetId' => 'id' ]);
    }

}
