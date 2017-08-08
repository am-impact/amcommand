<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette;

use amimpact\commandpalette\assetbundles\Palette\PaletteBundle;
use amimpact\commandpalette\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\web\View;

use yii\base\Event;

class CommandPalette extends Plugin
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

        // Adjust plugin name?
        if (! empty($this->getSettings()->pluginName)) {
            $this->name = $this->getSettings()->pluginName;
        }

        // Register stuff
        $this->_registerServices();
        $this->_registerPalette();
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

        return Craft::$app->getView()->renderTemplate('command-palette/settings', [
            'settings' => $settings,
            'themes' => $settings->getThemes(),
            'elementSearchElementTypes' => $settings->getElementSearchElementTypes(),
        ]);
    }

    /**
     * Register our plugin's services.
     *
     * @return void
     */
    private function _registerServices()
    {
        $this->setComponents([
            'entries' => \amimpact\commandpalette\services\Entries::class,
            'general' => \amimpact\commandpalette\services\General::class,
            'globals' => \amimpact\commandpalette\services\Globals::class,
            'plugins' => \amimpact\commandpalette\services\Plugins::class,
            'search' => \amimpact\commandpalette\services\Search::class,
            'settings' => \amimpact\commandpalette\services\Settings::class,
            'tasks' => \amimpact\commandpalette\services\Tasks::class,
            'utilities' => \amimpact\commandpalette\services\Utilities::class,
            'users' => \amimpact\commandpalette\services\Users::class,
        ]);
    }

    /**
     * Register the palette.
     *
     * @return void
     */
    private function _registerPalette()
    {
        // We only want to see the command palette in the backend, and want to initiate it once
        $requestService = Craft::$app->getRequest();
        if (! $requestService->getIsConsoleRequest() && $requestService->getIsCpRequest() && ! $requestService->getAcceptsJson() && ! Craft::$app->getUser()->getIsGuest()) {
            // Load resources
            $viewService = Craft::$app->getView();
            $viewService->registerAssetBundle(PaletteBundle::class);
            $viewService->registerTranslations('command-palette', [
                'Command executed',
                'Are you sure you want to execute this command?',
                'There are no more commands available.'
            ]);

            // Load palette
            Event::on(View::class, View::EVENT_END_BODY, function(Event $event) use ($viewService) {
                echo $viewService->renderTemplate('command-palette/palette', [
                    'commands' => $this->general->getCommands($this->getSettings()),
                ]);
            });
        }
    }
}
