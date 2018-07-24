<?php

namespace Luma\Campaignmonitor\Controller\Adminhtml\Api;

class Test extends \Magento\Backend\App\Action
{
    const ADMINHTML_SYSTEM_CONFIG_EDIT  = 'adminhtml/system_config/edit';

    const ERR_API_CALL_ERROR            = 'API Test Error: %s';
    const LOG_API_CALL_SUCCESSFUL       = 'API Test Successful.';

    protected $state;
    protected $storeManager;
    protected $resultJsonFactory;
    protected $apiFactory;
 
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\State $state,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory
    ) {
        parent::__construct($context);
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiFactory = $apiFactory;
    }

    /**
     * Performs a test API call.
     *
     * @link https://www.campaignmonitor.com/api/
     *
     * @return \\Magento\Framework\App\Action\Action
     */
    public function execute()
    {
        $jsonData = ['status' => 'error','message' => self::ERR_API_CALL_ERROR];
        if(\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE == $this->state->getAreaCode()){
            $data = $this->getRequest()->getParams();
            if(array_key_exists(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $data)){
                $websiteId = $data[\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE];
                $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultGroup()->getDefaultStoreId();
            } elseif (array_key_exists(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $data)) {
                $storeId = $data[\Magento\Store\Model\ScopeInterface::SCOPE_STORE];
            } else {
                $storeId = 0;
            }

            $reply = $this->apiFactory->create()->getListDetails($storeId);
            if ($reply['success'] === false) {
                $jsonData = ['status' => 'error', 'message' => sprintf(self::ERR_API_CALL_ERROR, $reply['data']['Message'])];
            } else {
                $jsonData = ['status' => 'success', 'message' => self::LOG_API_CALL_SUCCESSFUL];
            }
        }
        return $this->resultJsonFactory->create()->setData($jsonData);
    }

	/**
	 * @return mixed
	 */
	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Luma_Campaignmonitor::general');
    }
}
