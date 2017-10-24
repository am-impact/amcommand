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
        // Gather commands
        $commands = array();

        // Only add sections where the current user can edit
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
        // Gather commands
        $commands = array();

        // Only add sections where the current user can edit
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            $type = ucfirst(Craft::t(ucfirst($section->type)));
            if ($section->type != SectionType::Single) {
                // Get total entries
                $criteria = array(
                    'sectionId' => $section->id,
                    'locale'    => craft()->language,
                );
                $totalEntries = craft()->amCommand_elements->getTotalElements(ElementType::Entry, $criteria);

                // We have to get the entries for this section first
                $commands[] = array(
                    'name'    => $type . ': ' . $section->name,
                    'info'    => Craft::t('Total entries in this section: {total}', array('total' => $totalEntries)),
                    'more'    => true,
                    'call'    => 'editEntry',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'sectionHandle' => $section->handle
                    )
                );
            }
            else {
                // Get the Single entry
                $criteria = craft()->elements->getCriteria(ElementType::Entry);
                $criteria->sectionId = $section->id;
                $criteria->limit = 1;
                $criteria->status = null;
                $criteria->locale = craft()->language;
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
        // Do we have the required information?
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
        // Do we have the required information?
        if (! isset($variables['deleteAll'])) {
            return false;
        }

        // Do we want to delete all entries or just one?
        $deleteAll = $variables['deleteAll'] == 'true';

        // Gather commands
        $commands = array();

        // Only add sections where the current user can edit
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
                        'name'    => $section->name,
                        'info'    => Craft::t('Total entries in this section: {total}', array('total' => $totalEntries)),
                        'warn'    => $deleteAll,
                        'more'    => !$deleteAll,
                        'call'    => 'deleteEntriesFromSection',
                        'service' => 'amCommand_entries',
                        'vars'    => array(
                            'sectionId'     => $section->id,
                            'sectionHandle' => $section->handle,
                            'deleteAll'     => $deleteAll
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
        // Do we have the required information?
        if (! isset($variables['sectionId']) || ! isset($variables['sectionHandle']) || ! isset($variables['deleteAll'])) {
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
                craft()->amCommand->setReturnUrl(UrlHelper::getCpUrl('entries/' . $variables['sectionHandle']));
                craft()->amCommand->setReturnMessage(Craft::t('Entries deleted.'));
            }
            else {
                craft()->amCommand->setReturnMessage(Craft::t('Couldn’t delete entries.'));
            }

            return $result;
        }
        else {
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
        // Do we have the required information?
        if (! isset($variables['entryId'])) {
            return false;
        }

        // Delete entry!
        $entry  = craft()->entries->getEntryById($variables['entryId']);
        $result = craft()->entries->deleteEntry($entry);
        if ($result) {
            craft()->amCommand->deleteCurrentCommand();
            craft()->amCommand->setReturnMessage(Craft::t('Entry deleted.'));
        }
        else {
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
        // Do we have the required information?
        if (! isset($variables['entryId'])) {
            return false;
        }

        // What locale are we getting?
        $locale = null;
        if (isset($variables['locale'])) {
            $localeParts = explode('-', $variables['locale']);
            if (count($localeParts) == 1) {
                $locale = $localeParts[0];
            }
        }

        // Get the entry
        $currentEntry = craft()->entries->getEntryById($variables['entryId'], $locale);
        if (is_null($currentEntry)) {
            return false;
        }

        // Ask new entry's title
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
        // Do we have the required information?
        if (! isset($variables['entryId']) || ! isset($variables['searchText'])) {
            return false;
        }
        elseif (empty($variables['searchText'])) {
            craft()->amCommand->setReturnMessage(Craft::t('Title isn’t set.'));
            return false;
        }

        // Start duplicating!
        $result = false;
        $duplicatePrimaryLocaleEntry = false;
        foreach (craft()->i18n->getSiteLocales() as $locale) {
            // Current entry based on locale
            $currentEntry = craft()->entries->getEntryById($variables['entryId'], $locale->getId());
            if (! $currentEntry) {
                continue;
            }

            // We don't want to duplicate Single type entries
            $currentSection = $currentEntry->getSection();
            if ($currentSection->type == SectionType::Single) {
                return false;
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

            // Add parent
            if ($currentEntry->getParent()) {
                $newEntry->parentId = $currentEntry->getParent()->id;
            }

            // Set element ID, because we already have created the duplicated primary locale entry
            if ($duplicatePrimaryLocaleEntry !== false) {
                $newEntry->id = $duplicatePrimaryLocaleEntry->id;
            }

            // Set entry title and content
            $newEntry->getContent()->title = ($locale->id == $variables['locale']) ? $variables['searchText'] : $currentEntry->getContent()->title;
            $newEntry->getContent()->setAttributes($this->_getContentFromElement($currentEntry, $locale->getId()));

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
        }
        else {
            craft()->amCommand->setReturnMessage(Craft::t('Couldn’t duplicate entry.'));
        }

        return $result;
    }

    /**
     * Get older versions of an entry.
     *
     * @param array $variables
     *
     * @return false|array
     */
    public function compareEntryVersion($variables)
    {
        // Do we have the required information?
        if (! isset($variables['entryId'])) {
            return false;
        }

        // What locale are we getting?
        $locale = null;
        if (isset($variables['locale'])) {
            $localeParts = explode('-', $variables['locale']);
            if (count($localeParts) == 1) {
                $locale = $localeParts[0];
            }
        }

        // Get the entry
        $currentEntry = craft()->entries->getEntryById($variables['entryId'], $locale);
        if (is_null($currentEntry)) {
            return false;
        }

        // Does this entry have versions?
        $versions = craft()->entryRevisions->getVersionsByEntryId($variables['entryId'], $locale);
        if (! $versions || ! count($versions)) {
            craft()->amCommand->setReturnMessage(Craft::t('There are no older versions of this entry.'));
            return false;
        }

        // Map up the versions
        $commands = array();
        foreach ($versions as $version) {
            $commands[] = array(
                'name' => Craft::t('Version {num}', array('num' => $version->num)),
                'info' => Craft::t('From {date} by {user}.', array(
                    'date' => $version->dateCreated->localeDate(),
                    'user' => $version->getCreator()->getFullName(),
                )),
                'call'    => 'compareVersion',
                'service' => 'amCommand_entries',
                'vars'    => array_merge($variables, array(
                    'locale' => $locale,
                    'versionId' => $version->versionId
                ))
            );
        }

        // Let the palette know we are reversing the sorting
        craft()->amCommand->setReverseSorting(true);

        return $commands;
    }

    /**
     * Compare an entry version.
     *
     * @param array $variables
     *
     * @return false|string
     */
    public function compareVersion($variables)
    {
        // Do we have the required information?
        if (! isset($variables['entryId']) || ! isset($variables['locale']) || ! isset($variables['versionId'])) {
            return false;
        }

        // Get the entry
        $currentEntry = craft()->entries->getEntryById($variables['entryId'], (! empty($variables['locale']) ? $variables['locale'] : null));
        if (is_null($currentEntry)) {
            return false;
        }

        // Get the version
        $olderVersion = craft()->entryRevisions->getVersionById($variables['versionId']);
        if (! $olderVersion) {
            return false;
        }

        // Let the palette know we are returning HTML
        craft()->amCommand->setReturnHtml(true);

        // Compare them!
        return craft()->templates->render('amcommand/_commands/compareEntryVersion', array(
            'currentEntry' => $currentEntry,
            'olderVersion' => $olderVersion,
            'currentEntryAttributes' => craft()->amCommand_elements->getElementModelAttributes($currentEntry),
            'olderVersionAttributes' => craft()->amCommand_elements->getElementModelAttributes($olderVersion)
        ));
    }

    /**
     * Get content from an element.
     *
     * @param object $element
     * @param string $toLocale
     *
     * @return array
     */
    private function _getContentFromElement($element, $toLocale)
    {
        // Gather attributes
        $attributes = array();

        // Get basic content attributes
        $content = $element->getContent()->getAttributes();

        // Gather attributes based on the element's available fields
        $fieldLayout = $element->getFieldLayout();
        foreach ($fieldLayout->getFields() as $fieldLayoutField) {
            $field = $fieldLayoutField->getField();
            if ($element->{$field->handle} instanceof ElementCriteriaModel) {
                if ($field->type == 'Matrix') {
                    // Matrix required new models rather than returning the existing ones
                    // If you don't, these Matrix models will be saved to a different entry / entry locale
                    $blocks = array();
                    foreach ($element->{$field->handle}->status(null)->limit(null)->find() as $matrixBlock) {
                        // Create Matrix Block
                        $newMatrixBlock = new MatrixBlockModel();
                        $newMatrixBlock->fieldId = $matrixBlock->fieldId;
                        $newMatrixBlock->typeId = $matrixBlock->typeId;
                        $newMatrixBlock->ownerId = null;
                        $newMatrixBlock->locale = $toLocale;

                        // Set content
                        $blockData = $this->_getContentFromElement($matrixBlock, $toLocale);
                        $newMatrixBlock->setContentFromPost($blockData);

                        // Add block to Matrix Field
                        $blocks[] = $newMatrixBlock;
                    }
                    $attributes[$field->handle] = $blocks;
                }
                else {
                    $attributes[$field->handle] = $element->{$field->handle}->status(null)->limit(null)->ids();
                }
            }
            else if (isset($content[$field->handle])) {
                $attributes[$field->handle] = $content[$field->handle];
            }
        }

        return $attributes;
    }
}
