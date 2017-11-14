<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette\models;

use amimpact\commandpalette\CommandPalette;
use Craft;
use craft\base\Model;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\FileHelper;

class Settings extends Model
{
    /**
     * Define settings.
     */
    public $pluginName = '';
    public $theme = '';
    public $elementSearchElementTypes = [];

    /**
     * Returns the validation rules for attributes.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [['pluginName', 'theme'], 'string'],
        ];
    }

    /**
     * Get selected theme.
     *
     * @return false|string
     */
    public function getSelectedTheme()
    {
        // Did we select one?
        if (empty($this->theme) || $this->theme === 'Palette.css') {
            return false;
        }

        // Find theme
        $path = CommandPalette::$plugin->getBasePath().'/assetbundles/palette/dist/css/';
        if (file_exists($path . $this->theme)) {
            return $this->theme;
        }

        return false;
    }

    /**
     * Get available themes.
     *
     * @return array
     * @throws \yii\base\InvalidParamException
     */
    public function getThemes(): array
    {
        // Gather themes
        $themes = [];
        $path = CommandPalette::$plugin->getBasePath().'/assetbundles/palette/dist/css/';
        if (is_dir($path)) {
            $themeFiles = FileHelper::findFiles($path, [
                'only' => ['*.css'],
                'recursive' => false
            ]);

            if (is_array($themeFiles)) {
                foreach ($themeFiles as $file) {
                    $fileName = pathinfo($file, PATHINFO_BASENAME);
                    if ($fileName === 'Palette.css') {
                        continue; // Skip default
                    }
                    $themes[$fileName] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        natsort($themes);

        return array_merge(['' => Craft::t('command-palette', 'Default')], $themes);
    }

    /**
     * Get available element types that can be used for direct element searching.
     *
     * @return array
     */
    public function getElementSearchElementTypes(): array
    {
        // Gather element types
        $elementSearchElementTypes = [];
        $defaultEnabledElementTypes = [
            new Category(),
            new Entry(),
            new User(),
        ];

        // Find supported element types for element search, based on the settings
        if (is_array($this->elementSearchElementTypes)) {
            foreach ($this->elementSearchElementTypes as $elementType => $submittedInfo) {
                $elementSearchElementTypes[$elementType] = $submittedInfo;
            }
        }

        // Find supported element types for element search, based on the defaults
        foreach ($defaultEnabledElementTypes as $defaultEnabledElementType) {
            $refHandle = $defaultEnabledElementType::refHandle();
            if (! isset($elementSearchElementTypes[$refHandle])) {
                $elementSearchElementTypes[$refHandle] = [
                    'elementType' => $defaultEnabledElementType::displayName(),
                    'enabled' => 1,
                ];
            }
        }

        // Find supported element types for element search, based on all element types
        $elementTypes = Craft::$app->getElements()->getAllElementTypes();
        foreach ($elementTypes as $elementType) {
            $refHandle = $elementType::refHandle();
            if (! isset($elementSearchElementTypes[$refHandle])) {
                $elementSearchElementTypes[$refHandle] = [
                    'elementType' => $elementType::displayName(),
                    'enabled' => 0,
                ];
            }
        }

        // Sort by element type
        ksort($elementSearchElementTypes);

        return $elementSearchElementTypes;
    }
}
