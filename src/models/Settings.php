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

/**
 * Settings Model
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class Settings extends Model
{

    // Public Properties
    // =========================================================================

    /**
     * Whether to show the device emulator in Live Preview or not
     *
     * @var bool
     */
    public $showLivePreviewDeviceEmulator = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [ 'showLivePreviewDeviceEmulator', 'boolean' ]
        ];
    }

}