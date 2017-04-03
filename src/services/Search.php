<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;

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
     * Get the search option action for Craft Categories.
     *
     * @return bool
     */
    public function searchOptionCategories()
    {
        return $this->_setRealtimeAction('Categories');
    }

    /**
     * Get the search option action for Craft Entries.
     *
     * @return bool
     */
    public function searchOptionEntries()
    {
        return $this->_setRealtimeAction('Entries');
    }

    /**
     * Get the search option action for Craft Users.
     *
     * @return bool
     */
    public function searchOptionUsers()
    {
        return $this->_setRealtimeAction('Users');
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
        if (! isset($variables['searchText'])) {
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
            case 'Categories':
                return $this->_searchForElement(Category::class, $searchCriteria);
                break;
            case 'Entries':
                return $this->_searchForElement(Entry::class, $searchCriteria);
                break;
            case 'Users':
                return $this->_searchForElement(User::class, $searchCriteria);
                break;
            default:
                return false;
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
        $variables = [
            'option' => $searchOption
        ];

        return Command::$plugin->general->setReturnAction(Craft::t('command', 'Search on {option}', ['option' => Craft::t('app', $searchOption)]), '', 'searchOn', 'search', $variables, false);
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
        $variables = [
            'option' => $searchOption
        ];

        return Command::$plugin->general->setReturnAction(Craft::t('command', 'Search for {option}', ['option' => Craft::t('app', $searchOption)]), '', 'searchOn', 'search', $variables, true, true);
    }

    /**
     * Search for elements.
     *
     * @param string $elementType
     * @param string $searchCriteria
     *
     * @return array
     */
    private function _searchForElement($elementType, $searchCriteria)
    {
        // Get elements
        $elements = $elementType::find()
            ->search('*' . $searchCriteria . '*')
            ->status(null)
            ->orderBy('score')
            ->limit(null);

        // Gather commands based on the element type
        $commands = [];
        foreach ($elements as $element) {
            switch ($elementType) {
                case User::class:
                    $userInfo = [];
                    if ($element->firstName) {
                        $userInfo[] = $element->firstName;
                    }
                    if ($element->lastName) {
                        $userInfo[] = $element->lastName;
                    }
                    $userInfo[] = $element->email;

                    $commands[] = [
                        'name' => $element->username,
                        'info' => implode(' - ', $userInfo),
                        'url'  => $element->getCpEditUrl()
                    ];
                    break;

                default:
                    $commands[] = [
                        'name' => $element->title,
                        'info' => Craft::t('app', 'URI') . ': ' . $element->uri,
                        'url'  => $element->getCpEditUrl()
                    ];
                    break;
            }
        }
        return $commands;
    }
}
