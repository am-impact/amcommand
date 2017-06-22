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
        return '2.0.2';
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
            'themes' => $this->_getThemes(),
            'elementSearchElementTypes' => $this->_getElementSearchElementTypes($settings),
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
            craft()->templates->includeJsResource('amcommand/js/fuzzy-min.js');
            craft()->templates->includeTranslations('Command executed', 'Are you sure you want to execute this command?', 'There are no more commands available.');

            // Load CSS
            craft()->templates->includeCssResource('amcommand/css/Command.css');
            $themeFile = $this->_getSelectedTheme($settings);
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
        );
    }

    /**
     * Get available themes.
     * @return type
     */
    private function _getThemes()
    {
        // Gather themes
        $themes = array();
        $path = craft()->path->getPluginsPath().'amcommand/resources/css/';
        if (IOHelper::folderExists($path)) {
            $themeFiles = IOHelper::getFolderContents($path, false, '\.css$');

            if (is_array($themeFiles)) {
                foreach ($themeFiles as $file) {
                    $fileName = IOHelper::getFileName($file);
                    if ($fileName == 'Command.css') {
                        continue; // Skip default
                    }
                    $themes[$fileName] = IOHelper::getFileName($file, false);
                }
            }
        }
        natsort($themes);

        return array_merge(array('' => Craft::t('Default')), $themes);
    }

    /**
     * Get selected theme.
     *
     * @param array $settings
     *
     * @return false|string
     */
    private function _getSelectedTheme($settings)
    {
        // Did we select one?
        if (empty($settings->theme) || $settings->theme == 'Command.css') {
            return false;
        }

        // Find theme
        $path = craft()->path->getPluginsPath().'amcommand/resources/css/';
        if (IOHelper::fileExists($path . $settings->theme)) {
            return $settings->theme;
        }

        return false;
    }

    /**
     * Get available element types that can be used for direct element searching.
     *
     * @param array $settings
     *
     * @return array
     */
    private function _getElementSearchElementTypes($settings)
    {
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

        return $elementSearchElementTypes;
    }
}
