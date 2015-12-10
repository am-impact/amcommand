<?php
namespace Craft;

class AmCommandService extends BaseApplicationComponent
{
    private $_settings;
    private $_returnMessage;
    private $_returnUrl;
    private $_returnUrlWindow;
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
        // Add search commands
        $commands = $this->_getSearchCommands($commands);
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
        return json_encode($this->_sortCommands($commands));
    }

    /**
     * Trigger a command that was called through ajax.
     *
     * @param string $command   Command as function name.
     * @param string $service   [Optional] Which service should be called instead.
     * @param array  $variables [Optional] The optional variables.
     *
     * @return mixed False on error otherwise JSON with commands.
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
            if ($service == 'amCommand_search') {
                return $commandResult;
            }
            return $this->_sortCommands($commandResult);
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
     * @param bool   $newWindow [Optional] Whether to redirect in a new window.
     */
    public function setReturnUrl($url, $newWindow = false)
    {
        $this->_returnUrl = $url;
        $this->_returnUrlWindow = $newWindow;
    }

    /**
     * Get the return URL.
     *
     * @return int|string
     */
    public function getReturnUrl()
    {
        return isset($this->_returnUrl) ? array('url' => $this->_returnUrl, 'newWindow' => $this->_returnUrlWindow) : false;
    }

    /**
     * Set an action that'll be returned after a command was executed.
     *
     * @param string $tabs         Text above the search field.
     * @param string $searchText   Text in the search field.
     * @param string $callback     Which callback should be called.
     * @param string $service      In which service the callback is callable.
     * @param array  $variables    [Optional] Variables for the callback.
     * @param bool   $asynchronous [Optional] Whether the action should be submitted asynchronously.
     * @param bool   $realtime     [Optional] Whether this action should be submitted while the user types in the search field.
     */
    public function setReturnAction($tabs, $searchText, $callback, $service, $variables = array(), $asynchronous = true, $realtime = false)
    {
        $this->_returnAction = array(
            'tabs'       => $tabs,
            'searchText' => $searchText,
            'call'       => $callback,
            'service'    => $service,
            'vars'       => $variables,
            'async'      => $asynchronous,
            'realtime'   => $realtime
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
     * Order the commands by name.
     *
     * @param array $commands
     *
     * @return array
     */
    private function _sortCommands($commands)
    {
        usort($commands, function($a, $b) {
            return strcmp($a['name'], $b['name']);
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
            $entryId = current(explode('-', craft()->request->getSegment(3)));
            if ($entrySegment && is_numeric($entryId)) {
                // We don't want to duplicate Single type entries
                $entry = craft()->entries->getEntryById($entryId);
                if ($entry) {
                    $entrySection = $entry->getSection();
                    if ($entrySection->type != SectionType::Single) {
                        $currentCommands[] = array(
                            'name'    => Craft::t('Content') . ': ' . Craft::t('Duplicate entry'),
                            'info'    => Craft::t('Duplicate the current entry.'),
                            'call'    => 'duplicateEntry',
                            'service' => 'amCommand_entries',
                            'vars'    => array(
                                'entryId' => $entryId
                            )
                        );
                    }
                }
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
                    'name'    => Craft::t('Content') . ': ' . Craft::t('New entry'),
                    'info'    => Craft::t('Create a new entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'newEntry',
                    'service' => 'amCommand_entries'
                );
            }
            if ($editEnabled) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Content') . ': ' . Craft::t('Edit entries'),
                    'info'    => Craft::t('Edit an entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'editEntries',
                    'service' => 'amCommand_entries'
                );
            }
            if ($deleteEnabled) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Content') . ': ' . Craft::t('Delete entries'),
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
                    'name'    => Craft::t('Content') . ': ' . Craft::t('Delete all entries'),
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
                'name'    => Craft::t('Globals') . ': ' . Craft::t('Edit'),
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
        if ($this->_isEnabled('userCommands')) {
            $currentCommands[] = array(
                'name'    => Craft::t('Dashboard'),
                'url'     => UrlHelper::getUrl('dashboard')
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Users') . ': ' . Craft::t('Sign out'),
                'info'    => Craft::t('End current session.'),
                'url'     => UrlHelper::getUrl('logout')
            );
            if (craft()->userSession->isAdmin() || craft()->userSession->getUser()->can('editUsers')) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Users') . ': ' . Craft::t('New user'),
                    'info'    => Craft::t('Create a user.'),
                    'url'     => UrlHelper::getUrl('users/new')
                );
                $currentCommands[] = array(
                    'name'    => Craft::t('Users') . ': ' . Craft::t('Edit users'),
                    'info'    => Craft::t('Edit a user.'),
                    'more'    => true,
                    'call'    => 'editUser',
                    'service' => 'amCommand_users'
                );
                $currentCommands[] = array(
                    'name'    => Craft::t('Users') . ': ' . Craft::t('Delete users'),
                    'info'    => Craft::t('Delete a user other than your own.'),
                    'more'    => true,
                    'call'    => 'deleteUser',
                    'service' => 'amCommand_users'
                );
                if (craft()->userSession->isAdmin()) {
                    $currentCommands[] = array(
                        'name'    => Craft::t('Users') . ': ' . Craft::t('Login as user'),
                        'info'    => Craft::t('Log in as a different user, and navigate to their dashboard.'),
                        'more'    => true,
                        'call'    => 'loginUser',
                        'service' => 'amCommand_users'
                    );
                }
            }
        }
        return $currentCommands;
    }

    /**
     * Get useful search commands.
     *
     * @param array $currentCommands
     *
     * @return array
     */
    private function _getSearchCommands($currentCommands)
    {
        if ($this->_isEnabled('searchCommands')) {
            $currentCommands[] = array(
                'name'    => Craft::t('Search on {option}', array('option' => 'Craft')),
                'info'    => 'https://craftcms.com',
                'more'    => true,
                'call'    => 'searchOptionCraft',
                'service' => 'amCommand_search'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Search on {option}', array('option' => 'StackExchange')),
                'info'    => 'http://craftcms.stackexchange.com',
                'more'    => true,
                'call'    => 'searchOptionStackExchange',
                'service' => 'amCommand_search'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Search for {option}', array('option' => Craft::t('Categories'))),
                'more'    => true,
                'call'    => 'searchOptionCategories',
                'service' => 'amCommand_search'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Search for {option}', array('option' => Craft::t('Entries'))),
                'more'    => true,
                'call'    => 'searchOptionEntries',
                'service' => 'amCommand_search'
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Search for {option}', array('option' => Craft::t('Users'))),
                'more'    => true,
                'call'    => 'searchOptionUsers',
                'service' => 'amCommand_search'
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
                'name'    => Craft::t('Settings') . ': ' . Craft::t('New') . '...',
                'info'    => Craft::t('Add something new in the settings...'),
                'more'    => true,
                'call'    => 'newSettings',
                'service' => 'amCommand_settings'
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Fields'),
                'url'  => UrlHelper::getUrl('settings/fields')
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Sections'),
                'url'  => UrlHelper::getUrl('settings/sections')
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Settings') . ': ' . Craft::t('Sections') . ' - ' . Craft::t('Edit entry type'),
                'more'    => true,
                'call'    => 'sectionEntryTypes',
                'service' => 'amCommand_settings'
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Globals'),
                'url'  => UrlHelper::getUrl('settings/globals')
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Settings') . ': ' . Craft::t('Globals') . ' - ' . Craft::t('Global Sets'),
                'more'    => true,
                'call'    => 'globalSets',
                'service' => 'amCommand_settings'
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Users'),
                'url'  => UrlHelper::getUrl('settings/users')
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Routes'),
                'url'  => UrlHelper::getUrl('settings/routes')
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Categories'),
                'url'  => UrlHelper::getUrl('settings/categories')
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Assets'),
                'url'  => UrlHelper::getUrl('settings/assets')
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Locales'),
                'url'  => UrlHelper::getUrl('settings/locales')
            );
            $currentCommands[] = array(
                'name' => Craft::t('Settings') . ': ' . Craft::t('Plugins'),
                'url'  => UrlHelper::getUrl('settings/plugins')
            );
        }
        return $currentCommands;
    }
}
