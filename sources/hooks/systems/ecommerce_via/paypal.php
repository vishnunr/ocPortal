<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ecommerce
 */
class Hook_paypal
{
    /**
     * Get the PayPal payment address.
     *
     * @return string                   The answer.
     */
    protected function _get_payment_address()
    {
        return trim(ecommerce_test_mode() ? get_option('ipn_test') : get_option('ipn'));
    }

    /**
     * Get the remote form URL.
     *
     * @return URLPATH                  The remote form URL.
     */
    protected function _get_remote_form_url()
    {
        return 'https://secure.worldpay.com/wcc/purchase';
    }

    /**
     * Make a transaction (payment) button.
     *
     * @param  ID_TEXT                  The product codename.
     * @param  SHORT_TEXT               The human-readable product title.
     * @param  ID_TEXT                  The purchase ID.
     * @param  float                    A transaction amount.
     * @param  ID_TEXT                  The currency to use.
     * @return tempcode                 The button.
     */
    public function make_transaction_button($type_code, $item_name, $purchase_id, $amount, $currency)
    {
        $payment_address = $this->_get_payment_address();
        $ipn_url = $this->_get_remote_form_url();

        $user_details = array();
        if (!is_guest()) {
            $user_details['first_name'] = get_ocp_cpf('firstname');
            $user_details['last_name'] = get_ocp_cpf('lastname');
            $user_details['address1'] = get_ocp_cpf('building_name_or_number');
            $user_details['city'] = get_ocp_cpf('city');
            $user_details['state'] = get_ocp_cpf('state');
            $user_details['zip'] = get_ocp_cpf('post_code');
            $user_details['country'] = get_ocp_cpf('country');
        }

        return do_template('ECOM_BUTTON_VIA_PAYPAL', array(
            '_GUID' => 'b0d48992ed17325f5e2330bf90c85762',
            'TYPE_CODE' => $type_code,
            'ITEM_NAME' => $item_name,
            'PURCHASE_ID' => $purchase_id,
            'AMOUNT' => float_to_raw_string($amount),
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'IPN_URL' => $ipn_url,
            'MEMBER_ADDRESS' => $user_details,
        ));
    }

    /**
     * Make a subscription (payment) button.
     *
     * @param  ID_TEXT                  The product codename.
     * @param  SHORT_TEXT               The human-readable product title.
     * @param  ID_TEXT                  The purchase ID.
     * @param  float                    A transaction amount.
     * @param  integer                  The subscription length in the units.
     * @param  ID_TEXT                  The length units.
     * @set    d w m y
     * @param  ID_TEXT                  The currency to use.
     * @return tempcode                 The button.
     */
    public function make_subscription_button($type_code, $item_name, $purchase_id, $amount, $length, $length_units, $currency)
    {
        // NB: We don't support PayPal's "recur_times", but that's fine because it's really not that useful (we can just set a long non-recurring subscription to the same effect)

        $payment_address = $this->_get_payment_address();
        $ipn_url = $this->_get_remote_form_url();
        return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_PAYPAL', array(
            '_GUID' => '7c8b9ce1f60323e118da1bef416adff3',
            'TYPE_CODE' => $type_code,
            'ITEM_NAME' => $item_name,
            'LENGTH' => strval($length),
            'LENGTH_UNITS' => $length_units,
            'PURCHASE_ID' => $purchase_id,
            'AMOUNT' => float_to_raw_string($amount),
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'IPN_URL' => $ipn_url,
        ));
    }

    /**
     * Make a subscription cancellation button.
     *
     * @param  ID_TEXT                  The purchase ID.
     * @return tempcode                 The button
     */
    public function make_cancel_button($purchase_id)
    {
        return do_template('ECOM_CANCEL_BUTTON_VIA_PAYPAL', array('_GUID' => '091d7449161eb5c4f6129cf89e5e8e7e', 'PURCHASE_ID' => $purchase_id));
    }

    /**
     * Find whether the hook auto-cancels (if it does, auto cancel the given trans-ID).
     *
     * @param  string                   Transaction ID to cancel.
     * @return ?boolean                 True: yes. False: no. (NULL: cancels via a user-URL-directioning)
     */
    public function auto_cancel($trans_id)
    {
        return null;
    }

    /**
     * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
     *
     * @param  float                    A transaction amount.
     * @return float                    The fee
     */
    public function get_transaction_fee($amount)
    {
        return round(0.25 + 0.034 * $amount, 2);
    }

    /**
     * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
     *
     * @return array                    A long tuple of collected data.
     */
    public function handle_transaction()
    {
        $purchase_id = post_param_integer('custom', '-1');

        // Read in stuff we'll just log
        $reason_code = post_param('reason_code', '');
        $pending_reason = post_param('pending_reason', '');
        $memo = post_param('memo', '');
        foreach (array_keys($_POST) as $key) { // Custom product options go onto the memo
            $matches = array();
            if (preg_match('#^option_selection(\d+)$#', $key, $matches) != 0) {
                $memo .= "\n" . post_param('option_name' . $matches[1], '') . ' = ' . post_param('option_selection' . $matches[1], '');
            }
        }
        $txn_id = post_param('txn_id', ''); // May be blank for subscription, will be overwritten for them
        $parent_txn_id = post_param('parent_txn_id', '-1');

        // Work out how much money was made for the hook
        $mc_gross = post_param('mc_gross', ''); // May be blank for subscription
        $tax = post_param('tax', '');
        if (($tax != '') && (intval($tax) > 0) && ($mc_gross != '')) {
            $mc_gross = float_to_raw_string(floatval($mc_gross) - floatval($tax));
        }
        /*$shipping=post_param('shipping','');  Actually, the hook will have added shipping to the overall product cost
        if (($shipping!='') && (intval($shipping)>0) && ($mc_gross!='')) $mc_gross=float_to_raw_string(floatval($mc_gross)-floatval($shipping));*/
        $mc_currency = post_param('mc_currency', ''); // May be blank for subscription

        // More stuff that we might need
        $period = post_param('period3', '');

        // Valid transaction types / pre-processing for $item_name based on mapping rules
        $txn_type = post_param('txn_type', null);
        switch ($txn_type) {
            // Product
            case 'web_accept':
                $item_name = post_param('item_name');
                break;

            // Subscription
            case 'subscr_signup':
            case 'subscr_payment':
            case 'subscr_failed':
            case 'subscr_modify':
            case 'subscr_cancel':
            case 'subscr_eot':
                $item_name = ''; // These map through the ocPortal subscriptions table, based upon purchase_id; our blank item name will tell us we need to do that (blank item name --> a subscription not an item)
                break;

            // Cart
            case 'cart':
                require_lang('shopping');
                $item_name = do_lang('CART_ORDER', $purchase_id); // We will detect as the correct cart-order from the re-mapped item_name. This is a specially recognised item naming, reserved for cart products.
                break;

            // (Non-supported)
            case 'adjustment':
            case 'express_checkout':
            case 'masspay':
            case 'mp_cancel':
            case 'mp_signup':
            case 'merch_pmt':
            case 'new_case':
            case 'payout':
            case 'recurring_payment':
            case 'recurring_payment_expired':
            case 'recurring_payment_failed':
            case 'recurring_payment_profile_created':
            case 'recurring_payment_profile_cancel':
            case 'recurring_payment_skipped':
            case 'recurring_payment_suspended':
            case 'recurring_payment_suspended_due_to_max_failed_payment':
            case 'send_money':
            case 'virtual_terminal':
            default:
                exit(); // Non-supported for IPN in ocPortal
        }
        $payment_status = post_param('payment_status', '');
        switch ($payment_status) {
            // Subscription
            case '': // We map certain values of txn_type for subscriptions over to payment_status, as subscriptions have no payment status but similar data in txn_type which we do not use
                $mc_gross = post_param('mc_amount3');

                switch ($txn_type) {
                    case 'subscr_signup':
                        // SECURITY: Check it's a kind of subscription we would actually have generated
                        if (post_param_integer('recurring') != 1) {
                            fatal_ipn_exit(do_lang('IPN_SUB_RECURRING_WRONG'));
                        }

                        // SECURITY: Check user is not giving themselves a free trial (we don't support trials)
                        if ((post_param('amount1', '') != '') || (post_param('amount2', '') != '') || (post_param('mc_amount1', '') != '') || (post_param('mc_amount2', '') != '') || (post_param('period1', '') != '') || (post_param('period2', '') != '')) {
                            fatal_ipn_exit(do_lang('IPN_BAD_TRIAL'));
                        }

                        $payment_status = 'Completed';
                        $txn_id = post_param('subscr_id');

                        // NB: subscr_date is sent by IPN, but not user-settable. For the more complex PayPal APIs we may need to validate it

                        break;

                    case 'subscr_payment':
                        exit(); // We don't need to track individual payments

                    case 'subscr_failed':
                        exit(); // PayPal auto-cancels at a configured point ("To avoid unnecessary cancellations, you can specify that PayPal should reattempt failed payments before canceling subscriptions."). So, we only listen to the actual cancellation signal.

                    case 'subscr_modify':
                        $payment_status = 'SModified';
                        $txn_id = post_param('subscr_id') . '-m';
                        break;

                    case 'subscr_cancel':
                        exit(); // We ignore cancel transactions as we don't want to process them immediately - we just let things run until the end-of-term (see below). Maybe ideally we would process these in ocPortal as a separate state, but it would over-complicate things

                    case 'subscr_eot': // NB: An 'eot' means "end of *final* term" (i.e. if a payment fail / cancel / natural last term, has happened). PayPal's terminology is a little dodgy here.
                    case 'recurring_payment_suspended_due_to_max_failed_payment':
                        $payment_status = 'SCancelled';
                        $txn_id = post_param('subscr_id') . '-c';
                        break;
                }
                break;

            // Pending
            case 'Pending':
                break;

            // Completed
            case 'Completed':
            case 'Created':
                $payment_status = 'Completed';
                break;

            // (Non-supported)
            case 'Reversed':
            case 'Refunded':
            case 'Denied':
            case 'Expired':
            case 'Failed':
            case 'Canceled_Reversal':
            case 'Voided':
            case 'Processed': // Mass-payments
                exit(); // Non-supported for IPN in ocPortal
        }

        // SECURITY: Ignore sandbox transactions if we are not in test mode
        if (post_param_integer('test_ipn', 0) == 1) {
            if (!ecommerce_test_mode()) {
                exit();
            }
        }

        // SECURITY: Post back to PayPal system to validate
        if ((!ecommerce_test_mode()) && (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())/*allow debugging if your test IP was intentionally back-doored*/)) {
            require_code('files');
            $pure_post = isset($GLOBALS['PURE_POST']) ? $GLOBALS['PURE_POST'] : $_POST;
            if (get_magic_quotes_gpc()) {
                $pure_post = array_map('stripslashes', $pure_post);
            }
            $x = 0;
            $res = mixed();
            do { // Try up to 3 times
                $res = http_download_file('http://' . (ecommerce_test_mode() ? 'www.sandbox.paypal.com' : 'www.paypal.com') . '/cgi-bin/webscr', null, false, false, 'ocPortal', $pure_post + array('cmd' => '_notify-validate'));
                $x++;
            }
            while ((is_null($res)) && ($x < 3));
            if (is_null($res)) {
                fatal_ipn_exit(do_lang('IPN_SOCKET_ERROR'));
            }
            if (!(strcmp($res, 'VERIFIED') == 0)) {
                fatal_ipn_exit(do_lang('IPN_UNVERIFIED') . ' - ' . $res . ' - ' . flatten_slashed_array($pure_post, true), strpos($res, '<html') !== false);
            }
        }

        // SECURITY: Check it came into our own account
        $primary_paypal_email = get_option('primary_paypal_email');
        $receiver_email = post_param('receiver_email');
        if ($primary_paypal_email != '') {
            if ($receiver_email != $primary_paypal_email) {
                fatal_ipn_exit(do_lang('IPN_EMAIL_ERROR'));
            }
        } else {
            if ($receiver_email != $this->_get_payment_address()) {
                fatal_ipn_exit(do_lang('IPN_EMAIL_ERROR'));
            }
        }

        // Shopping cart
        if (addon_installed('shopping')) {
            $this->store_shipping_address($purchase_id);
        }

        return array($purchase_id, $item_name, $payment_status, $reason_code, $pending_reason, $memo, $mc_gross, $mc_currency, $txn_id, $parent_txn_id, $period);
    }

    /**
     * Make a transaction (payment) button for multiple shopping cart items.
     *
     * @param  array                    Items array.
     * @param  tempcode                 Currency symbol.
     * @param  AUTO_LINK                Order ID.
     * @return tempcode                 The button.
     */
    public function make_cart_transaction_button($items, $currency, $order_id)
    {
        $payment_address = $this->_get_payment_address();

        $ipn_url = $this->_get_remote_form_url();

        $notification_text = do_lang_tempcode('CHECKOUT_NOTIFICATION_TEXT', strval($order_id));

        $user_details = array();

        if (!is_guest()) {
            $user_details['first_name'] = get_ocp_cpf('firstname');
            $user_details['last_name'] = get_ocp_cpf('lastname');
            $user_details['address1'] = get_ocp_cpf('building_name_or_number');
            $user_details['city'] = get_ocp_cpf('city');
            $user_details['state'] = get_ocp_cpf('state');
            $user_details['zip'] = get_ocp_cpf('post_code');
            $user_details['country'] = get_ocp_cpf('country');
        }

        return do_template('ECOM_CART_BUTTON_VIA_PAYPAL', array(
            '_GUID' => '89b7edf976ef0143dd8dfbabd3378c95',
            'ITEMS' => $items,
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'IPN_URL' => $ipn_url,
            'ORDER_ID' => strval($order_id),
            'NOTIFICATION_TEXT' => $notification_text,
            'MEMBER_ADDRESS' => $user_details,
        ));
    }

    /**
     * Store shipping address for orders.
     *
     * @param  AUTO_LINK                Order ID.
     * @return ?mixed                   Address ID (NULL: No address record found).
     */
    public function store_shipping_address($order_id)
    {
        if (is_null(post_param('address_name', null))) {
            return null;
        }

        if (is_null($GLOBALS['SITE_DB']->query_select_value_if_there('shopping_order_addresses', 'id', array('order_id' => $order_id)))) {
            $shipping_address = array();
            $shipping_address['order_id'] = $order_id;
            $shipping_address['address_name'] = post_param('address_name', '');
            $shipping_address['address_street'] = post_param('address_street', '');
            $shipping_address['address_zip'] = post_param('address_zip', '');
            $shipping_address['address_city'] = post_param('address_city', '');
            $shipping_address['address_state'] = '';
            $shipping_address['address_country'] = post_param('address_country', '');
            $shipping_address['receiver_email'] = post_param('payer_email', '');
            $shipping_address['contact_phone'] = post_param('contact_phone', '');
            $shipping_address['first_name'] = post_param('first_name', '');
            $shipping_address['last_name'] = post_param('last_name', '');

            return $GLOBALS['SITE_DB']->query_insert('shopping_order_addresses', $shipping_address, true);
        }

        return null;
    }

    /**
     * Get the status message after a URL callback.
     *
     * @return ?string                  Message (NULL: none).
     */
    public function get_callback_url_message()
    {
        return get_param('message', null, true);
    }
}
