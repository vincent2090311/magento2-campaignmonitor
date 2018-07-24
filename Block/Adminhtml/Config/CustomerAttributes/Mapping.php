<?php

namespace Luma\Campaignmonitor\Block\Adminhtml\Config\CustomerAttributes;

class Mapping extends \Magento\Framework\View\Element\Html\Select 
{
    protected $customerAttributes;
   /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Braintree\Model\System\Config\Source\Country $countrySource
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context, 
        \Luma\Campaignmonitor\Model\Config\CustomerAttributes $customerAttributes, 
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerAttributes = $customerAttributes;
    }  
    /**
     * Returns countries array
     *
     * @return array
     */ 
     /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml() 
    {
        if (!$this->getOptions()) {
            $customerAttrs = $this->customerAttributes->getCustomerCustomFields();
            foreach ($customerAttrs as $code => $label) {
                $this->addOption($code, $label);
            }
        }
        return parent::_toHtml();
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value) {
        return $this->setName($value);
    }
}