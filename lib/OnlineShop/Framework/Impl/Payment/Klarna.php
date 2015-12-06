<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_Impl_Payment_Klarna implements OnlineShop_Framework_IPayment
{
    /**
     * @var string
     */
    protected $eid;

    /**
     * @var string
     */
    protected $sharedSecretKey;

    /**
     * @var string[]
     */
    protected $authorizedData;

    /**
     * @var string
     */
    protected $endpoint;


    /**
     * @param Zend_Config $xml
     *
     * @throws Exception
     */
    public function __construct(Zend_Config $xml)
    {
        $settings = $xml->config->{$xml->mode};
        if($settings->eid == '' || $settings->{'shared-secret-key'} == '')
        {
            throw new Exception('payment configuration is wrong. eid or shared-secret-key is empty !');
        }

        $this->eid = $settings->eid;
        $this->sharedSecretKey = $settings->{'shared-secret-key'};


        if($xml->mode == 'live')
        {
            $this->endpoint = 'https://checkout.klarna.com/checkout/orders';
        }
        else
        {
            $this->endpoint = 'https://checkout.testdrive.klarna.com/checkout/orders';
        }


        // init autoloader
        require_once PIMCORE_DOCUMENT_ROOT . '/plugins/OnlineShop/vendor/autoload.php';
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'Klarna';
    }


    /**
     * start payment
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     * @param array                       $config
     * @param \OnlineShop\Framework\CartManager\ICart  $cart
     *
     * @return string
     * @throws Exception
     */
    public function initPayment(\OnlineShop\Framework\PriceSystem\IPrice $price, array $config, \OnlineShop\Framework\CartManager\ICart $cart = null)
    {
        // check params
        $required = [  'purchase_country' => null
                       , 'locale' => null
                       , 'merchant_reference' => null
        ];
        $check = array_intersect_key($config, $required);

        if(count($required) != count($check))
        {
            throw new Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $check)))));
        }


        // 2. Configure the checkout order
        $config['purchase_currency'] = $price->getCurrency()->getShortName();
        $config['merchant']['id'] = $this->eid;


        // 3. Create a checkout order
        $order = $this->createOrder();
        $order->create($config);


        // 4. Render the checkout snippet
        $order->fetch();


        // Display checkout
        $snippet = $order['gui']['snippet'];

        return $snippet;
    }


    /**
     * @param mixed $response
     *
     * @return OnlineShop_Framework_Payment_IStatus
     * @throws Exception
     */
    public function handleResponse($response)
    {
        // check required fields
        $required = [
            'klarna_order' => null
        ];
        $authorizedData = [
            'klarna_order' => null
        ];


        // check fields
        $check = array_intersect_key($response, $required);
        if(count($required) != count($check))
        {
            throw new Exception( sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $authorizedData)))) );
        }


        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData( $authorizedData );

        $order = $this->createOrder( $authorizedData['klarna_order'] );
        $order->fetch();


        $statMap = [
            'checkout_complete' => OnlineShop_Framework_Payment_IStatus::STATUS_AUTHORIZED
            , 'created' => OnlineShop_Framework_Payment_IStatus::STATUS_CLEARED
        ];
        return new OnlineShop_Framework_Impl_Payment_Status(
            $order['merchant_reference']['orderid2']
            , $order['id']
            , $order['status']
            , array_key_exists($order['status'], $statMap)
                ? $statMap[ $order['status'] ]
                : OnlineShop_Framework_Payment_IStatus::STATUS_CANCELLED
            , [
                'klarna_amount' => $order['cart']['total_price_including_tax']
                , 'klarna_marshal' => json_encode( $order->marshal() )
                , 'klarna_reservation' => $order['reservation']
                , 'klarna_reference' => $order['reference']
            ]
        );
    }

    /**
     * return the authorized data from payment provider
     *
     * @return array
     */
    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }

    /**
     * set authorized data from payment provider
     *
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData)
    {
        $this->authorizedData = $authorizedData;
    }

    /**
     * execute payment
     *
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Payment_IStatus
     * @throws Exception
     */
    public function executeDebit(\OnlineShop\Framework\PriceSystem\IPrice $price = null, $reference = null)
    {
        if( $price )
        {
            // TODO or not ?
            throw new \Exception('not allowed');
        }
        else
        {
            $authorizedData = $this->getAuthorizedData();

            $order = $this->createOrder( $authorizedData['klarna_order'] );
            $order->fetch();

            if ($order['status'] == 'checkout_complete')
            {
                $order->update([
                    'status' => 'created'
                ]);
            }


            return new OnlineShop_Framework_Impl_Payment_Status(
                $reference
                , $order['id']
                , $order['status']
                , $order['status'] == 'created'
                ? OnlineShop_Framework_Payment_IStatus::STATUS_CLEARED
                : OnlineShop_Framework_Payment_IStatus::STATUS_CANCELLED
                , [
                    'klarna_amount' => $order['cart']['total_price_including_tax']
                    , 'klarna_marshal' => json_encode( $order->marshal() )
                ]
            );
        }
    }

    /**
     * execute credit
     *
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return OnlineShop_Framework_Payment_IStatus
     * @see http://developers.klarna.com/en/at+php/kco-v2/order-management-api#introduction
     */
    public function executeCredit(\OnlineShop\Framework\PriceSystem\IPrice $price, $reference, $transactionId)
    {
        // TODO: Implement executeCredit() method.
        throw new \Exception('not implemented');
    }


    /**
     * @param string $uri
     *
     * @return Klarna_Checkout_Order
     */
    public function createOrder($uri = null)
    {
        // init
        Klarna_Checkout_Order::$baseUri = $this->endpoint;
        Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';

        $connector = Klarna_Checkout_Connector::create( $this->sharedSecretKey );

        return new Klarna_Checkout_Order($connector, $uri);
    }
}