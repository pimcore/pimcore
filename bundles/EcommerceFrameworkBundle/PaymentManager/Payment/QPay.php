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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\RecurringPaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\FormResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Listing\Concrete;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated since v6.8.0 and will be removed in Pimcore 10.
 */
class QPay extends AbstractPayment implements \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface, RecurringPaymentInterface
{
    // supported hashing algorithms
    const HASH_ALGO_MD5 = 'md5';
    const HASH_ALGO_HMAC_SHA512 = 'hmac_sha512';

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $customer;

    /**
     * @var string
     */
    protected $toolkitPassword;

    /**
     * @var string
     */
    protected $paymenttype = 'SELECT';

    /**
     * Keep old implementation for backwards compatibility
     *
     * @var string
     */
    protected $hashAlgorithm = self::HASH_ALGO_MD5;

    /**
     * @var string[]
     */
    protected $authorizedData;

    /**
     * Whitelist of optional properties allowed for payment init
     *
     * @var array
     */
    protected $optionalPaymentProperties = [
        'imageURL',
        'confirmURL',
        'confirmMail',
        'displayText',
        'shopId', // value=mobile for mobile checkout page
    ];

    /**
     * Data properties to set from response to payment provider brick.
     *
     * @var array
     */
    protected $authorizedDataProperties = [
        'orderNumber',
        'language',
        'amount',
        'currency',
        'paymentType',
        'bankAccountIBAN',
        'bankAccountOwner',
        'anonymousPan',
        'maskedPan',
        'expiry',
    ];

    public function __construct(array $options, FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;

        $this->processOptions(
            $this->configureOptions(new OptionsResolver())->resolve($options)
        );
    }

    protected function processOptions(array $options)
    {
        parent::processOptions($options);

        $this->customer = $options['customer'];
        $this->secret = $options['secret'];

        if (isset($options['toolkit_password'])) {
            $this->toolkitPassword = $options['toolkit_password'];
        }

        if (isset($options['payment_type'])) {
            $this->paymenttype = $options['payment_type'];
        }

        if (isset($options['hash_algorithm'])) {
            $this->hashAlgorithm = $options['hash_algorithm'];
        }

        if (isset($options['optional_payment_properties'])) {
            $this->optionalPaymentProperties = array_unique(array_merge(
                $this->optionalPaymentProperties,
                $options['optional_payment_properties']
            ));
        }
    }

    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            'customer',
            'secret',
        ]);

        $resolver
            ->setDefined('toolkit_password')
            ->setAllowedTypes('toolkit_password', ['string']);

        $resolver
            ->setDefined('payment_type')
            ->setAllowedTypes('payment_type', ['string']);

        $resolver
            ->setDefined('hash_algorithm')
            ->setAllowedValues('hash_algorithm', [
                self::HASH_ALGO_MD5,
                self::HASH_ALGO_HMAC_SHA512,
            ]);

        $resolver
            ->setDefined('optional_payment_properties')
            ->setAllowedTypes('optional_payment_properties', 'array');

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
        return 'Qpay';
    }

    /**
     * @param array $formAttributes
     * @param PriceInterface $price
     * @param array $config
     *
     * @return array
     */
    protected function extendFormAttributes(array $formAttributes, PriceInterface $price, array $config): array
    {
        return $formAttributes;
    }

    /**
     * Start payment
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
        // check params
        $required = $this->getRequiredRequestFields();

        $check = array_intersect_key($config, $required);
        if (count($required) != count($check)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $check)))));
        }

        // collect payment data
        $paymentData['secret'] = $this->secret;
        $paymentData['customerId'] = $this->customer;
        $paymentData['amount'] = round($price->getAmount()->asNumeric(), 2);
        $paymentData['currency'] = $price->getCurrency()->getShortName();
        $paymentData['duplicateRequestCheck'] = 'yes';

        // can be overridden by adding paymentType to optional properties and passing its value in config
        $paymentData['paymentType'] = $this->paymenttype;

        foreach ($required as $property => $null) {
            $paymentData[$property] = $config[$property];
        }

        // handle optional properties
        foreach ($this->optionalPaymentProperties as $optionalProperty) {
            if (array_key_exists($optionalProperty, $config)) {
                $paymentData[$optionalProperty] = $config[$optionalProperty];
            }
        }

        // set fingerprint order
        $paymentData['requestFingerprintOrder'] = ''; // make sure the key is in the order array
        $paymentData['requestFingerprintOrder'] = implode(',', array_keys($paymentData));

        // compute fingerprint
        $fingerprint = $this->computeFingerprint(array_values($paymentData));

        // create form
        $formData = [];
        $formAttributes = [];

        $formAttributes['id'] = 'paymentForm';

        $formAttributes = $this->extendFormAttributes(['id' => 'paymentForm'], $price, $config);

        //form name needs to be null in order to make sure the element names are correct - and not FORMNAME[ELEMENTNAME]
        $form = $this->formFactory->createNamedBuilder(null, FormType::class, [], [
            'attr' => $formAttributes,
        ]);

        $form->setAction('https://checkout.wirecard.com/page/init.php');
        $form->setMethod('post');
        $form->setAttribute('data-currency', 'EUR');

        // omit these keys from the form
        $blacklistedFormKeys = ['secret'];
        foreach ($paymentData as $property => $value) {
            if (in_array($property, $blacklistedFormKeys)) {
                continue;
            }

            $form->add($property, HiddenType::class);
            $formData[$property] = $value;
        }

        // add fingerprint to request
        $form->add('requestFingerprint', HiddenType::class);
        $formData['requestFingerprint'] = $fingerprint;

        // add submit button
        $form->add('submitbutton', SubmitType::class, ['attr' => ['class' => 'btn']]);

        $form->setData($formData);

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function startPayment(OrderAgentInterface $orderAgent, PriceInterface $price, AbstractRequest $config): StartPaymentResponseInterface
    {
        $response = $this->initPayment($price, $config->asArray());

        return new FormResponse($orderAgent->getOrder(), $response);
    }

    /**
     * Handles response of payment provider and creates payment status object
     *
     * @param mixed $response
     *
     * @return StatusInterface
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {
        //unsetting response document because it is not needed (and spams up log files)
        unset($response['document']);

        // check required fields
        $required = [
            'orderIdent' => null,
        ];

        /* Initialize authorized data with null values */
        $authorizedDataProperties = $this->getAuthorizedDataProperties();
        $authorizedData = [];
        foreach ($authorizedDataProperties as $property) {
            $authorizedData[$property] = null;
        }

        // check fields
        $check = array_intersect_key($response, $required);
        if (count($required) != count($check)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $check)))));
        }

        // build fingerprint params
        $fingerprintParams = [];
        $fingerprintFields = explode(',', $response['responseFingerprintOrder']);
        foreach ($fingerprintFields as $field) {
            $fingerprintParams[] = $field === 'secret' ? $this->secret : $response[$field];
        }

        // compute and check fingerprint
        $fingerprint = $this->computeFingerprint($fingerprintParams);
        if ($response['paymentState'] !== 'FAILURE' && $fingerprint != $response['responseFingerprint']) {
            // fingerprint is wrong, ignore this response
            return new Status(
                $response['orderIdent'],
                $response['orderNumber'],
                $response['avsResponseMessage'] ?: $response['message'] ?: 'fingerprint error',
                StatusInterface::STATUS_CANCELLED
            );
        }

        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData($authorizedData);

        // restore price object for payment status
        $decimal = Decimal::zero();
        if ($authorizedData['amount']) {
            $decimal = Decimal::create($authorizedData['amount']);
        }
        $price = new Price($decimal, new Currency($authorizedData['currency']));

        return new Status(
            $response['orderIdent'],
            $response['orderNumber'],
            $response['avsResponseMessage'] ?: $response['message'],
            $response['orderNumber'] !== null && $response['paymentState'] == 'SUCCESS'
                ? StatusInterface::STATUS_AUTHORIZED
                : StatusInterface::STATUS_CANCELLED,
            [
                'qpay_amount' => (string)$price,
                'qpay_paymentType' => $response['paymentType'],
                'qpay_paymentState' => $response['paymentState'],
                'qpay_response' => $response,
            ]
        );
    }

    /**
     * @return array
     */
    protected function getRequiredRequestFields(): array
    {
        return [
            'successURL' => null,
            'cancelURL' => null,
            'failureURL' => null,
            'serviceURL' => null,
            'orderDescription' => null,
            'orderIdent' => null,
            'language' => null,
        ];
    }

    public function getAuthorizedDataProperties()
    {
        return $this->authorizedDataProperties;
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
     * Executes payment
     *
     * If price is given, recurPayment command is executed
     * If no price is given, amount from authorized Data is used and deposit command is executed
     *
     * Transaction-based operations by payment method: https://guides.wirecard.at/back-end_operations:transaction-based:table
     *
     * Recurring payment how to:                https://guides.wirecard.at/how_to:recurpayment
     * Recurring payment backend operation:     https://guides.wirecard.at/back-end_operations:transaction-based:recurpayment
     *
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
        if ($price) {
            // recurPayment

            $request = [
                'customerId' => $this->customer,
                'toolkitPassword' => $this->toolkitPassword,
                'command' => 'recurPayment',
                'language' => $this->authorizedData['language'],
                'requestFingerprint' => '',
                'orderDescription' => $reference,
                'sourceOrderNumber' => $this->authorizedData['orderNumber'],
                'amount' => $price->getAmount()->asNumeric(),
                'currency' => $price->getCurrency()->getShortName(),
            ];

            // add fingerprint
            $request['requestFingerprint'] = $this->computeFingerprint([
                $request['customerId'],
                $request['toolkitPassword'],
                $this->secret,
                $request['command'],
                $request['language'],
                $request['sourceOrderNumber'],
                $request['orderDescription'],
                $request['amount'],
                $request['currency'],
            ]);
        } else {
            // default clearing auth
            $price = new Price(Decimal::create($this->authorizedData['amount']), new Currency($this->authorizedData['currency']));

            $request = [
                'customerId' => $this->customer,
                'toolkitPassword' => $this->toolkitPassword,
                'command' => 'deposit',
                'language' => $this->authorizedData['language'],
                'requestFingerprint' => '',
                'orderNumber' => $this->authorizedData['orderNumber'],
                'amount' => $price->getAmount()->asNumeric(),
                'currency' => $price->getCurrency()->getShortName(),
            ];

            // add fingerprint
            $request['requestFingerprint'] = $this->computeFingerprint([
                $request['customerId'],
                $request['toolkitPassword'],
                $this->secret,
                $request['command'],
                $request['language'],
                $request['orderNumber'],
                $request['amount'],
                $request['currency'],
            ]);
        }

        // execute request
        $response = $this->serverToServerRequest('https://checkout.wirecard.com/page/toolkit.php', $request);

        // handle
        $properties = $this->getAuthorizedDataProperties();

        $authorizedData = array_intersect_key($response, array_flip($properties));
        $this->setAuthorizedData($authorizedData);

        // check response
        if ($response['status'] === '0') {
            // Operation successfully done.

            return new Status(
                $reference,
                $response['paymentNumber'] ?: $response['orderNumber'],
                '',
                StatusInterface::STATUS_CLEARED,
                [
                    'qpay_amount' => (string)$price,
                    'qpay_command' => $request['command'],
                    'qpay_response' => $response,
                ]
            );
        } elseif ($response['errors']) {
            // https://integration.wirecard.at/doku.php/backend:response_parameters

            $error = [];
            for ($e = 1; $e <= $response['errors']; $e++) {
                $error[] = $response['error_' . $e . '_error_message'];
            }

            return new Status(
                $reference,
                $response['paymentNumber'] ?: $response['orderNumber'],
                implode("\n", $error),
                StatusInterface::STATUS_CANCELLED,
                [
                    'qpay_amount' => (string)$price,
                    'qpay_command' => $request['command'],
                    'qpay_response' => $response,
                ]
            );
        } else {
            throw new \Exception(print_r($response, true));
        }
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
        // init request
        $request = [
            'customerId' => $this->customer,
            'toolkitPassword' => $this->toolkitPassword,
            'command' => 'refund',
            'language' => $this->authorizedData['language'],
            'requestFingerprint' => '',
            'orderNumber' => $reference,
            'amount' => $price->getAmount()->asNumeric(),
            'currency' => $price->getCurrency()->getShortName(),
            'merchantReference' => $transactionId,
        ];

        // add fingerprint
        $request['requestFingerprint'] = $this->computeFingerprint([
            $request['customerId'],
            $request['toolkitPassword'],
            $this->secret,
            $request['command'],
            $request['language'],
            $request['orderNumber'],
            $request['amount'],
            $request['currency'],
            $request['merchantReference'],
        ]);

        // execute request
        $response = $this->serverToServerRequest('/page/toolkit.php', $request);

        // check response
        if ($response['status'] === '0') {
            // Operation successfully done.

            return new Status(
                $transactionId,
                $reference,
                'executeCredit',
                StatusInterface::STATUS_CLEARED,
                [
                    'qpay_amount' => (string)$price,
                    'qpay_command' => $request['command'],
                    'qpay_response' => $response,
                ]
            );
        } elseif ($response['errorCode']) {
            // https://integration.wirecard.at/doku.php/backend:response_parameters

            return new Status(
                $transactionId,
                $reference,
                $response['message'],
                StatusInterface::STATUS_CANCELLED,
                [
                    'qpay_amount' => (string)$price,
                    'qpay_command' => $request['command'],
                    'qpay_response' => $response,
                ]
            );
        } else {
            throw new \Exception(print_r($response, true));
        }
    }

    /**
     * Compute fingerprint for array of input parameters depending on configured algorithm
     *
     * @param array $params
     *
     * @return string
     */
    protected function computeFingerprint(array $params)
    {
        $data = implode('', $params);
        $result = null;

        switch ($this->hashAlgorithm) {
            case static::HASH_ALGO_MD5:
                return $this->computeMd5Fingerprint($data);

            case static::HASH_ALGO_HMAC_SHA512:
                return $this->computeHmacSha512Fingerprint($data);

            default:
                throw new \LogicException(sprintf('Hash algorithm "%s" is not supported', $this->hashAlgorithm));
        }
    }

    /**
     * Compute MD5 fingerprint
     *
     * @param string $data
     *
     * @return string
     */
    protected function computeMd5Fingerprint($data)
    {
        return md5($data);
    }

    /**
     * Calculate HMAC_SHA512 fingerprint
     *
     * @param string $data
     *
     * @return string
     */
    protected function computeHmacSha512Fingerprint($data)
    {
        return hash_hmac('sha512', $data, $this->secret);
    }

    /**
     * @param string $url
     * @param array $params
     *
     * @return string[]
     */
    protected function serverToServerRequest($url, $params)
    {
        $postFields = '';
        foreach ($params as $key => $value) {
            $postFields .= $key . '=' . $value . '&';
        }

        $postFields = substr($postFields, 0, strlen($postFields) - 1);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_PORT, 443);
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $response = curl_exec($curl);
        curl_close($curl);

        $r = [];
        parse_str($response, $r);

        return $r;
    }

    public function setPaymentType($paymentType)
    {
        $this->paymenttype = $paymentType;
    }

    public function getPaymentType()
    {
        return $this->paymenttype;
    }

    public function applyRecurringPaymentCondition(Concrete $orders, $additionalParameters = [])
    {
        $providerBrickName = "PaymentProvider{$this->getName()}";
        $orders->addObjectbrick($providerBrickName);

        $orders->addConditionParam("{$providerBrickName}.auth_orderNumber IS NOT NULL");
        /* recurring payment possible for 400 days */
        $orders->addConditionParam("FROM_UNIXTIME({$providerBrickName}.paymentFinished) > (NOW() - INTERVAL 400 DAY)");
        /* consider credit card expiry if available */
        $orders->addConditionParam("({$providerBrickName}.auth_expiry IS NULL OR LAST_DAY(STR_TO_DATE({$providerBrickName}.auth_expiry, '%m/%Y')) >= CURDATE())");

        if ($paymentMethod = $additionalParameters['paymentMethod']) {
            $orders->addConditionParam("{$providerBrickName}.auth_paymentType = ?", $paymentMethod);
        }

        $orders->setOrderKey("`{$providerBrickName}`.`paymentFinished`", false);
        $orders->setOrder('DESC');

        return $orders;
    }

    /**
     * The response of a recurring payment may not contain all the data contained in the source order (like paymentmethod, ccard-number, etc)
     * so it is important to set at least the source order to the current payment brick.
     *
     * @param AbstractOrder $sourceOrder
     * @param mixed $paymentBrick
     *
     * @return mixed
     */
    public function setRecurringPaymentSourceOrderData(AbstractOrder $sourceOrder, $paymentBrick)
    {
        if (method_exists($paymentBrick, 'setSourceOrder')) {
            $paymentBrick->setSourceOrder($sourceOrder);
        } else {
            Logger::err('Could not set source order for performed recurring payment.');
        }

        $providerDataGetter = 'getPaymentProvider' . $this->getName();
        $recurringPaymentProperties = $this->getAuthorizedDataProperties();

        $sourceOrderPaymentBrick = $sourceOrder->getPaymentProvider()->{$providerDataGetter}();

        // if no data is provided in current payment brick, set it from the source order
        foreach ($recurringPaymentProperties as $field) {
            $setter = 'setAuth_' . $field;
            $getter = 'getAuth_' . $field;

            if (method_exists($sourceOrderPaymentBrick, $getter)
                && method_exists($paymentBrick, $setter)
                && method_exists($paymentBrick, $getter)
                && empty($paymentBrick->$getter())
            ) {
                $paymentBrick->{$setter}($sourceOrderPaymentBrick->{$getter});
            }
        }

        return $paymentBrick;
    }
}
