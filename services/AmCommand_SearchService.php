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
                foreach (AmCommandModel::getElementSearchElementTypes() as $elementType => $submittedInfo) {
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
     * Set the return action (site search).
     *
     * @param string $searchOption
     */
    private function _setAction($searchOption)
    {
        // Start action
        $variables = array(
            'option' => $searchOption
        );
        craft()->amCommand->setReturnAction(Craft::t('Search on {option}', array('option' => Craft::t($searchOption))), '', 'searchOn', 'amCommand_search', $variables, false);

        return true;
    }

    /**
     * Set the return action (element search).
     *
     * @param string $searchOption
     */
    private function _setRealtimeAction($searchOption)
    {
        // Get the element type info
        $actualElementType = craft()->elements->getElementType($searchOption);

        // Start action
        $variables = array(
            'option' => $searchOption
        );
        craft()->amCommand->setReturnAction(Craft::t('Search for {option}', array('option' => $actualElementType->getName())), '', 'searchOn', 'amCommand_search', $variables, true, true);

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
        $commands = array();

        // Optional icons
        $elementTypeIcons = array(
            ElementType::User => array(
                'type' => 'font',
                'content' => 'users',
            ),
            ElementType::Entry => array(
                'type' => 'font',
                'content' => 'section',
            ),
            ElementType::Category => array(
                'type' => 'font',
                'content' => 'categories',
            ),
            ElementType::GlobalSet => array(
                'type' => 'font',
                'content' => 'globe',
            ),
        );
        $elementTypeParts = explode('_', $elementType);
        if (isset($elementTypeParts[0])) {
            // Do we have a plugin for this Element Type?
            $lcHandle = StringHelper::toLowerCase($elementTypeParts[0]);
            $plugin = craft()->plugins->getPlugin($lcHandle);
            if ($plugin) {
                // Try to find the icon
                $iconPath = craft()->path->getPluginsPath().$lcHandle.'/resources/icon-mask.svg';

                if (IOHelper::fileExists($iconPath)) {
                    $elementTypeIcons[$elementType] = array(
                        'type' => 'svg',
                        'content' => IOHelper::getFileContents($iconPath),
                    );
                }
            }
        }

        // Find elements
        $elementTypeInfo = craft()->elements->getElementType($elementType);
        $criteria = craft()->elements->getCriteria($elementType, $searchCriteria);
        $criteria->search = '*' . $searchCriteria . '*';
        $criteria->status = null;
        $criteria->locale = craft()->language;
        $criteria->order = 'score';
        $elements = $criteria->find();
        foreach ($elements as $element) {
            switch ($elementType) {
                case ElementType::User:
                    $userInfo = array();
                    if (($fullName = $element->getFullName()) !== '') {
                        $userInfo[] = $fullName;
                    }
                    $userInfo[] = $element->email;

                    $command = array(
                        'name' => $element->username,
                        'info' => ($addElementTypeInfo ? $elementTypeInfo->getName() . ' | ' : '') . implode(' - ', $userInfo),
                        'url'  => $element->getCpEditUrl(),
                    );
                    break;

                default:
                    $command = array(
                        'name' => $element->__toString(),
                        'info' => ($addElementTypeInfo ? $elementTypeInfo->getName() . ' | ' : '') . Craft::t('URI') . ': ' . $element->uri,
                        'url'  => $element->getCpEditUrl(),
                    );
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
