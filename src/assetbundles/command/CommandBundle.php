<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\assetbundles\Command;

use amimpact\command\Command;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CommandBundle extends AssetBundle
{
    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // Define the path that your publishable resources live
        $this->sourcePath = "@amimpact/command/assetbundles/command/dist";

        // Define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // Gather resources
        $this->js = [
            'js/Command.js',
            'js/fuzzy-min.js',
        ];

        $this->css = [
            'css/Command.css',
        ];

        // Did we select a different theme?
        $settings = Command::$plugin->getSettings();
        $themeFile = $settings->getSelectedTheme();
        if ($themeFile) {
            $this->css[] = 'css/' . $themeFile;
        }

        parent::init();
    }
}
