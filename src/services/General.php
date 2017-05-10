<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;

class General extends Component
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

        $commands = [];
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
        // TODO:
        // $pluginCommands = $namespace::getInstance()->plugins->call('addCommands');
        // foreach ($pluginCommands as $pluginCommand) {
        //     foreach ($pluginCommand as $command) {
        //         $commands[] = $command;
        //     }
        // }
        // Return the commands nicely sorted
        return json_encode($this->_sortCommands($commands));
    }

    /**
     * Trigger a command that was called through ajax.
     *
     * @param string $command      Command as function name.
     * @param string $pluginHandle [Optional] Which plugin should be used.
     * @param string $service      [Optional] Which service should be called instead.
     * @param array  $variables    [Optional] The optional variables.
     *
     * @return mixed False on error otherwise JSON with commands.
     */
    public function triggerCommand($command, $pluginHandle, $service, $variables)
    {
        // Trigger a callback?
        if ($pluginHandle !== false && $pluginHandle !== 'false' && $service !== false && $pluginHandle != 'command') {
            // Trigger a plugin's callback
            $actualPlugin = Craft::$app->plugins->getPlugin($pluginHandle);
            if (! $actualPlugin) {
                return false;
            }
            elseif (! method_exists($actualPlugin->$service, $command)) {
                return false;
            }
            $commandResult = $actualPlugin->$service->$command($variables);
        }
        elseif ($service !== false) {
            // Trigger a service from our plugin
            if (! method_exists(Command::$plugin->$service, $command)) {
                return false;
            }
            $commandResult = Command::$plugin->$service->$command($variables);
        }
        else {
            // Command should be here
            if (! method_exists($this, $command)) {
                return false;
            }
            $commandResult = $this->$command($variables);
        }

        // Treat the result as a new list of commands
        if (is_array($commandResult)) {
            if ($service == 'search') {
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
        return isset($this->_returnUrl) ? ['url' => $this->_returnUrl, 'newWindow' => $this->_returnUrlWindow] : false;
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
     *
     * @return bool
     */
    public function setReturnAction($tabs, $searchText, $callback, $service, $variables = [], $asynchronous = true, $realtime = false)
    {
        $this->_returnAction = [
            'tabs'       => $tabs,
            'searchText' => $searchText,
            'call'       => $callback,
            'service'    => $service,
            'vars'       => $variables,
            'async'      => $asynchronous,
            'realtime'   => $realtime
        ];

        return true;
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
     * Check whether a command is enabled.
     *
     * @param string $command
     *
     * @return bool
     */
    private function _isEnabled($command)
    {
        return (bool) $this->_settings->$command;
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
        // New, edit and delete commands
        $newEnabled = $this->_isEnabled('newEntry');
        $editEnabled = $this->_isEnabled('editEntries');
        $deleteEnabled = $this->_isEnabled('deleteEntries');
        $deleteAllEnabled = $this->_isEnabled('deleteAllEntries');
        if (($newEnabled || $editEnabled || $deleteEnabled || $deleteAllEnabled) && (Craft::$app->getUser()->getIsAdmin() || Craft::$app->sections->getTotalEditableSections() > 0)) {
            if ($newEnabled) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('app', 'New entry'),
                    'info'    => Craft::t('command', 'Create a new entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'createEntry',
                    'service' => 'entries',
                ];
            }
            if ($editEnabled) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('app', 'Edit entries'),
                    'info'    => Craft::t('command', 'Edit an entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'editEntries',
                    'service' => 'entries'
                ];
            }
            if ($deleteEnabled) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('app', 'Delete entries'),
                    'info'    => Craft::t('command', 'Delete an entry in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'deleteEntries',
                    'service' => 'entries',
                    'vars'    => [
                        'deleteAll' => false
                    ]
                ];
            }
            if ($deleteAllEnabled && Craft::$app->getUser()->getIsAdmin()) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('command', 'Delete all entries'),
                    'info'    => Craft::t('command', 'Delete all entries in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'deleteEntries',
                    'service' => 'entries',
                    'vars'    => [
                        'deleteAll' => true
                    ]
                ];
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
        if ($this->_isEnabled('editGlobals') && (Craft::$app->getUser()->getIsAdmin() || Craft::$app->globals->getTotalEditableSets() > 0)) {
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Globals') . ': ' . Craft::t('app', 'Edit'),
                'more'    => true,
                'call'    => 'editGlobals',
                'service' => 'globals'
            ];
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
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Dashboard'),
                'url'     => UrlHelper::cpUrl('dashboard')
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'Sign out'),
                'info'    => Craft::t('command', 'End current session.'),
                'url'     => UrlHelper::cpUrl('logout')
            ];
            if (Craft::$app->getUser()->getIsAdmin() || Craft::$app->getUser()->getIdentity()->can('editUsers')) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'New user'),
                    'info'    => Craft::t('command', 'Create a user.'),
                    'url'     => UrlHelper::cpUrl('users/new')
                ];
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'Edit users'),
                    'info'    => Craft::t('command', 'Edit a user.'),
                    'more'    => true,
                    'call'    => 'editUsers',
                    'service' => 'users'
                ];
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'Delete users'),
                    'info'    => Craft::t('command', 'Delete a user other than your own.'),
                    'more'    => true,
                    'call'    => 'deleteUsers',
                    'service' => 'users'
                ];
                if (Craft::$app->getUser()->getIsAdmin()) {
                    $currentCommands[] = [
                        'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('command', 'Login as user'),
                        'info'    => Craft::t('command', 'Log in as a different user, and navigate to their dashboard.'),
                        'more'    => true,
                        'call'    => 'loginUsers',
                        'service' => 'users'
                    ];
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
            $currentCommands[] = [
                'name'    => Craft::t('command', 'Search on {option}', ['option' => 'Craft']),
                'info'    => 'https://craftcms.com',
                'more'    => true,
                'call'    => 'searchOptionCraft',
                'service' => 'search'
            ];
            $currentCommands[] = [
                'name'    => Craft::t('command', 'Search on {option}', ['option' => 'StackExchange']),
                'info'    => 'http://craftcms.stackexchange.com',
                'more'    => true,
                'call'    => 'searchOptionStackExchange',
                'service' => 'search'
            ];
            $currentCommands[] = [
                'name'    => Craft::t('command', 'Search for {option}', ['option' => Craft::t('app', 'Categories')]),
                'more'    => true,
                'call'    => 'searchOptionCategories',
                'service' => 'search'
            ];
            $currentCommands[] = [
                'name'    => Craft::t('command', 'Search for {option}', ['option' => Craft::t('app', 'Entries')]),
                'more'    => true,
                'call'    => 'searchOptionEntries',
                'service' => 'search'
            ];
            $currentCommands[] = [
                'name'    => Craft::t('command', 'Search for {option}', ['option' => Craft::t('app', 'Users')]),
                'more'    => true,
                'call'    => 'searchOptionUsers',
                'service' => 'search'
            ];
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
        if (! Craft::$app->getUser()->getIsAdmin()) {
            return $currentCommands;
        }
        if ($this->_isEnabled('tasks')) {
            $currentCommands[] = [
                'name'    => Craft::t('command', 'Tasks'),
                'info'    => Craft::t('command', 'Manage Craft tasks.'),
                'more'    => true,
                'call'    => 'getTaskCommands',
                'service' => 'tasks'
            ];
        }
        if ($this->_isEnabled('utilities')) {
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Utilities'),
                'info'    => Craft::t('command', 'Use one of the most used utilities.'),
                'more'    => true,
                'call'    => 'getUtilities',
                'service' => 'utilities'
            ];
        }
        if ($this->_isEnabled('settings')) {
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('command', 'New') . '...',
                'info'    => Craft::t('command', 'Add something new in the settings...'),
                'more'    => true,
                'call'    => 'createNewSetting',
                'service' => 'settings'
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Fields'),
                'url'  => UrlHelper::cpUrl('settings/fields')
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sections'),
                'url'  => UrlHelper::cpUrl('settings/sections')
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sections') . ' - ' . Craft::t('app', 'Edit'),
                'more'    => true,
                'call'    => 'editSections',
                'service' => 'settings'
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sections') . ' - ' . Craft::t('app', 'Edit entry type'),
                'more'    => true,
                'call'    => 'editSectionEntryTypes',
                'service' => 'settings'
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Globals'),
                'url'  => UrlHelper::cpUrl('settings/globals')
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Globals') . ' - ' . Craft::t('app', 'Global Sets'),
                'more'    => true,
                'call'    => 'editGlobalSets',
                'service' => 'settings'
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Users'),
                'url'  => UrlHelper::cpUrl('settings/users')
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Routes'),
                'url'  => UrlHelper::cpUrl('settings/routes')
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Categories'),
                'url'  => UrlHelper::cpUrl('settings/categories')
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Assets'),
                'url'  => UrlHelper::cpUrl('settings/assets')
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sites'),
                'url'  => UrlHelper::cpUrl('settings/sites')
            ];
            $currentCommands[] = [
                'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Plugins'),
                'url'  => UrlHelper::cpUrl('settings/plugins')
            ];
        }
        return $currentCommands;
    }
}
