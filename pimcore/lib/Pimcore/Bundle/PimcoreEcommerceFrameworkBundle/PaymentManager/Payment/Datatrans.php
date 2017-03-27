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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Config\Config;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Forms;

class Datatrans implements IPayment
{
    const TRANS_TYPE_DEBIT = '05';
    const TRANS_TYPE_CREDIT = '06';

    const AUTH_TYPE_AUTHORIZATION = 'NOA';
    const AUTH_TYPE_FINAL_AUTHORIZATION = 'FOA'; // final authorization (MasterCard/Maestro)

    /**
     * @var string
     */
    protected $endpoint = [];

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $sign;

    /**
     * @var bool
     */
    protected $useDigitalSignature = false;

    /**
     * @var string[]
     */
    protected $authorizedData = [];

    /**
     * @var IStatus
     */
    protected $paymentStatus;

    /**
     * @param Config $config
     *
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        $settings = $config->config->{$config->mode};
        if($settings->sign == '' || $settings->merchantId == '')
        {
            throw new \Exception('payment configuration is wrong. secret or customer is empty !');
        }

        $this->merchantId = $settings->merchantId;
        $this->sign = $settings->sign;

        // use digitally signed
        if ($settings->digitalSignature)
        {
            $this->useDigitalSignature = (bool)$settings->digitalSignature;
        }


        if($config->mode == 'live')
        {
            $this->endpoint['form'] = 'https://payment.datatrans.biz/upp/jsp/upStart.jsp';
            $this->endpoint['script'] = 'https://payment.datatrans.biz/upp/payment/js/datatrans-1.0.2.js';
            $this->endpoint['xmlAuthorize'] = 'https://payment.datatrans.biz/upp/jsp/XML_authorize.jsp';
            $this->endpoint['xmlProcessor'] = 'https://payment.datatrans.biz/upp/jsp/XML_processor.jsp';
        }
        else
        {
            $this->endpoint['form'] = 'https://pilot.datatrans.biz/upp/jsp/upStart.jsp';
            $this->endpoint['script'] = 'https://pilot.datatrans.biz/upp/payment/js/datatrans-1.0.2.js';
            $this->endpoint['xmlAuthorize'] = 'https://pilot.datatrans.biz/upp/jsp/XML_authorize.jsp';
            $this->endpoint['xmlProcessor'] = 'https://pilot.datatrans.biz/upp/jsp/XML_processor.jsp';
        }

    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Datatrans';
    }

    /**
     * start payment
     * @param IPrice $price
     * @param array                       $config
     *
     * @return \Symfony\Component\Form\FormBuilderInterface
     * @throws \Exception
     *
     * @see https://pilot.datatrans.biz/showcase/doc/Technical_Implementation_Guide.pdf
     * @see http://pilot.datatrans.biz/showcase/doc/XML_Authorisation.pdf
     */
    public function initPayment(IPrice $price, array $config)
    {
        // check params
        $required = [  'successUrl' => null
                       , 'errorUrl' => null
                       , 'cancelUrl' => null
                       , 'refno' => null
                       , 'useAlias' => null
                       , 'reqtype' => null
                       , 'language' => null
        ];
        $config = array_intersect_key($config, $required);

        if(count($required) != count($config))
        {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
        }



        // collect payment data
        $paymentData['amount'] = round($price->getAmount(), 2) * 100;
        $paymentData['currency'] = $price->getCurrency()->getShortName();
        $paymentData['reqtype'] = $config['reqtype'];
        // NOA – Authorisation only (default)
        // CAA – Authorisation and settlement


        // create sign
        if(!$this->useDigitalSignature)
        {
            $sign = $this->sign;
        }
        else
        {
            $data = [
                'merchantId' => $this->merchantId
                , 'amount' => $paymentData['amount']
                , 'currency' => $paymentData['currency']
                , 'refno' => $config['refno']
            ];
            $sign = hash_hmac('SHA256', implode('', $data), hex2bin($this->sign));
        }


        $formAttributes = [];
        $formData = [];

        // used for lightbox version
        $formAttributes['data-language'] = $config['language'];
        $formAttributes['data-merchant-id'] = $this->merchantId;
        $formAttributes['data-sign'] = $sign;
        $formAttributes['data-amount'] =  $paymentData['amount'];
        $formAttributes['data-currency'] = $paymentData['currency'];
        $formAttributes['data-refno'] = $config['refno'];
        $formAttributes['data-reqtype'] = $paymentData['reqtype'];
        $formAttributes['data-script'] = $this->endpoint['script'];
        $formAttributes['data-success-url'] = $config['successUrl'];
        $formAttributes['data-error-url'] = $config['errorUrl'];
        $formAttributes['data-cancel-url'] = $config['cancelUrl'];
        if($config['useAlias'])
        {
            $formAttributes['data-use-alias'] = 'true';
        }

        // create form
        //form name needs to be null in order to make sure the element names are correct - and not FORMNAME[ELEMENTNAME]
        $form = Forms::createFormFactory()->createNamedBuilder(null, FormType::class, [], [
            'attr' => $formAttributes
        ]);
        $form->setAction( $this->endpoint['form'] );
        $form->setMethod( 'post' );

        // auth
        $form->add('merchantId', HiddenType::class);
        $formData['merchantId'] = $this->merchantId;

        $form->add( 'hidden', 'sign', ['value' => $sign] );
        $formData['sign'] = $sign;

        // return urls
        $form->add('successUrl', HiddenType::class);
        $formData['successUrl'] = $config['successUrl'];
        $form->add('errorUrl', HiddenType::class);
        $formData['errorUrl'] = $config['errorUrl'];
        $form->add('cancelUrl', HiddenType::class);
        $formData['cancelUrl'] = $config['cancelUrl'];

        // config
        $form->add('language', HiddenType::class);
        $formData['language'] = $config['language'];

        // order data
        $form->add('amount', HiddenType::class);
        $formData['amount'] = $paymentData['amount'];
        $form->add('currency', HiddenType::class);
        $formData['currency'] = $paymentData['currency'];
        $form->add('refno', HiddenType::class);
        $formData['refno'] = $config['refno'];
        $form->add('reqtype', HiddenType::class);
        $formData['reqtype'] = $paymentData['reqtype'];




        if($config['useAlias'])
        {
            $form->add( 'hidden', 'useAlias', ['value' => 'yes'] );
        }


        // add submit button
        $form->add( 'submitbutton', SubmitType::class );
        $form->setData($formData);

        return $form;
    }

    /**
     * handle response / execute payment
     *
     * @param mixed $response
     *
     * @return IStatus
     * @throws \Exception
     *
     * @see http://pilot.datatrans.biz/showcase/doc/XML_Authorisation.pdf : Page 7 > 2.3 Authorisation response
     */
    public function handleResponse($response)
    {
        // check for provider error's
        if(array_key_exists('errorCode', $response))
        {
            throw new \Exception( $response['errorDetail'], $response['errorCode'] );
        }

        // check required fields
        $required = $this->getRequiredResponseFields($response);
        $authorizedData = [
            'aliasCC' => null
            , 'expm' => null
            , 'expy' => null
            , 'reqtype' => null
            , 'uppTransactionId' => null
            , 'amount' => null
            , 'currency' => null
            , 'refno' => null
        ];

        // check fields
        $response = array_intersect_key($response, $required);
        if(count($required) != count($response))
        {
            throw new \Exception( sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $response)))) );
        }


        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData( $authorizedData );


        // restore price object for payment status
        $price = new Price($response['amount'] / 100, new Currency($response['currency']));


        $paymentState = null;
        if(in_array($response['responseCode'],['01', '02']))
        {
            // success
            $paymentState = $response['reqtype'] == 'CAA'
                // CAA - authorization with immediate settlement
                ? AbstractOrder::ORDER_STATE_COMMITTED
                : AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED;

            $message = $response['responseMessage'];
        }
        else
        {
            // failed
            $paymentState = AbstractOrder::ORDER_STATE_ABORTED;
            $message = $response['errorDetail'];
        }


        return new Status(
            $response['refno']
            , $response['uppTransactionId']
            , $message
            , $paymentState
            , [
                'datatrans_amount' => (string)$price
                , 'datatrans_acqAuthorizationCode' => $response['acqAuthorizationCode']
                , 'datatrans_response' => $response
            ]
        );
    }

    /**
     * @param $response
     * @return array
     */
    protected function getRequiredResponseFields($response)
    {
        $required = [
            'uppTransactionId'     => null,
            'responseCode'         => null,
            'responseMessage'      => null,
            'pmethod'              => null,
            'reqtype'              => null,
            'acqAuthorizationCode' => null,
            'status'               => null,
            'uppMsgType'           => null,
            'refno'                => null,
            'amount'               => null,
            'currency'             => null,
        ];

        switch ($response['pmethod']) {
            // creditcard
            case 'VIS':
            case 'ECA':
                $required['expm'] = null;
                $required['expy'] = null;
                break;
        }

        return $required;
    }

    /**
     * Get valid authorization types
     *
     * @return array
     */
    public function getValidAuthorizationTypes()
    {
        return [
            static::AUTH_TYPE_AUTHORIZATION,
            static::AUTH_TYPE_FINAL_AUTHORIZATION
        ];
    }

    /**
     * @param IPrice $price
     * @param string $reference
     *
     * @return Status|IStatus
     * @throws \Exception
     */
    public function executeDebit(IPrice $price = null, $reference = null)
    {
        $uppTransactionId = null;

        if (in_array($this->authorizedData['reqtype'], $this->getValidAuthorizationTypes()) && $this->authorizedData['uppTransactionId']) {
            // restore price object for payment status
            $price = new Price($this->authorizedData['amount'] / 100, new Currency($this->authorizedData['currency']));

            // complete authorized payment
            $xml = $this->xmlSettlement(
                self::TRANS_TYPE_DEBIT
                , $this->authorizedData['amount']
                , $this->authorizedData['currency']
                , $reference ? : $this->authorizedData['refno']
                , $this->authorizedData['uppTransactionId']
            );

            $uppTransactionId = $this->authorizedData['uppTransactionId'];
        }
        else if($price === null)
        {
            // wrong call
            throw new \Exception('nothing to execute');
        }
        else
        {
            // authorisieren und zahlung ausführen
            $xml = $this->xmlAuthorisation(
                'CAA'
                , self::TRANS_TYPE_DEBIT
                , $price->getAmount() * 100
                , $price->getCurrency()->getShortName()
                , $reference
                , $this->authorizedData['aliasCC']
                , $this->authorizedData['expm']
                , $this->authorizedData['expy']
            );
        }


        // handle response
        $transaction = $xml->body->transaction;
        $status = (string)$transaction->attributes()['trxStatus'];
        $response = $transaction->{ $status };
        /* @var \SimpleXMLElement $response */

        $message = null;
        $paymentState = null;
        if($status == 'response' && in_array($response->responseCode,['01', '02']))
        {
            $paymentState = AbstractOrder::ORDER_STATE_COMMITTED;
            $message = (string)$response->responseMessage;
        }
        else
        {
            $paymentState = AbstractOrder::ORDER_STATE_ABORTED;
            $message = (string)$response->errorMessage.' | '.(string)$response->errorDetail;
        }


        // create and return status
        $status = new Status(
            (string)$transaction->attributes()['refno']
            , (string)$response->uppTransactionId ?: $uppTransactionId
            , $message
            , $paymentState
            , [
                'datatrans_amount' => (string)$price
                , 'datatrans_responseXML' => $transaction->asXML()
                , 'datatrans_acqAuthorizationCode' => (string)$response->acqAuthorizationCode
            ]
        );

        return $status;
    }

    /**
     * gutschrift ausführen
     * @param IPrice $price
     * @param string $reference
     * @param string $transactionId
     *
     * @return IStatus
     */
    public function executeCredit(IPrice $price, $reference, $transactionId)
    {
        if (in_array($this->authorizedData['reqtype'], $this->getValidAuthorizationTypes()) && $this->authorizedData['uppTransactionId']) {
            // restore price object for payment status
            $price = new Price($this->authorizedData['amount'] / 100, new Currency($this->authorizedData['currency']));

            // complete authorized payment
            $xml = $this->xmlSettlement(
                self::TRANS_TYPE_CREDIT
                , $this->authorizedData['amount']
                , $this->authorizedData['currency']
                , $this->authorizedData['refno']
                , $this->authorizedData['uppTransactionId']
            );
        }
        else
        {
            // complete authorized payment
            $xml = $this->xmlSettlement(
                self::TRANS_TYPE_CREDIT
                , $price->getAmount() * 100
                , $price->getCurrency()->getShortName()
                , $reference
                , $transactionId
            );
        }


        // handle response
        $transaction = $xml->body->transaction;
        $status = (string)$transaction->attributes()['trxStatus'];
        $response = $transaction->{ $status };

        $message = null;
        $paymentState = null;
        if($status == 'response' && in_array($response->responseCode,['01', '02']))
        {
            $paymentState = AbstractOrder::ORDER_STATE_COMMITTED;
            $message = (string)$response->responseMessage;
        }
        else
        {
            $paymentState = AbstractOrder::ORDER_STATE_ABORTED;
            $message = (string)$response->errorMessage.' | '.(string)$response->errorDetail;
        }


        // create and return status
        $status = new Status(
            (string)$transaction->attributes()['refno']
            , (string)$response->uppTransactionId
            , $message
            , $paymentState
            , [
                'datatrans_amount' => (string)$price
                , 'datatrans_responseXML' => $transaction->asXML()
                , 'datatrans_acqAuthorizationCode' => (string)$response->acqAuthorizationCode
            ]
        );

        return $status;
    }

    /**
     * Cancel authorization
     *
     * @param IPrice $price
     * @param string $reference
     * @param string $transactionId
     *
     * @return IStatus
     */
    public function executeAuthorizationCancel(IPrice $price, $reference, $transactionId)
    {
        $xml = $this->xmlCancelAuthorization(
            $price->getAmount() * 100,
            $price->getCurrency()->getShortName(),
            $reference,
            $transactionId
        );

        // handle response
        $transaction = $xml->body->transaction;
        $status      = (string)$transaction->attributes()['trxStatus'];

        /* @var \SimpleXMLElement $response */
        $response = $transaction->{$status};

        $message      = null;
        $paymentState = null;

        if ($status === 'response' && in_array($response->responseCode, ['01', '02'])) {
            $paymentState = AbstractOrder::ORDER_STATE_CANCELLED;
            $message      = (string)$response->responseMessage;
        } else {
            $paymentState = AbstractOrder::ORDER_STATE_ABORTED;
            $message      = (string)$response->errorMessage . ' | ' . (string)$response->errorDetail;
        }

        // create and return status
        $status = new Status(
            (string)$transaction->attributes()['refno'],
            (string)$transactionId,
            $message,
            $paymentState,
            [
                'datatrans_amount'               => (string)$price,
                'datatrans_responseXML'          => $transaction->asXML(),
                'datatrans_acqAuthorizationCode' => (string)$response->acqAuthorizationCode,
            ]
        );

        return $status;
    }

    /**
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData)
    {
        $this->authorizedData = $authorizedData;
    }

    /**
     * @return array
     */
    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }

    /**
     * transmit to datatrans
     * @param string $reqType              NOA = nur authorisieren, CAA = authorisieren und ausführen, COA = ausführen sofern es authorisiert wurde
     * @param string $transType            05 = debit transaction, 06 = – credit transaction
     * @param int    $amount               in kleinster einheit > 1,10 € > 110 !
     * @param string $currency
     * @param string $refno
     * @param string $aliasCC
     * @param string $expireMonth
     * @param string $expireYear
     *
     * @return \SimpleXMLElement
     * @see https://www.datatrans.ch/showcase/authorisation/xml-authorisation
     */
    protected function xmlAuthorisation($reqType, $transType, $amount, $currency, $refno, $aliasCC, $expireMonth, $expireYear)
    {
        // transaktion einleiten
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<authorizationService version="2">
  <body merchantId="%1$s">
    <transaction refno="%3$s">
      <request>
        <sign>%2$s</sign>

        <reqtype>%4$s</reqtype>
        <transtype>%5$s</transtype>

        <amount>%6$d</amount>
        <currency>%7$s</currency>

        <aliasCC>%8$s</aliasCC>
        <expm>%9$d</expm>
        <expy>%10$d</expy>
      </request>
    </transaction>
  </body>
</authorizationService>
XML;

        $xml = sprintf($xml
            , $this->merchantId
            , $this->sign

            , $refno
            , $reqType
            , $transType
            , $amount
            , $currency

            , $aliasCC
            , $expireMonth
            , $expireYear
        );

        return $this->xmlRequest($this->endpoint['xmlAuthorize'], $xml);
    }

    /**
     * authorisiertes settlement ausführen
     * @param string $transType 05 = settlement debit
     *                          06 = settlement credit
     * @param int    $amount    in kleinster einheit > 1,10 € > 110 !
     * @param string $currency
     * @param string            $reference
     * @param string            $transactionId
     *
     * @return \SimpleXMLElement
     */
    protected function xmlSettlement($transType, $amount, $currency, $reference, $transactionId)
    {
        // request erstellen
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<paymentService version="1">
  <body merchantId="%1$s">
    <transaction refno="%2$s">
      <request>
        <sign>%7$s</sign>

        <reqtype>COA</reqtype>
        <transtype>%6$s</transtype>
        <uppTransactionId>%3$d</uppTransactionId>

        <amount>%4$d</amount>
        <currency>%5$s</currency>
      </request>
    </transaction>
  </body>
</paymentService>
XML;

        $xml = sprintf($xml
            , $this->merchantId
            , $reference
            , $transactionId
            , $amount
            , $currency
            , $transType
            , $this->sign
        );

        return $this->xmlRequest($this->endpoint['xmlProcessor'], $xml);
    }

    /**
     * Cancel authorization
     *
     * @param int    $amount    in kleinster einheit > 1,10 € > 110 !
     * @param string $currency
     * @param string $reference
     * @param string $transactionId
     *
     * @return \SimpleXMLElement
     */
    protected function xmlCancelAuthorization($amount, $currency, $reference, $transactionId)
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<paymentService version="1">
  <body merchantId="%1$s">
    <transaction refno="%2$s">
      <request>
        <uppTransactionId>%3$d</uppTransactionId>
        <reqtype>DOA</reqtype>
        <amount>%4$d</amount>
        <currency>%5$s</currency>
      </request>
    </transaction>
  </body>
</paymentService>
XML;

        $xml = sprintf(
            $xml,
            $this->merchantId,
            $reference,
            $transactionId,
            $amount,
            $currency
        );

        return $this->xmlRequest($this->endpoint['xmlProcessor'], $xml);
    }

    /**
     * @param string $endpoint
     * @param string $xml
     * @return \SimpleXMLElement
     */
    protected function xmlRequest($endpoint, $xml)
    {
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);

        return simplexml_load_string($output);
    }
}
