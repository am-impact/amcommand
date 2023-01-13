<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette;

use amimpact\commandpalette\assetbundles\palette\PaletteBundle;
use amimpact\commandpalette\models\Settings;
use amimpact\commandpalette\services\Entries;
use amimpact\commandpalette\services\General;
use amimpact\commandpalette\services\Globals;
use amimpact\commandpalette\services\Plugins;
use amimpact\commandpalette\services\Search;
use amimpact\commandpalette\services\Tasks;
use amimpact\commandpalette\services\Users;
use amimpact\commandpalette\services\Utilities;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\View;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;

/**
 * Class CommandPalette
 *
 * @property Settings  $settings  The plugin's settings.
 * @property Entries   $entries   The entries service.
 * @property General   $general   The general service.
 * @property Globals   $globals   The globals service.
 * @property Plugins   $plugins   The plugins service.
 * @property Search    $search    The search service.
 * @property Tasks     $tasks     The tasks service.
 * @property Utilities $utilities The utilities service.
 * @property Users     $users     The users service.
 * @method Settings getSettings()
 */
class CommandPalette extends Plugin
{
    /**
     * @var CommandPalette
     */
    public static CommandPalette $plugin;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '4.0.0';

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

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
     * @return Model
     */
    protected function createSettingsModel(): Model
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
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
    private function _registerServices(): void
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
     * @throws RuntimeException
     */
    private function _registerPalette(): void
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
