<?php

namespace StripeIntegration\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use StripeIntegration\Payments\Helper\Logger;

class OrderObserver extends AbstractDataAssignObserver
{
    public function __construct(
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent
    )
    {
        $this->config = $config;
        $this->paymentIntent = $paymentIntent;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $eventName = $observer->getEvent()->getName();
        $method = $order->getPayment()->getMethod();

        if ($method != 'stripe_payments')
            return;

        switch ($eventName)
        {
            case 'sales_order_place_before':
                // We simply need to invalidate the local cache so that we don't try to update successful PIs
                $this->paymentIntent->isSuccessful();
                break;
            case 'sales_order_place_after':
                $this->updateOrderState($observer);

                // Different to M1, this is unnecessary
                // $this->updateStripeCustomer()
                break;
        }
    }

    public function updateOrderState($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if ($payment->getAdditionalInformation('stripe_outcome_type') == "manual_review")
        {
            $order->setHoldBeforeState($order->getState());
            $order->setHoldBeforeStatus($order->getStatus());
            $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED)
                ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_HOLDED));
            $order->addStatusToHistory(false, "Order placed under manual review by Stripe Radar", false);
            $order->save();
        }
    }
}
