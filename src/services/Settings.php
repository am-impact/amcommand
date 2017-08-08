<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette\services;

use amimpact\commandpalette\CommandPalette;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;

class Settings extends Component
{
    /**
     * Get settings that you're able to add new things to.
     *
     * @return array
     */
    public function createNewSetting()
    {
        $commands = [
            [
                'name' => Craft::t('app', 'Create a new field'),
                'url'  => UrlHelper::cpUrl('settings/fields/new')
            ],
            [
                'name'    => Craft::t('app', 'Create a new field') . ' - ' . Craft::t('app', 'Group'),
                'more'    => true,
                'call'    => 'createFieldInGroup',
                'service' => 'settings'
            ],
            [
                'name' => Craft::t('app', 'Create a new section'),
                'url'  => UrlHelper::cpUrl('settings/sections/new')
            ],
            [
                'name' => Craft::t('app', 'Create a new global set'),
                'url'  => UrlHelper::cpUrl('settings/globals/new')
            ],
            [
                'name' => Craft::t('app', 'Create a new user group'),
                'url'  => UrlHelper::cpUrl('settings/users/groups/new')
            ],
            [
                'name' => Craft::t('app', 'Create a new category group'),
                'url'  => UrlHelper::cpUrl('settings/categories/new')
            ],
            [
                'name' => Craft::t('app', 'Create a new asset source'),
                'url'  => UrlHelper::cpUrl('settings/assets/sources/new')
            ],
            [
                'name' => Craft::t('app', 'Create a new image transform'),
                'url'  => UrlHelper::cpUrl('settings/assets/transforms/new')
            ]
        ];
        return $commands;
    }

    /**
     * Get Field Groups to add a field to.
     *
     * @return array
     */
    public function createFieldInGroup()
    {
        // Gather commands
        $commands = [];

        // Find available field groups
        $groups = Craft::$app->getFields()->getAllGroups();
        foreach ($groups as $group) {
            $commands[] = [
                'name' => $group->name,
                'url'  => UrlHelper::cpUrl('settings/fields/new?groupId=' . $group->id)
            ];
        }

        return $commands;
    }

    /**
     * Get fields to edit.
     *
     * @return array
     */
    public function editFields()
    {
        // Gather commands
        $commands = [];

        // Find fields
        $fields = Craft::$app->getFields()->getAllFields();
        if ($fields) {
            foreach ($fields as $field) {
                $commands[] = [
                    'name' => $field->name,
                    'url'  => UrlHelper::cpUrl('settings/fields/edit/' . $field->id),
                ];
            }
        }

        return $commands;
    }

    /**
     * Get sections to edit.
     *
     * @return array
     */
    public function editSections()
    {
        // Gather commands
        $commands = [];

        // Find available sections
        $sections = Craft::$app->getSections()->getAllSections();
        foreach ($sections as $section) {
            $commands[] = [
                'name' => $section->name,
                'url'  => UrlHelper::cpUrl('settings/sections/' . $section->id),
            ];
        }

        return $commands;
    }

    /**
     * Get section entry types to edit.
     *
     * @return array
     */
    public function editSectionEntryTypes()
    {
        // Gather commands
        $commands = [];

        // Find available sections
        $sections = Craft::$app->getSections()->getAllSections();
        foreach ($sections as $section) {
            $entryTypes      = $section->getEntryTypes();
            $totalEntryTypes = count($entryTypes);
            $sectionName     = $totalEntryTypes > 1 ? $section->name . ': ' : '';
            foreach ($entryTypes as $entryType) {
                $commands[] = [
                    'name' => $sectionName . $entryType->name,
                    'url'  => $entryType->getCpEditUrl()
                ];
            }
        }

        return $commands;
    }

    /**
     * Get Global Sets to edit.
     *
     * @return array
     */
    public function editGlobalSets()
    {
        // Gather commands
        $commands = [];

        // Find available global sets
        $globalSets = GlobalSet::find()->all();
        foreach ($globalSets as $globalSet) {
            $commands[] = [
                'name' => $globalSet->name,
                'url'  => UrlHelper::cpUrl('settings/globals/' . $globalSet->id)
            ];
        }
        if (! count($commands)) {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('app', 'No global sets exist yet.'));
        }

        return $commands;
    }
}
