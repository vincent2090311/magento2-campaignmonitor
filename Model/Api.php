<?php

namespace Luma\Campaignmonitor\Model;

class Api extends CampaignMonitor
{
    /**
     * Gets all the Campaign Monitor clients for the given scope/scopeId for use in an HTML select.
     * The first option will be a 'Select Client...' prompt.
     *
     * On API Error, returns a single element array with key 'value' => 'error'
     *
     * @param string $scope
     * @param int $storeId
     * @return array|null
     */
    public function getClients($storeId)
    {
        $reply = $this->call(
            \Zend_Http_Client::GET,
            "clients",
            [],
            [],
            $storeId
        );

        $clients = [];
        if ($reply['success'] === false) {
            $this->helperData->log($reply);

            $clients[] = [
                'value'     => 'error',
                'label'     => self::LABEL_ENTER_YOUR_API_KEY,
                'message'   => sprintf(self::ERR_API_REQUEST, $reply['data']['Message'])
            ];
        } else {
            $clients[] = [
                'value' => '',
                'label' => self::LABEL_SELECT_CLIENT
            ];

            foreach ($reply['data'] as $client) {
                $clients[] = [
                    'value'  => $client['ClientID'],
                    'label'  => $client['Name']
                ];
            }
        }

        return $clients;
    }

    /**
     * Gets all the Campaign Monitor subscriber lists for the given clientId
     * using credentials from given scope/scopeId for use in an HTML select.
     * The last option will be a 'Create a new list' option
     *
     * On API Error, returns a single element array with key 'value' => 'error'
     *
     * @param string $clientId
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function getLists($storeId)
    {
        $clientId = $this->helperData->getApiClientId($storeId);

        $reply = $this->call(
            \Zend_Http_Client::GET,
            "clients/{$clientId}/lists",
            [],
            [],
            $storeId
        );

        return $reply;
    }

    /**
     * Gets the Campaign Monitor list details for the given listId
     * using credentials from given scope/scopeId for use in an HTML select.
     *
     * On API Error, returns a single element array with key 'value' => 'error'
     *
     * @param string $listId
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function getListDetails($storeId)
    {
        $listId = $this->helperData->getListId($storeId);

        $reply = $this->call(
            \Zend_Http_Client::GET,
            'lists/' . $listId,
            [],
            [],
            $storeId
        );

        return $reply;
    }

    /**
     * Subscribes an email address to CM. The list ID will be retrieved from the configuration using the scope/scopeId.
     *
     * @param string $email The email address to subscribe
     * @param string $scope
     * @param int $storeId
     * @return array|null
     */
    public function subscribe($email, $storeId)
    {
        $subscriberData = [
            'EmailAddress' => $email,
            'Resubscribe' => true,
            'RestartSubscriptionBasedAutoresponders' => true
        ];

        $listId = $this->helperData->getListId($storeId);
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        /* @var Mage_Customer_Model_Customer $customer */
        $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
        if ($customer->getId()) {
            $subscriberData['Name'] = $customer->getName();
        }

        $subscriberData['CustomFields'] = $this->helperData->getCustomFieldsData($customer);

        $reply = $this->call(
            \Zend_Http_Client::POST,
            'subscribers/' . $listId,
            $subscriberData,
            [],
            $storeId
        );

        return $reply;
    }

    /**
     * Un-subscribes an email address from CM list of scope/scopeId
     *
     * @param string $email The email to un-subscribe from CM
     * @param string $scope
     * @param int $storeId
     * @return array|null
     */
    public function unsubscribe($email, $storeId)
    {
        $listId = $this->helperData->getListId($storeId);

        $reply = $this->call(
            \Zend_Http_Client::POST,
            "subscribers/{$listId}/unsubscribe",
            ['EmailAddress' => $email],
            [],
            $storeId
        );

        return $reply;
    }

    /**
     * Creates Campaign Monitor customer custom fields on the list id defined in the scope.
     *
     * @param string $scope
     * @param int $scopeId
     * @return array List of errors, grouped by error message
     */
    public function createCustomerCustomFields($storeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Config_CustomerAttributes $attrSource */
        $attrSource = $this->customerAttributes->create();

        $errors = [];
        $linkedAttributes = $this->helperData->getCustomFieldsConfig($storeId);
        foreach ($linkedAttributes as $attr) {
            $fieldName = $attrSource->getCustomFieldName($attr, true);
            $dataType = $attrSource->getFieldType($attr);
            $options = $attrSource->getFieldOptions($attr);

            $params = [
                'FieldName'                 => $fieldName,
                'DataType'                  => $dataType,
                'VisibleInPreferenceCenter' => true,
            ];
            if (is_array($options) && count($options) > 0){
                $params['Options'] = $options;
            }

            $listId = $this->helperData->getListId($storeId);

            $reply = $this->call(
                \Zend_Http_Client::POST,
                "lists/{$listId}/customfields",
                $params,
                [],
                $storeId
            );

            if ($reply['success'] === false) {
                // Ignore 'field name already exists' errors
                if ($reply['data']['Code'] != \Luma\Campaignmonitor\Model\CampaignMonitor::CODE_FIELD_KEY_EXISTS) {
                    $message = $reply['data']['Message'];
                    $errors[$fieldName] = $message;
                }
            }
        }

        return $errors;
    }

    /**
     * Lists all Campaignmonitor subscribers and returns the list
     *
     * @param int $storeId
     * @return array
     */
    public function getCampaignmonitorSubscribers($date, $storeId)
    {
        $listId = $this->helperData->getListId($storeId);

        $reply = $this->call(
            \Zend_Http_Client::GET,
            "lists/{$listId}/active",
            [],
            ['date' => $date],
            $storeId
        );

        return $reply;
    }

    /**
     * Lists all Campaignmonitor Unsubscribed subscribers and returns the list
     *
     * @param int $storeId
     * @return array
     */
    public function getCampaignmonitorUnsubscribedSubscribers($date, $storeId)
    {
        $listId = $this->helperData->getListId($storeId);

        $reply = $this->call(
            \Zend_Http_Client::GET,
            "lists/{$listId}/unsubscribed",
            [],
            ['date' => $date],
            $storeId
        );

        return $reply;
    }

    /**
     * Lists all Campaignmonitor Deleted subscribers and returns the list
     *
     * @param int $storeId
     * @return array
     */
    public function getCampaignmonitorDeletedSubscribers($date, $storeId)
    {
        $listId = $this->helperData->getListId($storeId);

        $reply = $this->call(
            \Zend_Http_Client::GET,
            "lists/{$listId}/deleted",
            [],
            ['date' => $date],
            $storeId
        );

        return $reply;
    }

    /**
     * Performs an initial full subscriber sync from Magento to Campaign Monitor
     * for a particular store. The check for already synchronized list should be
     * done by the caller.
     *
     * @param $storeId
     * @return array|bool|null
     */
    public function performFullSync($storeId)
    {
        $listId = $this->helperData->getListId($storeId);
        $listData = $this->helperData->getSubscribers($storeId);

        $index = 0;
        do {
            $partialData = array_slice($listData, $index * self::MAX_CM_SUBSCRIBER_IMPORT, self::MAX_CM_SUBSCRIBER_IMPORT);
            $reply = $api->call(
                \Zend_Http_Client::POST,
                "subscribers/{$listId}/import",
                [
                    'Subscribers'                            => $partialData,
                    'Resubscribe'                            => false,
                    'QueueSubscriptionBasedAutoResponders'   => false,
                    'RestartSubscriptionBasedAutoresponders' => true
                ],
                [],
                $storeId
            );
        } while (
            $reply['success'] !== false
            &&
            count($listData) > (($index++ * self::MAX_CM_SUBSCRIBER_IMPORT) + self::MAX_CM_SUBSCRIBER_IMPORT)
        );

        return $reply;
    }
}