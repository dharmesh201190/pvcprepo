<?php

namespace StripeIntegration\Payments\Api;

use StripeIntegration\Payments\Api\ServiceInterface;
use StripeIntegration\Payments\Helper\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\CouldNotSaveException;

class Service implements ServiceInterface
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \StripeIntegration\Payments\Helper\ExpressHelper
     */
    private $expressHelper;

    /**
     * @var \StripeIntegration\Payments\Helper\Generic
     */
    private $stripeHelper;

    /**
     * @var \StripeIntegration\Payments\Model\Config
     */
    private $config;

    /**
     * @var \StripeIntegration\Payments\Model\StripeCustomer
     */
    private $stripeCustomer;

    /**
     * @var ServiceInputProcessor
     */
    private $inputProcessor;

    /**
     * @var \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory
     */
    private $estimatedAddressFactory;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    private $shippingMethodManager;

    /**
     * @var \Magento\Checkout\Api\ShippingInformationManagementInterface
     */
    private $shippingInformationManagement;

    /**
     * @var ShippingInformationInterfaceFactory
     */
    private $shippingInformationFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Service constructor.
     *
     * @param \Psr\Log\LoggerInterface                                     $logger
     * @param ScopeConfigInterface                                         $scopeConfig
     * @param StoreManagerInterface                                        $storeManager
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager
     * @param \Magento\Checkout\Model\Cart                                 $cart
     * @param \Magento\Checkout\Helper\Data                                $checkoutHelper
     * @param \Magento\Customer\Model\Session                              $customerSession
     * @param \Magento\Checkout\Model\Session                              $checkoutSession
     * @param \StripeIntegration\Payments\Helper\ExpressHelper             $expressHelper
     * @param \StripeIntegration\Payments\Helper\Generic                     $stripeHelper
     * @param \StripeIntegration\Payments\Model\Config                       $config
     * @param \StripeIntegration\Payments\Model\StripeCustomer               $stripeCustomer
     * @param ServiceInputProcessor                                        $inputProcessor
     * @param \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory      $estimatedAddressFactory
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface         $shippingMethodManager
     * @param \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
     * @param ShippingInformationInterfaceFactory                          $shippingInformationFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface                   $quoteRepository
     * @param CartManagementInterface                                      $quoteManagement
     * @param OrderSender                                                  $orderSender
     * @param ProductRepositoryInterface                                   $productRepository
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper,
        \StripeIntegration\Payments\Helper\Generic $stripeHelper,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer,
        ServiceInputProcessor $inputProcessor,
        \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManager,
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement,
        ShippingInformationInterfaceFactory $shippingInformationFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        CartManagementInterface $quoteManagement,
        OrderSender $orderSender,
        ProductRepositoryInterface $productRepository,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \StripeIntegration\Payments\Model\MobileDetect $detect
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->eventManager = $eventManager;
        $this->cart = $cart;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->expressHelper = $expressHelper;
        $this->stripeHelper = $stripeHelper;
        $this->config = $config;
        $this->stripeCustomer = $stripeCustomer;
        $this->inputProcessor = $inputProcessor;
        $this->estimatedAddressFactory = $estimatedAddressFactory;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformationFactory = $shippingInformationFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->productRepository = $productRepository;
        $this->paymentIntent = $paymentIntent;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->detect = $detect;;
    }


    public function chooseRedirectUrlBetween($externalUrl, $localUrl, $paymentMethod = null)
    {
        if ($paymentMethod == "stripe_payments_wechat" && !$this->detect->isMobile())
            return $localUrl;

        if (!empty($externalUrl))
            return $externalUrl;

        return $localUrl;
    }

    /**
     * Return URL
     * @return string
     */
    public function redirect_url()
    {
        $checkout = $this->checkoutHelper->getCheckout();
        $redirectUrl = $this->checkoutHelper->getCheckout()->getStripePaymentsRedirectUrl();
        $successUrl = $this->storeManager->getStore()->getUrl('checkout/onepage/success/');

        $lastRealOrderId = $checkout->getLastRealOrderId();
        if (empty($lastRealOrderId))
            return $this->chooseRedirectUrlBetween($redirectUrl, $successUrl);

        $order = $this->stripeHelper->loadOrderByIncrementId($lastRealOrderId);
        if (empty($order) || empty($order->getPayment()))
            return $this->chooseRedirectUrlBetween($redirectUrl, $successUrl);

        return $this->chooseRedirectUrlBetween($redirectUrl, $successUrl, $order->getPayment()->getMethod());
    }

    /**
     * Refunds any dangling PIs for the order and creates a new one for the checkout session
     *
     * @api
     * @return mixed Json object containing the new PI ID.
     */
    public function reset_payment_intent($status, $response)
    {
        if ($this->paymentIntent->isSuccessful() && !$this->paymentIntent->getDescription())
        {
            $quoteId = $this->stripeHelper->getQuote()->getId();
            $this->paymentIntent->fullRefund("duplicate", ["Status" => $status, "Response" => $response]);
            $this->paymentIntent->destroy($quoteId);
            $this->paymentIntent->create();
        }

        $clientSecret = $this->paymentIntent->getClientSecret();

        return \Zend_Json::encode([
            "paymentIntent" => $clientSecret
        ]);
    }

    public function payment_intent_refresh()
    {
        // We simply need to invalidate the local cache so that we don't try to update successful PIs
        $this->paymentIntent->isSuccessful();
        return \Zend_Json::encode([]);
    }

    /**
     * Return URL
     * @param mixed $address
     * @return string
     */
    public function estimate_cart($address)
    {
        try
        {
            $quote = $this->cart->getQuote();
            $rates = [];

            if (!$quote->isVirtual()) {
                // Set Shipping Address
                $shippingAddress = $this->expressHelper->getShippingAddress($address);
                $quote->getShippingAddress()
                      ->addData($shippingAddress)
                      ->save();

                // $quote->getShippingAddress()
                //     ->setCollectShippingRates(true);

                $this->quoteRepository->save($quote);
                $address = $quote->getShippingAddress();

                /** @var \Magento\Quote\Api\Data\EstimateAddressInterface $estimatedAddress */
                $estimatedAddress = $this->estimatedAddressFactory->create();
                $estimatedAddress->setCountryId($address->getCountryId());
                $estimatedAddress->setPostcode($address->getPostcode());
                $estimatedAddress->setRegion((string)$address->getRegion());
                $estimatedAddress->setRegionId($address->getRegionId());

                $rates = $this->shippingMethodManager->estimateByAddress($quote->getId(), $estimatedAddress);

                $this->cart->save();
            }

            $shouldInclTax = $this->expressHelper->shouldCartPriceInclTax($quote->getStore());
            $currency = $quote->getQuoteCurrencyCode();
            $result = [];
            foreach ($rates as $rate) {
                if ($rate->getErrorMessage()) {
                    continue;
                }

                $result[] = [
                    'id' => $rate->getCarrierCode() . '_' . $rate->getMethodCode(),
                    'label' => implode(' - ', [$rate->getCarrierTitle(), $rate->getMethodTitle()]),
                    //'detail' => $rate->getMethodTitle(),
                    'amount' => $this->expressHelper->getAmountCents($shouldInclTax ? $rate->getPriceInclTax() : $rate->getPriceExclTax(), $currency)
                ];
            }

            return \Zend_Json::encode([
                "paymentIntent" => $this->paymentIntent->create()->getClientSecret(),
                "results" => $result
            ]);
        }
        catch (\Exception $e)
        {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Apply Shipping Method
     *
     * @param mixed $address
     * @param string|null $shipping_id
     *
     * @return string
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function apply_shipping($address, $shipping_id = null)
    {
        if (count($address) === 0) {
            $address = $this->expressHelper->getDefaultShippingAddress();
        }

        $quote = $this->cart->getQuote();

        try {
            if (!$quote->isVirtual()) {
                // Set Shipping Address
                $shippingAddress = $this->expressHelper->getShippingAddress($address);
                $shipping = $quote->getShippingAddress()
                      ->addData($shippingAddress);

                if ($shipping_id) {
                    // Set Shipping Method
                    $shipping->setShippingMethod($shipping_id)
                             ->setCollectShippingRates(true)
                             ->collectShippingRates();

                    list($carrierCode, $methodCode) = explode('_', $shipping_id);

                    /** @var \Magento\Quote\Api\Data\AddressInterface $ba */
                    $shippingAddress = $this->inputProcessor->convertValue($shippingAddress, 'Magento\Quote\Api\Data\AddressInterface');

                    /** @var \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformation */
                    $shippingInformation = $this->shippingInformationFactory->create();
                    $shippingInformation
                        // ->setBillingAddress($shippingAddress)
                        ->setShippingAddress($shippingAddress)
                        ->setShippingCarrierCode($carrierCode)
                        ->setShippingMethodCode($methodCode);

                    $this->shippingInformationManagement->saveAddressInformation($quote->getId(), $shippingInformation);

                    // Update totals
                    $quote->setTotalsCollectedFlag(false);
                    $quote->collectTotals();

                    $this->quoteRepository->save($quote);
                }
            }

            $this->cart->save();

            $result = $this->expressHelper->getCartItems($quote);
            return \Zend_Json::encode([
                "paymentIntent" => $this->paymentIntent->create()->getClientSecret(),
                "results" => $result
            ]);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    public function set_billing_address($data)
    {
        try {
            $quote = $this->cart->getQuote();

            // Place Order
            $billingAddress = $this->expressHelper->getBillingAddress($data);

            // Set Billing Address
            $quote->getBillingAddress()
                  ->addData($billingAddress);

            $quote->setTotalsCollectedFlag(false);
            $quote->save();
        }
        catch (\Exception $e)
        {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }

        return \Zend_Json::encode([
            "paymentIntent" => $this->paymentIntent->create()->getClientSecret(),
            "results" => null
        ]);
    }

    /**
     * Place Order
     *
     * @param mixed $result
     *
     * @return string
     * @throws CouldNotSaveException
     */
    public function place_order($result)
    {
        $paymentMethod = $result['paymentMethod'];
        $paymentMethodId = $paymentMethod['id'];

        $quote = $this->cart->getQuote();

        try {
            // Create an Order ID for the customer's quote
            $quote->reserveOrderId()->save();

            // Place Order
            $billingAddress = $this->expressHelper->getBillingAddress($paymentMethod['billing_details']);

            // Set Billing Address
            $quote->getBillingAddress()
                  ->addData($billingAddress);

            if (!$quote->isVirtual()) {
                // Set Shipping Address
                $shippingAddress = $this->expressHelper->getShippingAddressFromResult($result);
                $shipping = $quote->getShippingAddress()
                                  ->addData($shippingAddress);

                // Set Shipping Method
                $shipping->setShippingMethod($result['shippingOption']['id'])
                         ->setCollectShippingRates(true);
            }

            // Update totals
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();

            if ($this->expressHelper->useStoreCurrency()) {
                $amount = $quote->getGrandTotal();
                $currency = $quote->getQuoteCurrencyCode();
            } else {
                $amount = $quote->getBaseGrandTotal();
                $currency = $quote->getBaseCurrencyCode();
            }

            // For multi-stripe account configurations, load the correct Stripe API key from the correct store view
            $this->storeManager->setCurrentStore($quote->getStoreId());
            $this->config->initStripe();

            // Set Checkout Method
            if (!$this->customerSession->isLoggedIn()) {
                // Use Guest Checkout
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST)
                      ->setCustomerId(null)
                      ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                      ->setCustomerIsGuest(true)
                      ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
            } else {
                $quote
                    ->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
            }

            $quote->getPayment()->setAdditionalInformation('token', $paymentMethodId);
            $quote->getPayment()->setAdditionalInformation('source_id', $paymentMethodId);

            if ($this->stripeCustomer->getStripeId()) {
                $quote->getPayment()->setAdditionalInformation('customer_stripe_id', $this->stripeCustomer->getStripeId());
                $quote->getPayment()->setAdditionalInformation('customer_email', $this->stripeCustomer->getCustomerEmail());
            }

            $quote->getPayment()->importData(['method' => 'stripe_payments']);

            // Save Quote
            $quote->save();

            // Place Order
            $this->paymentIntent->setPaymentMethod($paymentMethodId);
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->quoteManagement->submit($quote);
            if ($order) {
                $this->eventManager->dispatch(
                    'checkout_type_onepage_save_order_after',
                    ['order' => $order, 'quote' => $quote]
                );

                if ($order->getCanSendNewEmailFlag()) {
                    try {
                        $this->orderSender->send($order);
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                }

                $this->checkoutSession
                    ->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $this->eventManager->dispatch(
                'checkout_submit_all_after',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );

            return \Zend_Json::encode([
                'redirect' => $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => $this->stripeHelper->isSecure()])
            ]);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Add to Cart
     *
     * @param string $request
     * @param string|null $shipping_id
     *
     * @return string
     * @throws CouldNotSaveException
     */
    public function addtocart($request, $shipping_id = null)
    {
        $params = [];
        parse_str($request, $params);

        $productId = $params['product'];
        $related = $params['related_product'];

        if (isset($params['qty'])) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $objectManager->create('Magento\Framework\Locale\ResolverInterface')->getLocale()]
            );
            $params['qty'] = $filter->filter($params['qty']);
        }

        $quote = $this->cart->getQuote();

        try {
            // Get Product
            $storeId = $this->storeManager->getStore()->getId();
            $product = $this->productRepository->getById($productId, false, $storeId);

            $this->eventManager->dispatch(
                'stripe_payments_express_before_add_to_cart',
                ['product' => $product, 'request' => $request]
            );

            // Check is update required
            $isUpdated = false;
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    $item = $this->cart->updateItem($item->getId(), $params);
                    if ($item->getHasError()) {
                        throw new LocalizedException(__($item->getMessage()));
                    }

                    $isUpdated = true;
                    break;
                }
            }

            // Add Product to Cart
            if (!$isUpdated) {
                $item = $this->cart->addProduct($product, $params);
                if ($item->getHasError()) {
                    throw new LocalizedException(__($item->getMessage()));
                }

                if (!empty($related)) {
                    $this->cart->addProductsByIds(explode(',', $related));
                }
            }

            $this->cart->save();

            if ($shipping_id) {
                // Set Shipping Method
                if (!$quote->isVirtual()) {
                    // Set Shipping Method
                    $quote->getShippingAddress()->setShippingMethod($shipping_id)
                             ->setCollectShippingRates(true)
                             ->collectShippingRates();
                }
            }

            // Update totals
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();

            $result = $this->expressHelper->getCartItems($quote);
            return \Zend_Json::encode([
                "paymentIntent" => $this->paymentIntent->create()->getClientSecret(),
                "results" => $result
            ]);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Get Cart Contents
     *
     * @return string
     * @throws CouldNotSaveException
     */
    public function get_cart()
    {
        $quote = $this->cart->getQuote();

        try {
            $result = $this->expressHelper->getCartItems($quote);
            return \Zend_Json::encode($result);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }
}
