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
use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\User;

class Search extends Component
{
    /**
     * Get the search option action for Craft.
     *
     * @return bool
     */
    public function searchOptionCraft(): bool
    {
        return $this->_setAction('Craft');
    }

    /**
     * Get the search option action for StackExchange.
     *
     * @return bool
     */
    public function searchOptionStackExchange(): bool
    {
        return $this->_setAction('StackExchange');
    }

    /**
     * Get the search option action for an Element Type.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function searchOptionElementType(array $variables = []): bool
    {
        // Do we have the required information?
        if (! isset($variables['elementType'])) {
            return false;
        }

        return $this->_setRealtimeAction($variables['elementType']);
    }

    /**
     * Start the search action.
     *
     * @param array $variables
     *
     * @return bool|array
     */
    public function searchOn(array $variables = [])
    {
        // Do we have the required information?
        if (! isset($variables['searchText'], $variables['option'])) {
            return false;
        }

        // Do we have our search criteria?
        $searchCriteria = $variables['searchText'];
        if (empty($searchCriteria) || trim($searchCriteria) === '') {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'Search criteria isn’t set.'));
            return false;
        }

        // What are we searching for?
        switch ($variables['option']) {
            case 'Craft':
                CommandPalette::$plugin->general->setReturnUrl('https://craftcms.com/search?q=' . $searchCriteria, true);
                break;

            case 'StackExchange':
                CommandPalette::$plugin->general->setReturnUrl('http://craftcms.stackexchange.com/search?q=' . $searchCriteria, true);
                break;

            case 'DirectElements':
                // Gather elements
                $elements = [];

                // Start element searches
                $elementSearchElementTypes = CommandPalette::$plugin->getSettings()->getElementSearchElementTypes();
                foreach ($elementSearchElementTypes as $elementType => $submittedInfo) {
                    if (isset($submittedInfo['enabled']) && ($submittedInfo['enabled'] === '1' || $submittedInfo['enabled'] === 1)) {
                        $elements = array_merge($elements, $this->_searchForElementType($elementType, $searchCriteria, true));
                    }
                }

                return $elements;
                break;

            default:
                return $this->_searchForElementType($variables['option'], $searchCriteria);
                break;
        }

        return true;
    }

    /**
     * Set the return action.
     *
     * @param string $searchOption
     *
     * @return bool
     */
    private function _setAction($searchOption): bool
    {
        // Start action
        $variables = [
            'option' => $searchOption
        ];
        CommandPalette::$plugin->general->setReturnAction(Craft::t('command-palette', 'Search on {option}', ['option' => Craft::t('app', $searchOption)]), '', 'searchOn', 'search', $variables, false);

        return true;
    }

    /**
     * Set the return realtime action.
     *
     * @param string $searchOption
     *
     * @return bool
     */
    private function _setRealtimeAction($searchOption): bool
    {
        // Get the element type info
        $actualElementType = Craft::$app->getElements()->getElementTypeByRefHandle($searchOption);

        // Start action
        $variables = [
            'option' => $searchOption
        ];
        CommandPalette::$plugin->general->setReturnAction(Craft::t('command-palette', 'Search for {option}', ['option' => $actualElementType::displayName()]), '', 'searchOn', 'search', $variables, true, true);

        return true;
    }

    /**
     * Search for elements.
     *
     * @param string $elementType
     * @param string $searchCriteria
     * @param bool   $addElementTypeInfo [Optional] Whether to display the element type.
     *
     * @return array
     */
    private function _searchForElementType(string $elementType, string $searchCriteria, $addElementTypeInfo = false): array
    {
        // Gather commands
        $commands = [];

        // Optional icons
        $elementTypeIcons = [
            User::class => [
                'type' => 'font',
                'content' => 'users',
            ],
            Entry::class => [
                'type' => 'font',
                'content' => 'section',
            ],
            Category::class => [
                'type' => 'font',
                'content' => 'categories',
            ],
            GlobalSet::class => [
                'type' => 'font',
                'content' => 'globe',
            ],
        ];
        $elementTypeParts = explode('_', $elementType);
        if (isset($elementTypeParts[0])) {
            // TODO: Fix!
            // // Do we have a plugin for this Element Type?
            // $lcHandle = StringHelper::toLowerCase($elementTypeParts[0]);
            // $plugin = Craft::$app->getPlugins()->getPlugin($lcHandle);
            // if ($plugin) {
            // Try getPluginIconSvg from plugin service
            // }
        }

        // Get elements
        /** @var $actualElementType craft\base\Element */
        $actualElementType = Craft::$app->getElements()->getElementTypeByRefHandle($elementType);
        $elements = $actualElementType::find()
            ->search('*' . $searchCriteria . '*')
            ->status(null)
            ->orderBy('score')
            ->limit(null)
            ->all();

        foreach ($elements as $element) {
            /** @var $element craft\base\Element */
            if ($elementType === User::refHandle()) {
                /** @var $element User */
                $userInfo = [];
                if (($fullName = $element->getFullName()) !== '') {
                    $userInfo[] = $fullName;
                }
                $userInfo[] = $element->email;

                $command = [
                    'name' => $element->username,
                    'info' => ($addElementTypeInfo ? $actualElementType::displayName() . ' | ' : '') . implode(' - ', $userInfo),
                    'url' => $element->getCpEditUrl(),
                ];
            }
            else {
                $command = [
                    'name' => $element->__toString(),
                    'info' => ($addElementTypeInfo ? $actualElementType::displayName() . ' | ' : '') . Craft::t('app', 'URI') . ': ' . $element->uri,
                    'url' => $element->getCpEditUrl(),
                ];
            }

            // Is there an icon available?
            if (isset($elementTypeIcons[$elementType])) {
                $command['icon'] = $elementTypeIcons[$elementType];
            }

            // Are the keywords available in the command?
            $addSearchCriteria = false;
            foreach (str_split($searchCriteria) as $char) {
                if (stripos($command['name'], $char) === false) {
                    $addSearchCriteria = true;
                    break;
                }
            }
            if ($addSearchCriteria) {
                $command['name'] .= ' {' . $searchCriteria . '}';
            }

            $commands[] = $command;
        }

        return $commands;
    }
}
