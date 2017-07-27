<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command;

use amimpact\command\assetbundles\Command\CommandBundle;
use amimpact\command\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\web\View;

use yii\base\Event;

class Command extends Plugin
{
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '3.0.0';

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * Init Command.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'entries' => \amimpact\command\services\Entries::class,
            'general' => \amimpact\command\services\General::class,
            'globals' => \amimpact\command\services\Globals::class,
            'plugins' => \amimpact\command\services\Plugins::class,
            'search' => \amimpact\command\services\Search::class,
            'settings' => \amimpact\command\services\Settings::class,
            'tasks' => \amimpact\command\services\Tasks::class,
            'utilities' => \amimpact\command\services\Utilities::class,
            'users' => \amimpact\command\services\Users::class,
        ]);

        // We only want to see the command palette in the backend, and want to initiate it once
        $requestService = Craft::$app->getRequest();
        if (! $requestService->getIsConsoleRequest() && $requestService->getIsCpRequest() && ! $requestService->getAcceptsJson() && ! Craft::$app->getUser()->getIsGuest()) {
            // Load resources
            $viewService = Craft::$app->getView();
            $viewService->registerAssetBundle(CommandBundle::class);
            $viewService->registerTranslations('command', [
                'Command executed',
                'Are you sure you want to execute this command?',
                'There are no more commands available.'
            ]);

            // Load palette
            Event::on(View::class, View::EVENT_END_BODY, function(Event $event) use ($viewService) {
                echo $viewService->renderTemplate('command/palette', [
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
        // Settings
        $settings = $this->getSettings();

        return Craft::$app->getView()->renderTemplate('command/settings', [
            'settings' => $settings,
            'themes' => $settings->getThemes(),
            'elementSearchElementTypes' => $settings->getElementSearchElementTypes(),
        ]);
    }
}
