<?php

namespace Luma\Campaignmonitor\Observer;

class CustomerDeleted implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Luma\Campaignmonitor\Model\ApiFactory
     */
    protected $apiFactory;

    public function __construct(
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory
    ) {
        $this->apiFactory = $apiFactory;
    }

    /**
     * unsubscribes a user when delete customer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        try {
            $this->apiFactory->create()->unsubscribe($customer->getEmail(), $customer->getStoreId());
        } catch (\Exception $e) {
        }
    }
}
