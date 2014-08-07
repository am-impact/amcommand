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
        // Add content commands
        $commands = $this->_getContentCommands($commands);
        // Add user commands
        $commands = $this->_getUserCommands($commands);
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
        // Duplicate entry command
        $entrySegment = craft()->request->getSegment(1) == 'entries';
        $entryId = craft()->request->getSegment(3);
        if (is_numeric($entryId)) {
            $currentCommands[] = array(
                'name'    => Craft::t('Duplicate entry'),
                'type'    => Craft::t('Content'),
                'warn'    => true,
                'info'    => Craft::t('Duplicate the current entry.'),
                'call'    => 'duplicateEntry',
                'service' => 'amCommand_entries',
                'data'    => array(
                    'entryId' => $entryId
                )
            );
        }

         // New, edit and delete commands
        if (craft()->sections->getTotalEditableSections() > 0) {
            $currentCommands[] = array(
                'name'    => Craft::t('New Entry'),
                'type'    => Craft::t('Content'),
                'info'    => Craft::t('Create a new entry in one of the available sections.'),
                'call'    => 'newEntry',
                'service' => 'amCommand_entries'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Edit entries'),
                'type'    => Craft::t('Content'),
                'info'    => Craft::t('Edit an entry in one of the available sections.'),
                'call'    => 'editEntries',
                'service' => 'amCommand_entries'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Delete entries'),
                'type'    => Craft::t('Content'),
                'info'    => Craft::t('Delete an entry in one of the available sections.'),
                'call'    => 'deleteEntries',
                'service' => 'amCommand_entries',
                'data'    => array(
                    'deleteAll' => false
                )
            );
            if (craft()->userSession->isAdmin()) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Delete all entries'),
                    'type'    => Craft::t('Content'),
                    'info'    => Craft::t('Delete all entries in one of the available sections.'),
                    'call'    => 'deleteEntries',
                    'service' => 'amCommand_entries',
                    'data'    => array(
                        'deleteAll' => true
                    )
                );
            }
        }
        return $currentCommands;
    }

    /**
     * Get useful user commands.
     *
     * @param array $currentCommands
     *
     * @return array
     */
    private function _getUserCommands($currentCommands)
    {
        if (craft()->userSession->isAdmin() || craft()->userSession->getUser()->can('editUsers')) {
            $currentCommands[] = array(
                'name'    => Craft::t('Edit users'),
                'type'    => Craft::t('Users'),
                'info'    => Craft::t('Edit an user.'),
                'call'    => 'editUser',
                'service' => 'amCommand_users'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Delete users'),
                'type'    => Craft::t('Users'),
                'info'    => Craft::t('Delete an user other than your own.'),
                'call'    => 'deleteUser',
                'service' => 'amCommand_users'
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
        $currentCommands[] = array(
            'name' => Craft::t('Assets'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/assets')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Locales'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/locales')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Plugins'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/plugins')
        );
        return $currentCommands;
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
                'type' => Craft::t('Fields'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'name' => Craft::t('New Section'),
                'type' => Craft::t('Sections'),
                'url'  => UrlHelper::getUrl('settings/sections/new')
            ),
            array(
                'name' => Craft::t('New Global Set'),
                'type' => Craft::t('Globals'),
                'url'  => UrlHelper::getUrl('settings/globals/new')
            ),
            array(
                'name' => Craft::t('New Group'),
                'type' => Craft::t('User Groups'),
                'url'  => UrlHelper::getUrl('settings/users/groups/new')
            ),
            array(
                'name' => Craft::t('New Category Group'),
                'type' => Craft::t('Categories'),
                'url'  => UrlHelper::getUrl('settings/categories/new')
            ),
            array(
                'name' => Craft::t('New Source'),
                'type' => Craft::t('Assets'),
                'url'  => UrlHelper::getUrl('settings/assets/sources/new')
            ),
            array(
                'name' => Craft::t('New Transform'),
                'type' => Craft::t('Image Transforms'),
                'url'  => UrlHelper::getUrl('settings/assets/transforms/new')
            )
        );
        return $commands;
    }
}