<?php

class OnlineShop_Framework_Impl_Payment_Datatrans implements OnlineShop_Framework_IPayment
{
    const PRIVATE_NAMESPACE = 'datatrans';

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $sign;

    /**
     * @var string[]
     */
    protected $authorizedData;

    /**
     * @var OnlineShop_Framework_Payment_IStatus
     */
    protected $paymentStatus;


    /**
     * @param Zend_Config $xml
     *
     * @throws Exception
     */
    public function __construct(Zend_Config $xml)
    {
        $settings = $xml->config->{$xml->mode};
        if($settings->sign == '' || $settings->merchantId == '')
        {
            throw new Exception('payment configuration is wrong. secret or customer is empty !');
        }

        $this->merchantId = $settings->merchantId;
        $this->sign = $settings->sign;


        if($xml->mode == 'live')
        {
            $this->endpoint = 'https://payment.datatrans.biz/upp/jsp/XML_authorize.jsp';
        }
        else
        {
            $this->endpoint = 'https://pilot.datatrans.biz/upp/jsp/XML_authorize.jsp';
        }
    }


    /**
     * start payment
     * @param OnlineShop_Framework_IPrice $price
     * @param array                       $config
     *
     * @return Zend_Form
     * @throws Exception
     *
     * @see https://pilot.datatrans.biz/showcase/doc/Technical_Implementation_Guide.pdf
     * @see http://pilot.datatrans.biz/showcase/doc/XML_Authorisation.pdf
     */
    public function initPayment(OnlineShop_Framework_IPrice $price, array $config)
    {
        // check params
        $required = [  'successUrl' => null
                       , 'errorUrl' => null
                       , 'cancelUrl' => null
                       , 'internal_id' => null
                       , 'useAlias' => null
                       , 'reqtype' => null
                       , 'language' => null
        ];
        $config = array_intersect_key($config, $required);

        if(count($required) != count($config))
        {
            throw new Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
        }



        // collect payment data
        $paymentData['amount'] = round($price->getAmount(), 2) * 100;
        $paymentData['currency'] = $price->getCurrency()->getShortName();
        $paymentData['reqtype'] = $config['expressCheckout'] ? 'CAA' : 'NOA';
        // NOA – Authorisation only (default)
        // CAA – Authorisation and settlement


        // create form
        $form = new Zend_Form();
        $form->setAction( 'https://pilot.datatrans.biz/upp/jsp/upStart.jsp' );
        $form->setMethod( 'post' );

        // auth
        $form->addElement( 'hidden', 'merchantId', ['value' => $this->merchantId] );
        $form->addElement( 'hidden', 'sign', ['value' => $this->sign] );

        // return urls
        $form->addElement( 'hidden', 'successUrl', ['value' => $config['successUrl']] );
        $form->addElement( 'hidden', 'errorUrl', ['value' => $config['errorUrl']] );
        $form->addElement( 'hidden', 'cancelUrl', ['value' => $config['cancelUrl']] );

        // config
        $form->addElement( 'hidden', 'language', ['value' => $config['language']] );

        // order data
        $form->addElement( 'hidden', 'amount', ['value' => $paymentData['amount']] );
        $form->addElement( 'hidden', 'currency', ['value' => $paymentData['currency']] );
        $form->addElement( 'hidden', 'refno', ['value' => $config['internal_id']] );
        $form->addElement( 'hidden', 'reqtype', ['value' => $paymentData['reqtype']] );

        if($config['useAlias'])
        {
            $form->addElement( 'hidden', 'useAlias', ['value' => 'yes'] );
        }


        // add submit button
        $form->addElement( 'submit', 'submitbutton' );

        return $form;
    }


    /**
     * handle response / execute payment
     *
     * @param mixed $response
     *
     * @return OnlineShop_Framework_Impl_Payment_Status
     * @throws Exception
     *
     * @see http://pilot.datatrans.biz/showcase/doc/XML_Authorisation.pdf : Page 7 > 2.3 Authorisation response
     */
    public function handleResponse($response)
    {
        // check required fields
        $required = [  'uppTransactionId' => null
                     , 'responseCode' => null
                     , 'responseMessage' => null
                     , 'pmethod' => null
                     , 'reqtype' => null
                     , 'acqAuthorizationCode' => null
                     , 'status' => null
                     , 'uppMsgType' => null
                     , 'refno' => null
                     , 'amount' => null
                     , 'currency' => null
        ];
        $authorizedData = [
                     'aliasCC' => null
                     , 'expm' => null
                     , 'expy' => null
        ];


        switch($response['pmethod'])
        {
            // creditcard
            case 'VIS':
            case 'ECA':
                $required['aliasCC'] = null;
                $required['maskedCC'] = null;
                $required['expm'] = null;
                $required['expy'] = null;
                break;

            // paypal
            case 'PAP':
                $required['aliasCC'] = null;
                break;
        }


        // check fields
        $response = array_intersect_key($response, $required);
        if(count($required) != count($response))
        {
            throw new Exception( sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $authorizedData)))) );
        }


        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData( $authorizedData );


        // restore price object for payment status
        $price = new OnlineShop_Framework_Impl_Price($response['amount'] / 100, new Zend_Currency($response['currency']));


        $paymentState = null;
        if(in_array($response['responseCode'],['01', '02']))
        {
            // success
            $paymentState = $response['reqtype'] == 'CAA'
                ? OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED
                : OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED;

            $message = $response['responseMessage'];
        }
        else
        {
            // failed
            $paymentState = OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED;
            $message = $response['errorDetail'];
        }


        return new OnlineShop_Framework_Impl_Payment_Status(
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
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Impl_Payment_Status|OnlineShop_Framework_Payment_IStatus
     * @throws Exception
     */
    public function executeDebit(OnlineShop_Framework_IPrice $price = null, $reference = null)
    {
        // ...
        if($this->paymentStatus && $price === null)
        {
            return $this->paymentStatus;
        }
        else if($price === null)
        {
            throw new Exception('nothing to execute');
        }


        // zahlung ausführen
        $xml = $this->transmit(
             'CAA'
            , '05'
            , $price->getAmount() * 100
            , $price->getCurrency()->getShortName()
            , $reference
            , $this->authorizedData['aliasCC']
            , $this->authorizedData['expm']
            , $this->authorizedData['expy']
        );


        // handle response
        $transaction = $xml->body->transaction;
        $status = (string)$transaction->attributes()['trxStatus'];
        $response = $transaction->{ $status };
        /* @var SimpleXMLElement $response */

        $message = null;
        $paymentState = null;
        if($status == 'response' && in_array($response->responseCode,['01', '02']))
        {
            $paymentState = OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED;
            $message = (string)$response->responseMessage;
        }
        else
        {
            $paymentState = OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED;
            $message = (string)$response->errorMessage.' | '.(string)$response->errorDetail;
        }


        // create and return status
        $status = new OnlineShop_Framework_Impl_Payment_Status(
              (string)$transaction->attributes()['refno']
            , (string)$response->uppTransactionId
            , $message
            , $paymentState
            , [
                  'amount' => (string)$price
                  , 'responseXML' => $transaction->asXML()
                  , 'acqAuthorizationCode' => (string)$response->acqAuthorizationCode
              ]
        );

        return $status;
    }


    /**
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     * @param string                      $transactionId
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeCredit(OnlineShop_Framework_IPrice $price, $reference, $transactionId)
    {
        // gutschrift ausführen
        $xml = sprintf('
<?xml version="1.0" encoding="UTF-8" ?>
<paymentService version="1">
  <body merchantId="%1$s">
    <transaction refno="%2$s">
      <request>
        <transtype>06</transtype>
        <uppTransactionId>%3$d</uppTransactionId>

        <amount>%4$d</amount>
        <currency>%5$s</currency>
      </request>
    </transaction>
  </body>
</paymentService>
        '
            , $this->merchantId

            , $reference
            , $transactionId
            , $price->getAmount() * 100
            , $price->getCurrency()->getShortName()
        );

        $ch = curl_init( 'https://pilot.datatrans.biz/upp/jsp/XML_processor.jsp' );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($output);



        // handle response
        $transaction = $xml->body->transaction;
        $status = (string)$transaction->attributes()['trxStatus'];
        $response = $transaction->{ $status };

        $message = null;
        $paymentState = null;
        if($status == 'response' && in_array($response->responseCode,['01', '02']))
        {
            $paymentState = OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED;
            $message = (string)$response->responseMessage;
        }
        else
        {
            $paymentState = OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED;
            $message = (string)$response->errorMessage.' | '.(string)$response->errorDetail;
        }


        // create and return status
        $status = new OnlineShop_Framework_Impl_Payment_Status(
              (string)$transaction->attributes()['refno']
            , (string)$response->uppTransactionId
            , $message
            , $paymentState
            , [
                  'amount' => (string)$price
                  , 'responseXML' => $transaction->asXML()
                  , 'acqAuthorizationCode' => (string)$response->acqAuthorizationCode
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
     * @param string $reqType              NOA = nur authorisieren, CAA = direkt ausführen
     * @param string $transType            05 = debit transaction, 06 = – credit transaction
     * @param int    $amount               in kleinster einheit > 1,10 € > 110 !
     * @param string $currency
     * @param string $refno
     * @param string $aliasCC
     * @param string $expireMonth
     * @param string $expireYear
     *
     * @return SimpleXMLElement
     */
    protected function transmit($reqType, $transType, $amount, $currency, $refno, $aliasCC, $expireMonth, $expireYear)
    {
        // transaktion einleiten
        $xml = sprintf('
<?xml version="1.0" encoding="UTF-8" ?>
<authorizationService version="2">
  <body merchantId="%1$s">
    <transaction refno="%3$s"><!-- eigene order id -->
      <request>
        <sign>%2$s</sign>

        <reqtype>%4$s</reqtype><!-- NOA = nur authorisieren, CAA = direkt ausführen -->
        <transtype>%5$s</transtype>
        <uppTransactionId>141006145315284359</uppTransactionId>

        <amount>%6$d</amount>
        <currency>%7$s</currency>

        <aliasCC>%8$s</aliasCC>
        <expm>%9$d</expm>
        <expy>%10$d</expy>
      </request>
    </transaction>
  </body>
</authorizationService>
        '
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


        $ch = curl_init( $this->endpoint );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);

        return simplexml_load_string($output);
    }
}