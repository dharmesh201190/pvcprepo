<?php
namespace LR\Serviceupgrade\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    public function __construct(
    Context $context, PageFactory $resultPageFactory,
    \LR\Serviceupgrade\Helper\Data $lr_helper,
    \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    )
    {
        $this->lr_helper = $lr_helper;
        $this->_resultPageFactory = $resultPageFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $requestedPrice = $this->getRequest()->getParam("price");
        $requestedSku = $this->getRequest()->getParam("sku");
        $selectedvalues = $this->getRequest()->getParam("selectedvalues");

        $resultarray['attachment'] = '';
        if($selectedvalues){
            parse_str($selectedvalues, $attributeoptions);
            if($attributeoptions){
                if(isset($attributeoptions['super_attribute']) && !empty($attributeoptions['super_attribute'])) {
                    $attributesInfo = $attributeoptions['super_attribute'];
                    $product = $this->productRepository->get($requestedSku);
                    $simpleproduct = $product->getTypeInstance(true)->getProductByAttributes($attributesInfo, $product);
                    $resultPage = $this->_resultPageFactory->create();
                    $blockhtml = $resultPage->getLayout()->createBlock('Prince\Productattach\Block\Attachment')->setProductId($simpleproduct->getId())->setTemplate('Prince_Productattach::attachment_all.phtml')->toHtml();
                    $resultarray['attachment'] = $blockhtml;
                }
            }
        }


        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');   
        $flagVar = false;
        if ($requestedSku) {
            $Serviceupgrade = $objectManager->create('LR\Serviceupgrade\Model\ResourceModel\Serviceupgrade\Collection');
            $Serviceupgrade->addFieldToFilter('sku', $requestedSku);
            if (count($Serviceupgrade) == 0) {
                $Serviceupgrade = $objectManager->create('LR\Serviceupgrade\Model\ResourceModel\Serviceupgrade\Collection');
                $Serviceupgrade->addFieldToFilter('range_min_price', array('lteq' => $requestedPrice));
                $Serviceupgrade->addFieldToFilter('range_max_price', array('gteq' => $requestedPrice));
                $flagVar = true;
            }
            $holidays = $objectManager->create('LR\Serviceupgrade\Model\ResourceModel\Holiday\Collection');
            $currentdate = strtotime(date('Y-m-d'));
            $htmlString = '';
            if (count($Serviceupgrade)) {
                foreach ($Serviceupgrade as $serviceData) {
                    if ($flagVar) {
                        if ($serviceData->getSku() != '') {
                            continue;
                        }
                    }

                    $htmlString = $htmlString . '<div class="service-upgrade-options';
                    if ($serviceData->getRecommended()) {
                        $htmlString = $htmlString . " recommended";
                    }
                    $htmlString = $htmlString . '">';
                    $calculatedPrice = ($requestedPrice * $serviceData->getShippingPricePercent()) / 100;
                    
                    $finalPrice = $requestedPrice + $calculatedPrice;
                    $formattedCurrencyValue = $priceHelper->currency($finalPrice, true, false);
                    $formatedCalculatedPrice = $priceHelper->currency($calculatedPrice, true, false);

                    $shippingdays = $serviceData->getShippingDays();
                    $deliverydate = strtotime("+".$shippingdays." days", $currentdate);
                    if($holidays->count() > 0 && $shippingdays > 0){
                        $holidays_dates = array();
                        foreach ($holidays as $key => $value) {
                            $holiday_date =  strtotime($value['date']);
                            $holidays_dates[] = date('F',$holiday_date); 
                            if($currentdate <= $holiday_date && $holiday_date <= $deliverydate && Date('D',$holiday_date) != 'Sun' && Date('D',$holiday_date) != 'Sat'){
                                // && Date('D',$holiday_date) != 'Sun' && Date('D',$holiday_date) != 'Sat'
                                $shippingdays += 1;
                            }
                        }
                    }
                    $weekenddays = $this->lr_helper->countWeekendDays($currentdate,strtotime("+".$shippingdays." days", $currentdate));
                    $shippingdays += $weekenddays;

                    $day = Date("D",strtotime("+".$shippingdays." days", $currentdate));
                    if($day=='Sat'){
                        $shippingdays += 2;
                    } else if($day=='San'){
                        $shippingdays += 2;
                    }
                    
                    $shippinglabel = str_replace('{{days}}',$shippingdays,$serviceData->getShippingLable());
                    $optionDate = date("d/m/Y",strtotime("+".$shippingdays." days", $currentdate));                    
                    
                    $htmlString = $htmlString . '<input type="radio" class="radio required-entry service-trigger" shippingLabel="' . $shippinglabel .
                        '" percent="' . $serviceData->getShippingPricePercent() . '" price="' . $formattedCurrencyValue . '" item-date="' . $optionDate .
                        '" name="delivery" data-validate="{\'validate-one-required-by-name\':true}" value=\'{ "lable": "' . $shippinglabel.' ( ' . $optionDate . ')", "price": "' .
                        $finalPrice . '"}\'>';
                    

                    $htmlString = $htmlString . '<div class="service-opt-title">';
                    if($shippingdays > 0){
                        $htmlString .= '<div style="font-size:12px;">Delivery Date: <div class="delivery-date">'.date("d/m/Y",strtotime("+".$shippingdays." days", $currentdate)).'</div></div>';
                    }
                    $htmlString .= $shippinglabel;
                    $htmlString .= '</div>';
                    $htmlString .= '<div class="service-opt-percent">'. $formatedCalculatedPrice . '</div>';
                    $htmlString = $htmlString . '</div>';
                }
                $resultarray['status'] = 'success';
                $resultarray['html'] = $htmlString;
            } else {
                $resultarray['status'] = 'failed';
            }
        } else {
            $resultarray['status'] = 'failed';
        }
        $resultJson->setData($resultarray);
        return $resultJson;
    }

    private function countWeekendDays($start, $end)
    {
        // $start in timestamp
        // $end in timestamp
        $iter = 86400; // whole day in seconds
        $count = 0; // keep a count of Sats & Suns

        for($i = $start; $i <= $end; $i=$i+$iter)
        {
            if(Date('D',$i) == 'Sat' || Date('D',$i) == 'Sun')
            {
                $count++;
            }
        }
        return $count;
   }

}
