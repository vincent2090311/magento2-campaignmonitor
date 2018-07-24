<?php

namespace Luma\Campaignmonitor\Observer;

class SubscribeNewUser implements \Magento\Framework\Event\ObserverInterface
{
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
        \Magento\Framework\App\ResourceConnection $resource,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
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
        $subscriber = $observer->getEvent()->getSubscriber();
        if($subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED){
            $listId = $this->helperData->getListId($subscriber->getStoreId());
            try {
                $this->apiFactory->create()->subscribe($subscriber->getSubscriberEmail(), $subscriber->getStoreId());
                $this->helperData->updateSubscriberListId($subscriber->getSubscriberEmail(), $listId);
            } catch (\Exception $e) {
            }
        }
    }
}
