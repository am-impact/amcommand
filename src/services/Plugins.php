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
use craft\helpers\UrlHelper;

class Plugins extends Component
{
    /**
     * Get a list plugins with their settings URL.
     *
     * @return array
     */
    public function getSettingsUrl()
    {
        // Gather commands
        $commands = [];

        // Get plugins with their settings URL
        $enabledPlugins = Craft::$app->plugins->getAllPlugins();
        if ($enabledPlugins) {
            foreach ($enabledPlugins as $enabledPlugin) {
                if ($enabledPlugin->hasCpSettings) {
                    $commands[] = [
                        'name' => $enabledPlugin->name,
                        'url'  => UrlHelper::cpUrl('settings/plugins/' . $enabledPlugin->id)
                    ];
                }
            }
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'There are no enabled plugins with settings.'));
        }

        return $commands;
    }
}
