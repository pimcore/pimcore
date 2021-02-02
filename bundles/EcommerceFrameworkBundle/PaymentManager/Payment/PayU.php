<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment;

use GuzzleHttp\Client;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\UrlResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject\OnlineShopOrderItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated since v6.8.0 and will be moved to package "payment-provider-payu" in Pimcore 10.
 */
class PayU extends AbstractPayment implements \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface
{
    const ORDER_URL = 'https://secure%s.payu.com/api/v2_1/orders';
    const AUTHORIZE_URL = 'https://secure%s.payu.com/pl/standard/user/oauth/authorize';

    /** @var string $pos_id */
    protected $posId;

    /** @var string $md5_key */
    protected $md5Key;

    /** @var string $oauth_client_id */
    protected $oauthClientId;

    /** @var string $oauth_client_secret */
    protected $oauthClientSecret;

    /** @var string $accessToken */
    protected $accessToken;

    /** @var string $orderUrl */
    protected $orderUrl;

    /** @var string $authorizeUrl */
    protected $authorizeUrl;

    /** @var array $authorizedData */
    protected $authorizedData;

    /** @var Client */
    protected $client;

    public function __construct(Client $client, array $options)
    {
        $this->client = $client;

        $this->processOptions(
            $this->configureOptions(new OptionsResolver())->resolve($options)
        );
    }

    /**
     * @param array $options
     *
     * @throws \Exception
     */
    protected function processOptions(array $options)
    {
        $urlPart = $options['mode'] == 'sandbox' ? '.snd' : '';

        $this->posId = $options['pos_id'];
        $this->md5Key = $options['md5_key'];
        $this->oauthClientId = $options['oauth_client_id'];
        $this->oauthClientSecret = $options['oauth_client_secret'];
        $this->orderUrl = sprintf(self::ORDER_URL, $urlPart);
        $this->authorizeUrl = sprintf(self::AUTHORIZE_URL, $urlPart);
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @return OptionsResolver
     */
    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver->setRequired([
            'mode',
            'pos_id',
            'md5_key',
            'oauth_client_id',
            'oauth_client_secret',
        ]);

        $resolver
            ->setDefault('mode', 'sandbox')
            ->setAllowedValues('mode', ['sandbox', 'live']);

        $notEmptyValidator = function ($value) {
            return !empty($value);
        };

        foreach ($resolver->getRequiredOptions() as $requiredProperty) {
            $resolver->setAllowedValues($requiredProperty, $notEmptyValidator);
        }

        return $resolver;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'PayU';
    }

    /**
     * @param PriceInterface $price
     * @param array $config
     *
     * @return mixed|string
     *
     * @throws \Exception
     */
    public function initPayment(PriceInterface $price, array $config)
    {
        $required = [
            'extOrderId',
            'notifyUrl',
            'customerIp',
            'description',
            'continueUrl',
            'order',
        ];

        $config = array_intersect_key($config, array_flip($required));

        if (count($required) != count($config)) {
            throw new \Exception(sprintf(
                'required fields are missing! required: %s',
                implode(', ', array_keys(array_diff_key($required, $config)))
            ));
        }

        /** @var OnlineShopOrder $order */
        $order = $config['order'];

        $orderData['continueUrl'] = $config['continueUrl'];
        $orderData['extOrderId'] = $config['extOrderId'];
        $orderData['notifyUrl'] = $config['notifyUrl'];
        $orderData['description'] = $config['description'];
        $orderData['customerIp'] = $config['customerIp'];
        $orderData['merchantPosId'] = $this->posId;
        $orderData['buyer'] = [
            'email' => $order->getCustomer()->getEmail(),
        ];
        $orderData['currencyCode'] = $price->getCurrency()->getShortName();
        $orderData['totalAmount'] = (string) (round($price->getAmount()->asNumeric(), 2) * 100);
        $orderData['products'] = $this->setProducts($order->getItems());

        $orderData = $this->setAdditionalData($orderData);

        return $this->create($orderData);
    }

    /**
     * @param array $items
     *
     * @return OnlineShopOrderItem[]
     */
    protected function setProducts(array $items): array
    {
        $products = [];
        $items = array_values($items);

        /**
         * @var int $key
         * @var OnlineShopOrderItem $item
         */
        foreach ($items as $key => $item) {
            /** @var ProductECommerce $product */
            $product = $item->getProduct();

            $products[$key]['name'] = $product->getName();
            $products[$key]['unitPrice'] = (string) (round($product->getOSPrice()->getAmount()->asNumeric(), 2) * 100);
            $products[$key]['quantity'] = $item->getAmount();
        }

        return $products;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getAccessToken(): string
    {
        $response = $this->client->post($this->authorizeUrl, ['form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => $this->oauthClientId,
            'client_secret' => $this->oauthClientSecret,
        ]]);

        $response = \GuzzleHttp\json_decode($response->getBody()->getContents());

        if (!isset($response->access_token)) {
            throw new \Exception($response->error_description . ' check PayU configuration');
        }

        return $response->access_token;
    }

    /**
     * @param array $order
     *
     * @return string
     *
     * @throws \Exception
     */
    private function create(array $order): string
    {
        $response = $this->client->post($this->orderUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
            'json' => $order,
            'allow_redirects' => false,
            'http_errors' => false,
        ]);

        $response = \GuzzleHttp\json_decode($response->getBody()->getContents());

        if ($response->status->statusCode === 'SUCCESS') {
            return $response->redirectUri;
        }

        throw new \Exception($response->error_description);
    }

    /**
     * @inheritDoc
     */
    public function startPayment(OrderAgentInterface $orderAgent, PriceInterface $price, AbstractRequest $config): StartPaymentResponseInterface
    {
        $url = $this->initPayment($price, $config->asArray());

        return new UrlResponse($orderAgent->getOrder(), $url);
    }

    /**
     * Executes payment
     *
     * @param mixed $response
     *
     * @return StatusInterface
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {
        // check required fields
        $required = [
            'payMethod' => null,
            'orderId' => null,
            'extOrderId' => null,
            'InvoiceID' => null,
            'totalAmount' => null,
            'currencyCode' => null,
            'status' => null,
            'order' => null,
        ];

        $authorizedData = [
            'orderId' => null,
        ];

        // check fields
        $response = array_intersect_key($response['order'], $required);
        if (count($required) != count($response)) {
            throw new \Exception(sprintf(
                'required fields are missing! required: %s',
                implode(', ', array_keys(array_diff_key($required, $response)))
            ));
        }

        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData($authorizedData);

        $price = new Price(Decimal::create($response['totalAmount']), new Currency($response['currencyCode']));

        return $this->executeDebit($price, $response);
    }

    /**
     * @inheritdoc
     */
    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }

    /**
     * @inheritdoc
     */
    public function setAuthorizedData(array $authorizedData)
    {
        $this->authorizedData = $authorizedData;
    }

    /**
     * @inheritdoc
     */
    public function executeDebit(PriceInterface $price = null, $response = null)
    {
        /** @var OnlineShopOrder $order */
        $order = $response['order'];

        if ($response['status'] == 'COMPLETED' &&
            $price->getGrossAmount()->asNumeric() == round($order->getTotalPrice(), 2) * 100
        ) {
            return new Status(
                $response['extOrderId'],
                $response['orderId'],
                null,
                AbstractOrder::ORDER_STATE_COMMITTED,
                [
                    'payu_PaymentType' => $response['payMethod']['type'],
                    'payu_amount' => (string) $price,
                ]
            );
        } else {
            return new Status(
                $response['extOrderId'],
                $response['orderId'],
                $response['status'],
                AbstractOrder::ORDER_STATE_ABORTED
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function executeCredit(PriceInterface $price, $reference, $transactionId)
    {
        // TODO: Implement executeCredit() method.
        throw new \Exception('not implemented');
    }

    /**
     * Set additional data example mcpData for multi-currency:
     * http://developers.payu.com/en/multi-currency.html
     *
     * @param array $orderData
     *
     * @return array
     */
    protected function setAdditionalData(array $orderData): array
    {
        return $orderData;
    }
}
