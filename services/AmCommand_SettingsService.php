<?php
namespace Craft;

class AmCommand_SettingsService extends BaseApplicationComponent
{
    /**
     * Get settings that you're able to add new things to.
     *
     * @return array
     */
    public function newSettings()
    {
        $commands = array(
            array(
                'name' => Craft::t('Fields') . ': ' . Craft::t('New Field'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'name' => Craft::t('Sections') . ': ' . Craft::t('New Section'),
                'url'  => UrlHelper::getUrl('settings/sections/new')
            ),
            array(
                'name' => Craft::t('Globals') . ': ' . Craft::t('New Global Set'),
                'url'  => UrlHelper::getUrl('settings/globals/new')
            ),
            array(
                'name' => Craft::t('User Groups') . ': ' . Craft::t('New Group'),
                'url'  => UrlHelper::getUrl('settings/users/groups/new')
            ),
            array(
                'name' => Craft::t('Categories') . ': ' . Craft::t('New Category Group'),
                'url'  => UrlHelper::getUrl('settings/categories/new')
            ),
            array(
                'name' => Craft::t('Assets') . ': ' . Craft::t('New Source'),
                'url'  => UrlHelper::getUrl('settings/assets/sources/new')
            ),
            array(
                'name' => Craft::t('Image Transforms') . ': ' . Craft::t('New Transform'),
                'url'  => UrlHelper::getUrl('settings/assets/transforms/new')
            )
        );
        return $commands;
    }

    /**
     * Get section entry types to edit.
     *
     * @return array
     */
    public function sectionEntryTypes()
    {
        $commands = array();
        $sections = craft()->sections->getAllSections();
        foreach ($sections as $section) {
            $entryTypes      = $section->getEntryTypes();
            $totalEntryTypes = count($entryTypes);
            $sectionName     = $totalEntryTypes > 1 ? $section->name . ': ' : '';
            foreach ($entryTypes as $entryType) {
                $commands[] = array(
                    'name' => $sectionName . $entryType->name,
                    'url'  => $entryType->getCpEditUrl()
                );
            }
        }
        return $commands;
    }
}