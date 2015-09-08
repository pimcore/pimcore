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
 * Simple benchmark utility
 *
 * PHP version 5.3
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Klarna <support@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */

require_once 'src/Klarna/Checkout.php';
require_once 'tests/CURLHandleStub.php';

/**
 * Factory that returns stubbed instance of the CURlHandle wrapper
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    David K. <david.keijser@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class CURLStubFactory extends Klarna_Checkout_HTTP_CURLFactory
{
    /**
     * Create a new cURL handle
     *
     * @return Klarna_Checkout_HTTP_CURLHandleStub
     */
    public function handle()
    {
        $stub = new Klarna_Checkout_HTTP_CURLHandleStub;
        $stub->info['http_code'] = 201;
        return $stub;
    }
}

$resource = new Klarna_Checkout_Order(
    array(
        'foo' => 'Foo',
        'bar' => 'bar'
    )
);

$connector = new Klarna_Checkout_Connector(
    new Klarna_Checkout_HTTP_CURLTransport(
        new CURLStubFactory
    ),
    new Klarna_Checkout_Digest,
    'sharedSecret'
);

$count = 20000;
$start = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $resource->create($connector);
    $resource->fetch($connector);
    $resource->update($connector);
}
$end = microtime(true);

echo json_encode(
    array(
        'start' => $start,
        'end' => $end,
        'duration' => $end - $start,
        'count' => $count
    )
);
