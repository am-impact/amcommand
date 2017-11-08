<?php
namespace Craft;

class AmCommandModel extends BaseModel
{
    private static $settings;

    /**
     * Get plugin settings.
     *
     * @return array
     */
    public static function getSettings()
    {
        if (! self::$settings) {
            $plugin = craft()->plugins->getPlugin('amcommand');
            self::$settings = $plugin->getSettings();
        }

        return self::$settings;
    }

    /**
     * Get available themes.
     *
     * @return array
     */
    public static function getThemes()
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
     * @return false|string
     */
    public static function getSelectedTheme()
    {
        // Did we select one?
        if (empty(self::getSettings()->theme) || self::getSettings()->theme == 'Command.css') {
            return false;
        }

        // Find theme
        $path = craft()->path->getPluginsPath().'amcommand/resources/css/';
        if (IOHelper::fileExists($path . self::getSettings()->theme)) {
            return self::getSettings()->theme;
        }

        return false;
    }

    /**
     * Get available element types that can be used for direct element searching.
     *
     * @return array
     */
    public static function getElementSearchElementTypes()
    {
        // Gather element types
        $elementSearchElementTypes = array();
        $defaultEnabledElementTypes = array(
            ElementType::Category,
            ElementType::Entry,
            ElementType::User,
        );

        // Find supported element types for element search, based on the settings
        if (is_array(self::getSettings()->elementSearchElementTypes)) {
            foreach (self::getSettings()->elementSearchElementTypes as $elementType => $submittedInfo) {
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
