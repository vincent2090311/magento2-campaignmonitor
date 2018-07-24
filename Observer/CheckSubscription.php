<?php

namespace Luma\Campaignmonitor\Observer;

class CheckSubscription implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

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

    public function __construct(
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->resource = $resource;
        $this->apiFactory = $apiFactory;
        $this->helperData = $helperData;
    }

    /**
     * Subscribes a new user when given a request event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @listen controller_action_predispatch_newsletter_subscriber_new
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $postdata = $request->getPostValue();
        $listId = $this->helperData->getListId($postdata['customer']['store_id']);
        try {
            if($postdata['subscription']){
                $this->apiFactory->create()->subscribe($postdata['customer']['email'], $postdata['customer']['store_id']);
                $this->helperData->updateSubscriberListId($postdata['customer']['email'], $listId);
            } else {
                $this->apiFactory->create()->unsubscribe($postdata['customer']['email'], $postdata['customer']['store_id']);
                $this->helperData->updateSubscriberListId($postdata['customer']['email'], $listId, false);
            }
        } catch (\Exception $e) {
            
        }
    }
}
