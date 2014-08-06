<?php
namespace Craft;

class AmCommandService extends BaseApplicationComponent
{
    /**
     * Get all available commands.
     *
     * @return array
     */
    public function getCommands()
    {
        $commands = array();
        // Add Content commands
        $commands = $this->_getContentCommands($commands);
        // Add settings commands
        $commands = $this->_getSettingCommands($commands);
        // Add other plugin's commands
        $pluginCommands = craft()->plugins->call('addCommands');
        foreach ($pluginCommands as $pluginCommand) {
            foreach ($pluginCommand as $command) {
                $commands[] = $command;
            }
        }
        // Return the commands nicely sorted
        return $this->_sortCommands($commands);
    }

    /**
     * Trigger a command that was called through ajax.
     *
     * @param string $command Command as function name.
     * @param string $service [Optional] Which service should be called instead.
     * @param array  $data    [Optional] The optional data.
     *
     * @return mixed False on error otherwise an array with commands.
     */
    public function triggerCommand($command, $service, $data)
    {
        if ($service !== false) {
            if (! method_exists(craft()->$service, $command)) {
                return false;
            }
            $commandResult = craft()->$service->$command($data);
        } else {
            if (! method_exists($this, $command)) {
                return false;
            }
            $commandResult = $this->$command($data);
        }
        // Treat the result as a new list of commands
        if (is_array($commandResult)) {
            $newCommands = $this->_sortCommands($commandResult);
            return craft()->templates->render('amcommand/triggerCommand', array(
                'data' => $newCommands
            ));
        } else {
            return $commandResult;
        }
    }

    /**
     * Order the commands by type and name.
     *
     * @param array $commands
     *
     * @return array
     */
    private function _sortCommands($commands)
    {
        usort($commands, function($a, $b) {
            return strcmp($a['type'] . $a['name'], $b['type'] . $b['name']);
        });
        return $commands;
    }

    /**
     * Get useful content commands.
     *
     * @param array $currentCommands
     *
     * @return array
     */
    private function _getContentCommands($currentCommands)
    {
        if (craft()->sections->getTotalEditableSections() > 0) {
            $currentCommands[] = array(
                'name' => Craft::t('New Entry'),
                'type' => Craft::t('Content'),
                'info' => Craft::t('Create a new entry in one of the available sections.'),
                'call' => '_newEntry'
            );
            $currentCommands[] = array(
                'name' => Craft::t('Edit entries'),
                'type' => Craft::t('Content'),
                'info' => Craft::t('Edit an entry in one of the available sections.'),
                'call' => '_editEntries'
            );
            $currentCommands[] = array(
                'name' => Craft::t('Delete entries'),
                'type' => Craft::t('Content'),
                'info' => Craft::t('Delete all entries in one of the available sections.'),
                'call' => '_deleteEntries'
            );
        }
        return $currentCommands;
    }

    /**
     * Get useful settings.
     *
     * @param array $currentCommands
     *
     * @return array
     */
    private function _getSettingCommands($currentCommands)
    {
        if (! craft()->userSession->isAdmin()) {
            return array();
        }
        $currentCommands[] = array(
            'name' => Craft::t('New') . '...',
            'type' => Craft::t('Settings'),
            'info' => Craft::t('Add something new in the settings...'),
            'call' => '_newSettings'
        );
        $currentCommands[] = array(
            'name' => Craft::t('Fields'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/fields')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Sections'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/sections')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Globals'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/globals')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Users'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/users')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Routes'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/routes')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Categories'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/categories')
        );
        return $currentCommands;
    }

    /**
     * Get all available sections to add a new entry to.
     *
     * @return array
     */
    private function _newEntry()
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
    private function _editEntries()
    {
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            $commands[] = array(
                'name' => $section->name,
                'type' => Craft::t('Edit entries'),
                'call' => '_editEntry',
                'data' => array(
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
    private function _editEntry($data)
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
            $url = UrlHelper::getCpUrl('entries/' . $data['sectionHandle'] . '/' . $entry->id);

            if (craft()->isLocalized() && $entry->locale != craft()->language)
            {
                $url .= '/' . $entry->locale;
            }

            $commands[] = array(
                'name' => $entry->title,
                'type' => '',
                'url'  => $url
            );
        }
        return $commands;
    }

    /**
     * Get all available sections to delete all entries from.
     *
     * @return array
     */
    private function _deleteEntries()
    {
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single) {
                $criteria = craft()->elements->getCriteria(ElementType::Entry);
                $criteria->sectionId = $section->id;
                $criteria->limit = null;
                $totalEntries = $criteria->total();

                // Only add the command if the section has any entries
                if ($totalEntries > 0) {
                    $commands[] = array(
                        'name' => $section->name . ' (' . $totalEntries . ')',
                        'type' => Craft::t('Delete entries'),
                        'warn' => true,
                        'call' => '_deleteEntriesFromSection',
                        'data' => array(
                            'sectionId' => $section->id
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
     * @return bool
     */
    private function _deleteEntriesFromSection($data)
    {
        if (! isset($data['sectionId'])) {
            return false;
        }
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->sectionId = $data['sectionId'];
        $criteria->limit = null;
        $entries = $criteria->find();
        return craft()->entries->deleteEntry($entries);
    }

    /**
     * Get settings that you're able to add new things to.
     *
     * @return array
     */
    private function _newSettings()
    {
        $commands = array(
            array(
                'name' => Craft::t('New Field'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'name' => Craft::t('New Section'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/sections/new')
            ),
            array(
                'name' => Craft::t('New Global Set'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/globals/new')
            ),
            array(
                'name' => Craft::t('New Group'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/users/groups/new')
            ),
            array(
                'name' => Craft::t('New Category Group'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/categories/new')
            )
        );
        return $commands;
    }
}