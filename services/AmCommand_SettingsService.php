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
                'name' => Craft::t('Create a new field'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'name'    => Craft::t('Create a new field') . ' - ' . Craft::t('Group'),
                'more'    => true,
                'call'    => 'newFieldInGroup',
                'service' => 'amCommand_settings'
            ),
            array(
                'name' => Craft::t('Create a new section'),
                'url'  => UrlHelper::getUrl('settings/sections/new')
            ),
            array(
                'name' => Craft::t('Create a new global set'),
                'url'  => UrlHelper::getUrl('settings/globals/new')
            ),
            array(
                'name' => Craft::t('Create a new user group'),
                'url'  => UrlHelper::getUrl('settings/users/groups/new')
            ),
            array(
                'name' => Craft::t('Create a new category group'),
                'url'  => UrlHelper::getUrl('settings/categories/new')
            ),
            array(
                'name' => Craft::t('Create a new asset source'),
                'url'  => UrlHelper::getUrl('settings/assets/sources/new')
            ),
            array(
                'name' => Craft::t('Create a new image transform'),
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

    /**
     * Get Global Sets to edit.
     *
     * @return array
     */
    public function globalSets()
    {
        $commands = array();
        $criteria = craft()->elements->getCriteria(ElementType::GlobalSet);
        $globalSets = $criteria->find();
        foreach ($globalSets as $globalSet) {
            $commands[] = array(
                'name' => $globalSet->name,
                'url'  => UrlHelper::getUrl('settings/globals/' . $globalSet->id)
            );
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('No global sets exist yet.'));
        }
        return $commands;
    }

    /**
     * Get Field Groups to add a field to.
     *
     * @return array
     */
    public function newFieldInGroup()
    {
        $commands = array();
        $groups = craft()->fields->getAllGroups();
        foreach ($groups as $group) {
            $commands[] = array(
                'name' => $group->name,
                'url'  => UrlHelper::getUrl('settings/fields/new?groupId=' . $group->id)
            );
        }
        return $commands;
    }
}
