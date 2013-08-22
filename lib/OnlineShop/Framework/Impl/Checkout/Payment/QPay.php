<?php

class OnlineShop_Framework_Impl_Checkout_Payment_QPay extends OnlineShop_Framework_Impl_Checkout_AbstractPayment implements OnlineShop_Framework_ICheckoutPayment
{
    const PRIVATE_NAMESPACE = 'paymentQPay';

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
    protected $paymenttype = 'SELECT';   # SELECT

    /**
     * @var string
     */
    protected $gatewayReferenceNumber;


    /**
     * @param Zend_Config                $xml
     * @param OnlineShop_Framework_ICart $cart
     * @throws Exception
     */
    public function __construct(Zend_Config $xml, OnlineShop_Framework_ICart $cart)
    {
        $settings = $xml->{$xml->mode};
        if($settings->secret == '' || $settings->customer == '')
        {
            throw new Exception('payment configuration is wrong. secret or customer is empty !');
        }

        $this->secret = $settings->secret;
        $this->customer = $settings->customer;
        $this->cart = $cart;

        if($settings->paymenttype)
        {
            $this->paymenttype = $settings->paymenttype;
        }

        // load checkout data
        $this->loadCheckoutData();
    }


    /**
     * @param array $config
     *
     * @return Zend_Form|bool
     */
    public function initPayment(array $config)
    {
        // init
        $return = false;    // return false on errors
        $this->errors = array();

        // check params
        $required = array('successURL', 'cancelURL', 'failureURL', 'serviceURL', 'orderDescription', 'language');
        $missing = array();

        foreach($required as $property)
        {
            if(!array_key_exists($property, $config))
            {
                $missing[] = $property;
            }
        }

        if(count($missing) > 0)
        {
            $this->errors[] = sprintf('required field %s is missing', implode(', ', $missing));
            return $return;
        }



        // collect payment data
        $paymentData['secret'] = $this->secret;
        $paymentData['customerId'] = $this->customer;
        $paymentData['amount'] = round($this->cart->getPriceCalculator()->getGrandTotal()->getAmount(), 2);
        $paymentData['currency'] = $this->cart->getPriceCalculator()->getGrandTotal()->getCurrency()->getShortName();
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
        $form->addElement( 'submit', 'submit' );

        return $form;
    }


    /**
     * @param mixed $response
     *
     * @return OnlineShop_Framework_Impl_Checkout_Payment_Status
     */
    public function handleResponse($response)
    {
        // init
        $return = false;    // return false on errors
        $this->errors = array();

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
            $this->errors[] = 'fingerprint is invalid';
        }


        // save
        $this->gatewayReferenceNumber = $response['gatewayReferenceNumber'];

        $status = new OnlineShop_Framework_Impl_Checkout_Payment_Status(
             base64_decode($response['internal_id']),
             $this->gatewayReferenceNumber,
             $this->isPaid() ? OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED : OnlineShop_Framework_AbstractOrder::ORDER_STATE_CANCELLED
        );


        $this->saveCheckoutData();

        return $status;
    }


    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->gatewayReferenceNumber !== NULL;
    }


    /**
     * @return string|null
     */
    public function getPayReference()
    {
        return $this->gatewayReferenceNumber;
    }


    /**
     * load session data
     */
    protected function loadCheckoutData()
    {
        $data = json_decode($this->cart->getCheckoutData(self::PRIVATE_NAMESPACE), true);
        $this->gatewayReferenceNumber = $data['gatewayReferenceNumber'];
        $this->errors = $data['Errors'];
    }


    /**
     * save session data
     */
    protected function saveCheckoutData()
    {
        $data = array(
            'gatewayReferenceNumber' => $this->gatewayReferenceNumber,
            'Errors' => $this->errors
        );

        $this->cart->setCheckoutData(self::PRIVATE_NAMESPACE, json_encode($data));
        $this->cart->save();
    }
}