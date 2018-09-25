<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\errors;

use yii\base\Exception;

/**
 * Class TargetNotFoundException
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class TargetNotFoundException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Target not found';
    }
}
