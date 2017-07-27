<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\GlobalSet;

class Search extends Component
{
    /**
     * Get the search option action for Craft.
     *
     * @return bool
     */
    public function searchOptionCraft()
    {
        return $this->_setAction('Craft');
    }

    /**
     * Get the search option action for StackExchange.
     *
     * @return bool
     */
    public function searchOptionStackExchange()
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
    public function searchOptionElementType($variables)
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
     * @return bool
     */
    public function searchOn($variables)
    {
        // Do we have the required information?
        if (! isset($variables['searchText']) || ! isset($variables['option'])) {
            return false;
        }

        // Do we have our search criteria?
        $searchCriteria = $variables['searchText'];
        if (empty($searchCriteria) || trim($searchCriteria) == '') {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'Search criteria isn’t set.'));
            return false;
        }

        // What are we searching for?
        switch ($variables['option']) {
            case 'Craft':
                Command::$plugin->general->setReturnUrl('https://craftcms.com/search?q=' . $searchCriteria, true);
                break;

            case 'StackExchange':
                Command::$plugin->general->setReturnUrl('http://craftcms.stackexchange.com/search?q=' . $searchCriteria, true);
                break;

            case 'DirectElements':
                // Gather elements
                $elements = [];

                // Start element searches
                $plugin = Craft::$app->getPlugins()->getPlugin('command');
                if ($plugin) {
                    $pluginSettings = $plugin->getSettings();
                    if (is_array($pluginSettings->elementSearchElementTypes)) {
                        foreach ($pluginSettings->elementSearchElementTypes as $elementType => $submittedInfo) {
                            if (isset($submittedInfo['enabled']) && $submittedInfo['enabled'] === '1') {
                                $elements = array_merge($elements, $this->_searchForElementType($elementType, $searchCriteria, true));
                            }
                        }
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
    private function _setAction($searchOption)
    {
        // Start action
        $variables = [
            'option' => $searchOption
        ];
        Command::$plugin->general->setReturnAction(Craft::t('command', 'Search on {option}', ['option' => Craft::t('app', $searchOption)]), '', 'searchOn', 'search', $variables, false);

        return true;
    }

    /**
     * Set the return realtime action.
     *
     * @param string $searchOption
     *
     * @return bool
     */
    private function _setRealtimeAction($searchOption)
    {
        // Get the element type info
        $actualElementType = Craft::$app->getElements()->getElementTypeByRefHandle($searchOption);

        // Start action
        $variables = [
            'option' => $searchOption
        ];
        Command::$plugin->general->setReturnAction(Craft::t('command', 'Search for {option}', ['option' => $actualElementType->displayName()]), '', 'searchOn', 'search', $variables, true, true);

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
    private function _searchForElementType($elementType, $searchCriteria, $addElementTypeInfo = false)
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
        $actualElementType = Craft::$app->getElements()->getElementTypeByRefHandle($elementType);
        $elements = $actualElementType::find()
            ->search('*' . $searchCriteria . '*')
            ->status(null)
            ->orderBy('score')
            ->limit(null);

        foreach ($elements as $element) {
            switch ($elementType) {
                case User::refHandle():
                    $userInfo = [];
                    if (($fullName = $element->getFullName()) !== '') {
                        $userInfo[] = $fullName;
                    }
                    $userInfo[] = $element->email;

                    $command = [
                        'name' => $element->username,
                        'info' => ($addElementTypeInfo ? $actualElementType::displayName() . ' | ' : '') . implode(' - ', $userInfo),
                        'url'  => $element->getCpEditUrl(),
                    ];
                    break;

                default:
                    $command = [
                        'name' => $element->__toString(),
                        'info' => ($addElementTypeInfo ? $actualElementType::displayName() . ' | ' : '') . Craft::t('app', 'URI') . ': ' . $element->uri,
                        'url'  => $element->getCpEditUrl(),
                    ];
                    break;
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
