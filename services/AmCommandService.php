<?php
namespace Craft;

class AmCommandService extends BaseApplicationComponent
{
    private $_settings;
    private $_returnTitle;
    private $_returnMessage;
    private $_returnUrl;
    private $_returnUrlWindow;
    private $_returnAction;
    private $_returnCommands;
    private $_deleteCurrentCommand = false;
    private $_reverseSorting = false;
    private $_returnHtml = false;

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
        }
        else {
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
        }
        else {
            return $commandResult;
        }
    }

    /**
     * Set title that'll be returned after a command was executed.
     *
     * @param string $title
     */
    public function setReturnTitle($title)
    {
        $this->_returnTitle = $title;
    }

    /**
     * Get the return title.
     *
     * @return bool|string
     */
    public function getReturnTitle()
    {
        return isset($this->_returnTitle) ? $this->_returnTitle : false;
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
     * Set command that'll be returned after a command was executed.
     *
     * Note: You can use this to overwrite the normal command's result.
     *
     * @param array $commands
     */
    public function setReturnCommands($commands)
    {
        $this->_returnCommands = $commands;
    }

    /**
     * Get the return commands.
     *
     * @return bool|array
     */
    public function getReturnCommands()
    {
        return isset($this->_returnCommands) ? $this->_sortCommands($this->_returnCommands) : false;
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
     * Whether the palette should reverse the commands's sorting.
     *
     * @param bool $value
     */
    public function setReverseSorting($value)
    {
        $this->_reverseSorting = $value;
    }

    /**
     * Whether the palette will have HTML returned.
     *
     * @param bool $value
     */
    public function setReturnHtml($value)
    {
        $this->_returnHtml = $value;
    }

    /**
     * Get the return html.
     *
     * @return bool
     */
    public function getReturnHtml()
    {
        return is_bool($this->_returnHtml) ? $this->_returnHtml : false;
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
        $reverseSorting = $this->_reverseSorting;
        usort($commands, function($a, $b) use ($reverseSorting) {
            return $reverseSorting ? strnatcmp($b['name'], $a['name']) : strnatcmp($a['name'], $b['name']);
        });

        // Add necessary keys for fuzzy sort
        foreach ($commands as &$command) {
            if (! isset($command['info'])) {
                $command['info'] = '';
            }
        }

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
        $entryId = current(explode('-', craft()->request->getSegment(3)));
        if ($entrySegment && is_numeric($entryId)) {
            $entry = craft()->entries->getEntryById($entryId);
            if ($entry) {
                $entrySection = $entry->getSection();

                // We don't want to duplicate Single type entries
                if ($entrySection->type != SectionType::Single) {
                    $currentCommands[] = array(
                        'name'    => Craft::t('Content') . ': ' . Craft::t('Duplicate entry'),
                        'info'    => Craft::t('Duplicate the current entry.'),
                        'call'    => 'duplicateEntry',
                        'service' => 'amCommand_entries',
                        'vars'    => array(
                            'entryId' => $entryId,
                            'locale'  => craft()->request->getSegment(-1),
                        ),
                        'icon'    => array(
                            'type' => 'font',
                            'content' => 'section',
                        )
                    );
                }
                $currentCommands[] = array(
                    'name'    => Craft::t('Content') . ': ' . Craft::t('Compare entry version'),
                    'info'    => Craft::t('Compare the current entry with older versions.'),
                    'call'    => 'compareEntryVersion',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'entryId' => $entryId,
                        'locale'  => craft()->request->getSegment(-1),
                    ),
                    'icon'    => array(
                        'type' => 'font',
                        'content' => 'section',
                    )
                );
            }
        }

         // New, edit and delete commands
        if (craft()->userSession->isAdmin() || craft()->sections->getTotalEditableSections() > 0) {
            $currentCommands[] = array(
                'name'    => Craft::t('Content') . ': ' . Craft::t('New entry'),
                'info'    => Craft::t('Create a new entry in one of the available sections.'),
                'more'    => true,
                'call'    => 'newEntry',
                'service' => 'amCommand_entries',
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'section',
                )
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Content') . ': ' . Craft::t('Edit entries'),
                'info'    => Craft::t('Edit an entry in one of the available sections.'),
                'more'    => true,
                'call'    => 'editEntries',
                'service' => 'amCommand_entries',
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'section',
                )
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Content') . ': ' . Craft::t('Delete entries'),
                'info'    => Craft::t('Delete an entry in one of the available sections.'),
                'more'    => true,
                'call'    => 'deleteEntries',
                'service' => 'amCommand_entries',
                'vars'    => array(
                    'deleteAll' => false
                ),
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'section',
                )
            );
            if (craft()->userSession->isAdmin()) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Content') . ': ' . Craft::t('Delete all entries'),
                    'info'    => Craft::t('Delete all entries in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'deleteEntries',
                    'service' => 'amCommand_entries',
                    'vars'    => array(
                        'deleteAll' => true
                    ),
                    'icon'    => array(
                        'type' => 'font',
                        'content' => 'section',
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
        if (craft()->userSession->isAdmin() || craft()->globals->getTotalEditableSets() > 0) {
            $currentCommands[] = array(
                'name'    => Craft::t('Globals') . ': ' . Craft::t('Edit'),
                'more'    => true,
                'call'    => 'editGlobals',
                'service' => 'amCommand_globals',
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'globe',
                )
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
        $currentCommands[] = array(
            'name'    => Craft::t('Dashboard'),
            'url'     => UrlHelper::getUrl('dashboard'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'gauge',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Sign out'),
            'info' => Craft::t('End current session.'),
            'url'  => UrlHelper::getUrl('logout')
        );
        $currentCommands[] = array(
            'name' => Craft::t('My Account'),
            'url'  => UrlHelper::getUrl('myaccount')
        );
        if (craft()->userSession->isAdmin() || craft()->userSession->getUser()->can('editUsers')) {
            $currentCommands[] = array(
                'name'    => Craft::t('Users') . ': ' . Craft::t('New user'),
                'info'    => Craft::t('Create a user.'),
                'url'     => UrlHelper::getUrl('users/new'),
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'users',
                )
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Users') . ': ' . Craft::t('Edit users'),
                'info'    => Craft::t('Edit a user.'),
                'more'    => true,
                'call'    => 'editUser',
                'service' => 'amCommand_users',
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'users',
                )
            );
            $currentCommands[] = array(
                'name'    => Craft::t('Users') . ': ' . Craft::t('Delete users'),
                'info'    => Craft::t('Delete a user other than your own.'),
                'more'    => true,
                'call'    => 'deleteUser',
                'service' => 'amCommand_users',
                'icon'    => array(
                    'type' => 'font',
                    'content' => 'users',
                )
            );
            if (craft()->userSession->isAdmin()) {
                $currentCommands[] = array(
                    'name'    => Craft::t('Users') . ': ' . Craft::t('Login as user'),
                    'info'    => Craft::t('Log in as a different user, and navigate to their dashboard.'),
                    'more'    => true,
                    'call'    => 'loginUser',
                    'service' => 'amCommand_users',
                    'icon'    => array(
                        'type' => 'font',
                        'content' => 'users',
                    )
                );
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
        // Site searches
        $currentCommands[] = array(
            'name'    => Craft::t('Search on {option}', array('option' => 'Craft')),
            'info'    => 'https://craftcms.com',
            'more'    => true,
            'call'    => 'searchOptionCraft',
            'service' => 'amCommand_search',
            'icon'    => array(
                'type' => 'font',
                'content' => 'search',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Search on {option}', array('option' => 'StackExchange')),
            'info'    => 'http://craftcms.stackexchange.com',
            'more'    => true,
            'call'    => 'searchOptionStackExchange',
            'service' => 'amCommand_search',
            'icon'    => array(
                'type' => 'font',
                'content' => 'search',
            )
        );

        // Element searches
        if (is_array($this->_settings->elementSearchElementTypes)) {
            foreach ($this->_settings->elementSearchElementTypes as $elementType => $submittedInfo) {
                if (isset($submittedInfo['enabled']) && $submittedInfo['enabled'] === '1') {
                    $actualElementType = craft()->elements->getElementType($elementType);
                    $currentCommands[] = array(
                        'name'    => Craft::t('Search for {option}', array('option' => $actualElementType->getName())),
                        'more'    => true,
                        'call'    => 'searchOptionElementType',
                        'service' => 'amCommand_search',
                        'vars'    => array(
                            'elementType' => $elementType
                        ),
                        'icon'    => array(
                            'type' => 'font',
                            'content' => 'search',
                        )
                    );
                }
            }
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

        $currentCommands[] = array(
            'name'    => Craft::t('Tasks'),
            'info'    => Craft::t('Manage Craft tasks.'),
            'more'    => true,
            'call'    => 'taskCommands',
            'service' => 'amCommand_tasks',
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Tools'),
            'info'    => Craft::t('Use one of the most used tools.'),
            'more'    => true,
            'call'    => 'listTools',
            'service' => 'amCommand_tools',
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('New') . '...',
            'info'    => Craft::t('Add something new in the settings...'),
            'more'    => true,
            'call'    => 'createNewSetting',
            'service' => 'amCommand_settings',
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Fields'),
            'url'  => UrlHelper::getUrl('settings/fields'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'field',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('Fields') . ' - ' . Craft::t('Edit'),
            'more'    => true,
            'call'    => 'editFields',
            'service' => 'amCommand_settings',
            'icon'    => array(
                'type' => 'font',
                'content' => 'field',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('Fields') . ' - ' . Craft::t('Duplicate'),
            'more'    => true,
            'call'    => 'duplicateFields',
            'service' => 'amCommand_settings',
            'icon'    => array(
                'type' => 'font',
                'content' => 'field',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Sections'),
            'url'  => UrlHelper::getUrl('settings/sections'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'section',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('Sections') . ' - ' . Craft::t('Edit'),
            'more'    => true,
            'call'    => 'editSections',
            'service' => 'amCommand_settings',
            'icon'    => array(
                'type' => 'font',
                'content' => 'section',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('Sections') . ' - ' . Craft::t('Edit entry type'),
            'more'    => true,
            'call'    => 'editSectionEntryTypes',
            'service' => 'amCommand_settings',
            'icon'    => array(
                'type' => 'font',
                'content' => 'section',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Globals'),
            'url'  => UrlHelper::getUrl('settings/globals'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('Globals') . ' - ' . Craft::t('Global Sets'),
            'more'    => true,
            'call'    => 'editGlobalSets',
            'service' => 'amCommand_settings',
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Users'),
            'url'  => UrlHelper::getUrl('settings/users'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Routes'),
            'url'  => UrlHelper::getUrl('settings/routes'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'routes',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Categories'),
            'url'  => UrlHelper::getUrl('settings/categories'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Assets'),
            'url'  => UrlHelper::getUrl('settings/assets'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'settings',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Locales'),
            'url'  => UrlHelper::getUrl('settings/locales'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'language',
            )
        );
        $currentCommands[] = array(
            'name' => Craft::t('Settings') . ': ' . Craft::t('Plugins'),
            'url'  => UrlHelper::getUrl('settings/plugins'),
            'icon'    => array(
                'type' => 'font',
                'content' => 'plugin',
            )
        );
        $currentCommands[] = array(
            'name'    => Craft::t('Settings') . ': ' . Craft::t('Plugin settings'),
            'more'    => true,
            'call'    => 'getSettingsUrl',
            'service' => 'amCommand_plugins',
            'icon'    => array(
                'type' => 'font',
                'content' => 'plugin',
            )
        );

        return $currentCommands;
    }
}
