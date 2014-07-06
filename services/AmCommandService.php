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
        $data = array();
        $this->_getSettings($data);
        $this->_getSections($data);
        return $data;
    }

    /**
     * Get useful settings.
     */
    private function _getSettings(&$data)
    {
        $data[] = array(
            'type'   => Craft::t('Settings'),
            'name'   => Craft::t('Fields'),
            'url'    => 'settings/fields'
        );
        $data[] = array(
            'type'   => Craft::t('Settings'),
            'name'   => Craft::t('Sections'),
            'url'    => 'settings/sections'
        );
    }

    /**
     * Get all available sections.
     */
    private function _getSections(&$data)
    {
        $availableSections = craft()->sections->getAllSections();
        foreach ($availableSections as $section) {
            if ($section->type != 'single') {
                $data[] = array(
                    'type'   => Craft::t('New entry'),
                    'name'   => $section->name,
                    'url'    => 'entries/' . $section->handle . '/new'
                );
            }
        }
    }
}