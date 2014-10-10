<?php

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
    protected $paymenttype = 'SELECT';

    /**
     * @var string
     */
    protected $orderNumber;


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

        if($settings->paymenttype)
        {
            $this->paymenttype = $settings->paymenttype;
        }
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
                       , 'language' => null
        ];
        $config = array_intersect_key($config, $required);

        if(count($required) != count($config))
        {
            throw new Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
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
        // check fingerprint
        $fields = explode(',', $response['responseFingerprintOrder']);
        $fingerprint = '';
        foreach($fields as $field)
        {
            $fingerprint .= $field == 'secret' ? $this->secret : $response[ $field ];
        }

        $fingerprint = md5($fingerprint);
        if($fingerprint != $response['responseFingerprint'])
        {
            // fingerprint is wrong, ignore this response
            throw new Exception( 'fingerprint is invalid' );
        }


        // restore price object for payment status
        $price = new OnlineShop_Framework_Impl_Price($response['amount'], new Zend_Currency($response['currency']));


        return new OnlineShop_Framework_Impl_Payment_Status(
            base64_decode($response['internal_id'])
            , $response['orderNumber']
            , $response['avsResponseMessage']
            , $response['orderNumber'] !== NULL
                ? OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED
                : OnlineShop_Framework_AbstractOrder::ORDER_STATE_CANCELLED
            , [
                'qpay_amount' => (string)$price
                , 'qpay_paymentType' => $response['paymentType']
                , 'qpay_paymentState' => $response['paymentState']
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
        // TODO: Implement getAuthorizedData() method.
    }

    /**
     * set authorized data from payment provider
     *
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData)
    {
        // TODO: Implement setAuthorizedData() method.
    }

    /**
     * execute payment
     *
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeDebit(OnlineShop_Framework_IPrice $price = null, $reference = null)
    {
        // TODO: Implement executeDebit() method.
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
}