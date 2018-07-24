<?php

namespace Luma\Campaignmonitor\Block\Adminhtml\Config;

class CustomerAttributes extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray 
{
    protected $_columns = [];

    protected $_customerAttrRenderer;
    /**
     * Enable the "Add after" button or not
     *
     * @var bool
     */
    protected $_addAfter = true;
     /**
     * Label of add button
     *
     * @var string
     */
    protected $_addButtonLabel;
    /**
     * Check if columns are defined, set template
     *
     * @return void
     */
    protected function _construct() 
    {
        parent::_construct();
        $this->_addButtonLabel = __('Add');
    }
    /**
     * Returns renderer for country element
     *
     * @return \Magento\Braintree\Block\Adminhtml\Form\Field\Countries
     */
    protected function getCustomerAttrRenderer() 
    {
        if (!$this->_customerAttrRenderer) {
            $this->_customerAttrRenderer = $this->getLayout()->createBlock('\Luma\Campaignmonitor\Block\Adminhtml\Config\CustomerAttributes\Mapping', '', ['data' => ['is_render_to_js_template' => true]]);
        }
        return $this->_customerAttrRenderer;
    }
    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender() 
    {
        $this->addColumn('attributes', [
            'label' => __('Customer Attributes'),
            'renderer' => $this->getCustomerAttrRenderer(),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row) 
    {
        $attr = $row->getAttributes();
        $options = [];
        if ($attr) {
            $options['option_' . $this->getCustomerAttrRenderer()->calcOptionHash($attr)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}