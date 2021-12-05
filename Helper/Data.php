<?php

namespace Luma\Campaignmonitor\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_WEBHOOK_ID                        = 'createsend_general/api/webhook_id';
    const XML_PATH_LIST_ID                           = 'createsend_general/api/list_id';
    const XML_PATH_NEW_LIST_NAME                     = 'createsend_general/api/new_list_name';
    const XML_PATH_AUTHENTICATION_METHOD             = 'createsend_general/api/authentication_method';
    const XML_PATH_API_ID                            = 'createsend_general/api/api_key';
    const XML_PATH_API_CLIENT_ID                     = 'createsend_general/api/api_client_id';
    const XML_PATH_OAUTH_CLIENT_ID                   = 'createsend_general/api/oauth_client_id';
    const XML_PATH_OAUTH_CLIENT_SECRET               = 'createsend_general/api/oauth_client_secret';
    const XML_PATH_WEBHOOK_ENABLED                   = 'createsend_general/advanced/webhook_enabled';
    const XML_PATH_OAUTH_ACCESS_TOKEN                = 'createsend_general/api/oauth_access_token';
    const XML_PATH_OAUTH_ACCESS_TOKEN_EXPIRY_DATE    = 'createsend_general/api/oauth_access_token_expiry_date';
    const XML_PATH_OAUTH_REFRESH_TOKEN               = 'createsend_general/api/oauth_refresh_token';
    const XML_PATH_SUBSCRIBER_SYNC_ENABLED           = 'createsend_general/advanced/subscriber_synchronisation_enabled';
    const XML_PATH_LOGGING                           = 'createsend_general/advanced/logging';
    const XML_PATH_M_TO_CM_ATTRIBUTES                = 'createsend_general/advanced/customer_attributes';
    const XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES       = 'createsend_customers/wishlists/wishlist_product_attributes';
    const XML_PATH_MAX_WISHLIST_ITEMS                = 'createsend_customers/wishlists/max_wishlist_items';
    const XML_PATH_TRANSACTIONAL_EMAIL_ENABLED       = 'createsend_transactional/emails/transactional_email_enabled';
    const XML_PATH_EMAIL_RETENTION_DAYS              = 'createsend_transactional/emails/transactional_email_retention_days';

    protected $canLog = null;

    /**
     * @var  \Luma\Campaignmonitor\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $customerGroupFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $customerAttributes;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Luma\Campaignmonitor\Model\Config\CustomerAttributesFactory $customerAttributes,
        \Luma\Campaignmonitor\Logger\Logger $logger
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->customerFactory = $customerFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerAttributes = $customerAttributes;
        $this->logger = $logger;
    }
    /**
     * Logs all extension specific notices to a separate file
     *
     * @param string | array $message The message to log
     * @param int $level The log level (defined in the Zend_Log class)
     */
    public function log($message)
    {
        if ($this->canLog()) {
	        if ( is_array( $message ) ) {
		        $message = print_r( $message, true );
	        }
            $this->logger->info($message);
        }
    }

    /**
     * @return bool
     */
    public function canLog()
    {
        if ($this->canLog === null) {
            $this->canLog = $this->scopeConfig->getValue(self::XML_PATH_LOGGING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return $this->canLog;
    }

    /**
     * Returns the authentication method configuration value.
     * No need to trim as value comes from source model and not from user input.
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function isCronSynchroniseEnable()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SUBSCRIBER_SYNC_ENABLED);
    }

    /**
     * Returns the authentication method configuration value.
     * No need to trim as value comes from source model and not from user input.
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getAuthenticationMethod()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AUTHENTICATION_METHOD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns a sanitized version of the API key configuration value
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getApiKey($storeId)
    {
        return trim($this->scopeConfig->getValue(self::XML_PATH_API_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId));
    }

    /**
     * Returns a sanitized version of the API key Client ID configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getApiClientId($storeId)
    {
        return trim($this->scopeConfig->getValue(self::XML_PATH_API_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId));
    }

    /**
     * Returns a sanitized version of the list id configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getListId($storeId)
    {
        return urlencode(trim($this->scopeConfig->getValue(self::XML_PATH_LIST_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)));
    }

    /**
     * Returns a sanitized version of the list id configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getCustomFieldsConfig($storeId)
    {
        $fieldConfig = @unserialize($this->scopeConfig->getValue(self::XML_PATH_M_TO_CM_ATTRIBUTES, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId));
        if (is_array($fieldConfig)) {
            $linkedAttributes = call_user_func_array('array_merge_recursive', $fieldConfig);
            return array_unique($linkedAttributes['attributes']);
        }
        return [];
    }

    /**
     * Returns true if the configuration for the specified scope/scopeId is complete.
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function isCompleteConfig($storeId)
    {
        $reply = true;
        $apiKey = $this->getApiKey($storeId);
        $listId = $this->getListId($storeId);
        if (!$apiKey || !$listId) {
            $reply = false;
        }
        return $reply;
    }

    /**
     * Generate an array of custom fields based on a config setting and customer data.
     * Customer data includes purchase and wish list products data.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    public function getCustomFieldsData($customer)
    {
        $customFields = [];
        if ($customer->getId()) {
            $storeId = $customer->getStoreId();
            $linkedAttributes = $this->getCustomFieldsConfig($storeId);
            $customFields = [];
            if (count($linkedAttributes) > 0) {
                $attrMapping = $this->customerAttributes->create();
                foreach ($linkedAttributes as $attr) {
                    switch ($attr) {
                        case 'group_id':
                            $group = $this->customerGroupFactory->create()->load($customer->getGroupId())->getData();
                            $customFields[] = array("Key" => $attrMapping->getCustomFieldName($attr, true), "Value" => $group['customer_group_code']);
                            break;
                        case 'gender':
                            $gender = $customer->getAttribute($attr)->getSource()->getOptionText($customer->getData($attr));
                            $customFields[] = array("Key" => $attrMapping->getCustomFieldName($attr, true), "Value" => $gender);
                            break;
                        default:
                            $customFields[] = array("Key" => $attrMapping->getCustomFieldName($attr, true), "Value" => $customer->getData($attr));
                            break;
                    }
                }
            }
        }
        return $customFields;
    }

    /**
     * Lists all Magento subscribers and returns the list in an array compatible with
     * the Campaign Monitor API.
     *
     * @param int $storeId
     * @return array
     */
    public function getSubscribers($storeId)
    {
        $listData = [];

        /** @var \Magento\Newsletter\Model\Subscriber $subscribers */
        $subscribers = $this->subscriberFactory->create();

        $collection = $subscribers->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('subscriber_status', \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);

        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        foreach ($collection as $subscriber) {
            $email = $subscriber->getSubscriberEmail();

            $subscriberData['Name'] = "";
            $subscriberData['EmailAddress'] = $email;

            $websiteId = $this->storeManager->getStore($subscriber->getStoreId())->getWebsiteId();

            /* @var Mage_Customer_Model_Customer $customer */
            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
            if ($customer->getId()) {
                $subscriberData['Name'] = $customer->getName();
                $subscriberData['CustomFields'] = $this->getCustomFieldsData($customer);
            }

            $listData[] = $subscriberData;
        }

        return $listData;
    }

    /**
     * Lists all Magento subscribers and returns the list in an array compatible with
     * the Campaign Monitor API.
     *
     * @param int $storeId
     * @return array
     */
    public function updateSubscriberListId($email, $listId, $isSubscribe = true)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('newsletter_subscriber');
        $select = $connection->select()->from($table, ['cm_listid'])->where("subscriber_email = ?", $email);
        $cm_listid = array_filter(explode(',', $connection->fetchOne($select)));

        if($isSubscribe == true){
            $cm_listid[] = $listId;
        } else {
            $cm_listid = array_diff($cm_listid,[$listId]);
        }

        $data = ['cm_listid' => implode(',', array_unique($cm_listid))];
        $where = ['subscriber_email = ?' => $email];
        $connection->update($table, $data, $where);
    }
}
