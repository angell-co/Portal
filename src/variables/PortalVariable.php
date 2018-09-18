<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\variables;

use angellco\portal\Portal;

use Craft;

/**
 * Portal Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.foo }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     0.1.0
 */
class PortalVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @return \angellco\portal\services\Targets
     */
    public function targets()
    {
        return Portal::$plugin->targets;
    }

}
