<?php
namespace Craft;

class AmCommand_SettingsService extends BaseApplicationComponent
{
    /**
     * Get settings that you're able to add new things to.
     *
     * @return array
     */
    public function createNewSetting()
    {
        $commands = array(
            array(
                'name' => Craft::t('Create a new field'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'name'    => Craft::t('Create a new field') . ' - ' . Craft::t('Group'),
                'more'    => true,
                'call'    => 'createFieldInGroup',
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
     * Get sections to edit.
     *
     * @return array
     */
    public function editSections()
    {
        $commands = array();
        $sections = craft()->sections->getAllSections();
        foreach ($sections as $section) {
            $commands[] = array(
                'name' => $section->name,
                'url'  => UrlHelper::getCpUrl('settings/sections/' . $section->id),
            );
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
    public function editGlobalSets()
    {
        $commands = array();
        $criteria = craft()->elements->getCriteria(ElementType::GlobalSet);
        $globalSets = $criteria->find();
        if ($globalSets) {
            foreach ($globalSets as $globalSet) {
                $commands[] = array(
                    'name' => $globalSet->name,
                    'url'  => UrlHelper::getUrl('settings/globals/' . $globalSet->id)
                );
            }
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
    public function createFieldInGroup()
    {
        $commands = array();
        $groups = craft()->fields->getAllGroups();
        if ($groups) {
            foreach ($groups as $group) {
                $commands[] = array(
                    'name' => $group->name,
                    'url'  => UrlHelper::getUrl('settings/fields/new?groupId=' . $group->id)
                );
            }
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
        $commands = array();
        $fields = craft()->fields->getAllFields();
        if ($fields) {
            foreach ($fields as $field) {
                $commands[] = array(
                    'name' => $field->name,
                    'url'  => UrlHelper::getCpUrl('settings/fields/edit/' . $field->id),
                );
            }
        }
        return $commands;
    }

    /**
     * Get fields to duplicate.
     *
     * @return array
     */
    public function duplicateFields()
    {
        $commands = array();
        $fields = craft()->fields->getAllFields();
        if ($fields) {
            foreach ($fields as $field) {
                $commands[] = array(
                    'name'    => $field->name,
                    'info'    => $field->type,
                    'call'    => 'fieldDuplicator',
                    'service' => 'amCommand_settings',
                    'vars'    => array(
                        'step'        => 1,
                        'fieldName'   => $field->name,
                        'fieldHandle' => $field->handle,
                    )
                );
            }
        }
        return $commands;
    }

    /**
     * Start field duplicator.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function fieldDuplicator($variables = array())
    {
        if (! isset($variables['step'])) {
            return false;
        }

        switch ($variables['step']) {
            case 1:
                // Set action variables
                $title = Craft::t('Name of the duplicated field?');
                $searchText = $variables['fieldName'];
                $callback = 'fieldDuplicator';
                $service = 'amCommand_settings';
                $variables = array_merge($variables, array(
                    'step' => 2,
                ));

                // Set a&m command action
                craft()->amCommand->setReturnAction($title, $searchText, $callback, $service, $variables);
                break;

            case 2:
                // Get field name
                if (! isset($variables['searchText'])) {
                    return false;
                }
                elseif (empty($variables['searchText'])) {
                    craft()->amCommand->setReturnMessage(Craft::t('Name isn’t set.'));
                    return false;
                }

                // Does it already exist?
                $field = craft()->fields->getFieldByHandle(StringHelper::toCamelCase($variables['searchText']));
                if ($field) {
                    craft()->amCommand->setReturnMessage(Craft::t('Field already exists.'));
                    return false;
                }

                // Get selected field
                $field = craft()->fields->getFieldByHandle($variables['fieldHandle']);
                if (! $field) {
                    return false;
                }

                // Duplicate the field
                $duplicatedField = new FieldModel();
                $duplicatedField->groupId = $field->groupId;
                $duplicatedField->name = $variables['searchText'];
                $duplicatedField->handle = StringHelper::toCamelCase($variables['searchText']);
                $duplicatedField->instructions = $field->instructions;
                $duplicatedField->translatable = $field->translatable;
                $duplicatedField->type = $field->type;
                $duplicatedField->settings = $field->settings;

                // Save it as a new field!
                if (craft()->fields->saveField($duplicatedField)) {
                    craft()->amCommand->setReturnMessage(Craft::t('Field saved.'));
                }
                else {
                    return false;
                }

                break;
        }

        return true;
    }
}
