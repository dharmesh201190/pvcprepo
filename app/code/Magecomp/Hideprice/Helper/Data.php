<?php
namespace Magecomp\Hideprice\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_PATH_ENABLED = 'hideprice/main/enable';
	const XML_PATH_CUSTOMERGROUPS = 'hideprice/general/customer_groups';
	const XML_PATH_BUTTONTEXT	= 'hideprice/general/buttontext';
	const XML_PATH_CMS_PAGE_IDENTIFIRE	= 'hideprice/general/cmspage';	
	protected $_objectManager;
	protected $_categoryFactory;
	public function __construct(\Magento\Framework\App\Helper\Context $context,\Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Framework\Registry $registry,\Magento\Catalog\Model\CategoryFactory $categoryFactory)
	{
		$this->_objectManager = $objectManager;
		$this->registry = $registry;
		$this->_categoryFactory = $categoryFactory;
		parent::__construct($context);
	}
	public function isEnabled()
	{
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
	public function getButtontext()
	{
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTONTEXT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
	public function getCmspagelink()
	{
        return $this->scopeConfig->getValue(
            self::XML_PATH_CMS_PAGE_IDENTIFIRE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
	public function isValidCustomerGroup()
	{
		$customerGroups = explode(",", $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMERGROUPS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$customerSession = $this->_objectManager->create('Magento\Customer\Model\Session');
		$customerGroupId = $customerSession->getCustomerGroupId();
		if (in_array($customerGroupId, $customerGroups)) {
			return true;
		}
        return false;
	}
	public function getHideprice($product_id)
	{
		unset($options);
		$options = array();
		if($this->isEnabled())
		{
			if($this->isValidCustomerGroup())
			{
					$options['button_text'] = $this->getButtontext();
					return $options;
			}
		}
		return $options;
	}
}