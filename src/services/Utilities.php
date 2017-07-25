<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\helpers\App;

class Utilities extends Component
{
    /**
     * Get a list of utilities.
     *
     * @return array
     */
    public function getUtilities()
    {
        $commands = [
            [
                'name'    => Craft::t('app', 'Clear Caches'),
                'warn'    => true,
                'call'    => 'clearCaches',
                'service' => 'utilities',
            ],
        ];

        return $commands;
    }

    /**
     * Start utility: Clear Caches.
     *
     * @return bool
     */
    public function clearCaches()
    {
        // Max power please!
        App::maxPowerCaptain();

        // Delete cache!
        Craft::$app->getTemplateCaches()->deleteAllCaches();

        return true;
    }
}
