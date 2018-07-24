<?php

namespace Luma\Campaignmonitor\Model\Config\Attributes;

abstract class AbstractAttributes
{
    /** @var array $_fields */
    protected $_fields;

    /** @var array $_customFieldNameMapping Custom field name for Magento attributes ["magento" => "campaignmonitor"] */
    protected $_customFieldNameMapping;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];

        foreach ($this->_fields as $key => $field) {
            $optionArray[] = ['value' => $key, 'label' => $field['label']];
        }

        return $optionArray;
    }

    /**
     * Returns the field type for the attribute from the $_fields array.
     * Returns null if not found.
     *
     * @param $field
     * @return string|null
     */
    public function getFieldType($field)
    {
        if (isset($this->_fields[$field])) {
            return $this->_fields[$field]['type'];
        } else {
            return null;
        }
    }

    /**
     * Returns the display/frontend name for the attribute from the $_fields array.
     * Returns null if not found.
     *
     * @param $field
     * @return string|null
     */
    public function getFieldLabel($field)
    {
        if (isset($this->_fields[$field])) {
            return $this->_fields[$field]['label'];
        } else {
            return null;
        }
    }

    /**
     * Returns an array of string options for the field.
     * If the field is not a MultiSelectOne/MultiSelectMany, an empty array should be returned.
     * This function should be overridden if the subclass has attributes that are select options.
     *
     * @param string $field
     * @return array
     */
    public function getFieldOptions($field)
    {
        return [];
    }

    /**
     * Returns the Campaign Monitor custom field name given the Magento attribute name.
     *
     * @param string $field The Magento attribute name
     * @param bool $returnDefault If true, returns the default value. Otherwise, returns null.
     * @return null|string
     */
    public function getCustomFieldName($field, $returnDefault = true)
    {
        if (array_key_exists($field, $this->_customFieldNameMapping)) {
            return $this->_customFieldNameMapping[$field];
        } else {
            if ($returnDefault) {
                return $field;
            } else {
                return null;
            }
        }
    }
}
