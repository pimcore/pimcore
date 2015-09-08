<?php
/**
 * Copyright 2012 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This file demonstrates the use of the Klarna library to complete
 * the purchase and display the thank you page snippet.
 *
 * PHP version 5.3.4
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage Examples
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
// [[examples-push]]
require_once 'src/Klarna/Checkout.php';

session_start();

Klarna_Checkout_Order::$contentType
    = "application/vnd.klarna.checkout.aggregated-order-v2+json";

$connector = Klarna_Checkout_Connector::create('sharedSecret');

@$checkoutId = $_GET['klarna_order'];
$order = new Klarna_Checkout_Order($connector, $checkoutId);
$order->fetch();

if ($order['status'] == "checkout_complete") {
    // At this point make sure the order is created in your system and send a
    // confirmation email to the customer
    $update['status'] = 'created';
    $update['merchant_reference'] = array(
        'orderid1' => uniqid()
    );
    $order->update($update);
}
// [[examples-push]]
