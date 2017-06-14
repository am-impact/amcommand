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

        // Start element search
        $this->_setRealtimeAction($variables['elementType']);
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
        // Do we have the required information?
        if (! isset($variables['searchText']) || ! isset($variables['option'])) {
            return false;
        }

        // Do we have the keywords?
        $searchCriteria = $variables['searchText'];
        if (empty($searchCriteria) || trim($searchCriteria) == '') {
            craft()->amCommand->setReturnMessage(Craft::t('Search criteria isn’t set.'));
            return false;
        }

        // Where will we search?
        switch ($variables['option']) {
            case 'Craft':
                craft()->amCommand->setReturnUrl('https://craftcms.com/search?q=' . $searchCriteria, true);
                break;
            case 'StackExchange':
                craft()->amCommand->setReturnUrl('http://craftcms.stackexchange.com/search?q=' . $searchCriteria, true);
                break;
            case 'DirectElements':
                // Gather elements
                $elements = array();

                // Start element searches
                $plugin = craft()->plugins->getPlugin('amcommand');
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

        $actualElementType = craft()->elements->getElementType($searchOption);

        craft()->amCommand->setReturnAction(Craft::t('Search for {option}', array('option' => $actualElementType->getName())), '', 'searchOn', 'amCommand_search', $variables, true, true);
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
                    if (($fullName = $element->getFullName()) !== '') {
                        $userInfo[] = $fullName;
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
                        'name' => $element->__toString(),
                        'info' => ($addElementTypeInfo ? $elementTypeInfo->getName() . ' | ' : '') . Craft::t('URI') . ': ' . $element->uri,
                        'url'  => $element->getCpEditUrl()
                    );
                    break;
            }
        }

        return $commands;
    }
}
