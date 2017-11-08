<?php
/**
 * Command palette for Craft.
 *
 * @package   Am Command
 * @author    Hubert Prein
 */
namespace Craft;

class AmCommandPlugin extends BasePlugin
{
    public function getName()
    {
        $pluginNameOverride = $this->getSettings()->getAttribute('pluginNameOverride');
        return empty($pluginNameOverride) ? Craft::t('Command') : $pluginNameOverride;
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/am-impact/amcommand/master/releases.json';
    }

    public function getVersion()
    {
        return '2.2.0';
    }

    public function getSchemaVersion()
    {
        return '2.0.1';
    }

    public function getDeveloper()
    {
        return 'a&m impact';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    public function hasCpSection()
    {
        return true;
    }

    /**
     * Display command palette settings.
     */
    public function getSettingsHtml()
    {
        // Settings
        $settings = $this->getSettings();

        return craft()->templates->render('amcommand/settings', array(
            'settings' => $settings,
            'themes' => AmCommandModel::getThemes(),
            'elementSearchElementTypes' => AmCommandModel::getElementSearchElementTypes(),
        ));
    }

    /**
     * Load command palette.
     *
     * @return void
     */
    public function init()
    {
        // We only want to see the command palette in the backend
        // User has to be logged in (or it will also work on the login page)
        // Make sure we only run our code once on pages like Entries, by using: craft()->request->isAjaxRequest
        if (craft()->request->isCpRequest() && craft()->userSession->isLoggedIn() && ! craft()->request->isAjaxRequest()) {
            // Gather commands
            $settings = $this->getSettings();
            $commands = craft()->amCommand->getCommands($settings);

            // Get the HTML
            $html = craft()->templates->render('amcommand/palette');
            craft()->templates->includeFootHtml($html);

            // Load javascript
            $js = sprintf('new Craft.AmCommand(%s);', $commands);
            craft()->templates->includeJs($js);
            craft()->templates->includeJsResource('amcommand/js/AmCommand.min.js');
            craft()->templates->includeJsResource('amcommand/js/fuzzysort.min.js');
            craft()->templates->includeTranslations('Command executed', 'Are you sure you want to execute this command?', 'There are no more commands available.');

            // Load CSS
            craft()->templates->includeCssResource('amcommand/css/Command.css');
            $themeFile = AmCommandModel::getSelectedTheme();
            if ($themeFile) {
                craft()->templates->includeCssResource('amcommand/css/' . $themeFile);
            }
        }
    }

    /**
     * Plugin settings.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'theme'                     => array(AttributeType::String, 'default' => ''),
            'elementSearchElementTypes' => array(AttributeType::Mixed),
            'pluginNameOverride'        => AttributeType::String,
        );
    }
}
