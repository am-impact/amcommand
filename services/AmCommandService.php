<?php
namespace Craft;

class AmCommandService extends BaseApplicationComponent
{
    private $_settings;
    private $_returnMessage;
    private $_returnUrl;
    private $_returnAction;
    private $_deleteCurrentCommand = false;

    /**
     * Get all available commands.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getCommands($settings)
    {
        $this->_settings = $settings;

        $commands = array();
        // Add content commands
        $commands = $this->_getContentCommands($commands);
        // Add global commands
        $commands = $this->_getGlobalCommands($commands);
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
     * @param string $command   Command as function name.
     * @param string $service   [Optional] Which service should be called instead.
     * @param array  $variables [Optional] The optional variables.
     *
     * @return mixed False on error otherwise an array with commands.
     */
    public function triggerCommand($command, $service, $variables)
    {
        if ($service !== false) {
            if (! method_exists(craft()->$service, $command)) {
                return false;
            }
            $commandResult = craft()->$service->$command($variables);
        } else {
            if (! method_exists($this, $command)) {
                return false;
            }
            $commandResult = $this->$command($variables);
        }
        // Treat the result as a new list of commands
        if (is_array($commandResult)) {
            $newCommands = $this->_sortCommands($commandResult);
            return craft()->templates->render('amcommand/triggerCommand', array(
                'commands' => $newCommands
            ));
        } else {
            return $commandResult;
        }
    }

    /**
     * Set a message that'll be shown to the user after a command was executed.
     *
     * @param string $message
     */
    public function setReturnMessage($message)
    {
        $this->_returnMessage = $message;
    }

    /**
     * Get the return message.
     *
     * @return int|string
     */
    public function getReturnMessage()
    {
        return isset($this->_returnMessage) ? $this->_returnMessage : false;
    }

    /**
     * Set an URL to redirect to after executing a command.
     *
     * @param string $url
     */
    public function setReturnUrl($url)
    {
        $this->_returnUrl = $url;
    }

    /**
     * Get the return URL.
     *
     * @return int|string
     */
    public function getReturnUrl()
    {
        return isset($this->_returnUrl) ? $this->_returnUrl : false;
    }

    /**
     * Set an action that'll be returned after a command was executed.
     *
     * @param string $tabs       Text above the search field.
     * @param string $searchText Text in the search field.
     * @param string $callback   Which callback should be called.
     * @param string $service    In which service the callback is callable.
     * @param array  $variables  [Optional] Variables for the callback.
     */
    public function setReturnAction($tabs, $searchText, $callback, $service, $variables = array())
    {
        $this->_returnAction = array(
            'tabs'       => $tabs,
            'searchText' => $searchText,
            'call'       => $callback,
            'service'    => $service,
            'vars'       => $variables
        );
    }

    /**
     * Get the return action.
     *
     * @return int|array
     */
    public function getReturnAction()
    {
        return isset($this->_returnAction) ? $this->_returnAction : false;
    }

    /**
     * Delete the command when the result is returned.
     */
    public function deleteCurrentCommand()
    {
        $this->_deleteCurrentCommand = true;
    }

    /**
     * Get the current command delete status.
     *
     * @return bool
     */
    public function getDeleteStatus()
    {
        return $this->_deleteCurrentCommand;
    }

    /**
     * Check whether a command is enabled.
     *
     * @param string $command
     *
     * @return bool
     */
    private function _isEnabled($command)
    {
        return (bool)$this->_settings->$command;
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
            return strcmp((isset($a['type']) ? $a['type'] : '') . $a['name'], (isset($b['type']) ? $b['type'] : '') . $b['name']);
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
        if ($this->_isEnabled('duplicateEntry')) {
            $entrySegment = craft()->request->getSegment(1) == 'entries';
            $entryId = craft()->request->getSegment(3);
            if ($entrySegment && is_numeric($entryId)) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Duplicate entry'),
                    'type'    => Craft::t('Content'),
                    'info'    => Craft::t('Duplicate the current entry.'),
                    'call'    => 'duplicateEntry',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'entryId' => $entryId
                    )
                );
            }
        }

         // New, edit and delete commands
        $newEnabled = $this->_isEnabled('newEntry');
        $editEnabled = $this->_isEnabled('editEntries');
        $deleteEnabled = $this->_isEnabled('deleteEntries');
        $deleteAllEnabled = $this->_isEnabled('deleteAllEntries');
        if (($newEnabled || $editEnabled || $deleteEnabled || $deleteAllEnabled) && (craft()->userSession->isAdmin() || craft()->sections->getTotalEditableSections() > 0)) {
            if ($newEnabled) {
                $currentCommands[] = array(
                    'name'    => Craft::t('New Entry'),
                    'type'    => Craft::t('Content'),
                    'info'    => Craft::t('Create a new entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'newEntry',
                    'service' => 'amCommand_entries'
                );
            }
            if ($editEnabled) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Edit entries'),
                    'type'    => Craft::t('Content'),
                    'info'    => Craft::t('Edit an entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'editEntries',
                    'service' => 'amCommand_entries'
                );
            }
            if ($deleteEnabled) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Delete entries'),
                    'type'    => Craft::t('Content'),
                    'info'    => Craft::t('Delete an entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'deleteEntries',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'deleteAll' => false
                    )
                );
            }
            if ($deleteAllEnabled && craft()->userSession->isAdmin()) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Delete all entries'),
                    'type'    => Craft::t('Content'),
                    'info'    => Craft::t('Delete all entries in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'deleteEntries',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'deleteAll' => true
                    )
                );
            }
        }
        return $currentCommands;
    }

    /**
     * Get useful globals commands.
     *
     * @param array $currentCommands
     *
     * @return array
     */
    private function _getGlobalCommands($currentCommands)
    {
        if ($this->_isEnabled('editGlobals') && (craft()->userSession->isAdmin() || craft()->globals->getTotalEditableSets() > 0)) {
            $currentCommands[] = array(
                'name'    => Craft::t('Edit'),
                'type'    => Craft::t('Globals'),
                'more'    => true,
                'call'    => 'editGlobals',
                'service' => 'amCommand_globals'
            );
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
        if ($this->_isEnabled('userCommands') && (craft()->userSession->isAdmin() || craft()->userSession->getUser()->can('editUsers'))) {
            $currentCommands[] = array(
                'name'    => Craft::t('Administrate users'),
                'info'    => Craft::t('Create, edit or delete a user.'),
                'more'    => true,
                'call'    => 'userCommands',
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
            return $currentCommands;
        }
        if ($this->_isEnabled('tools')) {
            $currentCommands[] = array(
                'name'    => Craft::t('Tools'),
                'info'    => Craft::t('Use one of the most used tools.'),
                'more'    => true,
                'call'    => 'listTools',
                'service' => 'amCommand_tools'
            );
        }
        if ($this->_isEnabled('settings')) {
            $currentCommands[] = array(
                'name' => Craft::t('New') . '...',
                'type' => Craft::t('Settings'),
                'info' => Craft::t('Add something new in the settings...'),
                'more' => true,
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
        }
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