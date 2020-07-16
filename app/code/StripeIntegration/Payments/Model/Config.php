<?php

namespace StripeIntegration\Payments\Model;

use StripeIntegration\Payments\Helper;
use StripeIntegration\Payments\Helper\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public static $moduleName           = "Magento2";
    public static $moduleVersion        = "1.1.2";
    public static $moduleUrl            = "https://stripe.com/magento";

    // active
    // title
    // stripe_mode
    // stripe_test_sk
    // stripe_mode
    // stripe_test_pk
    // stripe_mode
    // stripe_live_sk
    // stripe_mode
    // stripe_live_pk
    // stripe_mode
    // payment_action
    // expired_authorizations
    // payment_action
    // avs
    // ccsave
    // order_status
    // card_autodetect
    // cctypes
    // card_autodetect
    // receipt_email
    // allowspecific
    // specificcountry
    // sort_order

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Helper\Generic $helper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->encryptor = $encryptor;

        $this->initStripe();
    }

    public function initStripe()
    {
        \Stripe\Stripe::setApiVersion("2019-03-14");
        \Stripe\Stripe::setApiKey($this->getSecretKey());
        \Stripe\Stripe::setAppInfo($this::$moduleName, $this::$moduleVersion, $this::$moduleUrl);
    }

    public static function module()
    {
        return self::$moduleName . " v" . self::$moduleVersion;
    }

    public function addOn($name, $version, $url = null)
    {
        $info = \Stripe\Stripe::getAppInfo();

        // Has been called twice
        if (strstr($info['version'], $name . '/' . $version) !== false)
            return;

        if ($name && $version)
            $info['version'] .= ' ' . $name . '/' . $version;

        if ($url)
            $info['url'] .= ', ' . $url;

        \Stripe\Stripe::setAppInfo($info['name'], $info['version'], $info['url']);
    }

    public function getConfigData($field, $method = null)
    {
        $storeId = $this->helper->getStoreId();

        $section = "";
        if ($method)
            $section = "_$method";

        $data = $this->scopeConfig->getValue("payment/stripe_payments$section/$field", ScopeInterface::SCOPE_STORE, $storeId);

        return $data;
    }

    public function isEnabled()
    {
        $enabled = ((bool)$this->getConfigData('active')) && $this->getSecretKey() && $this->getPublishableKey();
        return $enabled;
    }

    public function getStripeMode()
    {
        return $this->getConfigData('stripe_mode', 'basic');
    }

    public function getSecretKey()
    {
        $mode = $this->getStripeMode();
        $key = $this->getConfigData("stripe_{$mode}_sk", "basic");

        // The following is due to a magento bug causing the key to need to be saved more than once to be decrypted correctly
        if (!preg_match('/^[A-Za-z0-9_]+$/',$key))
            $key = $this->encryptor->decrypt($key);

        return trim($key);
    }

    public function getPublishableKey()
    {
        $mode = $this->getStripeMode();
        return trim($this->getConfigData("stripe_{$mode}_pk", "basic"));
    }

    public function getWebhooksSigningSecret()
    {
        $mode = $this->getStripeMode();
        $key = $this->getConfigData("stripe_{$mode}_wss", "basic");

        // The following is due to a magento bug causing the key to need to be saved more than once to be decrypted correctly
        if (!preg_match('/^[A-Za-z0-9_]+$/',$key))
            $key = $this->encryptor->decrypt($key);

        return trim($key);
    }

    public function isAutomaticInvoicingEnabled()
    {
        return (bool)$this->getConfigData("automatic_invoicing");
    }

    public function isReceiptEmailEnabled()
    {
        return (bool)$this->getConfigData('receipt_email');
    }

    public function getSecurityMethod()
    {
        // Older security methods have been depreciated
        return 2;
    }

    // If the module is unconfigured, payment_action will be null, defaulting to authorize & capture, so this would still return the correct value
    public function isAuthorizeOnly()
    {
        return ($this->getConfigData('payment_action') == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE);
    }

    public function isStripeJsEnabled()
    {
        return $this->getSecurityMethod() == 1;
    }

    public function isStripeElementsEnabled()
    {
        return $this->getSecurityMethod() == 2;
    }

    public function isStripeRadarEnabled()
    {
        return (($this->getConfigData('radar_risk_level') > 0) && !$this->helper->isAdmin());
    }

    public function isApplePayEnabled()
    {
        return $this->getConfigData('apple_pay_checkout')
            && ($this->isStripeJsEnabled() || $this->isStripeElementsEnabled())
            && !$this->helper->isAdmin();
    }

    public function isPaymentRequestButtonEnabled()
    {
        return $this->isApplePayEnabled() && $this->isStripeElementsEnabled();
    }

    public function useStoreCurrency()
    {
        return (bool)$this->getConfigData('use_store_currency');
    }

    public function getSaveCards()
    {
        return $this->getConfigData('ccsave');
    }

    public function getStatementDescriptor()
    {
        return $this->getConfigData('statement_descriptor');
    }

    public function retryWithSavedCard()
    {
        return $this->getConfigData('expired_authorizations') == 1;
    }

    public function setIsStripeAPIKeyError($isError)
    {
        $this->isStripeAPIKeyError = $isError;
    }

    public function alwaysSaveCards()
    {
        return ($this->getSaveCards() == 2 || $this->helper->hasSubscriptions() || $this->helper->isMultiShipping());
    }

    public function getIsStripeAPIKeyError()
    {
        if (isset($this->isStripeAPIKeyError))
            return $this->isStripeAPIKeyError;

        return false;
    }

    public function getApplePayLocation()
    {
        $location = $this->getConfigData('apple_pay_location');

        if (!$location)
            return 1;
        else
            return (int)$location;
    }

    public function getAmountCurrencyFromQuote($quote, $useCents = true)
    {
        $params = array();
        $items = $quote->getAllItems();

        if ($this->useStoreCurrency())
        {
            $amount = $quote->getGrandTotal();
            $currency = $quote->getQuoteCurrencyCode();
        }
        else
        {
            $amount = $quote->getBaseGrandTotal();;
            $currency = $quote->getBaseCurrencyCode();
        }

        if ($useCents)
        {
            $cents = 100;
            if ($this->helper->isZeroDecimal($currency))
                $cents = 1;

            $fields["amount"] = round($amount * $cents);
        }
        else
        {
            // Used for Apple Pay only
            $fields["amount"] = number_format($amount, 2, '.', '');
        }

        $fields["currency"] = $currency;

        return $fields;
    }

    public function getStripeParamsFrom($order)
    {
        if ($this->useStoreCurrency())
        {
            $amount = $order->getGrandTotal();
            $currency = $order->getOrderCurrencyCode();
        }
        else
        {
            $amount = $order->getBaseGrandTotal();
            $currency = $order->getBaseCurrencyCode();
        }

        $cents = 100;
        if ($this->helper->isZeroDecimal($currency))
            $cents = 1;

        $metadata = [
            "Module" => Config::module(),
            "Order #" => $order->getIncrementId()
        ];
        if ($order->getCustomerIsGuest())
        {
            $customer = $this->helper->getGuestCustomer($order);
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
            $metadata["Guest"] = "Yes";
        }
        else
            $customerName = $order->getCustomerName();

        $params = array(
          "amount" => round($amount * $cents),
          "currency" => $currency,
          "description" => "Order #".$order->getRealOrderId().' by '.$customerName,
          "metadata" => $metadata
        );

        if ($this->isReceiptEmailEnabled() && $this->helper->getCustomerEmail())
            $params["receipt_email"] = $this->helper->getCustomerEmail();

        return $params;
    }
}
