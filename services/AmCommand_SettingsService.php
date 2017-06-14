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
                'name'    => Craft::t('Create a new overview and detail section'),
                'call'    => 'overviewDetailSectionGenerator',
                'service' => 'amCommand_settings'
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
        // Gather commands
        $commands = array();

        // Find sections
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
        // Gather commands
        $commands = array();

        // Find sections
        $sections = craft()->sections->getAllSections();
        foreach ($sections as $section) {
            // Find the entry types
            $entryTypes = $section->getEntryTypes();
            $totalEntryTypes = count($entryTypes);
            $sectionName = $totalEntryTypes > 1 ? $section->name . ': ' : '';
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
        // Gather commands
        $commands = array();

        // Find global sets
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
        // Gather commands
        $commands = array();

        // Find field groups
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
        // Gather commands
        $commands = array();

        // Find fields
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
        // Gather commands
        $commands = array();

        // Find fields
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
        // Do we have the required information?
        if (! isset($variables['step'])) {
            return false;
        }

        // Which step are we at?
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

    /**
     * Start overview and detail section generator.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function overviewDetailSectionGenerator($variables = array())
    {
        // Do we have the required information?
        if (! isset($variables['step'])) {
            // Set action variables
            $title = Craft::t('Name of the overview section?');
            $searchText = '';
            $callback = 'overviewDetailSectionGenerator';
            $service = 'amCommand_settings';
            $variables = array(
                'step' => 1,
            );

            // Set a&m command action
            craft()->amCommand->setReturnAction($title, $searchText, $callback, $service, $variables);

            return true;
        }

        // Which step are we at?
        switch ($variables['step']) {
            case 1:
                // Get overview name
                if (! isset($variables['searchText'])) {
                    return false;
                }
                elseif (empty($variables['searchText'])) {
                    craft()->amCommand->setReturnMessage(Craft::t('Name isn’t set.'));
                    return false;
                }

                // Set action variables
                $title = Craft::t('Name of the detail section?');
                $searchText = '';
                $callback = 'overviewDetailSectionGenerator';
                $service = 'amCommand_settings';
                $variables = array_merge($variables, array(
                    'step'         => 2,
                    'overviewName' => $variables['searchText']
                ));

                // Set a&m command action
                craft()->amCommand->setReturnAction($title, $searchText, $callback, $service, $variables);
                break;

            case 2:
                // Get detail name
                if (! isset($variables['searchText'])) {
                    return false;
                }
                elseif (empty($variables['searchText'])) {
                    craft()->amCommand->setReturnMessage(Craft::t('Name isn’t set.'));
                    return false;
                }

                // Set commands
                $commands = array(
                    array(
                        'name'    => Craft::t('Channel'),
                        'call'    => 'overviewDetailSectionGenerator',
                        'service' => 'amCommand_settings',
                        'vars'    => array_merge($variables, array(
                            'step'       => 3,
                            'detailType' => SectionType::Channel,
                            'detailName' => $variables['searchText']
                        ))
                    ),
                    array(
                        'name'    => Craft::t('Structure'),
                        'call'    => 'overviewDetailSectionGenerator',
                        'service' => 'amCommand_settings',
                        'vars'    => array_merge($variables, array(
                            'step'       => 3,
                            'detailType' => SectionType::Structure,
                            'detailName' => $variables['searchText']
                        ))
                    )
                );
                craft()->amCommand->setReturnCommands($commands);
                craft()->amCommand->setReturnTitle(Craft::t('Type of the detail section?'));
                break;

            case 3:
                if ($this->_createSections($variables)) {
                    craft()->amCommand->setReturnMessage(Craft::t('Sections created.'));
                }
                break;
        }

        return true;
    }

    /**
     * Create the sections.
     *
     * @param array $variables
     *
     * @return bool
     */
    private function _createSections($variables)
    {
        // Do we have the required information?
        if (! isset($variables['overviewName']) || ! isset($variables['detailName']) || ! isset($variables['detailType'])) {
            return false;
        }
        $overviewName = $variables['overviewName'];
        $detailName = $variables['detailName'];

        // Get primary locale
        $primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();

        // Overview section
        $overviewSection = new SectionModel();
        $overviewSection->name = $overviewName;
        $overviewSection->handle = StringHelper::toCamelCase($overviewName);
        $overviewSection->type = SectionType::Single;
        $overviewSection->hasUrls = false;
        $overviewSection->template = StringHelper::toKebabCase($overviewName);
        $overviewSection->setLocales(array(
            $primaryLocaleId => new SectionLocaleModel(array(
                'locale'    => $primaryLocaleId,
                'urlFormat' => StringHelper::toKebabCase($overviewName),
            ))
        ));
        if (! craft()->sections->saveSection($overviewSection)) {
            return false;
        }

        // Detail section
        $detailSection = new SectionModel();
        $detailSection->type = $variables['detailType'];
        $detailSection->name = $detailName;
        $detailSection->handle = StringHelper::toCamelCase($detailName);
        $detailSection->hasUrls = true;
        $detailSection->template = StringHelper::toKebabCase($overviewName) . '/_entry';
        $detailSection->setLocales(array(
            $primaryLocaleId => new SectionLocaleModel(array(
                'locale'          => $primaryLocaleId,
                'urlFormat'       => StringHelper::toKebabCase($overviewName) . '/{slug}',
                'nestedUrlFormat' => ($variables['detailType'] == SectionType::Structure ? '{parent.uri}/{slug}' : null),
            ))
        ));
        if (! craft()->sections->saveSection($detailSection)) {
            return false;
        }

        // Make sure the templates directory exists
        $templatesPath = craft()->path->getSiteTemplatesPath() . $overviewSection->template . '/';
        IOHelper::ensureFolderExists($templatesPath);

        // Create templates
        $sampleFile = craft()->path->getPluginsPath() . 'amcommand/templates/_commands/overviewDetailSectionGenerator.twig';
        if (! IOHelper::fileExists($templatesPath . 'index.twig')) {
            IOHelper::copyFile($sampleFile, $templatesPath . 'index.twig');
        }
        if (! IOHelper::fileExists($templatesPath . '_entry.twig')) {
            IOHelper::copyFile($sampleFile, $templatesPath . '_entry.twig');
        }

        return true;
    }
}
