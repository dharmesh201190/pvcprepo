<?php
namespace Magecomp\Hideprice\Model\Config\Source;
use \Psr\Log\LoggerInterface;

class Cms implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\Locale\ListsInterface
     */
        
    public function __construct(LoggerInterface $logger){
		$this->logger = $logger;
	}
    public function toOptionArray()
    {
         /**
     * @return array
     */
	 $res = array();
	  $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$collection = $objectManager->get('\Magento\Cms\Model\ResourceModel\Page\CollectionFactory')->create();
	// add Filter if you want 
		$collection->addFieldToFilter('is_active' , \Magento\Cms\Model\Page::STATUS_ENABLED);
		$data['value'] = 'customer/account/login/';
        $data['label'] = 'Login Page';
	    $res[] = $data;
		foreach($collection as $page){
		   $data['value'] = $page->getData('identifier');
           $data['label'] = $page->getData('title');
		   $res[] = $data;
		}
		return $res;
    }
}