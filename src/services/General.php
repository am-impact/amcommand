<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette\services;

use amimpact\commandpalette\CommandPalette;
use amimpact\commandpalette\events\RegisterCommandsEvent;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;

class General extends Component
{
    /**
     * @event RegisterCommandsEvent The event that is triggered to register more commands.
     */
    const EVENT_REGISTER_COMMANDS = 'registerCommands';

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

        // Gather commands
        $commands = [];

        // Add general commands
        $commands = $this->_getContentCommands($commands);
        $commands = $this->_getGlobalCommands($commands);
        $commands = $this->_getUserCommands($commands);
        $commands = $this->_getSearchCommands($commands);
        $commands = $this->_getSettingCommands($commands);

        // Give plugins a chance to add their own
        $event = new RegisterCommandsEvent([
            'commands' => $commands
        ]);
        $this->trigger(self::EVENT_REGISTER_COMMANDS, $event);
        $commands = $event->commands;

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
        if ($pluginHandle !== false && $pluginHandle !== 'false' && $service !== false && $pluginHandle != 'command-palette') {
            // Trigger a plugin's callback
            $actualPlugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
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
            if (! method_exists(CommandPalette::$plugin->$service, $command)) {
                return false;
            }
            $commandResult = CommandPalette::$plugin->$service->$command($variables);
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
        // New, edit and delete commands
        if (Craft::$app->getUser()->getIsAdmin() || Craft::$app->getSections()->getTotalEditableSections() > 0) {
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('app', 'New entry'),
                'info'    => Craft::t('command-palette', 'Create a new entry in one of the available sections.'),
                'more'    => true,
                'call'    => 'createEntry',
                'service' => 'entries',
                'icon'    => [
                    'type' => 'font',
                    'content' => 'section',
                ]
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('app', 'Edit entries'),
                'info'    => Craft::t('command-palette', 'Edit an entry in one of the available sections.'),
                'more'    => true,
                'call'    => 'editEntries',
                'service' => 'entries',
                'icon'    => [
                    'type' => 'font',
                    'content' => 'section',
                ]
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('app', 'Delete entries'),
                'info'    => Craft::t('command-palette', 'Delete an entry in one of the available sections.'),
                'more'    => true,
                'call'    => 'deleteEntries',
                'service' => 'entries',
                'vars'    => [
                    'deleteAll' => false
                ],
                'icon'    => [
                    'type' => 'font',
                    'content' => 'section',
                ]
            ];
            if (Craft::$app->getUser()->getIsAdmin()) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Content') . ': ' . Craft::t('command-palette', 'Delete all entries'),
                    'info'    => Craft::t('command-palette', 'Delete all entries in one of the available sections.'),
                    'more'    => true,
                    'call'    => 'deleteEntries',
                    'service' => 'entries',
                    'vars'    => [
                        'deleteAll' => true
                    ],
                    'icon'    => [
                        'type' => 'font',
                        'content' => 'section',
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
        if (Craft::$app->getUser()->getIsAdmin() || Craft::$app->getGlobals()->getTotalEditableSets() > 0) {
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Globals') . ': ' . Craft::t('app', 'Edit'),
                'more'    => true,
                'call'    => 'editGlobals',
                'service' => 'globals',
                'icon'    => [
                    'type' => 'font',
                    'content' => 'globe',
                ]
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
        $currentCommands[] = [
            'name' => Craft::t('app', 'Dashboard'),
            'url'  => UrlHelper::cpUrl('dashboard'),
            'icon' => [
                'type' => 'font',
                'content' => 'gauge',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Sign out'),
            'info' => Craft::t('command-palette', 'End current session.'),
            'url'  => UrlHelper::cpUrl('logout')
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'My Account'),
            'url'  => UrlHelper::cpUrl('myaccount')
        ];
        if (Craft::$app->getUser()->getIsAdmin() || Craft::$app->getUser()->getIdentity()->can('editUsers')) {
            $currentCommands[] = [
                'name' => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'New user'),
                'info' => Craft::t('command-palette', 'Create a user.'),
                'url'  => UrlHelper::cpUrl('users/new'),
                'icon' => [
                    'type' => 'font',
                    'content' => 'users',
                ]
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'Edit users'),
                'info'    => Craft::t('command-palette', 'Edit a user.'),
                'more'    => true,
                'call'    => 'editUsers',
                'service' => 'users',
                'icon' => [
                    'type' => 'font',
                    'content' => 'users',
                ]
            ];
            $currentCommands[] = [
                'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('app', 'Delete users'),
                'info'    => Craft::t('command-palette', 'Delete a user other than your own.'),
                'more'    => true,
                'call'    => 'deleteUsers',
                'service' => 'users',
                'icon' => [
                    'type' => 'font',
                    'content' => 'users',
                ]
            ];
            if (Craft::$app->getUser()->getIsAdmin()) {
                $currentCommands[] = [
                    'name'    => Craft::t('app', 'Users') . ': ' . Craft::t('command-palette', 'Login as user'),
                    'info'    => Craft::t('command-palette', 'Log in as a different user, and navigate to their dashboard.'),
                    'more'    => true,
                    'call'    => 'loginUsers',
                    'service' => 'users',
                    'icon' => [
                        'type' => 'font',
                        'content' => 'users',
                    ]
                ];
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
        $currentCommands[] = [
            'name'    => Craft::t('command-palette', 'Search on {option}', ['option' => 'Craft']),
            'info'    => 'https://craftcms.com',
            'more'    => true,
            'call'    => 'searchOptionCraft',
            'service' => 'search',
            'icon'    => [
                'type' => 'font',
                'content' => 'search',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('command-palette', 'Search on {option}', ['option' => 'StackExchange']),
            'info'    => 'http://craftcms.stackexchange.com',
            'more'    => true,
            'call'    => 'searchOptionStackExchange',
            'service' => 'search',
            'icon'    => [
                'type' => 'font',
                'content' => 'search',
            ]
        ];

        // Element searches
        if (is_array($this->_settings->elementSearchElementTypes)) {
            foreach ($this->_settings->elementSearchElementTypes as $elementType => $submittedInfo) {
                if (isset($submittedInfo['enabled']) && $submittedInfo['enabled'] === '1') {
                    $actualElementType = Craft::$app->getElements()->getElementTypeByRefHandle($elementType);
                    $currentCommands[] = [
                        'name'    => Craft::t('command-palette', 'Search for {option}', ['option' => $actualElementType::displayName()]),
                        'more'    => true,
                        'call'    => 'searchOptionElementType',
                        'service' => 'search',
                        'vars'    => [
                            'elementType' => $elementType
                        ],
                        'icon'    => [
                            'type' => 'font',
                            'content' => 'search',
                        ]
                    ];
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
        if (! Craft::$app->getUser()->getIsAdmin()) {
            return $currentCommands;
        }
        $currentCommands[] = [
            'name'    => Craft::t('command-palette', 'Tasks'),
            'info'    => Craft::t('command-palette', 'Manage Craft tasks.'),
            'more'    => true,
            'call'    => 'getTaskCommands',
            'service' => 'tasks',
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('app', 'Utilities'),
            'info'    => Craft::t('command-palette', 'Use one of the most used utilities.'),
            'more'    => true,
            'call'    => 'getUtilities',
            'service' => 'utilities',
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('command-palette', 'New') . '...',
            'info'    => Craft::t('command-palette', 'Add something new in the settings...'),
            'more'    => true,
            'call'    => 'createNewSetting',
            'service' => 'settings',
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Fields'),
            'url'  => UrlHelper::cpUrl('settings/fields'),
            'icon' => [
                'type' => 'font',
                'content' => 'field',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Fields') . ' - ' . Craft::t('app', 'Edit'),
            'more'    => true,
            'call'    => 'editFields',
            'service' => 'settings',
            'icon'    => [
                'type' => 'font',
                'content' => 'field',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sections'),
            'url'  => UrlHelper::cpUrl('settings/sections'),
            'icon' => [
                'type' => 'font',
                'content' => 'section',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sections') . ' - ' . Craft::t('app', 'Edit'),
            'more'    => true,
            'call'    => 'editSections',
            'service' => 'settings',
            'icon' => [
                'type' => 'font',
                'content' => 'section',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sections') . ' - ' . Craft::t('app', 'Edit entry type'),
            'more'    => true,
            'call'    => 'editSectionEntryTypes',
            'service' => 'settings',
            'icon' => [
                'type' => 'font',
                'content' => 'section',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Globals'),
            'url'  => UrlHelper::cpUrl('settings/globals'),
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Globals') . ' - ' . Craft::t('app', 'Global Sets'),
            'more'    => true,
            'call'    => 'editGlobalSets',
            'service' => 'settings',
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Users'),
            'url'  => UrlHelper::cpUrl('settings/users'),
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Routes'),
            'url'  => UrlHelper::cpUrl('settings/routes'),
            'icon' => [
                'type' => 'font',
                'content' => 'routes',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Categories'),
            'url'  => UrlHelper::cpUrl('settings/categories'),
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Assets'),
            'url'  => UrlHelper::cpUrl('settings/assets'),
            'icon' => [
                'type' => 'font',
                'content' => 'settings',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Sites'),
            'url'  => UrlHelper::cpUrl('settings/sites'),
            'icon' => [
                'type' => 'font',
                'content' => 'world',
            ]
        ];
        $currentCommands[] = [
            'name' => Craft::t('app', 'Settings') . ': ' . Craft::t('app', 'Plugins'),
            'url'  => UrlHelper::cpUrl('settings/plugins'),
            'icon' => [
                'type' => 'font',
                'content' => 'plugin',
            ]
        ];
        $currentCommands[] = array(
            'name'    => Craft::t('app', 'Settings') . ': ' . Craft::t('command-palette', 'Plugin settings'),
            'more'    => true,
            'call'    => 'getSettingsUrl',
            'service' => 'plugins',
            'icon'    => array(
                'type' => 'font',
                'content' => 'plugin',
            )
        );
        return $currentCommands;
    }
}
