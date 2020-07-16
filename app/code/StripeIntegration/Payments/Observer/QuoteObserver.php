<?php

namespace StripeIntegration\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use StripeIntegration\Payments\Helper\Logger;

class QuoteObserver implements ObserverInterface
{
    public function __construct(\StripeIntegration\Payments\Model\PaymentIntent $paymentIntent)
    {
        $this->paymentIntent = $paymentIntent;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $this->paymentIntent->updateFrom($quote);
    }
}
