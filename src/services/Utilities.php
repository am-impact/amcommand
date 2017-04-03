<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;

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
        Craft::$app->getConfig()->maxPowerCaptain();

        return Craft::$app->getTemplateCaches()->deleteAllCaches();
    }
}
