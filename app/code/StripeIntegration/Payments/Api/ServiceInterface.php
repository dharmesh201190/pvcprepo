<?php

namespace StripeIntegration\Payments\Api;

interface ServiceInterface
{
    /**
     * Returns Redirect Url
     *
     * @api
     * @return string Redirect Url
     */
    public function redirect_url();

    /**
     * Refunds any dangling PIs for the order and creates a new one for the checkout session
     *
     * @api
     * @param string|null $status
     * @param string|null $response
     *
     * @return mixed Json object containing the new PI ID.
     */
    public function reset_payment_intent($status, $response);

    /**
    * Invalidates the cache for the locally saved Payment Intent
    *
    * @api
    *
    * @return mixed
    */
    public function payment_intent_refresh();

    /**
     * Estimate Shipping by Address
     *
     * @api
     * @param mixed $address
     *
     * @return string
     */
    public function estimate_cart($address);

    /**
     * Set billing address from data object
     *
     * @api
     * @param mixed $data
     *
     * @return string
     */
    public function set_billing_address($data);

    /**
     * Apply Shipping Method
     *
     * @api
     * @param mixed $address
     * @param string|null $shipping_id
     *
     * @return string
     */
    public function apply_shipping($address, $shipping_id = null);

    /**
     * Place Order
     *
     * @api
     * @param mixed $result
     *
     * @return string
     */
    public function place_order($result);

    /**
     * Add to Cart
     *
     * @api
     * @param string $request
     * @param string|null $shipping_id
     *
     * @return string
     */
    public function addtocart($request, $shipping_id = null);

    /**
     * Get Cart Contents
     *
     * @api
     * @return string
     */
    public function get_cart();
}
