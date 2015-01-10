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
     * Start the search action.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function searchOn($variables)
    {
        if (! isset($variables['searchText'])) {
            return false;
        }
        elseif (empty($variables['searchText']) || trim($variables['searchText']) == '') {
            craft()->amCommand->setReturnMessage(Craft::t('Search criteria isn’t set.'));
            return false;
        }
        switch ($variables['option']) {
            case 'Craft':
                craft()->amCommand->setReturnUrl('http://buildwithcraft.com/search?q=' . $variables['searchText'], true);
                break;
            case 'StackExchange':
                craft()->amCommand->setReturnUrl('http://craftcms.stackexchange.com/search?q=' . $variables['searchText'], true);
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
     */
    private function _setAction($searchOption)
    {
        $variables = array(
            'option' => $searchOption
        );

        craft()->amCommand->setReturnAction(Craft::t('Search on {option}', array('option' => $searchOption)), '', 'searchOn', 'amCommand_search', $variables);
    }
}