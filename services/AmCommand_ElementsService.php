<?php
namespace Craft;

class AmCommand_ElementsService extends BaseApplicationComponent
{
    /**
     * Get elements.
     *
     * @param string $elementType
     * @param array  $attributes
     *
     * @return bool|array
     */
    public function getElements($elementType, $attributes = array())
    {
        return $this->_getElements($elementType, $attributes);
    }

    /**
     * Get total elements.
     *
     * @param type $elementType
     * @param type $attributes
     *
     * @return type
     */
    public function getTotalElements($elementType, $attributes)
    {
        return $this->_getElements($elementType, $attributes, true);
    }

    /**
     * Get elements.
     *
     * @param string $elementType
     * @param array  $attributes
     * @param bool   $getTotal
     *
     * @return mixed
     */
    private function _getElements($elementType, $attributes, $getTotal = false)
    {
        $contentTable = null;
        $fieldColumns = null;

        // Get element criteria model
        $criteria = craft()->elements->getCriteria($elementType, $attributes);
        $criteria->limit = null;
        $criteria->status = null;

        // Get the elements query
        $query = craft()->elements->buildElementsQuery($criteria, $contentTable, $fieldColumns, $getTotal);
        if (! $query) {
            return $getTotal ? 0 : false;
        }

        // Find records!
        $elements = $getTotal ? $query->queryColumn() : $query->queryAll();
        if (! $elements) {
            return $getTotal ? 0 : false;
        }

        return $getTotal ? count($elements) : $elements;
    }
}
