<?php
namespace LR\Matrixtable\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

class CheckoutCartAddObserver implements ObserverInterface
{

    protected $_layout;
    protected $_storeManager;
    protected $_request;
    private $_serializer;

    public function __construct(
    \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\View\LayoutInterface $layout, \Magento\Framework\App\RequestInterface $request, Json $serializer
    )
    {
        $this->_layout = $layout;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    public function execute(EventObserver $observer)
    {
        $params = $this->_request->getParams();
        
        /*$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($params, true));*/

               
        if (isset($params['delivery'])) {
            $jsonArray = json_decode($params['delivery'], true);
            $shippingLable = $jsonArray['lable'];

            ///// custom option add section
            $item = $observer->getQuoteItem();
            
            $additionalOptions = array();
            if ($additionalOption = $item->getOptionByCode('additional_options')) {
                $additionalOptions = (array) unserialize($additionalOption->getValue());
            }

            $additionalOptions[] = [
                'label' => 'Turnaround',
                'value' => $shippingLable
            ];

            //echo "<pre/>";
            //print_r($jsonArray);
            //print_r($additionalOptions);

            if (count($additionalOptions) > 0) {
                $item->addOption(array(
                    'product_id' => $item->getProductId(),
                    'code' => 'additional_options',
                    'value' => $this->_serializer->serialize($additionalOptions)
                ));
            }

            //// Price Update section

            $item = $observer->getEvent()->getData('quote_item');
            $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
            $finalPrice = $item->getProduct()->getFinalPrice();
            //echo "Product QTY: " . $item->getProduct()->getAttributeText("print_qty");exit;
            $shippingPrice = $jsonArray['price'];
            $price = $finalPrice + $shippingPrice;

            //$item->setCustomPrice($price);
            $item->setCustomPrice($shippingPrice);
            $item->setOriginalCustomPrice($shippingPrice);
            $item->getProduct()->setIsSuperMode(true);
        }
    }
}
