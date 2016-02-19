<?php
namespace Craft;

class AmCommand_EntriesService extends BaseApplicationComponent
{
    /**
     * Get all available sections to add a new entry to.
     *
     * @return array
     */
    public function newEntry()
    {
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single) {
                $commands[] = array(
                    'name' => $section->name,
                    'url'  => UrlHelper::getUrl('entries/' . $section->handle . '/new')
                );
            }
        }
        return $commands;
    }

    /**
     * Get all available sections to edit an entry from.
     *
     * @return array
     */
    public function editEntries()
    {
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            $type = ucfirst(Craft::t(ucfirst($section->type)));
            if ($section->type != SectionType::Single) {
                // We have to get the entries for this section first
                $commands[] = array(
                    'name'    => $type . ': ' . $section->name,
                    'more'    => true,
                    'call'    => 'editEntry',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'sectionHandle' => $section->handle
                    )
                );
            } else {
                // Get the Single entry
                $criteria = craft()->elements->getCriteria(ElementType::Entry);
                $criteria->sectionId = $section->id;
                $criteria->limit = 1;
                $criteria->status = null;
                $criteria->locale = craft()->i18n->getPrimarySiteLocaleId();
                $entry = $criteria->first();

                if ($entry) {
                    $commands[] = array(
                        'name' => $type . ': ' . $section->name,
                        'url'  => $entry->getCpEditUrl()
                    );
                }
            }
        }
        return $commands;
    }

    /**
     * Get all available entries to edit from a section.
     *
     * @param array $variables
     *
     * @return array
     */
    public function editEntry($variables)
    {
        if (! isset($variables['sectionHandle'])) {
            return false;
        }

        // Gather commands
        $commands = array();

        // Find entries
        $criteria = array(
            'section' => $variables['sectionHandle'],
            'locale'  => craft()->language,
        );
        $entries = craft()->amCommand_elements->getElements(ElementType::Entry, $criteria);
        if (! $entries) {
            craft()->amCommand->setReturnMessage(Craft::t('No entries in this section exist yet.'));
        }
        else {
            foreach ($entries as $entry) {
                // Get CP edit URL
                $url = UrlHelper::getCpUrl('entries/'.$variables['sectionHandle'].'/'.$entry['id'].($entry['slug'] ? '-'.$entry['slug'] : ''));
                if (craft()->isLocalized()) {
                    $url .= '/'.craft()->language;
                }

                // Add command
                $commands[] = array(
                    'name' => $entry['title'],
                    'info' => Craft::t('URI') . ': ' . $entry['uri'],
                    'url'  => $url
                );
            }
        }
        return $commands;
    }

    /**
     * Get all available sections to delete all entries from.
     *
     * @param array $variables
     *
     * @return array
     */
    public function deleteEntries($variables)
    {
        if (! isset($variables['deleteAll'])) {
            return false;
        }
        // Do we want to delete all entries or just one?
        $deleteAll = $variables['deleteAll'] == 'true';
        // Create new list of commands
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single) {
                // Get total entries
                $criteria = array(
                    'sectionId' => $section->id,
                    'locale'    => craft()->language,
                );
                $totalEntries = craft()->amCommand_elements->getTotalElements(ElementType::Entry, $criteria);

                // Only add the command if the section has any entries
                if ($totalEntries > 0) {
                    $commands[] = array(
                        'name'    => $section->name . ' (' . $totalEntries . ')',
                        'warn'    => $deleteAll,
                        'more'    => !$deleteAll,
                        'call'    => 'deleteEntriesFromSection',
                        'service' => 'amCommand_entries',
                        'vars'    => array(
                            'sectionId' => $section->id,
                            'deleteAll' => $deleteAll
                        )
                    );
                }
            }
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('There are no entries within the available sections.'));
        }
        return $commands;
    }

    /**
     * Delete all entries from a section.
     *
     * @param array $variables
     *
     * @return bool|array
     */
    public function deleteEntriesFromSection($variables)
    {
        if (! isset($variables['sectionId']) || ! isset($variables['deleteAll'])) {
            return false;
        }
        // Delete them all?
        $deleteAll = $variables['deleteAll'] == 'true';

        // Find entries
        $criteria = array(
            'sectionId' => $variables['sectionId'],
            'locale'  => craft()->language,
        );
        $entries = craft()->amCommand_elements->getElements(ElementType::Entry, $criteria);

        // Delete all entries or one by one?
        if ($deleteAll) {
            // Gather entry IDs
            $entryIds = array();
            foreach ($entries as $entry) {
                $entryIds[] = $entry['id'];
            }

            // Delete all entries
            $result = craft()->elements->deleteElementById($entryIds);
            if ($result) {
                craft()->amCommand->setReturnMessage(Craft::t('Entries deleted.'));
            } else {
                craft()->amCommand->setReturnMessage(Craft::t('Couldn’t delete entries.'));
            }
            return $result;
        } else {
            // Return entries with the option to delete one
            $commands = array();
            foreach ($entries as $entry) {
                $commands[] = array(
                    'name'    => $entry['title'],
                    'info'    => Craft::t('URI') . ': ' . $entry['uri'],
                    'warn'    => true,
                    'call'    => 'deleteEntry',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'entryId' => $entry['id']
                    )
                );
            }
            if (! count($commands)) {
                craft()->amCommand->setReturnMessage(Craft::t('No entries in this section exist yet.'));
            }
            return $commands;
        }
    }

    /**
     * Delete an entry.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function deleteEntry($variables)
    {
        if (! isset($variables['entryId'])) {
            return false;
        }
        $entry  = craft()->entries->getEntryById($variables['entryId']);
        $result = craft()->entries->deleteEntry($entry);
        if ($result) {
            craft()->amCommand->deleteCurrentCommand();
            craft()->amCommand->setReturnMessage(Craft::t('Entry deleted.'));
        } else {
            craft()->amCommand->setReturnMessage(Craft::t('Couldn’t delete entry.'));
        }
        return $result;
    }

    /**
     * Get the duplicate entry action.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function duplicateEntry($variables)
    {
        if (! isset($variables['entryId'])) {
            return false;
        }
        $currentEntry = craft()->entries->getEntryById($variables['entryId']);
        if (is_null($currentEntry)) {
            return false;
        }
        $variables['locale'] = $currentEntry->locale;
        craft()->amCommand->setReturnAction(Craft::t('Title of new entry:'), $currentEntry->getContent()->title, 'duplicateAnEntry', 'amCommand_entries', $variables);
        return true;
    }

    /**
     * Duplicate an entry.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function duplicateAnEntry($variables)
    {
        if (! isset($variables['entryId']) || ! isset($variables['searchText'])) {
            return false;
        }
        elseif (empty($variables['searchText'])) {
            craft()->amCommand->setReturnMessage(Craft::t('Title isn’t set.'));
            return false;
        }
        $result = false;
        $duplicatePrimaryLocaleEntry = false;
        foreach (craft()->i18n->getSiteLocales() as $locale) {
            // Current entry based on locale
            $currentEntry = craft()->entries->getEntryById($variables['entryId'], $locale->getId());
            if (is_null($currentEntry)) {
                continue;
            }

            // We don't want to duplicate Single type entries
            $currentSection = $currentEntry->getSection();
            if ($currentSection->type == SectionType::Single) {
                return false;
            }

            // Current entry data
            $currentParent     = $currentEntry->getParent();
            $currentTitle      = $currentEntry->getContent()->title;
            $currentAttributes = $this->_getAttributesForModel($currentEntry);

            // Override title?
            if ($locale->id == $variables['locale']) {
                $currentTitle = $variables['searchText'];
            }

            // New entry
            $newEntry = new EntryModel();
            $newEntry->sectionId  = $currentEntry->sectionId;
            $newEntry->typeId     = $currentEntry->typeId;
            $newEntry->locale     = $currentEntry->locale;
            $newEntry->authorId   = $currentEntry->authorId;
            $newEntry->enabled    = $currentEntry->enabled;
            $newEntry->postDate   = $currentEntry->postDate;
            $newEntry->expiryDate = $currentEntry->expiryDate;
            if (! is_null($currentParent)) {
                $newEntry->parentId = $currentParent->id; // Structure type entry
            }

            // Set element ID, because we already have created the duplicated primary locale entry
            if ($duplicatePrimaryLocaleEntry !== false) {
                $newEntry->id = $duplicatePrimaryLocaleEntry->id;
            }

            // Set entry title and content
            $newEntry->getContent()->title = $currentTitle;
            $newEntry->getContent()->setAttributes($currentAttributes);

            // Save duplicate entry
            $result = craft()->entries->saveEntry($newEntry);

            // Remember element ID, because we don't want new entries for each locale...
            if ($result && $duplicatePrimaryLocaleEntry === false) {
                $duplicatePrimaryLocaleEntry = $newEntry;
            }
        }
        // Update other locales URIs since somehow the uri is the same as the primary locale
        if ($duplicatePrimaryLocaleEntry !== false) {
            craft()->elements->updateElementSlugAndUriInOtherLocales($duplicatePrimaryLocaleEntry);
        }
        // Return duplication result
        if ($result) {
            if ($duplicatePrimaryLocaleEntry !== false) {
                craft()->amCommand->setReturnUrl($duplicatePrimaryLocaleEntry->getCpEditUrl());
            }
            craft()->amCommand->setReturnMessage(Craft::t('Entry duplicated.'));
        } else {
            craft()->amCommand->setReturnMessage(Craft::t('Couldn’t duplicate entry.'));
        }
        return $result;
    }

    /**
     * Get attributes for a Model.
     *
     * @param EntryModel/MatrixBlockModel $model
     *
     * @return array
     */
    private function _getAttributesForModel($model)
    {
        $attributes = array();
        $content    = $model->getContent()->getAttributes();
        $fieldLayout = $model->getFieldLayout();
        foreach ($fieldLayout->getFields() as $fieldLayoutField) {
            $field = $fieldLayoutField->getField();
            if ($model->{$field->handle} instanceof ElementCriteriaModel) {
                if ($field->type == 'Matrix') {
                    $blocks = array();
                    foreach ($model->{$field->handle}->find() as $matrixBlock) {
                        // Create Matrix Block
                        $newMatrixBlock = new MatrixBlockModel();
                        $newMatrixBlock->fieldId = $matrixBlock->fieldId;
                        $newMatrixBlock->typeId  = $matrixBlock->typeId;
                        $newMatrixBlock->ownerId = null;
                        $newMatrixBlock->locale  = $model->locale;

                        // Set content
                        $blockData = $this->_getAttributesForModel($matrixBlock);
                        $newMatrixBlock->setContentFromPost($blockData);

                        // Add block to Matrix Field
                        $blocks[] = $newMatrixBlock;
                    }
                    $attributes[$field->handle] = $blocks;
                } else {
                    $attributes[$field->handle] = $model->{$field->handle}->ids();
                }
            }
            else if (isset($content[$field->handle])) {
                $attributes[$field->handle] = $content[$field->handle];
            }
        }
        return $attributes;
    }
}
