<?php

namespace Luma\Campaignmonitor\Model;

/**
 * Responsible for making calls and getting responses from the Campaign Monitor API.
 * Uses the key authorization mechanism (see the link for further information)
 *
 * @link https://www.campaignmonitor.com/api/getting-started/
 */
class CampaignMonitor
{
    const API_BASE_URL      = 'https://api.createsend.com/';
    const API_PATH          = 'api/v3.2/';
    const API_OAUTH_PATH    = 'oauth/';

    const CODE_SUBSCRIBER_NOT_IN_LIST = 203;
    const CODE_DUPLICATE_SEGMENT_TITLE  = 275;
    const CODE_INVALID_SEGMENT_RULES    = 277;
    const CODE_FIELD_KEY_EXISTS         = 255;

    const ERR_API_REQUEST         = 'API Error: %s';
    const ERR_INVALID_HTTP_METHOD = 'The method "%s" is not an acceptable HTTP method';
    const ERR_INVALID_AUTH_METHOD = 'The method "%s" is not an acceptable Authentication method';
    const ERR_INVALID_JSON        = 'The following response is not valid JSON: "%s"';
    const ERR_ID_REQUIRED         = 'The "%s" data is required to make this call';
    const ERR_NO_LIST_ID_AT_SCOPE = 'There is no list ID defined at this %s/%s scope. Cannot create webhook';
    const ERR_CANNOT_UPDATE_WEBHOOK   = 'API Error: Cannot update webhook (%s)';
    const ERR_CANNOT_LIST_WEBHOOKS    = 'API Error: Cannot list webhook (%s)';

    const ERR_CANNOT_CREATE_CUSTOM_FIELD    = 'API Error: Cannot create custom field: (%1$s): %2$s';
    const ERR_CREATE_CUSTOM_FIELDS          = 'Please create these custom fields on the Campaign Monitor list: %s';
    const ERR_SEGMENT_EXISTS            = 'A segment with the same title already exists on this list.';
    const ERR_UNABLE_TO_CREATE_SEGMENT  = 'Unable to create segment (%1$s): %2$s';
    const LOG_SEGMENT_CREATED           = 'Segment successfully created (%s).';

    const LOG_API_REQUEST           = 'API Request %s @ %s: %s';
    const LOG_API_RESPONSE          = 'API Response (%s) @ %s: %s';
    const LOG_CREATED_WEBHOOK       = 'Created webhook with ID "%s"';
    const LOG_DELETED_WEBHOOK       = 'Deleted webhook with ID "%s"';
    const LOG_WEBHOOKS_NOT_ENABLED  = 'Webhooks not enabled.';

    const WEBHOOK_EVENT_SUBSCRIBE   = 'Subscribe';
    const WEBHOOK_EVENT_UPDATE      = 'Update';
    const WEBHOOK_EVENT_DEACTIVATE  = 'Deactivate';

    const SUBSCRIBER_STATUS_ACTIVE      = 'Active';
    const SUBSCRIBER_STATUS_DELETED     = 'Deleted';
    
    const WEBHOOK_STATUS_ACTIVE         = 'Active';
    const WEBHOOK_STATUS_UNSUBSCRIBED   = 'Unsubscribed';
    const WEBHOOK_STATUS_DELETED        = 'Deleted';

    const WEBHOOK_PAYLOAD_FORMAT_JSON   = 'Json';

    const OAUTH_API_TOKEN_REQUEST       = 'oauth_token';

    const FIELD_TYPE_TEXT               = 'Text';
    const FIELD_TYPE_NUMBER             = 'Number';
    const FIELD_TYPE_DATE               = 'Date';
    const FIELD_TYPE_SELECT_ONE         = 'MultiSelectOne';
    const FIELD_TYPE_SELECT_MANY        = 'MultiSelectMany';
    const FIELD_TYPE_COUNTRY            = 'Country';

    const CM_MAX_CUSTOM_FIELD_LENGTH    = 100;
    const CM_CUSTOM_FIELD_PREFIX        = 'Magento ';

    const WISHLIST_CUSTOM_FIELD_PREFIX  = 'Wishlist Item';
    const WISHLIST_CUSTOM_FIELD_PATTERN = '%1$s %2$s %3$s';

    const USER_AGENT_STRING             = 'CM_Magento_Extension; Magento %s; Extension %s; List ID %s';

    const MAX_CM_SUBSCRIBER_IMPORT      = 1000;
    const LABEL_SELECT_CLIENT           = 'Select Client...';
    const LABEL_CREATE_NEW_LIST         = 'Create a new list in Campaign Monitor';
    const LABEL_ENTER_YOUR_API_KEY      = 'Enter your API Key';

    // Methods used by the API
    /** @var array */
    protected $_supportedMethods = [
        \Zend_Http_Client::DELETE,
        \Zend_Http_Client::GET,
        \Zend_Http_Client::POST,
        \Zend_Http_Client::PUT
    ];

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Luma\Campaignmonitor\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Luma\Campaignmonitor\Block\Adminhtml\Config\Version
     */
    protected $moduleVersion;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerAttributes;

    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData,
        \Luma\Campaignmonitor\Block\Adminhtml\Config\Version $moduleVersion,
        \Luma\Campaignmonitor\Model\Config\CustomerAttributesFactory $customerAttributes
    ) {
        $this->productMetadata = $productMetadata;
        $this->jsonHelper = $jsonHelper;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->helperData = $helperData;
        $this->moduleVersion = $moduleVersion;
        $this->customerAttributes = $customerAttributes;
    }

    /**
     * Calculate the version of Magento through a checking of arbitrary magento characteristics.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Responsible for providing an interface to the API and parsing the data returned from the API into a manageable
     * format.
     *
     * If the API returns a JSON object that is just a string that string will be in the [data][Message] key. This key
     * is also responsible for error messages if the API request fails as well as error messages the API returns.
     *
     * Returns an array of the form
     * [success] => bool
     * [status_code] => int
     * [data] => array
     *     [Message] => string
     *     [DataA] => Returned field
     *     [DataB] => Returned field
     *
     * @param string $method The HTTP method to use. Accepted methods are defined at the top of this class and constants
     *                       are available in the Zend_Http_Client class.
     * @param string $endpoint The API endpoint to query; for example: lists/1eax88123c7cedasdas70fd05saxqwbf
     * @param int $storeId The id of the store
     * @param array $postFields An array of fields to send the end point
     * @param array $queryParams An array of URI query parameters to append to the URI
     *
     * @return array|null
     */
    public function call($method, $endpoint, $storeId, $postFields = [], $queryParams = [])
    {
        /** @var array $data */
        $data = [
            'success' => false,
            'status_code' => null,
            'data' => null
        ];

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = $this->helperData;
        
        $authenticationMethod = $helper->getAuthenticationMethod();
        $apiKey = $helper->getApiKey($storeId);
        if (!$apiKey) {
            $data['data']['Message'] = 'No API key set in configuration';
            return $data;
        }

        $mageVersion = strtoupper($this->getMagentoVersion());
        $extVersion = $this->moduleVersion->getModuleVersion();
        $listId = $helper->getListId($storeId);

        $userAgent = sprintf(self::USER_AGENT_STRING, $mageVersion, $extVersion, $listId);

        $response = $this->_callApi($authenticationMethod, $apiKey, $method, $endpoint, $userAgent, $postFields, $queryParams);
        if (!$response) {
            $data['data']['Message'] = 'An error occurred during the request.';
            return $data;
        }

        $data['success'] = $response->isSuccessful();

        // Get the response content. The response will either be a JSON encoded object or nothing, depending on the
        // call. The only situation in which content is not JSON is a request URI without a .json file suffix.
        if (stripos($response->getHeader('content-type'), 'application/json') !== null) {
            try {
                $returnContent = $this->jsonHelper->jsonDecode($response->getBody());

                // The API sometimes returns a string, not an array.
                if (is_array($returnContent)) {
                    $data['data'] = $returnContent;
                } else {
                    $data['data'] = ['Message' => $returnContent];
                }
            } catch (\Exception $e) {
                $helper->log(sprintf(self::ERR_INVALID_JSON, $response->getBody()));
            }
        }

        $data['status_code'] = $response->getStatus();
        return $data;
    }

    /**
     * Responsible for making the actual calls to the API. The authorization method is either "API Key" or "OAuth"
     * authorisation method, and documentation can be found at the link below.
     *
     * @link https://www.campaignmonitor.com/api/
     *
     * @param string $authenticationMethod The Authentication Method to be used for the request ('api_key' or 'oauth')
     * @param string $apiKeyOrToken The API key or OAuth Access Token required to make the API request
     * @param string $method The HTTP Method for the request. Valid methods are documented at the top of the class
     * @param string $endpoint The endpoint to make the request of
     * @param string $userAgent HTTP User-Agent to use
     * @param array $postFields The fields that should be posted to the end point
     * @param array $queryParams Any query params that should be appended to the request
     *
     * @return \Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException if the method given is not an accepted method
     * @throws \Zend_Http_Client_Exception if something goes wrong during the connection
     */
    protected function _callApi($authenticationMethod, $apiKey, $method, $endpoint, $userAgent, $postFields = [], $queryParams = [])
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = $this->helperData;

        if (in_array($method, $this->_supportedMethods) === false) {
            throw new \Magento\Framework\Exception\LocalizedException(sprintf(self::ERR_INVALID_HTTP_METHOD, $method));
        }

        // Construct the client
        $client = new \Zend_Http_Client();
        $client->setMethod($method);
        $client->setConfig(['useragent' => $userAgent]);

        $uri = self::API_BASE_URL . self::API_PATH . $endpoint . '.json';

        $client->setAuth($apiKey, null, \Zend_Http_Client::AUTH_BASIC);

        if (count($queryParams) > 0) {
            $uri .= '?' . http_build_query($queryParams);
        }
        $client->setUri($uri);

        $payload = $this->jsonHelper->jsonEncode($postFields);

        // Optionally set the POST payload
        if ($method === \Zend_Http_Client::POST) {
            $client->setRawData($payload, 'application/json');
        }

        // Log the request for debugging
        $helper->log(sprintf(self::LOG_API_REQUEST, $method, $client->getUri()->__toString(), $payload));

        try {
            $response = $client->request();
        } catch (\Zend_Http_Client_Exception $e) {
            $helper->log($e->getMessage());
            return false;
        }

        // Log response
        $helper->log(sprintf(self::LOG_API_RESPONSE, $response->getStatus(), $client->getUri()->__toString(), $response->getBody()));

        return $response;
    }
}