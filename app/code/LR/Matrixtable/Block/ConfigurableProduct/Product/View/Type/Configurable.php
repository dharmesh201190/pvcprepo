<?php
namespace LR\Matrixtable\Block\ConfigurableProduct\Product\View\Type;

use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Format;

class Configurable 
{

    protected $jsonEncoder;
    protected $jsonDecoder;

    /**
     * @var Format
     */
    protected $localeFormat;
    protected $_priceCurrency;

    public function __construct(
    EncoderInterface $jsonEncoder, DecoderInterface $jsonDecoder, Format $localeFormat = null, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    )
    {

        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(Format::class);
        $this->_priceCurrency = $priceCurrency;
    }
    
    public function aroundGetJsonConfig(
    \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject, \Closure $proceed
    )
    {
        $config = $proceed();
        $config = $this->jsonDecoder->decode($config);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $registry = $objectManager->get('\Magento\Framework\Registry');
        $currentProduct = $registry->registry('current_product');
        if (!empty($currentProduct)) {
            
            /* Attribute Description start */
            $attributeData = $currentProduct->getTypeInstance()->getConfigurableAttributes($currentProduct);
            $attributeDescription = array();
            $eavModel = $objectManager->create('Magento\Catalog\Model\ResourceModel\Eav\Attribute');
            foreach($attributeData as $attdata){
                $attr = $eavModel->load($attdata['attribute_id']);
                $attributeDescription[$attdata['attribute_id']] = $attr->getDescription();
            }
            /* Attribute Description end */
            
            $data = $currentProduct->getTypeInstance()->getConfigurableOptions($currentProduct);
            //$repository = $objectManager->get('Magento\Catalog\Model\ProductRepository');

            //$repository = $objectManager->get('\Magento\Catalog\Model\ProductFactory')->create();

            foreach ($data as $attr) {                
                foreach ($attr as $p) {                                   
                    $optionsConfig[$p['sku']][$p['attribute_code']] = $p['value_index'];                    
                }
            }

            $simpleProductArray = array();
            foreach ($optionsConfig as $sku => $d) {                
                $pr = $objectManager->get('\Magento\Catalog\Model\ProductFactory')->create();
                $pr = $pr->loadByAttribute('sku', $sku);                
                
                //$pr = $repository->get($sku);
                $simpleProductArray[$pr->getId()]['price'] = $this->_priceCurrency->getCurrency()->getCurrencySymbol() . $this->localeFormat->getNumber($pr->getPrice());                
                $simpleProductArray[$pr->getId()]['print_qty'] = $pr->getAttributeText("print_qty");
                $simpleProductArray[$pr->getId()]['qty_attr'] = $pr->getPrintQty();
            }           
            

            $_children = $currentProduct->getTypeInstance()->getUsedProducts($currentProduct);
            $childProductIds = array();
            foreach ($_children as $child) {
                $childProductIds[] = $child->getSku();
            }

            $productCollection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
            $collection = $productCollection->create()
                ->addAttributeToSelect('recommended_product')
                ->addFieldToFilter('recommended_product', 1)
                ->addFieldToFilter(
                'sku', ['in' => $childProductIds]
            );
            $recommendedArray = array();
            foreach ($collection as $productData) {
                $recommendedArray = $optionsConfig[$productData->getSku()];
            }     

            /*$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/config_simple.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($simpleProductArray, true));*/
            
            $config['simpleProductPrice'] = $simpleProductArray;
            $config['currency'] = $this->_priceCurrency->getCurrency()->getCurrencySymbol();
            $config['recommended'] = $recommendedArray;
            $config['description'] = $attributeDescription;
        }

        return $this->jsonEncoder->encode($config);
    }
}
