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

/**
 * Class CommandPalette
 *
 * @property Settings                                    $settings  The plugin's settings.
 * @property \amimpact\commandpalette\services\Entries   $entries   The entries service.
 * @property \amimpact\commandpalette\services\General   $general   The general service.
 * @property \amimpact\commandpalette\services\Globals   $globals   The globals service.
 * @property \amimpact\commandpalette\services\Plugins   $plugins   The plugins service.
 * @property \amimpact\commandpalette\services\Search    $search    The search service.
 * @property \amimpact\commandpalette\services\Tasks     $tasks     The tasks service.
 * @property \amimpact\commandpalette\services\Utilities $utilities The utilities service.
 * @property \amimpact\commandpalette\services\Users     $users     The users service.
 * @method Settings getSettings()
 */
class CommandPalette extends Plugin
{
    /**
     * @var CommandPalette
     */
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
     * @throws \yii\base\Exception
     * @throws \Twig_Error_Loader
     * @throws \RuntimeException
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
            'entries' => services\Entries::class,
            'general' => services\General::class,
            'globals' => services\Globals::class,
            'plugins' => services\Plugins::class,
            'search' => services\Search::class,
            'settings' => services\Settings::class,
            'tasks' => services\Tasks::class,
            'utilities' => services\Utilities::class,
            'users' => services\Users::class,
        ]);
    }

    /**
     * Register the palette.
     *
     * @return void
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     * @throws \Twig_Error_Loader
     * @throws \RuntimeException
     */
    private function _registerPalette()
    {
        Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
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
                echo $viewService->renderTemplate('command-palette/palette', [
                    'commands' => json_encode(CommandPalette::$plugin->general->getCommands()),
                ]);
            }
        });
    }
}
