<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170614_141800_amCommand_defaultElementTypeSettings extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        // Settings
        $plugin = craft()->plugins->getPlugin('amcommand');
        $elementSearchElementTypes = array();
        $defaultEnabledElementTypes = array(
            ElementType::Category,
            ElementType::Entry,
            ElementType::User,
        );

        // Find supported element types for element search, based on all element types
        $elementTypes = craft()->elements->getAllElementTypes();
        foreach ($elementTypes as $elementType => $elementTypeInfo) {
            if (! isset($elementSearchElementTypes[$elementType])) {
                $elementSearchElementTypes[$elementType] = array(
                    'elementType' => $elementType,
                    'enabled' => in_array($elementType, $defaultEnabledElementTypes) ? '1' : '',
                );
            }
        }

        // Sort by element type
        ksort($elementSearchElementTypes);

        // Save settings!
        return craft()->plugins->savePluginSettings($plugin, array(
            'elementSearchElementTypes' => $elementSearchElementTypes
        ));
    }
}
