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

use angellco\portal\models\Target;

use Craft;
use craft\db\ActiveRecord;
use craft\models\Site;
use yii\db\ActiveQueryInterface;

/**
 * Class Target_SiteSettings record.
 *
 * @property int $id ID
 * @property int $targetId Target ID
 * @property int $siteId Site ID
 * @property string $template Template
 * @property Target $target Target
 * @property Site $site Site
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class Target_SiteSettings extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%portal_targets_sites}}';
    }

    /**
     * Returns the associated target.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup(): ActiveQueryInterface
    {
        return $this->hasOne(Target::class, [ 'id' => 'targetId' ]);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, [ 'id' => 'siteId' ]);
    }
}
