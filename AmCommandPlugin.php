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
         return 'Command';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/am-impact/amcommand/master/releases.json';
    }

    public function getVersion()
    {
        return '2.0.1';
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
        $elementSearchElementTypes = array();
        $defaultEnabledElementTypes = array(
            ElementType::Category,
            ElementType::Entry,
            ElementType::User,
        );

        // Find supported element types for element search, based on the settings
        if (is_array($settings->elementSearchElementTypes)) {
            foreach ($settings->elementSearchElementTypes as $elementType => $submittedInfo) {
                $elementSearchElementTypes[$elementType] = $submittedInfo;
            }
        }

        // Find supported element types for element search, based on all element types
        $elementTypes = craft()->elements->getAllElementTypes();
        foreach ($elementTypes as $elementType => $elementTypeInfo) {
            if (! isset($elementSearchElementTypes[$elementType])) {
                $elementSearchElementTypes[$elementType] = array(
                    'elementType' => $elementType,
                    'enabled' => in_array($elementType, $defaultEnabledElementTypes) ? 1 : 0,
                );
            }
        }

        // Sort by element type
        ksort($elementSearchElementTypes);

        return craft()->templates->render('amcommand/settings', array(
            'settings' => $settings,
            'elementSearchElementTypes' => $elementSearchElementTypes,
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
            $commands = craft()->amCommand->getCommands($this->getSettings());

            // Get the HTML
            $html = craft()->templates->render('amcommand/palette');
            craft()->templates->includeFootHtml($html);

            // Load javascript
            $js = sprintf('new Craft.AmCommand(%s);', $commands);
            craft()->templates->includeJs($js);
            craft()->templates->includeJsResource('amcommand/js/AmCommand.min.js');
            craft()->templates->includeJsResource('amcommand/js/fuzzy-min.js');
            craft()->templates->includeCssResource('amcommand/css/AmCommand.css');
            craft()->templates->includeTranslations('Command executed', 'Are you sure you want to execute this command?', 'There are no more commands available.');
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
            'elementSearchElementTypes' => array(AttributeType::Mixed),
        );
    }
}
