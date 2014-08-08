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
                    'type' => Craft::t('New Entry'),
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
            $commands[] = array(
                'name'    => $section->name,
                'type'    => Craft::t('Edit entries'),
                'call'    => 'editEntry',
                'service' => 'amCommand_entries',
                'data'    => array(
                    'sectionHandle' => $section->handle
                )
            );
        }
        return $commands;
    }

    /**
     * Get all available entries to edit from a section.
     *
     * @param array $data
     *
     * @return array
     */
    public function editEntry($data)
    {
        if (! isset($data['sectionHandle'])) {
            return false;
        }
        $commands = array();
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $data['sectionHandle'];
        $criteria->limit = null;
        $entries = $criteria->find();
        foreach ($entries as $entry) {
            $commands[] = array(
                'name' => $entry->title,
                'type' => Craft::t('Edit'),
                'url'  => $entry->getCpEditUrl()
            );
        }
        return $commands;
    }

    /**
     * Get all available sections to delete all entries from.
     *
     * @param array $data
     *
     * @return array
     */
    public function deleteEntries($data)
    {
        if (! isset($data['deleteAll'])) {
            return false;
        }
        // Do we want to delete all entries or just one?
        $deleteAll = $data['deleteAll'] == 'true';
        $type = $deleteAll ? Craft::t('Delete all entries') : Craft::t('Delete entries');
        // Create new list of commands
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single) {
                // Get the total entries number
                $criteria = craft()->elements->getCriteria(ElementType::Entry);
                $criteria->sectionId = $section->id;
                $criteria->limit = null;
                $totalEntries = $criteria->total();

                // Only add the command if the section has any entries
                if ($totalEntries > 0) {
                    $commands[] = array(
                        'name'    => $section->name . ' (' . $totalEntries . ')',
                        'type'    => $type,
                        'warn'    => $deleteAll,
                        'call'    => 'deleteEntriesFromSection',
                        'service' => 'amCommand_entries',
                        'data'    => array(
                            'sectionId' => $section->id,
                            'deleteAll' => $deleteAll
                        )
                    );
                }
            }
        }
        return $commands;
    }

    /**
     * Delete all entries from a section.
     *
     * @param array $data
     *
     * @return bool|array
     */
    public function deleteEntriesFromSection($data)
    {
        if (! isset($data['sectionId']) || ! isset($data['deleteAll'])) {
            return false;
        }
        $deleteAll = $data['deleteAll'] == 'true';
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->sectionId = $data['sectionId'];
        $criteria->limit = null;
        $entries = $criteria->find();
        if ($deleteAll) {
            // Delete all entries
            return craft()->entries->deleteEntry($entries);
        } else {
            // Return entries with the option to delete one
            $commands = array();
            foreach ($entries as $entry) {
                $commands[] = array(
                    'name'    => $entry->title,
                    'type'    => Craft::t('Delete'),
                    'warn'    => true,
                    'call'    => 'deleteEntry',
                    'service' => 'amCommand_entries',
                    'data'    => array(
                        'entryId' => $entry->id
                    )
                );
            }
            return $commands;
        }
    }

    /**
     * Delete an entry.
     *
     * @param array $data
     *
     * @return bool
     */
    public function deleteEntry($data)
    {
        if (! isset($data['entryId'])) {
            return false;
        }
        $entry = craft()->entries->getEntryById($data['entryId']);
        return craft()->entries->deleteEntry($entry);
    }

    /**
     * Duplicate an entry.
     *
     * @param array $data
     *
     * @return bool
     */
    public function duplicateEntry($data)
    {
        if (! isset($data['entryId'])) {
            return false;
        }
        $result = false;
        $duplicatePrimaryLocaleEntry = false;
        foreach (craft()->i18n->getSiteLocales() as $locale) {
            // Current entry based on locale
            $currentEntry = craft()->entries->getEntryById($data['entryId'], $locale->getId());
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
            $currentContent    = $currentEntry->getContent()->getAttributes();
            $currentAttributes = array();

            // Set current attributes; Because we don't want to just copy all attributes like the id and elementId
            $fieldLayout = $currentEntry->getFieldLayout();
            foreach ($fieldLayout->getFields() as $fieldLayoutField) {
                $field = $fieldLayoutField->getField();
                if ($currentEntry->{$field->handle} instanceof ElementCriteriaModel) {
                    $currentAttributes[$field->handle] = $currentEntry->{$field->handle}->ids();
                }
                else if (isset($currentContent[$field->handle])) {
                    $currentAttributes[$field->handle] = $currentContent[$field->handle];
                }
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
        return $result;
    }
}