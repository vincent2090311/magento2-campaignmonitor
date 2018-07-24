<?php

namespace Luma\Campaignmonitor\Model\Config;

use Luma\Campaignmonitor\Model\CampaignMonitor;

class CustomerAttributes extends \Luma\Campaignmonitor\Model\Config\Attributes\AbstractAttributes
{
    protected $_fields = [];

    /** @var array $_customFieldNameMapping */
    // Custom field name should be less than 100 characters
    protected $_customFieldNameMapping = [
        'created_at'       => 'Customer Date Created',
        'created_in'       => 'Customer Created From Store',
        'dob'              => 'Customer Date Of Birth',
        'gender'           => 'Customer Gender',
        'group_id'         => 'Customer Group'
    ];

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        $this->customerFactory = $customerFactory;

        /** @var \Magento\Customer\Model\Customer $customerModel */
        $customerModel = $this->customerFactory->create();
        $magentoAttributes = $customerModel->getAttributes();

        foreach ($magentoAttributes as $attribute) {
            $code = $attribute->getAttributeCode();
            $label = $attribute->getFrontendLabel();
            if (array_key_exists($code, $this->_customFieldNameMapping)) {
                // Get the attribute type for Campaign Monitor
                if ($attribute->getFrontendInput() === 'date' || $attribute->getFrontendInput() === 'datetime') {
                    $type = CampaignMonitor::FIELD_TYPE_DATE;
                } elseif ($attribute->getBackendType() == 'int' && $attribute->getFrontendInput() == 'text') {
                    $type = CampaignMonitor::FIELD_TYPE_NUMBER;
                } elseif ($attribute->getBackendType() == 'decimal') {
                    $type = CampaignMonitor::FIELD_TYPE_NUMBER;
                } elseif ($code == 'gender' || $this->_isBooleanAttribute($attribute)) {
                    $type = CampaignMonitor::FIELD_TYPE_SELECT_ONE;

                    $allOptions = $attribute->getSource()->getAllOptions(false);
                    $options = [];
                    foreach ($allOptions as $option) {
                        $options[] = $option['label'];
                    }
                    $this->_fields[$code]['options'] = $options;
                } else {
                    $type = CampaignMonitor::FIELD_TYPE_TEXT;
                }

                // Populate the field list
                $this->_fields[$code]['label'] = $this->_customFieldNameMapping[$code];
                $this->_fields[$code]['type'] = $type;
            }
        }
        asort($this->_fields);
    }

    /**
     * Returns true if the attribute type is boolean based on source model.
     * Returns false otherwise.
     *
     * @param Mage_Customer_Model_Entity_Attribute $attribute
     * @return bool
     */
    protected function _isBooleanAttribute($attribute)
    {
        return $attribute->getSourceModel() && $attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean';
    }

    /**
     * Returns all attribute option labels in an array
     *
     * @param string $field
     * @return array
     */
    public function getFieldOptions($field)
    {
        if (array_key_exists($field, $this->_fields) && $this->_fields[$field]['type'] == CampaignMonitor::FIELD_TYPE_SELECT_ONE) {
            return $this->_fields[$field]['options'];
        } else {
            return [];
        }
    }

    public function getCustomerCustomFields()
    {
        return $this->_customFieldNameMapping;
    }
}
