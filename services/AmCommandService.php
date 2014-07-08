<?php
namespace Craft;

class AmCommandService extends BaseApplicationComponent
{
    /**
     * Get all available search options.
     *
     * @return array
     */
    public function getAvailableSearches()
    {
        $data = $this->_getSettings();
        $data = array_merge($data, $this->_getSections());
        return $data;
    }

    /**
     * Get useful settings.
     */
    private function _getSettings()
    {
        $settings = array(
            // Overview
            array(
                'type' => Craft::t('Settings'),
                'name' => Craft::t('Fields'),
                'url'  => UrlHelper::getUrl('settings/fields')
            ),
            array(
                'type' => Craft::t('Settings'),
                'name' => Craft::t('Globals'),
                'url'  => UrlHelper::getUrl('settings/globals')
            ),
            array(
                'type' => Craft::t('Settings'),
                'name' => Craft::t('Users'),
                'url'  => UrlHelper::getUrl('settings/users')
            ),
            array(
                'type' => Craft::t('Settings'),
                'name' => Craft::t('Sections'),
                'url'  => UrlHelper::getUrl('settings/sections')
            ),
            // New
            array(
                'type' => Craft::t('Settings'),
                'name' => Craft::t('New Field'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'type' => Craft::t('Settings'),
                'name' => Craft::t('New Section'),
                'url'  => UrlHelper::getUrl('settings/sections/new')
            )
        );
        return $settings;
    }

    /**
     * Get all available sections.
     */
    private function _getSections()
    {
        $sections = array();
        $availableSections = craft()->sections->getAllSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single) {
                $sections[] = array(
                    'type' => Craft::t('New Entry'),
                    'name' => $section->name,
                    'url'  => UrlHelper::getUrl('entries/' . $section->handle . '/new')
                );
            }
        }
        return $sections;
    }
}