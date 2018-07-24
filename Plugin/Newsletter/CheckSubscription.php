<?php

namespace Luma\Campaignmonitor\Plugin\Newsletter;

class CheckSubscription
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Luma\Campaignmonitor\Model\ApiFactory
     */
    protected $apiFactory;

    /**
     * @var \Luma\Campaignmonitor\Helper\Data
     */
    protected $helperData;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Luma\Campaignmonitor\Model\ApiFactory $apiFactory
     * @param \Luma\Campaignmonitor\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resource,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
        $this->customerSession = $customerSession;
        $this->resource = $resource;
        $this->apiFactory = $apiFactory;
        $this->helperData = $helperData;
    }

    public function afterExecute(
        \Magento\Newsletter\Controller\Manage\Save $subject,
        $result
    ) {
        $customer = $this->customerSession->getCustomer();
        $listId = $this->helperData->getListId($customer->getStoreId());
        $is_subscribed = $subject->getRequest()->getParam('is_subscribed', false);
        if($is_subscribed){
            $this->apiFactory->create()->subscribe($customer->getEmail(), $customer->getStoreId());
            $this->helperData->updateSubscriberListId($customer->getEmail(), $listId);
        } else {
            $this->apiFactory->create()->unsubscribe($customer->getEmail(), $customer->getStoreId());
            $this->helperData->updateSubscriberListId($customer->getEmail(), $listId, false);
        }
    }
}