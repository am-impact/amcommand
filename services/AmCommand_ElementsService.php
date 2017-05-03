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
     * Get the fields handles from an element's Field Layout.
     *
     * @param object $element
     *
     * @return array
     */
    public function getFieldHandlesFromElementLayout($element)
    {
        $fields = array();

        if (is_object($element)) {
            $fields = array(
                'elementId',
                'locale',
                'title'
            );

            if (method_exists($element, 'getFieldLayout')) {
                $layout = $element->getFieldLayout();
                if ($layout) {
                    $layoutFields = $layout->getFields();
                    if ($layoutFields) {
                        foreach ($layoutFields as $field) {
                            $actualField = $field->getField();
                            if ($actualField) {
                                $fields[ $actualField->type . '_' . $actualField->id ] = $actualField->handle;
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Get attributes from a model.
     *
     * @param object $model
     *
     * @return array
     */
    public function getElementModelAttributes($model)
    {
        // Model attributes
        $attributes = $model->getAttributes();

        // Content attributes
        if (method_exists($model, 'getContent') && method_exists($model, 'getFieldLayout')) {
            // Get available field handles
            $fieldHandles = craft()->amCommand_elements->getFieldHandlesFromElementLayout($model);

            // Get all content attributes for this model
            $contentAttributes = $model->getContent()->getAttributes();

            // Only add attributes which actually make sense
            foreach ($fieldHandles as $typeWithId => $handle) {
                // Do we have object related fields?
                if (! is_int($typeWithId) && isset($model->$handle) && is_object($model->$handle) && stripos(get_class($model->$handle), 'model') !== false) {
                    if ($model->$handle instanceof ElementCriteriaModel) {
                        $elements = $model->$handle->find();
                        $elementsData = array();

                        foreach ($elements as $element) {
                            $elementsData[] = $this->getElementModelAttributes($element);
                        }

                        if (count($elementsData)) {
                            $attributes[$handle] = (count($elementsData) > 1) ? $elementsData : $elementsData[0];
                        }
                    }
                    else {
                        $attributes[$handle] = $model->$handle->getAttributes();
                    }
                }
                elseif (isset($contentAttributes[$handle])) {
                    $attributes[$handle] = $contentAttributes[$handle];
                }
            }
        }

        return $attributes;
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
