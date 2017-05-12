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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Config\Config;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Forms;

class QPay implements IPayment
{
    // supported hashing algorithms
    const HASH_ALGO_MD5 = 'md5';
    const HASH_ALGO_HMAC_SHA512 = 'hmac_sha512';

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
        'shopId' // value=mobile for mobile checkout page
    ];

    /**
     * @param Config $config
     *
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        $settings = $config->config->{$config->mode};
        if ($settings->secret == '' || $settings->customer == '') {
            throw new \Exception('payment configuration is wrong. secret or customer is empty !');
        }

        $this->secret = $settings->secret;
        $this->customer = $settings->customer;
        $this->toolkitPassword = $settings->toolkitPassword;

        if ($settings->paymenttype) {
            $this->paymenttype = $settings->paymenttype;
        }

        $this->initHashAlgorithm($settings);
        $this->initOptionalPaymentProperties($settings);
    }

    /**
     * Initialize hash algorithm
     *
     * @param Config $settings
     */
    protected function initHashAlgorithm(Config $settings)
    {
        if ($settings->hashAlgorithm) {
            $hashAlgorithm = (string) $settings->hashAlgorithm;
            if (!in_array($hashAlgorithm, [static::HASH_ALGO_MD5, static::HASH_ALGO_HMAC_SHA512])) {
                throw new \InvalidArgumentException(sprintf('%s is no valid hash algorithm', $hashAlgorithm));
            }

            $this->hashAlgorithm = $hashAlgorithm;
        }
    }

    /**
     * Initialize optional payment properties from config
     *
     * @param Config $settings
     */
    protected function initOptionalPaymentProperties(Config $settings)
    {
        if ($settings->optionalPaymentProperties instanceof Config) {
            $configArray = $settings->optionalPaymentProperties->toArray();
            if (isset($configArray['property'])) {
                // Zend_Config behaves differently if there's just a single object in the set
                if (is_array($configArray['property'])) {
                    foreach ($configArray['property'] as $optionalProperty) {
                        $this->optionalPaymentProperties[] = $optionalProperty;
                    }
                } elseif (is_string($configArray['property'])) {
                    $this->optionalPaymentProperties[] = $configArray['property'];
                }
            }

            $this->optionalPaymentProperties = array_unique($this->optionalPaymentProperties);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Qpay';
    }

    /**
     * @param IPrice $price
     * @param array $config
     *
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    public function initPayment(IPrice $price, array $config)
    {
        // check params
        $required = [
            'successURL' => null, 'cancelURL' => null, 'failureURL' => null, 'serviceURL' => null, 'orderDescription' => null, 'orderIdent' => null, 'language' => null
        ];

        $check = array_intersect_key($config, $required);
        if (count($required) != count($check)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $check)))));
        }

        // collect payment data
        $paymentData['secret'] = $this->secret;
        $paymentData['customerId'] = $this->customer;
        $paymentData['amount'] = round($price->getAmount(), 2);
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

        //form name needs to be null in order to make sure the element names are correct - and not FORMNAME[ELEMENTNAME]
        $form = Forms::createFormFactory()->createNamedBuilder(null, FormType::class, [], [
            'attr' => ['id' => 'paymentForm']
        ]);
        $form->setAction('https://www.qenta.com/qpay/init.php');
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
     * @param mixed $response
     *
     * @return IStatus
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {
        //unsetting response document because it is not needed (and spams up log files)
        unset($response['document']);

        // check required fields
        $required = [
            'orderIdent' => null
        ];

        $authorizedData = [
            'orderNumber' => null, 'language' => null, 'amount' => null, 'currency' => null
        ];

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
                $response['orderIdent'], $response['orderNumber'], $response['avsResponseMessage'] ?: $response['message'] ?: 'fingerprint error', IStatus::STATUS_CANCELLED
            );
        }

        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData($authorizedData);

        // restore price object for payment status
        $price = new Price($authorizedData['amount'], new Currency($authorizedData['currency']));

        return new Status(
            $response['orderIdent'], $response['orderNumber'], $response['avsResponseMessage'] ?: $response['message'], $response['orderNumber'] !== null && $response['paymentState'] == 'SUCCESS'
                ? IStatus::STATUS_AUTHORIZED
                : IStatus::STATUS_CANCELLED, [
                'qpay_amount' => (string)$price, 'qpay_paymentType' => $response['paymentType'], 'qpay_paymentState' => $response['paymentState'], 'qpay_response' => $response
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
     *  if price is given, recurPayment command is executed
     *  if no price is given, amount from authorized Data is used and deposit command is executed
     *
     * @param IPrice $price
     * @param string                      $reference
     *
     * @return IStatus
     *
     * @throws \Exception
     */
    public function executeDebit(IPrice $price = null, $reference = null)
    {
        // https://integration.wirecard.at/doku.php/wcp:toolkit_light:start
        // https://integration.wirecard.at/doku.php/wcs:backend_operations?s[]=deposit
        // https://integration.wirecard.at/doku.php/backend:deposit

        if ($price) {
            // recurPayment

            $request = [
                'customerId' => $this->customer, 'toolkitPassword' => $this->toolkitPassword, 'command' => 'recurPayment', 'language' => $this->authorizedData['language'], 'requestFingerprint' => '', 'orderDescription' => $reference, 'sourceOrderNumber' => $this->authorizedData['orderNumber'], 'amount' => $price->getAmount(), 'currency' => $price->getCurrency()->getShortName()
            ];

            // add fingerprint
            $request['requestFingerprint'] = $this->computeFingerprint([
                $request['customerId'], $request['toolkitPassword'], $this->secret, $request['command'], $request['language'], $request['sourceOrderNumber'], $request['orderDescription'], $request['amount'], $request['currency']
            ]);
        } else {
            // default clearing auth
            $price = new Price($this->authorizedData['amount'], new Currency($this->authorizedData['currency']));

            $request = [
                'customerId' => $this->customer, 'toolkitPassword' => $this->toolkitPassword, 'command' => 'deposit', 'language' => $this->authorizedData['language'], 'requestFingerprint' => '', 'orderNumber' => $this->authorizedData['orderNumber'], 'amount' => $price->getAmount(), 'currency' => $price->getCurrency()->getShortName()
            ];

            // add fingerprint
            $request['requestFingerprint'] = $this->computeFingerprint([
                $request['customerId'], $request['toolkitPassword'], $this->secret, $request['command'], $request['language'], $request['orderNumber'], $request['amount'], $request['currency']
            ]);
        }

        // execute request
        $response = $this->serverToServerRequest('https://checkout.wirecard.com/page/toolkit.php', $request);

        // check response
        if ($response['status'] === '0') {
            // Operation successfully done.

            return new Status(
                $reference, $response['paymentNumber'] ?: $response['orderNumber'], '', IStatus::STATUS_CLEARED, [
                    'qpay_amount' => (string)$price, 'qpay_command' => $request['command'], 'qpay_response' => $response
                ]
            );
        } elseif ($response['errors']) {
            // https://integration.wirecard.at/doku.php/backend:response_parameters

            $error = [];
            for ($e = 1; $e <= $response['errors']; $e++) {
                $error[] = $response['error_' . $e . '_error_message'];
            }

            return new Status(
                $reference, $response['paymentNumber'] ?: $response['orderNumber'], implode("\n", $error), IStatus::STATUS_CANCELLED, [
                    'qpay_amount' => (string)$price, 'qpay_command' => $request['command'], 'qpay_response' => $response
                ]
            );
        } else {
            throw new \Exception(print_r($response, true));
        }
    }

    /**
     * execute credit
     *
     * @param IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return IStatus
     */
    public function executeCredit(IPrice $price, $reference, $transactionId)
    {
        // init request
        $request = [
            'customerId' => $this->customer, 'toolkitPassword' => $this->toolkitPassword, 'command' => 'refund', 'language' => $this->authorizedData['language'], 'requestFingerprint' => '', 'orderNumber' => $reference, 'amount' => $price->getAmount(), 'currency' => $price->getCurrency()->getShortName(), 'merchantReference' => $transactionId
        ];

        // add fingerprint
        $request['requestFingerprint'] = $this->computeFingerprint([
            $request['customerId'], $request['toolkitPassword'], $this->secret, $request['command'], $request['language'], $request['orderNumber'], $request['amount'], $request['currency'], $request['merchantReference']
        ]);

        // execute request
        $response = $this->serverToServerRequest('/page/toolkit.php', $request);

        // check response
        if ($response['status'] === '0') {
            // Operation successfully done.

            return new Status(
                $transactionId, $reference, 'executeCredit', IStatus::STATUS_CLEARED, [
                    'qpay_amount' => (string)$price, 'qpay_command' => $request['command'], 'qpay_response' => $response
                ]
            );
        } elseif ($response['errorCode']) {
            // https://integration.wirecard.at/doku.php/backend:response_parameters

            return new Status(
                $transactionId, $reference, $response['message'], IStatus::STATUS_CANCELLED, [
                    'qpay_amount' => (string)$price, 'qpay_command' => $request['command'], 'qpay_response' => $response
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
        $data   = implode('', $params);
        $result = null;

        switch ($this->hashAlgorithm) {
            case static::HASH_ALGO_MD5:
                return $this->computeMd5Fingerprint($data);

            case static::HASH_ALGO_HMAC_SHA512:
                return $this->computeHmacSha512Fingerprint($data);
        }
    }

    /**
     * Compute MD5 fingerprint
     *
     * @param $data
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
     * @param $data
     *
     * @return string
     */
    protected function computeHmacSha512Fingerprint($data)
    {
        return hash_hmac('sha512', $data, $this->secret);
    }

    /**
     * @param $url
     * @param $params
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
}
