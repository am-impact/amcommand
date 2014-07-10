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
        $data = array(
            Craft::t('Settings')  => $this->_getSettings(),
            Craft::t('New Entry') => $this->_getSections()
        );
        return $data;
    }

    /**
     * Get useful settings.
     *
     * @return array
     */
    private function _getSettings()
    {
        if (! craft()->userSession->isAdmin()) {
            return array();
        }
        $settings = array(
            // Overview
            Craft::t('Fields')      => UrlHelper::getUrl('settings/fields'),
            Craft::t('Globals')     => UrlHelper::getUrl('settings/globals'),
            Craft::t('Users')       => UrlHelper::getUrl('settings/users'),
            Craft::t('Sections')    => UrlHelper::getUrl('settings/sections'),
            // New
            Craft::t('New Field')   => UrlHelper::getUrl('settings/fields/new'),
            Craft::t('New Section') => UrlHelper::getUrl('settings/sections/new')
        );
        return $settings;
    }

    /**
     * Get all available sections.
     *
     * @return array
     */
    private function _getSections()
    {
        $sections = array();
        $availableSections = craft()->sections->getAllSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single && craft()->userSession->checkPermission('editEntries:'.$section->id)) {
                $sections[$section->name] = UrlHelper::getUrl('entries/' . $section->handle . '/new');
            }
        }
        return $sections;
    }
}