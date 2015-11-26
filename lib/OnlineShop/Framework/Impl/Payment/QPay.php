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


class OnlineShop_Framework_Impl_Payment_QPay implements OnlineShop_Framework_IPayment
{
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
     * @var string[]
     */
    protected $authorizedData;

    /**
     * @var Zend_Locale
     */
    protected $currencyLocale;


    /**
     * @param Zend_Config $xml
     *
     * @throws Exception
     */
    public function __construct(Zend_Config $xml)
    {
        $settings = $xml->config->{$xml->mode};
        if($settings->secret == '' || $settings->customer == '')
        {
            throw new Exception('payment configuration is wrong. secret or customer is empty !');
        }

        $this->secret = $settings->secret;
        $this->customer = $settings->customer;
        $this->toolkitPassword = $settings->toolkitPassword;

        if($settings->paymenttype)
        {
            $this->paymenttype = $settings->paymenttype;
        }

        $this->currencyLocale = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale();
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'Qpay';
    }


    /**
     * start payment
     * @param OnlineShop_Framework_IPrice $price
     * @param array                       $config
     *
     * @return Zend_Form
     * @throws Exception
     * @throws Zend_Form_Exception
     */
    public function initPayment(OnlineShop_Framework_IPrice $price, array $config)
    {
        // check params
        $required = [  'successURL' => null
                       , 'cancelURL' => null
                       , 'failureURL' => null
                       , 'serviceURL' => null
                       , 'orderDescription' => null
                       , 'orderIdent' => null
                       , 'language' => null
        ];
        $check = array_intersect_key($config, $required);

        if(count($required) != count($check))
        {
            throw new Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $check)))));
        }


        // collect payment data
        $paymentData['secret'] = $this->secret;
        $paymentData['customerId'] = $this->customer;
        $paymentData['amount'] = round($price->getAmount(), 2);
        $paymentData['currency'] = $price->getCurrency()->getShortName();
        $paymentData['language'] = $config['language'];
        $paymentData['orderDescription'] = $config['orderDescription'];
        $paymentData['successURL'] = $config['successURL'];
        $paymentData['duplicateRequestCheck'] = 'yes';
        $paymentData['orderIdent'] = $config['orderIdent'];
        $paymentData['requestfingerprintorder'] = '';

        if(array_key_exists('displayText', $config)) {
            $paymentData['displayText'] = $config['displayText'];
        }


        // generate fingerprint
        $paymentData['requestfingerprintorder'] = implode(',', array_keys($paymentData));
        $fingerprint = md5(implode('', $paymentData));


        // create form
        $form = new Zend_Form(array('disableLoadDefaultDecorators' => false));
        $form->setAction( 'https://www.qenta.com/qpay/init.php' );
        $form->setMethod( 'post' );
        $form->addElement( 'hidden', 'customerId', array('value' => $this->customer) );
        $form->addElement( 'hidden', 'paymenttype', array('value' => $this->paymenttype) );
        $form->addElement( 'hidden', 'amount', array('value' => $paymentData['amount']) );
        $form->addElement( 'hidden', 'currency', array('value' => $paymentData['currency']) );
        $form->addElement( 'hidden', 'language', array('value' => $paymentData['language']) );
        $form->addElement( 'hidden', 'orderDescription', array('value' => $paymentData['orderDescription']) );
        $form->addElement( 'hidden', 'orderIdent', array('value' => $paymentData['orderIdent']) );
        $form->addElement( 'hidden', 'requestfingerprintorder', array('value' => $paymentData['requestfingerprintorder']) );
        $form->addElement( 'hidden', 'requestfingerprint', array('value' => $fingerprint) );
        $form->addElement( 'hidden', 'successURL', array('value' => $config['successURL']) );
        $form->addElement( 'hidden', 'failureURL', array('value' => $config['failureURL']) );
        $form->addElement( 'hidden', 'cancelURL', array('value' => $config['cancelURL']) );
        $form->addElement( 'hidden', 'serviceURL', array('value' => $config['serviceURL']) );
        $form->addElement( 'hidden', 'duplicateRequestCheck', array('value' => 'yes') );

        // add optional data
        if(array_key_exists('displayText', $config)) {
            $form->addElement( 'hidden', 'displayText', array('value' => $config['displayText']) );
        }
        if(array_key_exists('imageURL', $config)) {
            $form->addElement( 'hidden', 'imageURL', array('value' => $config['imageURL']) );
        }

        // add submit button
        $form->addElement( 'submit', 'submitbutton' );

        return $form;
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
            'orderIdent' => null
        ];

        $authorizedData = [
            'orderNumber' => null
            , 'language' => null
            , 'amount' => null
            , 'currency' => null
        ];


        // check fields
        $check = array_intersect_key($response, $required);
        if(count($required) != count($check))
        {
            throw new Exception( sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $authorizedData)))) );
        }


        // check fingerprint
        $fields = explode(',', $response['responseFingerprintOrder']);
        $fingerprint = '';
        foreach($fields as $field)
        {
            $fingerprint .= $field == 'secret' ? $this->secret : $response[ $field ];
        }

        $fingerprint = md5($fingerprint);
        if($response["paymentState"] !== "FAILURE" && $fingerprint != $response['responseFingerprint'])
        {
            // fingerprint is wrong, ignore this response
            return new OnlineShop_Framework_Impl_Payment_Status(
                $response['orderIdent']
                , $response['orderNumber']
                , $response['avsResponseMessage'] ?: $response['message']
                , OnlineShop_Framework_Payment_IStatus::STATUS_CANCELLED
            );
        }


        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData( $authorizedData );


        // restore price object for payment status
        $price = new OnlineShop_Framework_Impl_Price($authorizedData['amount'], new Zend_Currency($authorizedData['currency'], OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale()));


        return new OnlineShop_Framework_Impl_Payment_Status(
            $response['orderIdent']
            , $response['orderNumber']
            , $response['avsResponseMessage'] ?: $response['message']
            , $response['orderNumber'] !== NULL && $response['paymentState'] == 'SUCCESS'
                ? OnlineShop_Framework_Payment_IStatus::STATUS_AUTHORIZED
                : OnlineShop_Framework_Payment_IStatus::STATUS_CANCELLED
            , [
                'qpay_amount' => (string)$price
                , 'qpay_paymentType' => $response['paymentType']
                , 'qpay_paymentState' => $response['paymentState']
                , 'qpay_response' => print_r($response, true)
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
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Payment_IStatus
     * @throws Exception
     */
    public function executeDebit(OnlineShop_Framework_IPrice $price = null, $reference = null)
    {
        // TODO: Implement executeDebit() method.
        # https://integration.wirecard.at/doku.php/wcp:toolkit_light:start
        # https://integration.wirecard.at/doku.php/wcs:backend_operations?s[]=deposit
        # https://integration.wirecard.at/doku.php/backend:deposit


        if( $price )
        {
            // recurPayment

            $request = [
                'customerId' => $this->customer
                , 'toolkitPassword' => $this->toolkitPassword
                , 'command' => 'recurPayment'
                , 'language' => $this->authorizedData['language']
                , 'requestFingerprint' => ''
                , 'orderDescription' => $reference
                , 'sourceOrderNumber' => $this->authorizedData['orderNumber']
                , 'amount' => $price->getAmount()
                , 'currency' => $price->getCurrency()->getShortName()
            ];


            // add fingerprint
            $request['requestFingerprint'] = $this->computeFingerprint(
                $request['customerId']
                , $request['toolkitPassword']
                , $this->secret
                , $request['command']
                , $request['language']
                , $request['sourceOrderNumber']
                , $request['orderDescription']
                , $request['amount']
                , $request['currency']
            );

        }
        else
        {
            // default clearing auth
            $price = new OnlineShop_Framework_Impl_Price($this->authorizedData['amount'], new Zend_Currency($this->authorizedData['currency'], $this->currencyLocale));

            $request = [
                'customerId' => $this->customer
                , 'toolkitPassword' => $this->toolkitPassword
                , 'command' => 'deposit'
                , 'language' => $this->authorizedData['language']
                , 'requestFingerprint' => ''
                , 'orderNumber' => $this->authorizedData['orderNumber']
                , 'amount' => $price->getAmount()
                , 'currency' => $price->getCurrency()->getShortName()
            ];


            // add fingerprint
            $request['requestFingerprint'] = $this->computeFingerprint(
                $request['customerId']
                , $request['toolkitPassword']
                , $this->secret
                , $request['command']
                , $request['language']
                , $request['orderNumber']
                , $request['amount']
                , $request['currency']
            );

        }


        // execute request
        $response = $this->serverToServerRequest( 'https://checkout.wirecard.com/page/toolkit.php', $request );


        // check response
        if($response['status'] === '0')
        {
            // Operation successfully done.

            return new OnlineShop_Framework_Impl_Payment_Status(
                $reference
                , $response['paymentNumber'] ?: $response['orderNumber']
                , ''
                , OnlineShop_Framework_Payment_IStatus::STATUS_CLEARED
                , [
                    'qpay_amount' => (string)$price
                    , 'qpay_command' => $request['command']
                    , 'qpay_response' => print_r($response, true)
                ]
            );
        }
        else if($response['errors'])
        {
            // https://integration.wirecard.at/doku.php/backend:response_parameters

            $error = [];
            for($e = 1; $e <= $response['errors']; $e++)
            {
                $error[] = $response['error_' . $e . '_error_message'];
            }

            return new OnlineShop_Framework_Impl_Payment_Status(
                $reference
                , $response['paymentNumber'] ?: $response['orderNumber']
                , implode("\n", $error)
                , OnlineShop_Framework_Payment_IStatus::STATUS_CANCELLED
                , [
                    'qpay_amount' => (string)$price
                    , 'qpay_command' => $request['command']
                    , 'qpay_response' => print_r($response, true)
                ]
            );
        }
        else
        {
            throw new Exception( print_r($response, true) );
        }
    }

    /**
     * execute credit
     *
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeCredit(OnlineShop_Framework_IPrice $price, $reference, $transactionId)
    {
        // TODO: Implement executeCredit() method.
    }


    /**
     * @return string
     */
    protected function computeFingerprint()
    {
        $seed = '';
        for ($i=0; $i<func_num_args(); $i++)
        {
            $seed .= func_get_arg($i);
        }

        return md5($seed);
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
        foreach ($params as $key => $value)
        {
            $postFields .= $key . '=' . $value . '&';
        }
        $postFields = substr($postFields, 0, strlen($postFields)-1);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_PORT, 443);
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $response = curl_exec($curl);
        curl_close($curl);

        $r = [];
        parse_str($response, $r);
        return $r;
    }
}