<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 30.07.13
 * Time: 16:51
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Checkout_Payment_PayPal extends OnlineShop_Framework_Impl_Checkout_AbstractPayment implements OnlineShop_Framework_ICheckoutPayment
{
    const PRIVATE_NAMESPACE = 'paymentPayPal';

    /**
     * @var string  sandbox|null
     */
    protected $environment;

    /**
     * @var SoapClient
     */
    protected $client;

    /**
     * @var int
     */
    protected $protocol = 94;

    /**
     * @var string
     */
    protected $TransactionID;


    /**
     * @param Zend_Config                $xml
     * @param OnlineShop_Framework_ICart $cart
     * @throws Exception
     */
    public function __construct(Zend_Config $xml, OnlineShop_Framework_ICart $cart)
    {
        // init
        $this->cart = $cart;
        $this->environment = $xml->mode == 'sandbox' ? 'sandbox' : '';
        $credentials = $xml->{$xml->mode};

        // load checkout data
        $this->loadCheckoutData();


        // create paypal interface
        $wsdl = sprintf('https://www.sandbox.paypal.com/wsdl/PayPalSvc.wsdl', $this->environment);
        $location = sprintf('https://api-3t.sandbox.paypal.com/2.0', $this->environment);
        $this->client = new SoapClient($wsdl, array('location' => $location));

        // auth
        $auth = new stdClass();
        $auth->Credentials = new stdClass();
        $auth->Credentials->Username = $credentials->api_username;
        $auth->Credentials->Password = $credentials->api_password;
        $auth->Credentials->Signature = $credentials->api_signature;

        $header = new SoapHeader('urn:ebay:api:PayPalAPI', 'RequesterCredentials', $auth);

        $this->client->__setSoapHeaders($header);

    }


    /**
     * save session data
     */
    public function __destruct()
    {
        $this->saveCheckoutData();
    }


    /**
     * start payment
     * @param array $config
     *
     * @return string|bool
     * @link https://devtools-paypal.com/apiexplorer/PayPalAPIs
     */
    public function initPayment(array $config)
    {
        // init
        $return = false;    // return false on errors
        $this->errors = array();

        // check params
        $required = array('ReturnURL', 'CancelURL', 'OrderDescription');
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
            $this->errors[] = sprintf('required field %s is missing', implode(',', $missing));
            return $return;
        }


        // create request
        $x = new stdClass;
        $x->SetExpressCheckoutRequest = new stdClass();
        $x->SetExpressCheckoutRequest->Version = $this->protocol;
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails = new stdClass();
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->ReturnURL = $config['ReturnURL'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->CancelURL = $config['CancelURL'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->NoShipping = "1";
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->AllowNote = "0";
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->PaymentDetails = $this->createPaymentDetails();
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->OrderDescription = $config['OrderDescription'];


        // add optional config
        foreach($config as $name => $value)
        {
            $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->{$name} = $value;
        }


        // execute request
        try
        {
            $ret = $this->client->SetExpressCheckout($x);
        }
        catch (Exception $e)
        {
            $this->errors[] = $e->getMessage();
            return $return;
        }


        // check Ack
        if($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning')
        {
            # pay url
            $return = 'https://www.' . $this->environment . '.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $ret->Token;
        }
        else
        {
            $errors = is_array($ret->Errors)
                ? $ret->Errors
                : array($ret->Errors);
            foreach($errors as $error)
            {
                $this->errors[] = $error->LongMessage;
            }
        }


        return $return;
    }


    /**
     * execute payment
     *
     * @param mixed $response
     * @return boole
     */
    public function handleResponse($response)
    {
        // init
        $return = false;
        $this->errors = array();

        // check if we have all required fields
        if($response['token'] == '' || $response['PayerID'] == '')
        {
            $this->errors[] = 'required field "token" or "PayerId" is missing';
            return $return;
        }


        // Execute payment
        $x = new stdClass;
        $x->DoExpressCheckoutPaymentRequest = new stdClass();
        $x->DoExpressCheckoutPaymentRequest->Version = $this->protocol;
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails = new stdClass();
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->Token = $response['token'];
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PayerID = $response['PayerID'];
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PaymentDetails = $this->createPaymentDetails();

        try
        {
            $ret = $this->client->DoExpressCheckoutPayment($x);
        }
        catch (Exception $e)
        {
            $this->errors[] = $e->getMessage();
            return $return;
        }


        // check Ack
        if($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning')
        {
            $this->TransactionID = $ret->DoExpressCheckoutPaymentResponseDetails->PaymentInfo->TransactionID;
            $return = true;
        }
        else
        {
            $errors = is_array($ret->Errors)
                ? $ret->Errors
                : array($ret->Errors);
            foreach($errors as $error)
            {
                $this->errors[] = $error->LongMessage;
            }
        }

        return $return;
    }


    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->TransactionID !== NULL;
    }


    /**
     * @return string|null
     */
    public function getPayReference()
    {
        return $this->TransactionID;
    }


    /**
     * @return stdClass
     */
    protected function createPaymentDetails()
    {
        // init
        $priceCalculator = $this->cart->getPriceCalculator();
        $currency = $priceCalculator->getGrandTotal()->getCurrency()->getShortName();


        // create order total
        $paymentDetails = new stdClass();
        $paymentDetails->OrderTotal = new stdClass();
        $paymentDetails->OrderTotal->_ = $this->cart->getPriceCalculator()->getGrandTotal()->getAmount();
        $paymentDetails->OrderTotal->currencyID = $currency;


        // add article
        $itemTotal = 0;
        $paymentDetails->PaymentDetailsItem = array();
        foreach($this->cart->getItems() as $item)
        {
            $article = new stdClass();
            $article->Name = $item->getProduct()->getOSName();
            $article->Description = $item->getComment();
            $article->Number = $item->getProduct()->getOSProductNumber();
            $article->Quantity = $item->getCount();
            $article->Amount = new stdClass();
            $article->Amount->_ = $item->getPrice()->getAmount();
            $article->Amount->currencyID = $currency;

            $paymentDetails->PaymentDetailsItem[] = $article;
            $itemTotal += $item->getPrice()->getAmount();
        }


        // add modificators
        foreach($priceCalculator->getPriceModifications() as $name => $modification)
        {
            if($modification instanceof OnlineShop_Framework_IModificatedPrice && $name == 'shipping')
            {
                // add shipping charge
                $paymentDetails->ShippingTotal = new stdClass();
                $paymentDetails->ShippingTotal->_ = $modification->getAmount();
                $paymentDetails->ShippingTotal->currencyID = $currency;
            }
            else if($modification instanceof OnlineShop_Framework_IModificatedPrice && $modification->getAmount() !== 0)
            {
                // add discount line
                $article = new stdClass();
                $article->Name = $modification->getDescription();
                $article->Quantity = 1;
                $article->PromoCode = $modification->getDescription();
                $article->Amount = new stdClass();
                $article->Amount->_ = $modification->getAmount();
                $article->Amount->currencyID = $currency;
                $paymentDetails->PaymentDetailsItem[] = $article;

                $itemTotal += $modification->getAmount();;
            }
        }


        // create item total
        $paymentDetails->ItemTotal = new stdClass();
        $paymentDetails->ItemTotal->_ = $itemTotal;
        $paymentDetails->ItemTotal->currencyID = $currency;


        return $paymentDetails;
    }


    /**
     * load session data
     */
    protected function loadCheckoutData()
    {
        $data = json_decode($this->cart->getCheckoutData(self::PRIVATE_NAMESPACE), true);
        $this->TransactionID = $data['TransactionID'];
        $this->errors = $data['Errors'];
    }


    /**
     * save session data
     */
    protected function saveCheckoutData()
    {
        $data = array(
            'TransactionID' => $this->TransactionID,
            'Errors' => $this->errors
        );

        $this->cart->setCheckoutData(self::PRIVATE_NAMESPACE, json_encode($data));
        $this->cart->save();
    }
}

