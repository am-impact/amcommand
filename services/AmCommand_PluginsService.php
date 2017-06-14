<?php
namespace Craft;

class AmCommand_PluginsService extends BaseApplicationComponent
{
    /**
     * Get a list plugins with their settings URL.
     *
     * @return array
     */
    public function getSettingsUrl()
    {
        // Gather commands
        $commands = array();

        // Get plugins with their settings URL
        $enabledPlugins = craft()->plugins->getPlugins();
        if ($enabledPlugins) {
            foreach ($enabledPlugins as $enabledPlugin) {
                if ($enabledPlugin->hasSettings()) {
                    if (($settingUrl = $enabledPlugin->getSettingsUrl()) !== null) {
                        $commands[] = array(
                            'name' => $enabledPlugin->getName(),
                            'url'  => UrlHelper::getUrl($enabledPlugin->getSettingsUrl())
                        );
                    }
                    else {
                        $commands[] = array(
                            'name' => $enabledPlugin->getName(),
                            'url'  => UrlHelper::getUrl('settings/plugins/' . strtolower($enabledPlugin->getClassHandle()))
                        );
                    }
                }
            }
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('There are no enabled plugins with settings.'));
        }

        return $commands;
    }
}
