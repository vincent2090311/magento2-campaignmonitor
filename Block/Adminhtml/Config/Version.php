<?php

namespace Luma\Campaignmonitor\Block\Adminhtml\Config;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    const MODULE_NAME = "Luma_Campaignmonitor";

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->getModuleVersion();
    }

    public function getModuleVersion()
    {        
        $moduleInfo = $this->moduleList->getOne(self::MODULE_NAME);
        return $moduleInfo['setup_version'];
    }
}