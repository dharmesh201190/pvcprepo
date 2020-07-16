<?php

namespace LR\Matrixtable\Plugin;

use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Magento\Framework\Serialize\Serializer\Json;
class ToOrderItem
{
    private $serializer;
    public function __construct(Json $serializer = null) 
    {        
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }     

    public function aroundConvert(QuoteToOrderItem $subject, \Closure $proceed, $item, $data = [])
    {
        // Get Order Item
        $orderItem = $proceed($item, $data);
        // Get Quote Item's additional Options
        $additionalOptions = $item->getOptionByCode('additional_options');
        // Check if there is any additional options in Quote Item
        if(!empty($additionalOptions)){            
                // Get Order Item's other options
                $options = $orderItem->getProductOptions();
                // Set additional options to Order Item
                $options['additional_options'] =  $this->serializer->unserialize($additionalOptions->getValue());
                $orderItem->setProductOptions($options);            
        }
        return $orderItem;
    }
}

