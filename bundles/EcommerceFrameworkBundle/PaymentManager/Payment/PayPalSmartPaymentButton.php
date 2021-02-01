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

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\JsonResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated since v6.8.0 and will be moved to package "payment-provider-paypal-smart-payment-button" in Pimcore 10.
 */
class PayPalSmartPaymentButton extends AbstractPayment implements \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface
{
    const CAPTURE_STRATEGY_MANUAL = 'manual';
    const CAPTURE_STRATEGY_AUTOMATIC = 'automatic';

    /**
     * @var PayPalHttpClient
     */
    protected $payPalHttpClient;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var array
     */
    protected $applicationContext = [];

    /**
     * @var string
     */
    protected $captureStrategy = self::CAPTURE_STRATEGY_MANUAL;

    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    protected $authorizedData;

    public function __construct(array $options, EnvironmentInterface $environment)
    {
        $this->processOptions(
            $this->configureOptions(new OptionsResolver())->resolve($options)
        );

        $this->environment = $environment;
    }

    /**
     * @return EnvironmentInterface
     */
    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    /**
     * @param EnvironmentInterface $environment
     */
    public function setEnvironment(EnvironmentInterface $environment): void
    {
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'PayPalSmartButton';
    }

    /**
     * Start payment
     *
     * @param PriceInterface $price
     * @param array $config
     *
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an symfony form builder which needs to submitted (e.g. datatrans and wirecard)
     */
    public function initPayment(PriceInterface $price, array $config)
    {
        // check params
        $required = [
            'return_url' => null,
            'cancel_url' => null,
            'OrderDescription' => null,
            'InternalPaymentId' => null,
        ];

        $config = array_intersect_key($config, $required);

        if (count($required) != count($config)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
        }

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = $this->buildRequestBody($price, $config);

        $response = $this->payPalHttpClient->execute($request);

        return $response->result;
    }

    protected function buildRequestBody(PriceInterface $price, array $config)
    {
        $applicationContext = $this->applicationContext;
        if ($config['return_url']) {
            $applicationContext['return_url'] = $config['return_url'];
        }
        if ($config['cancel_url']) {
            $applicationContext['cancel_url'] = $config['cancel_url'];
        }

        $requestBody = [
            'intent' => 'CAPTURE',
            'application_context' => $applicationContext,
            'purchase_units' => [
                [
                    'custom_id' => $config['InternalPaymentId'],
                    'description' => $config['OrderDescription'],
                    'amount' => [
                        'currency_code' => $price->getCurrency()->getShortName(),
                        'value' => $price->getGrossAmount()->asString(2),
                    ],
                ],
            ],
        ];

        return $requestBody;
    }

    /**
     * @inheritDoc
     */
    public function startPayment(OrderAgentInterface $orderAgent, PriceInterface $price, AbstractRequest $config): StartPaymentResponseInterface
    {
        $result = $this->initPayment($price, $config->asArray());

        if ($result instanceof \stdClass) {
            if ($json = json_encode($result)) {
                return new JsonResponse($orderAgent->getOrder(), $json);
            }
        }

        json_decode($result);
        if (json_last_error() == JSON_ERROR_NONE) {
            return new JsonResponse($orderAgent->getOrder(), $result);
        }

        throw new \Exception('result of initPayment neither stdClass nor JSON');
    }

    /**
     * Handles response of payment provider and creates payment status object
     *
     * @param StatusInterface $response
     *
     * @return StatusInterface
     */
    public function handleResponse($response)
    {
        // check required fields
        $required = [
            'orderID' => null,
            'payerID' => null,
        ];

        $authorizedData = [
            'orderID' => null,
            'payerID' => null,
            'email_address' => null,
            'given_name' => null,
            'surname' => null,
        ];

        // check fields
        $response = array_intersect_key($response, $required);
        if (count($required) != count($response)) {
            throw new \Exception(sprintf(
                'required fields are missing! required: %s',
                implode(', ', array_keys(array_diff_key($required, $response)))
            ));
        }

        $orderId = $response['orderID'];

        $statusResponse = $this->payPalHttpClient->execute(new OrdersGetRequest($orderId));

        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $authorizedData['email_address'] = $statusResponse->result->payer->email_address;
        $authorizedData['given_name'] = $statusResponse->result->payer->name->given_name;
        $authorizedData['surname'] = $statusResponse->result->payer->name->surname;
        $this->setAuthorizedData($authorizedData);

        switch ($this->captureStrategy) {

            case self::CAPTURE_STRATEGY_MANUAL:

                return new Status(
                    $statusResponse->result->purchase_units[0]->custom_id,
                    $response['orderID'],
                    '',
                    $statusResponse->result->status == 'APPROVED' ? StatusInterface::STATUS_AUTHORIZED : StatusInterface::STATUS_CANCELLED,
                    [
                    ]
                );

            case self::CAPTURE_STRATEGY_AUTOMATIC:
                return $this->executeDebit();

            default:
                throw new InvalidConfigException("Unknown capture strategy '" . $this->captureStrategy . "'");
        }
    }

    /**
     * Returns the authorized data from payment provider
     *
     * @return array
     */
    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }

    /**
     * Set authorized data from payment provider
     *
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData)
    {
        $this->authorizedData = $authorizedData;
    }

    /**
     * Executes payment
     *
     * @param PriceInterface $price
     * @param string $reference
     *
     * @return StatusInterface
     */
    public function executeDebit(PriceInterface $price = null, $reference = null)
    {
        if (null !== $price) {
            throw new \Exception('Setting other price than defined in Order not supported by paypal api');
        }

        $orderId = $this->getAuthorizedData()['orderID'];
        $statusResponse = $this->payPalHttpClient->execute(new OrdersCaptureRequest($orderId));

        return new Status(
            $statusResponse->result->purchase_units[0]->payments->captures[0]->custom_id,
            $orderId,
            '',
            $statusResponse->result->status == 'COMPLETED' ? StatusInterface::STATUS_CLEARED : StatusInterface::STATUS_CANCELLED,
            [
                'transactionId' => $statusResponse->result->purchase_units[0]->payments->captures[0]->id,
            ]
        );
    }

    /**
     * Executes credit
     *
     * @param PriceInterface $price
     * @param string $reference
     * @param string $transactionId
     *
     * @return StatusInterface
     */
    public function executeCredit(PriceInterface $price, $reference, $transactionId)
    {
        throw new \Exception('not implemented');
    }

    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            'mode',
            'client_id',
            'client_secret',
            'shipping_preference',
            'user_action',
            'capture_strategy',
        ]);

        $resolver
            ->setDefault('mode', 'sandbox')
            ->setAllowedValues('mode', ['sandbox', 'production'])
            ->setDefault('shipping_preference', 'NO_SHIPPING')
            ->setAllowedValues('shipping_preference', ['GET_FROM_FILE', 'NO_SHIPPING', 'SET_PROVIDED_ADDRESS'])
            ->setDefault('user_action', 'PAY_NOW')
            ->setAllowedValues('user_action', ['CONTINUE', 'PAY_NOW'])
            ->setDefault('capture_strategy', self::CAPTURE_STRATEGY_AUTOMATIC)
            ->setAllowedValues('capture_strategy', [self::CAPTURE_STRATEGY_AUTOMATIC, self::CAPTURE_STRATEGY_MANUAL]);

        $notEmptyValidator = function ($value) {
            return !empty($value);
        };

        foreach ($resolver->getRequiredOptions() as $requiredProperty) {
            $resolver->setAllowedValues($requiredProperty, $notEmptyValidator);
        }

        return $resolver;
    }

    protected function processOptions(array $options)
    {
        parent::processOptions($options);

        $this->payPalHttpClient = $this->buildPayPalClient($options['client_id'], $options['client_secret'], $options['mode']);
        $this->clientId = $options['client_id'];

        $this->applicationContext = [
            'shipping_preference' => $options['shipping_preference'],
            'user_action' => $options['user_action'],
        ];

        $this->captureStrategy = $options['capture_strategy'];
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $mode
     *
     * @return PayPalHttpClient
     */
    protected function buildPayPalClient(string $clientId, string $clientSecret, string $mode = 'sandbox')
    {
        if ($mode == 'production') {
            $environment = new ProductionEnvironment($clientId, $clientSecret);
        } else {
            $environment = new SandboxEnvironment($clientId, $clientSecret);
        }

        return new PayPalHttpClient($environment);
    }

    /**
     * @param Currency|null $currency
     *
     * @return string
     */
    public function buildPaymentSDKLink(Currency $currency = null)
    {
        if (null === $currency) {
            $currency = $this->getEnvironment()->getDefaultCurrency();
        }

        return 'https://www.paypal.com/sdk/js?client-id=' . $this->clientId . '&currency=' . $currency->getShortName();
    }
}
