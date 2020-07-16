<?php
namespace LR\Serviceupgrade\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Catalog\Model\StoreManager
     */
    protected $storeManager;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Registry $registry     
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Magento\Framework\Registry $registry,        
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->sessionFactory = $sessionFactory;
        $this->registry = $registry;        
        $this->storeManager = $storeManager;
    }
    
    /**
     * @return string
     */
    public function submitformurl()
    {
        $submitformurl = $this->storeManager->getStore()->getBaseUrl() . 'serviceupgrade/index/submit';
        return $submitformurl;
    }

    public function countWeekendDays($start, $end)
    {
        // $start in timestamp
        // $end in timestamp
        $iter = 86400; // whole day in seconds
        $count = 0; // keep a count of Sats & Suns

        for($i = $start; $i <= $end; $i=$i+$iter)
        {
            /*echo 'day == '.Date('D',$i);
            echo '<br/>';*/
            if(Date('D',$i) == 'Sat' || Date('D',$i) == 'Sun')
            {
                $count++;
            }
        }
        return $count;
   }
}
