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
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;

use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\Config\HobexConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\HobexRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\SnippetResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;

/**
 * @deprecated since v6.8.0 and will be moved to package "pimcore/payment-hobex" in Pimcore 10.
 */
class Hobex extends AbstractPayment implements PaymentInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const LOCK_KEY = 'hobex-handleresponse-lock';

    const HOST_URL_TESTSYSTEM = 'https://test.oppwa.com';
    const HOST_URL_LIVESYSTEM = 'https://oppwa.com';

    const PAYMENT_TYPE_PREAUTHORIZATION = 'PA';
    const PAYMENT_TYPE_DEBIT = 'DB';
    const PAYMENT_TYPE_CREDIT = 'CD';
    const PAYMENT_TYPE_CAPTURE = 'CP';
    const PAYMENT_TYPE_REVERSAL = 'RV';
    const PAYMENT_TYPE_REFUND = 'RF';

    const TRANSACTION_CATEGORY_ECOMMERCE = 'EC';

    /**
     * @var LockFactory
     */
    protected $lockFactory;

    /** @var EngineInterface */
    private $template;

    private $authorizedData = [];

    /** @var HobexConfig */
    private $config;

    public function __construct(array $options, EngineInterface $template, LoggerInterface $hobexLogger, LockFactory $lockFactory)
    {
        $this->setLogger($hobexLogger);
        $this->template = $template;
        $this->configureOptions(new OptionsResolver())->resolve($options);

        $this->config = new HobexConfig();
        $this->config
            ->setEntityId($options['entityId'])
            ->setAuthorizationBearer($options['authorizationBearer'])
            ->setTestSystem($options['testSystem'])
            ->setHostURL($options['testSystem'] ? static::HOST_URL_TESTSYSTEM : static::HOST_URL_LIVESYSTEM)
            ->setPaymentMethods($options['payment_methods']) //Hobex terminology: "paymentBrands"
            ->setWebhookSecret($options['webhookSecret'])
        ;
        $this->lockFactory = $lockFactory;
    }

    /**
     * @return HobexConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Check options that have been passed by the main configuration
     *
     * @param OptionsResolver $resolver
     *
     * @return OptionsResolver
     */
    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('testSystem', false);
        $resolver->setDefault('payment_methods', []);
        $resolver->setRequired([
            'entityId',
            'authorizationBearer',
            'testSystem',
        ]);

        $resolver->setAllowedTypes('testSystem', 'bool');
        $resolver->setAllowedTypes('payment_methods', 'array');

        $notEmptyValidator = function ($value) {
            return !empty($value);
        };

        foreach ($resolver->getRequiredOptions() as $requiredProperty) {
            if (!in_array($requiredProperty, ['testSystem'])) {
                $resolver->setAllowedValues($requiredProperty, $notEmptyValidator);
            }
        }

        return $resolver;
    }

    /**
     * @return string
     */
    protected function getStartPaymentURL(): string
    {
        return $this->config->getHostURL().'/v1/checkouts';
    }

    /**
     * @inheritDoc
     * parameter configuration see https://hobex.docs.oppwa.com/reference/parameters
     *
     * @param HobexRequest $requestConfig
     *
     * @return SnippetResponse
     */
    public function startPayment(OrderAgentInterface $orderAgent, PriceInterface $price, AbstractRequest $requestConfig): StartPaymentResponseInterface
    {
        $client = new Client([
                'base_uri' => $this->getStartPaymentURL(),
                'headers' => [
                    'Authorization:Bearer' => $this->config->getAuthorizationBearer(),
                ],
            ]
        );

        $response = null;
        try {
            $params = [
                'entityId' => $this->config->getEntityId(),
                'amount' => $price->getAmount()->asString(2),
                'taxAmount' => $price->getAmount()->sub($price->getNetAmount())->asString(2),
                'currency' => $price->getCurrency()->getShortName(),
                'paymentType' => static::PAYMENT_TYPE_DEBIT,
                'merchantTransactionId' => $this->createMerchantId($orderAgent->getOrder()),
                'customParameters[\'internalTransactionId\']' => $orderAgent->getOrder()->getLastPaymentInfo()->getInternalPaymentId(),
                'transactionCategory' => static::TRANSACTION_CATEGORY_ECOMMERCE,
            ];

            if (!empty($requestConfig->getInvoiceId())) {
                $params['merchantInvoiceId'] = $requestConfig->getInvoiceId();
            }

            if (!empty($requestConfig->getMemo())) {
                $params['merchantMemo'] = $requestConfig->getMemo();
            }

            $params = $this->addCustomPaymentData($params);

            $response = $client->request('post', '', ['form_params' => $params]);
            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            $this->logger->debug('Received JSON response in '.self::class.'::initPayment', $jsonResponse);

            //result codes: see https://hobex.docs.oppwa.com/reference/resultCodes
            if ($jsonResponse && isset($jsonResponse['id'])) {
                $this->handleStartPaymentResponse($orderAgent, $jsonResponse, $requestConfig);
                $renderedWidget = $this->renderWidget($requestConfig, $jsonResponse['id']);
                $response = new SnippetResponse($orderAgent->getOrder(), $renderedWidget);

                return $response;
            }

            throw new \Exception('Could not parse response.');
        } catch (\Exception $e) {
            $this->logException('Cannot initialize payment', 'initPayment', $e, ['response' => $response]);
            throw $e;
        }
    }

    protected function logException(string $message, string $method, \Exception $e, array $logParams)
    {
        $this->logger->alert($message, array_merge([
            'class' => self::class,
            'method' => $method,
            'line' => $e->getLine(),
            'message' => $e->getMessage(), ],
            $logParams
        ));
    }

    /**
     * Hook for adding additional payment parameters to the request.
     * see https://hobex.docs.oppwa.com/reference/parameters
     *
     * @param array $params
     *
     * @return array
     */
    protected function addCustomPaymentData(array $params): array
    {
        return $params;
    }

    protected function renderWidget(HobexRequest $requestConfig, string $checkoutId): string
    {
        $js = '<script async src="'.$this->config->getHostURL().'/v1/paymentWidgets.js?checkoutId=%s"></script>';
        if ($requestConfig->getLocale()) {
            $js = '<script>
                        var wpwlOptions = {
                            locale: "'.$requestConfig->getLocale().'",
                            style: "'.$requestConfig->getStyle().'"
                        };
                    </script>'.$js;
        }
        $form = '<form action="%s" class="paymentWidgets" data-brands="%s"></form>';

        $brands = $this->config->getPaymentMethods();
        if (!empty($requestConfig->getBrands())) {
            $brands = $requestConfig->getBrands();
        }

        $renderedWidget = sprintf($js.$form, $checkoutId, $requestConfig->getShopperResultUrl(), implode(' ', $brands));

        return $renderedWidget;
    }

    /**
     * Handles response of payment provider and creates payment status object.
     *
     * @param array|StatusInterface $response
     *
     * @return StatusInterface
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {
        $responseStatus = StatusInterface::STATUS_PENDING;
        $checkoutId = $response['id'];
        $lock = $this->lockFactory->createLock(self::LOCK_KEY . $checkoutId);
        $lock->acquire(true);

        try {
            $jsonResponse = null;
            if ($response['base64Content']) {
                $jsonResponse = $this->handleWebhookResponse($response);
            }
            if (!$jsonResponse) {
                $transactionId = $this->getExistingTransactionId($checkoutId);
                if ($transactionId) {
                    $resourcePath = sprintf('/v1/query/%s', $transactionId);
                } else {
                    $resourcePath = $response['resourcePath'];
                    if (!$resourcePath) {
                        $resourcePath = sprintf('/v1/checkouts/%s/payment', $checkoutId);
                    }
                }
                $client = new Client([
                        'base_uri' => $this->config->getHostURL() . $resourcePath,
                        'headers' => [
                            'Authorization:Bearer' => $this->config->getAuthorizationBearer(),
                        ],
                    ]
                );
                $response = $client->request('get', '?entityId=' . $this->config->getEntityId());
                $jsonResponse = json_decode($response->getBody()->getContents(), true);
            }
            $this->logger->debug('Received JSON response in ' . self::class . '::handleResponse', $jsonResponse);

            $internalPaymentId = $jsonResponse['customParameters']['internalTransactionId'];

            $clearedParams = [
                'paymentType' => $jsonResponse['paymentBrand'],
                'amount' => $jsonResponse['amount'],
                'currency' => $jsonResponse['currency'],
                'merchantMemo' => $jsonResponse['merchantMemo'],
                'paymentState' => $jsonResponse['result']['code'],
                'extId' => $jsonResponse['id'],
                'checkoutId' => $jsonResponse['ndc'],
                'transactionId' => $jsonResponse['merchantTransactionId'],
            ];

            $this->setAuthorizedData($clearedParams);

            //https://hobex.docs.oppwa.com/reference/resultCodes
            if ($this->isSuccess($jsonResponse['result']['code'])) {
                $paymentType = $jsonResponse['paymentType'];
                switch ($paymentType) {
                    case self::PAYMENT_TYPE_DEBIT:
                        $responseStatus = StatusInterface::STATUS_CLEARED;
                        break;
                    default: $responseStatus = StatusInterface::STATUS_AUTHORIZED;
                }
            }

            $providerData = $this->createProviderData($jsonResponse);

            $responseStatus = new Status(
                $internalPaymentId, //internal Payment ID
                $jsonResponse['id'], //paymentReference
                $jsonResponse['result']['description'],
                $responseStatus,
                $providerData
            );
        } catch (\Exception $e) {
            $this->logException('Could not process payment response.', 'handleResponse', $e, ['response' => $response]);
            throw $e;
        } finally {
            $lock->release();
        }

        return $responseStatus;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getName()
    {
        return 'Hobex';
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
     * Start payment and build form, including token.
     *
     * @param PriceInterface $price
     * @param array $config
     *
     * @return FormBuilderInterface
     *
     * @throws \Exception
     */
    public function initPayment(PriceInterface $price, array $config)
    {
        throw new \Exception('Hobex is only supported in V7.');
    }

    /**
     * Executes payment
     *
     *  if price is given, recurPayment command is executed
     *  if no price is given, amount from authorized Data is used and deposit command is executed
     *
     * @param PriceInterface $price
     * @param string $reference
     *
     * @return StatusInterface
     *
     * @throws \Exception
     */
    public function executeDebit(PriceInterface $price = null, $reference = null)
    {
        throw new NotImplementedException('executeDebit is not implemented yet.');
    }

    /**
     * Executes credit
     *
     * @param PriceInterface $price
     * @param string $reference
     * @param string $transactionId
     *
     * @return StatusInterface
     *
     * @throws \Exception
     */
    public function executeCredit(PriceInterface $price, $reference, $transactionId)
    {
        throw new NotImplementedException('executeCredit is not implemented yet.');
    }

    /**
     * https://hobex.docs.oppwa.com/reference/resultCodes
     *
     * @param string $code
     *
     * @return bool
     */
    protected function isSuccess($code)
    {
        return strpos($code, '000.100.') === 0 || strpos($code, '000.000.') === 0;
    }

    /**
     * unlike documented, merchantTransactionId only allows numeric values (N20)
     * might be required and should be unique
     * creates numeric value from internal paymentId
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     *
     * @return int
     */
    protected function createMerchantId(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order)
    {
        if ($order->getLastPaymentInfo()) {
            $internalPaymentId = $order->getLastPaymentInfo()->getInternalPaymentId();
            if ($internalPaymentId) {
                $txtId = (int) preg_replace('/\D/', 0, str_replace('payment_', '', $internalPaymentId));

                return $txtId;
            }
        }

        return 0;
    }

    /**
     * prefix all keys with 'hobex_' to allow pimcore to store the values in fieldcollection PaymentInfo
     *
     * @param array $jsonResponse
     * @param string $prefix
     *
     * @return array
     */
    protected function createProviderData($jsonResponse, $prefix = 'hobex_')
    {
        $providerData = [];

        // prefix keys with hobex_ to allow pimcore to store the values in Fieldcollection PaymentInfo
        foreach ($jsonResponse as $key => $value) {
            if (is_array($value)) {
                $data = $this->createProviderData($value, $prefix . $key . '_');
                $providerData = $providerData + $data;
            } else {
                $providerData[$prefix . $key] = $value;
            }
        }

        return $providerData;
    }

    /** hook to customized handle jsonResponse of start payment */
    protected function handleStartPaymentResponse(
        OrderAgentInterface $orderAgent,
        $jsonResponse,
        \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\HobexRequest $requestConfig
    ) {
    }

    /**
     * @param string $checkoutId
     *
     * @return string|null
     *
     * @throws \Exception
     */
    protected function getExistingTransactionId($checkoutId)
    {
        $transactionId = null;
        $list = OnlineShopOrder::getList([
            'objectbricks' => ['PaymentProviderHobex'],

        ]);
        $list->setCondition('PaymentProviderHobex.auth_checkoutId = ?', $checkoutId);
        if ($list->count() > 0) {
            /** @var OnlineShopOrder $order */
            $order = $list->current();
            $hobex = $order->getPaymentProvider()->getPaymentProviderHobex();
            $status = $hobex->getAuth_paymentState();
            if (!$this->isStatusPending($status)) {
                $transactionId = $hobex->getAuth_extId();
            }
        }

        return $transactionId;
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    protected function isStatusPending($status)
    {
        return strpos($status, '000.200.') === 0;
    }

    /**
     * @parama array  $response
     *
     * @return array|null
     */
    protected function handleWebhookResponse($response)
    {
        $secret = $this->config->getWebhookSecret();
        if (!$secret) {
            $this->logger->debug('can not handle webhook response in ' . self::class . '::handleWebhookResponse, no webhook secret defined');
        } else {
            $authTag = $response['authTag'];
            $initVector = $response['initVector'];

            $key = hex2bin($secret);
            $iv = hex2bin($initVector);
            $auth_tag = hex2bin($authTag);
            $cipher_text = hex2bin($response['base64Content']);
            $jsonResult = openssl_decrypt($cipher_text, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $auth_tag);
            $result = json_decode($jsonResult, true);
            if ($result['type'] == 'PAYMENT') {
                return $result['payload'];
            }
        }

        return null;
    }
}
