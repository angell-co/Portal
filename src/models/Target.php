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
class Target extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $template;

    /**
     * @var string
     */
    public $context;

    /**
     * @var int
     */
    public $siteId;


    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [
                [
                    'name',
                    'template',
                    'context',
                ],
                'string',
            ],
            [['siteId'], 'number', 'min' => 1],
        ];
    }
}
