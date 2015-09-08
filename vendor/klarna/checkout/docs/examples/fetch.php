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
 * Example of a fetch call.
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

// [[examples-fetch]]
require_once 'src/Klarna/Checkout.php';

$sharedSecret = 'sharedSecret';

Klarna_Checkout_Order::$contentType
    = 'application/vnd.klarna.checkout.aggregated-order-v2+json';

$orderUri = 'https://checkout.testdrive.klarna.com/checkout/orders/ABC123';

$connector = Klarna_Checkout_Connector::create($sharedSecret);
$order = new Klarna_Checkout_Order($connector, $orderUri);

$order->fetch();
// [[examples-fetch]]
