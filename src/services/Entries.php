<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\models\Section;

class Entries extends Component
{
    /**
     * Get all available sections to add a new entry to.
     *
     * @return array
     */
    public function createEntry()
    {
        // Gather commands
        $commands = [];

        // Find available sections
        $availableSections = Craft::$app->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != Section::TYPE_SINGLE) {
                $commands[] = [
                    'name' => $section->name,
                    'url'  => UrlHelper::cpUrl('entries/' . $section->handle . '/new')
                ];
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
        $commands = [];

        // Find available sections
        $availableSections = Craft::$app->sections->getEditableSections();
        foreach ($availableSections as $section) {
            $type = ucfirst(Craft::t('app', ucfirst($section->type)));
            if ($section->type != Section::TYPE_SINGLE) {
                // Get total entries
                $totalEntries = Entry::find()
                    ->section($section->handle)
                    ->limit(null)
                    ->status(null)
                    ->count();

                // We have to get the entries for this section first
                $commands[] = [
                    'name'    => $type . ': ' . $section->name,
                    'info'    => Craft::t('command', 'Total entries in this section: {total}', ['total' => $totalEntries]),
                    'more'    => true,
                    'call'    => 'editEntry',
                    'service' => 'entries',
                    'vars'    => [
                        'sectionHandle' => $section->handle
                    ]
                ];
            }
            else {
                // Get the Single entry
                $entry = Entry::find()
                    ->section($section->handle)
                    ->limit(1)
                    ->status(null)
                    ->one();

                if ($entry) {
                    $commands[] = [
                        'name' => $type . ': ' . $section->name,
                        'url'  => $entry->getCpEditUrl()
                    ];
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
        $commands = [];

        // Find entries
        $entries = Entry::find()
            ->section($variables['sectionHandle'])
            ->limit(null)
            ->status(null)
            ->all();
        if (! $entries) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'No entries in this section exist yet.'));
        }
        else {
            foreach ($entries as $entry) {
                // Add command
                $commands[] = [
                    'name' => $entry->title,
                    'info' => Craft::t('app', 'URI') . ': ' . $entry->uri,
                    'url'  => $entry->getCpEditUrl()
                ];
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

        // Create new list of commands
        $commands = [];
        $availableSections = Craft::$app->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != Section::TYPE_SINGLE) {
                // Get total entries
                $totalEntries = Entry::find()
                    ->section($section->handle)
                    ->limit(null)
                    ->status(null)
                    ->count();

                // Only add the command if the section has any entries
                if ($totalEntries > 0) {
                    $commands[] = [
                        'name'    => $section->name,
                        'info'    => Craft::t('command', 'Total entries in this section: {total}', ['total' => $totalEntries]),
                        'warn'    => $deleteAll,
                        'more'    => !$deleteAll,
                        'call'    => 'deleteEntriesFromSection',
                        'service' => 'entries',
                        'vars'    => [
                            'sectionHandle' => $section->handle,
                            'deleteAll' => $deleteAll,
                        ]
                    ];
                }
            }
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'There are no entries within the available sections.'));
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
        if (! isset($variables['sectionHandle']) || ! isset($variables['deleteAll'])) {
            return false;
        }

        // Delete them all?
        $deleteAll = $variables['deleteAll'] == 'true';

        // Find entries
        $entries = Entry::find()
            ->section($variables['sectionHandle'])
            ->limit(null)
            ->status(null)
            ->all();

        // Delete all entries or one by one?
        if ($deleteAll) {
            // Delete all entries
            $success = false;
            foreach ($entries as $entry) {
                if (Craft::$app->elements->deleteElementById($entry->id)) {
                    $success = true;
                }
            }

            // Did we delete some?
            if ($success) {
                Command::$plugin->general->setReturnUrl(UrlHelper::cpUrl('entries/' . $variables['sectionHandle']));
                Command::$plugin->general->setReturnMessage(Craft::t('command', 'Entries deleted.'));
            }
            else {
                Command::$plugin->general->setReturnMessage(Craft::t('command', 'Couldn’t delete entries.'));
            }

            return $success;
        }

        // Return entries with the option to delete one
        $commands = [];
        foreach ($entries as $entry) {
            $commands[] = [
                'name'    => $entry->title,
                'info'    => Craft::t('app', 'URI') . ': ' . $entry->uri,
                'warn'    => true,
                'call'    => 'deleteEntry',
                'service' => 'entries',
                'vars'    => [
                    'entryId' => $entry->id
                ]
            ];
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'No entries in this section exist yet.'));
        }

        return $commands;
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
        $success = Craft::$app->elements->deleteElementById($variables['entryId']);
        if ($success) {
            Command::$plugin->general->deleteCurrentCommand();
            Command::$plugin->general->setReturnMessage(Craft::t('app', 'Entry deleted.'));
        }
        else {
            Command::$plugin->general->setReturnMessage(Craft::t('app', 'Couldn’t delete entry.'));
        }

        return $success;
    }
}
