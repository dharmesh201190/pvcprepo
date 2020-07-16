<?php

namespace StripeIntegration\Payments\Observer;

use Magento\Framework\Event\ObserverInterface;
use StripeIntegration\Payments\Helper\Logger;
use StripeIntegration\Payments\Exception\WebhookException;

class WebhooksObserver implements ObserverInterface
{
    public function __construct(
        \StripeIntegration\Payments\Helper\Webhooks $webhooksHelper,
        \StripeIntegration\Payments\Helper\Generic $paymentsHelper,
        \StripeIntegration\Payments\Model\Config $config,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender
    )
    {
        $this->webhooksHelper = $webhooksHelper;
        $this->paymentsHelper = $paymentsHelper;
        $this->config = $config;
        $this->orderCommentSender = $orderCommentSender;
    }

    protected function orderAgeLessThan($minutes, $order)
    {
        $created = strtotime($order->getCreatedAt());
        $now = time();
        return (($now - $created) < ($minutes * 60));
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $arrEvent = $observer->getData('arrEvent');
        $stdEvent = $observer->getData('stdEvent');
        $object = $observer->getData('object');

        $order = $this->webhooksHelper->loadOrderFromEvent($arrEvent);

        switch ($eventName)
        {
            // The following can trigger when:
            // 1. A merchant uses the Stripe Dashboard to manually capture a payment intent that was Authorized Only
            // 2. When a normal order is placed at the checkout, in which case we need to ignore this
            case 'stripe_payments_webhook_payment_intent_succeeded':

                // This is scenario 2 which we need to ignore
                if (empty($order) || $this->orderAgeLessThan($minutes = 3, $order))
                    throw new WebhookException("Ignoring", 202);

                $paymentIntentId = $arrEvent['data']['object']['id'];
                $captureCase = \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE;
                $params = [
                    "amount" => $arrEvent['data']['object']['amount_received'],
                    "currency" => $arrEvent['data']['object']['currency']
                ];

                $this->paymentsHelper->invoiceOrder($order, $paymentIntentId, $captureCase, $params);

                // $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                //     ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                //     ->save();

                break;

            case 'stripe_payments_webhook_charge_refunded_card':

                $this->webhooksHelper->refund($order, $object);
                break;

            case 'stripe_payments_webhook_source_chargeable_bancontact':
            case 'stripe_payments_webhook_source_chargeable_giropay':
            case 'stripe_payments_webhook_source_chargeable_ideal':
            case 'stripe_payments_webhook_source_chargeable_sepa_debit':
            case 'stripe_payments_webhook_source_chargeable_sofort':
            case 'stripe_payments_webhook_source_chargeable_multibanco':
            case 'stripe_payments_webhook_source_chargeable_eps':
            case 'stripe_payments_webhook_source_chargeable_przelewy':
            case 'stripe_payments_webhook_source_chargeable_alipay':
            case 'stripe_payments_webhook_source_chargeable_wechat':

                $this->webhooksHelper->charge($order, $arrEvent['data']['object']);
                break;

            case 'stripe_payments_webhook_source_canceled_bancontact':
            case 'stripe_payments_webhook_source_canceled_giropay':
            case 'stripe_payments_webhook_source_canceled_ideal':
            case 'stripe_payments_webhook_source_canceled_sepa_debit':
            case 'stripe_payments_webhook_source_canceled_sofort':
            case 'stripe_payments_webhook_source_canceled_multibanco':
            case 'stripe_payments_webhook_source_canceled_eps':
            case 'stripe_payments_webhook_source_canceled_przelewy':
            case 'stripe_payments_webhook_source_canceled_alipay':
            case 'stripe_payments_webhook_source_canceled_wechat':

                $cancelled = $this->paymentsHelper->cancelOrCloseOrder($order);
                if ($cancelled)
                    $this->addOrderCommentWithEmail($order, "Sorry, your order has been canceled because a payment request was sent to your bank, but we did not receive a response back. Please contact us or place your order again.");
                break;

            case 'stripe_payments_webhook_source_failed_bancontact':
            case 'stripe_payments_webhook_source_failed_giropay':
            case 'stripe_payments_webhook_source_failed_ideal':
            case 'stripe_payments_webhook_source_failed_sepa_debit':
            case 'stripe_payments_webhook_source_failed_sofort':
            case 'stripe_payments_webhook_source_failed_multibanco':
            case 'stripe_payments_webhook_source_failed_eps':
            case 'stripe_payments_webhook_source_failed_przelewy':
            case 'stripe_payments_webhook_source_failed_alipay':
            case 'stripe_payments_webhook_source_failed_wechat':

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail($order, "Your order has been canceled because the payment authorization failed.");
                break;

            case 'stripe_payments_webhook_charge_succeeded_bancontact':
            case 'stripe_payments_webhook_charge_succeeded_giropay':
            case 'stripe_payments_webhook_charge_succeeded_ideal':
            case 'stripe_payments_webhook_charge_succeeded_sepa_debit':
            case 'stripe_payments_webhook_charge_succeeded_sofort':
            case 'stripe_payments_webhook_charge_succeeded_multibanco':
            case 'stripe_payments_webhook_charge_succeeded_eps':
            case 'stripe_payments_webhook_charge_succeeded_przelewy':
            case 'stripe_payments_webhook_charge_succeeded_alipay':
            case 'stripe_payments_webhook_charge_succeeded_wechat':

                $invoiceCollection = $order->getInvoiceCollection();

                foreach ($invoiceCollection as $invoice)
                {
                    if ($invoice->getState() != \Magento\Sales\Model\Order\Invoice::STATE_PAID)
                        $invoice->pay()->save();
                }

                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->save();
                break;

            case 'stripe_payments_webhook_charge_failed_bancontact':
            case 'stripe_payments_webhook_charge_failed_giropay':
            case 'stripe_payments_webhook_charge_failed_ideal':
            case 'stripe_payments_webhook_charge_failed_sepa_debit':
            case 'stripe_payments_webhook_charge_failed_sofort':
            case 'stripe_payments_webhook_charge_failed_multibanco':
            case 'stripe_payments_webhook_charge_failed_eps':
            case 'stripe_payments_webhook_charge_failed_przelewy':
            case 'stripe_payments_webhook_charge_failed_alipay':
            case 'stripe_payments_webhook_charge_failed_wechat':

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail($order, "Your order has been canceled. The payment authorization succeeded, however the authorizing provider declined the payment when a charge was attempted.");
                break;

            default:
                # code...
                break;
        }
    }

    public function addOrderCommentWithEmail($order, $comment)
    {
        $order->addStatusToHistory($status = false, $comment, $isCustomerNotified = true);
        $this->orderCommentSender->send($order, $notify = true, $comment);
        $order->save();
    }
}
