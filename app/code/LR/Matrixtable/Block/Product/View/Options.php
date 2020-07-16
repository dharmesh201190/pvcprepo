<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product options block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace LR\Matrixtable\Block\Product\View;

use Magento\Catalog\Model\Product;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Options extends \Magento\Catalog\Block\Product\View\Options
{
    /**
     * @var Product
     */
    protected $_product;

    /**
     * Product option
     *
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $_option;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry = null;

    /**
     * Catalog product
     *
     * @var Product
     */
    protected $_catalogProduct;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogData;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Model\Product\Option $option
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Product\Option $option,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        array $data = []
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->_catalogData = $catalogData;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_registry = $registry;
        $this->_option = $option;
        $this->arrayUtils = $arrayUtils;
        parent::__construct($context, $pricingHelper, $catalogData, $jsonEncoder, $option, $registry, $arrayUtils, $data);
    }

    /**
     * Retrieve product object
     *
     * @return Product
     * @throws \LogicExceptions
     */
    public function getProduct()
    {
        if (!$this->_product) {
            if ($this->_registry->registry('current_product')) {
                $this->_product = $this->_registry->registry('current_product');
            } else {
                throw new \LogicException('Product is not defined');
            }
        }
        return $this->_product;
    }

    /**
     * Set product object
     *
     * @param Product $product
     * @return \Magento\Catalog\Block\Product\View\Options
     */
    public function setProduct(Product $product = null)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getGroupOfOption($type)
    {
        $group = $this->_option->getGroupByType($type);

        return $group == '' ? 'default' : $group;
    }

    /**
     * Get product options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->getProduct()->getOptions();
    }

    /**
     * @return bool
     */
    public function hasOptions()
    {
        if ($this->getOptions()) {
            return true;
        }
        return false;
    }

    /**
     * Get price configuration
     *
     * @param \Magento\Catalog\Model\Product\Option\Value|\Magento\Catalog\Model\Product\Option $option
     * @return array
     */
    protected function _getPriceConfiguration($option)
    {
        $optionTitle = $option->getTitle();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        $a0Title = $scopeConfig->getValue('lr_predefine_size/a0/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a1Title = $scopeConfig->getValue('lr_predefine_size/a1/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a2Title = $scopeConfig->getValue('lr_predefine_size/a2/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a3Title = $scopeConfig->getValue('lr_predefine_size/a3/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a4Title = $scopeConfig->getValue('lr_predefine_size/a4/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a5Title = $scopeConfig->getValue('lr_predefine_size/a5/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a6Title = $scopeConfig->getValue('lr_predefine_size/a6/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a7Title = $scopeConfig->getValue('lr_predefine_size/a7/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $a2030Title = $scopeConfig->getValue('lr_predefine_size/a2030/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);



        if($option->getTitle() == "A0"){
            $optionTitle = $a0Title;
        }

        if($option->getTitle() == "A1"){
            $optionTitle = $a1Title;
        }

        if($option->getTitle() == "A2"){
            $optionTitle = $a2Title;
        }

        if($option->getTitle() == "A3"){
            $optionTitle = $a3Title;
        }

        if($option->getTitle() == "A4"){
            $optionTitle = $a4Title;
        }

        if($option->getTitle() == "A5"){
            $optionTitle = $a5Title;
        }

        if($option->getTitle() == "A6"){
            $optionTitle = $a6Title;
        }

        if($option->getTitle() == "A7"){
            $optionTitle = $a7Title;
        }

        if($option->getTitle() == "A2030"){
            $optionTitle = $a2030Title;
        }

        $optionPrice = $this->pricingHelper->currency($option->getPrice(true), false, false);
        $data = [
            'prices' => [
                'oldPrice' => [
                    'amount' => $this->pricingHelper->currency($option->getRegularPrice(), false, false),
                    'adjustments' => [],
                ],
                'basePrice' => [
                    'amount' => $this->_catalogData->getTaxPrice(
                        $option->getProduct(),
                        $optionPrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    ),
                ],
                'finalPrice' => [
                    'amount' => $this->_catalogData->getTaxPrice(
                        $option->getProduct(),
                        $optionPrice,
                        true,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    ),
                ],
            ],
            'type' => $option->getPriceType(),
            'name' => $optionTitle
        ];
        return $data;
    }

    /**
     * Get json representation of
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $config = [];
        foreach ($this->getOptions() as $option) {
            /* @var $option \Magento\Catalog\Model\Product\Option */
            if ($option->hasValues()) {
                $tmpPriceValues = [];
                foreach ($option->getValues() as $valueId => $value) {
                    $tmpPriceValues[$valueId] = $this->_getPriceConfiguration($value);
                }
                $priceValue = $tmpPriceValues;
            } else {
                $priceValue = $this->_getPriceConfiguration($option);
            }
            $config[$option->getId()] = $priceValue;
        }

        $configObj = new \Magento\Framework\DataObject(
            [
                'config' => $config,
            ]
        );

        //pass the return array encapsulated in an object for the other modules to be able to alter it eg: weee
        $this->_eventManager->dispatch('catalog_product_option_price_configuration_after', ['configObj' => $configObj]);

        $config=$configObj->getConfig();

        return $this->_jsonEncoder->encode($config);
    }

    /**
     * Get option html block
     *
     * @param \Magento\Catalog\Model\Product\Option $option
     * @return string
     */
    public function getOptionHtml(\Magento\Catalog\Model\Product\Option $option)
    {
        $type = $this->getGroupOfOption($option->getType());
        $renderer = $this->getChildBlock($type);

        $renderer->setProduct($this->getProduct())->setOption($option);

        return $this->getChildHtml($type, false);
    }

    /**
     * Decorate a plain array of arrays or objects
     *
     * @param array $array
     * @param string $prefix
     * @param bool $forceSetAll
     * @return array
     */
    public function decorateArray($array, $prefix = 'decorated_', $forceSetAll = false)
    {
        return $this->arrayUtils->decorateArray($array, $prefix, $forceSetAll);
    }
}
