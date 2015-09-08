<?php
/**
 * Copyright 2013 Klarna AB
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
 * Example of a create call.
 *
 * PHP version 5.3.4
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    David Keijser <david.keijser@klarna.com>
 * @author    Rickard Dybeck <rickard.dybeck@klarna.com>
 * @copyright 2013 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */

// [[examples-create]]
require_once 'src/Klarna/Checkout.php';

$eid = '0';
$sharedSecret = 'sharedSecret';

Klarna_Checkout_Order::$baseUri
    = 'https://checkout.testdrive.klarna.com/checkout/orders';
Klarna_Checkout_Order::$contentType
    = "application/vnd.klarna.checkout.aggregated-order-v2+json";

$connector = Klarna_Checkout_Connector::create($sharedSecret);
$order = new Klarna_Checkout_Order($connector);

// Array containing the cart items
$cart = array(
    array(
        'reference' => '123456789',
        'name' => 'Klarna t-shirt',
        'quantity' => 2,
        'unit_price' => 12300,
        'discount_rate' => 1000,
        'tax_rate' => 2500
    ),
    array(
        'type' => 'shipping_fee',
        'reference' => 'SHIPPING',
        'name' => 'Shipping Fee',
        'quantity' => 1,
        'unit_price' => 4900,
        'tax_rate' => 2500
    )
);

$create['purchase_country'] = 'SE';
$create['purchase_currency'] = 'SEK';
$create['locale'] = 'sv-se';
$create['merchant']['id'] = $eid;
$create['merchant']['terms_uri'] = 'http://example.com/terms.php';
$create['merchant']['checkout_uri'] = 'https://example.com/checkout.php';
$create['merchant']['confirmation_uri']
    = 'https://example.com/thankyou.php?sid=123&klarna_order={checkout.order.uri}';
$create['merchant']['push_uri']
    = 'https://example.com/push.php?sid=123&klarna_order={checkout.order.uri}';

foreach ($cart as $item) {
    $create['cart']['items'][] = $item;
}

$order->create($create);
// [[examples-create]]
