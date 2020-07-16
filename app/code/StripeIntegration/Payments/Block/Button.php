<?php

namespace StripeIntegration\Payments\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use StripeIntegration\Payments\Helper\Logger;

class Button extends Template
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \StripeIntegration\Payments\Model\Config
     */
    protected $config;

    /**
     * @var \StripeIntegration\Payments\Helper\ExpressHelper
     */
    protected $expressHelper;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;

    /**
     * Button constructor.
     *
     * @param Template\Context                       $context
     * @param Registry                               $registry
     * @param PriceCurrencyInterface                 $priceCurrency
     * @param \StripeIntegration\Payments\Model\Config $config
     * @param \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper
     * @param \Magento\Checkout\Helper\Data          $checkoutHelper
     * @param \Magento\Tax\Helper\Data               $taxHelper
     * @param \Magento\Framework\Locale\Resolver     $localeResolver
     * @param array                                  $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Helper\Generic $paymentsHelper,
        \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Framework\Locale\Resolver $localeResolver,
        array $data = []
    ) {

        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->priceCurrency = $priceCurrency;
        $this->config = $config;
        $this->expressHelper = $expressHelper;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->checkoutHelper = $checkoutHelper;
        $this->taxHelper = $taxHelper;
        $this->localeResolver = $localeResolver;
        $this->paymentsHelper = $paymentsHelper;
    }

    /**
     * Check Is Block enabled
     * @return bool
     */
    public function isEnabled($location)
    {
        return $this->expressHelper->isEnabled($location);
    }

    /**
     * Get Publishable Key
     * @return string
     */
    public function getPublishableKey()
    {
        return $this->config->getPublishableKey();
    }

    /**
     * Get Button Config
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getButtonConfig()
    {
        return [
            'type' => $this->expressHelper->getStoreConfig('payment/stripe_payments_express/button_type'),
            'theme' => $this->expressHelper->getStoreConfig('payment/stripe_payments_express/button_theme'),
            'height' => $this->expressHelper->getStoreConfig('payment/stripe_payments_express/button_height')
        ];
    }

    /**
     * Get Payment Request Params
     * @return array
     */
    public function getApplePayParams()
    {
        if ($this->paymentsHelper->hasSubscriptions())
            return null;

        return array_merge(
            [
                'country' => $this->getCountry(),
                'requestPayerName' => true,
                'requestPayerEmail' => true,
                'requestPayerPhone' => true,
                'requestShipping' => !$this->getQuote()->isVirtual(),
            ],
            $this->expressHelper->getCartItems($this->getQuote())
        );
    }

    /**
     * Get Payment Request Params for Single Product
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductApplePayParams()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('product');

        if (!$product || $product->getCryozonicSubEnabled()) {
            return [];
        }

        $quote = $this->getQuote();

        $currency = $quote->getQuoteCurrencyCode();
        if (empty($currency)) {
            $currency = $quote->getStore()->getCurrentCurrency()->getCode();
        }

        // Get Current Items in Cart
        $params = $this->expressHelper->getCartItems($quote);
        $amount = $params['total']['amount'];
        $items = $params['displayItems'];

        $shouldInclTax = $this->expressHelper->shouldCartPriceInclTax($quote->getStore());
        if ($this->expressHelper->getStoreConfig('payment/stripe_payments/use_store_currency')) {
            $convertedFinalPrice = $this->priceCurrency->convertAndRound(
                $product->getFinalPrice(),
                null,
                $currency
            );

            $price = $this->expressHelper->getProductDataPrice(
                $product,
                $convertedFinalPrice,
                $shouldInclTax,
                $quote->getCustomerId(),
                $quote->getStore()->getStoreId()
            );
        } else {
            $price = $this->expressHelper->getProductDataPrice(
                $product,
                $product->getFinalPrice(),
                $shouldInclTax,
                $quote->getCustomerId(),
                $quote->getStore()->getStoreId()
            );
        }

        // Append Current Product
        $productTotal = $this->expressHelper->getAmountCents($price, $currency);
        $amount += $productTotal;

        $items[] = [
            'label' => $product->getName(),
            'amount' => $productTotal,
            'pending' => false
        ];

        return [
            'country' => $this->getCountry(),
            'currency' => strtolower($currency),
            'total' => [
                'label' => $this->getLabel(),
                'amount' => $amount,
                'pending' => true
            ],
            'displayItems' => $items,
            'requestPayerName' => true,
            'requestPayerEmail' => true,
            'requestPayerPhone' => true,
            'requestShipping' => $this->expressHelper->shouldRequestShipping($quote, $product),
        ];
    }

    /**
     * Get Quote
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quote = $this->checkoutHelper->getCheckout()->getQuote();
        if (!$quote->getId()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $quote = $objectManager->create('Magento\Checkout\Model\Session')->getQuote();
        }

        return $quote;
    }

    /**
     * Get Country Code
     * @return string
     */
    public function getCountry()
    {
        $countryCode = $this->getQuote()->getBillingAddress()->getCountryId();
        if (empty($countryCode)) {
            $countryCode = $this->expressHelper->getDefaultCountry();
        }
        return $countryCode;
    }

    /**
     * Get Label
     * @return string
     */
    public function getLabel()
    {
        return $this->expressHelper->getLabel($this->getQuote());
    }
}
