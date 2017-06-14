<?php
namespace Craft;

class AmCommand_SearchService extends BaseApplicationComponent
{
    /**
     * Get the search option action for Craft.
     *
     * @return bool
     */
    public function searchOptionCraft()
    {
        $this->_setAction('Craft');
        return true;
    }

    /**
     * Get the search option action for StackExchange.
     *
     * @return bool
     */
    public function searchOptionStackExchange()
    {
        $this->_setAction('StackExchange');
        return true;
    }

    /**
     * Get the search option action for Craft Categories.
     *
     * @return bool
     */
    public function searchOptionCategories()
    {
        $this->_setRealtimeAction('Categories');
        return true;
    }

    /**
     * Get the search option action for Craft Entries.
     *
     * @return bool
     */
    public function searchOptionEntries()
    {
        $this->_setRealtimeAction('Entries');
        return true;
    }

    /**
     * Get the search option action for Craft Users.
     *
     * @return bool
     */
    public function searchOptionUsers()
    {
        $this->_setRealtimeAction('Users');
        return true;
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
        if (! isset($variables['searchText']) || ! isset($variables['option'])) {
            return false;
        }
        $searchCriteria = $variables['searchText'];
        if (empty($searchCriteria) || trim($searchCriteria) == '') {
            craft()->amCommand->setReturnMessage(Craft::t('Search criteria isn’t set.'));
            return false;
        }
        switch ($variables['option']) {
            case 'Craft':
                craft()->amCommand->setReturnUrl('https://craftcms.com/search?q=' . $searchCriteria, true);
                break;
            case 'StackExchange':
                craft()->amCommand->setReturnUrl('http://craftcms.stackexchange.com/search?q=' . $searchCriteria, true);
                break;
            case 'Categories':
                return $this->_searchForElement(ElementType::Category, $searchCriteria);
                break;
            case 'Entries':
                return $this->_searchForElement(ElementType::Entry, $searchCriteria);
                break;
            case 'Users':
                return $this->_searchForElement(ElementType::User, $searchCriteria);
                break;
            case 'Elements':
                $entries = $this->_searchForElement(ElementType::Entry, $searchCriteria, true);
                $categories = $this->_searchForElement(ElementType::Category, $searchCriteria, true);
                $users = $this->_searchForElement(ElementType::User, $searchCriteria, true);
                return array_merge($entries, $categories, $users);
                break;
            default:
                return false;
                break;
        }
        return true;
    }

    /**
     * Set the return action (site search).
     *
     * @param string $searchOption
     */
    private function _setAction($searchOption)
    {
        $variables = array(
            'option' => $searchOption
        );

        craft()->amCommand->setReturnAction(Craft::t('Search on {option}', array('option' => Craft::t($searchOption))), '', 'searchOn', 'amCommand_search', $variables, false);
    }

    /**
     * Set the return action (element search).
     *
     * @param string $searchOption
     */
    private function _setRealtimeAction($searchOption)
    {
        $variables = array(
            'option' => $searchOption
        );

        craft()->amCommand->setReturnAction(Craft::t('Search for {option}', array('option' => Craft::t($searchOption))), '', 'searchOn', 'amCommand_search', $variables, true, true);
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
    private function _searchForElement($elementType, $searchCriteria, $addElementTypeInfo = false)
    {
        $elementTypeInfo = craft()->elements->getElementType($elementType);
        $criteria = craft()->elements->getCriteria($elementType, $searchCriteria);
        $criteria->search = '*' . $searchCriteria . '*';
        $criteria->status = null;
        $criteria->locale = craft()->language;
        $criteria->order = 'score';
        $elements = $criteria->find();

        $commands = array();
        foreach ($elements as $element) {
            switch ($elementType) {
                case ElementType::User:
                    $userInfo = array();
                    if ($element->firstName) {
                        $userInfo[] = $element->firstName;
                    }
                    if ($element->lastName) {
                        $userInfo[] = $element->lastName;
                    }
                    $userInfo[] = $element->email;

                    $commands[] = array(
                        'name' => $element->username,
                        'info' => ($addElementTypeInfo ? $elementTypeInfo->getName() . ' | ' : '') . implode(' - ', $userInfo),
                        'url'  => $element->getCpEditUrl()
                    );
                    break;

                default:
                    $commands[] = array(
                        'name' => $element->title,
                        'info' => ($addElementTypeInfo ? $elementTypeInfo->getName() . ' | ' : '') . Craft::t('URI') . ': ' . $element->uri,
                        'url'  => $element->getCpEditUrl()
                    );
                    break;
            }
        }
        return $commands;
    }
}
