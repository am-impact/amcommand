<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
 */

namespace amimpact\command;

use amimpact\command\models\Settings;
use amimpact\command\assetbundles\Command\CommandBundle;

use Craft;
use craft\base\Plugin;
use craft\web\View;

use yii\base\Event;

class Command extends Plugin
{
    public static $plugin;

    /**
     * Init Command.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // We only want to see the command palette in the backend, and want to initiate it once
        if (Craft::$app->getRequest()->getIsCpRequest() && ! Craft::$app->getUser()->getIsGuest() && ! Craft::$app->getRequest()->getAcceptsJson()) {
            // Load resources
            Craft::$app->view->registerAssetBundle(CommandBundle::class);
            Craft::$app->view->registerTranslations('command', [
                'Command executed',
                'Are you sure you want to execute this command?',
                'There are no more commands available.'
            ]);

            // Load palette
            Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
                echo Craft::$app->view->renderTemplate('command/palette', [
                    'commands' => Command::$plugin->general->getCommands($this->getSettings()),
                ]);
            });
        }
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        $commands = [
            'newEntry' => [
                'name' => Craft::t('command', 'New entry'),
                'info' => Craft::t('command', 'Create a new entry in one of the available sections.')
            ],
            'editEntries' => [
                'name' => Craft::t('command', 'Edit entries'),
                'info' => Craft::t('command', 'Edit an entry in one of the available sections.')
            ],
            'deleteEntries' => [
                'name' => Craft::t('command', 'Delete entries'),
                'info' => Craft::t('command', 'Delete an entry in one of the available sections.')
            ],
            'deleteAllEntries' => [
                'name' => Craft::t('command', 'Delete all entries'),
                'info' => Craft::t('command', 'Delete all entries in one of the available sections.') . ' (' . Craft::t('command', 'This action may only be performed by admins.') . ')'
            ],
            'editGlobals' => [
                'name' => Craft::t('command', 'Globals'),
                'info' => Craft::t('command', 'Edit')
            ],
            'userCommands' => [
                'name' => Craft::t('command', 'Administrate users'),
                'info' => Craft::t('command', 'Create, edit or delete a user.')
            ],
            'searchCommands' => [
                'name' => Craft::t('command', 'Search'),
                'info' => Craft::t('command', 'You’re able to search within a site that offers help.')
            ],
            'settings' => [
                'name' => Craft::t('command', 'Settings'),
                'info' => Craft::t('command', 'This action may only be performed by admins.')
            ],
            'tasks' => [
                'name' => Craft::t('command', 'Tasks'),
                'info' => Craft::t('command', 'This action may only be performed by admins.')
            ],
            'utilities' => [
                'name' => Craft::t('command', 'Utilities'),
                'info' => Craft::t('command', 'This action may only be performed by admins.')
            ]
        ];

        return Craft::$app->view->renderTemplate('command/settings', [
            'commands' => $commands,
            'settings' => $this->getSettings()
        ]);
    }
}
