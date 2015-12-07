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


class OnlineShop_Framework_Impl_Payment_PayPal implements OnlineShop_Framework_IPayment
{
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
     * @var string[]
     */
    protected $authorizedData;


    /**
     * @param Zend_Config $xml
     */
    public function __construct(Zend_Config $xml)
    {
        // init
        $this->environment = $xml->mode == 'sandbox' ? 'sandbox' : '';
        $credentials = $xml->config->{$xml->mode};


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
     * @return string
     */
    public function getName()
    {
        return 'PayPal';
    }


    /**
     * start payment
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     * @param array                       $config
     *
     * @return string
     * @throws Exception
     * @link https://devtools-paypal.com/apiexplorer/PayPalAPIs
     */
    public function initPayment(\OnlineShop\Framework\PriceSystem\IPrice $price, array $config)
    {
        // check params
        $required = [  'ReturnURL' => null
                       , 'CancelURL' => null
                       , 'OrderDescription' => null
                       , 'InvoiceID' => null
        ];
        $config = array_intersect_key($config, $required);

        if(count($required) != count($config))
        {
            throw new Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
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
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->PaymentDetails = $this->createPaymentDetails( $price );
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->OrderDescription = $config['OrderDescription'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->InvoiceID = $config['InvoiceID'];


        // add optional config
        foreach($config as $name => $value)
        {
            $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->{$name} = $value;
        }


        // execute request
        $ret = $this->client->SetExpressCheckout($x);


        // check Ack
        if($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning')
        {
            # pay url
            return 'https://www.' . $this->environment . '.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $ret->Token;
        }
        else
        {
            $messages = null;
            $errors = is_array($ret->Errors)
                ? $ret->Errors
                : array($ret->Errors);
            foreach($errors as $error)
            {
                $messages .= $error->LongMessage ."\n";
            }
            throw new Exception( $messages );
        }
    }


    /**
     * execute payment
     * @param mixed $response
     *
     * @return OnlineShop_Framework_Payment_IStatus
     * @throws Exception
     */
    public function handleResponse($response)
    {
        // check required fields
        $required = [   'token' => null
                      , 'PayerID' => null
                      , 'InvoiceID' => null
                      , 'amount' => null
                      , 'currency' => null
        ];
        $authorizedData = [
              'token' => null
            , 'PayerID' => null
        ];


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
        $price = new \OnlineShop\Framework\PriceSystem\Price($response['amount'], new Zend_Currency($response['currency']));


        // execute
        //TODO do not call this in handle response, but call it in the controller!
        //TODO return a 'intermediate' status
        return $this->executeDebit($price, $response['InvoiceID']);
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
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeDebit(\OnlineShop\Framework\PriceSystem\IPrice $price = null, $reference = null)
    {
        // Execute payment
        $x = new stdClass;
        $x->DoExpressCheckoutPaymentRequest = new stdClass();
        $x->DoExpressCheckoutPaymentRequest->Version = $this->protocol;
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails = new stdClass();
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->Token = $this->authorizedData['token'];
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PayerID = $this->authorizedData['PayerID'];
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PaymentDetails = $this->createPaymentDetails( $price );


        // execute
        $ret = $this->client->DoExpressCheckoutPayment($x);


        // check Ack
        if($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning')
        {
            // success

            $paymentInfo = $ret->DoExpressCheckoutPaymentResponseDetails->PaymentInfo;
            return new OnlineShop_Framework_Impl_Payment_Status(
                $reference
                , $paymentInfo->TransactionID
                , null
                , \OnlineShop\Framework\Model\AbstractOrder::ORDER_STATE_COMMITTED
                , [
                    'paypal_TransactionType' => $paymentInfo->TransactionType
                    , 'paypal_PaymentType' => $paymentInfo->PaymentType
                    , 'paypal_amount' => (string)$price
                ]
            );

        }
        else
        {
            // failed

            $message = '';
            $errors = is_array($ret->Errors)
                ? $ret->Errors
                : array($ret->Errors);
            foreach($errors as $error)
            {
                $message .= $error->LongMessage . "\n";
            }


            return new OnlineShop_Framework_Impl_Payment_Status(
                $reference
                , $ret->CorrelationID
                , $message
                , \OnlineShop\Framework\Model\AbstractOrder::ORDER_STATE_ABORTED
            );
        }
    }

    /**
     * execute credit
     *
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeCredit(\OnlineShop\Framework\PriceSystem\IPrice $price, $reference, $transactionId)
    {
        // TODO: Implement executeCredit() method.
    }


    /**
     * @param \OnlineShop\Framework\PriceSystem\IPrice $price
     *
     * @return stdClass
     */
    protected function createPaymentDetails(\OnlineShop\Framework\PriceSystem\IPrice $price) # \OnlineShop\Framework\Model\AbstractOrder $order
    {
        // create order total
        $paymentDetails = new stdClass();
        $paymentDetails->OrderTotal = new stdClass();
        $paymentDetails->OrderTotal->_ = $price->getAmount();
        $paymentDetails->OrderTotal->currencyID = $price->getCurrency()->getShortName();


//        // add article
//        $itemTotal = 0;
//        $paymentDetails->PaymentDetailsItem = array();
//        foreach($this->cart->getItems() as $item)
//        {
//            $article = new stdClass();
//            $article->Name = $item->getProduct()->getOSName();
//            $article->Description = $item->getComment();
//            $article->Number = $item->getProduct()->getOSProductNumber();
//            $article->Quantity = $item->getCount();
//            $article->Amount = new stdClass();
//            $article->Amount->_ = $item->getPrice()->getAmount();
//            $article->Amount->currencyID = $currency;
//
//            $paymentDetails->PaymentDetailsItem[] = $article;
//            $itemTotal += $item->getPrice()->getAmount();
//        }
//
//
//        // add modificators
//        foreach($priceCalculator->getPriceModifications() as $name => $modification)
//        {
//            if($modification instanceof OnlineShop_Framework_IModificatedPrice && $name == 'shipping')
//            {
//                // add shipping charge
//                $paymentDetails->ShippingTotal = new stdClass();
//                $paymentDetails->ShippingTotal->_ = $modification->getAmount();
//                $paymentDetails->ShippingTotal->currencyID = $currency;
//            }
//            else if($modification instanceof OnlineShop_Framework_IModificatedPrice && $modification->getAmount() !== 0)
//            {
//                // add discount line
//                $article = new stdClass();
//                $article->Name = $modification->getDescription();
//                $article->Quantity = 1;
//                $article->PromoCode = $modification->getDescription();
//                $article->Amount = new stdClass();
//                $article->Amount->_ = $modification->getAmount();
//                $article->Amount->currencyID = $currency;
//                $paymentDetails->PaymentDetailsItem[] = $article;
//
//                $itemTotal += $modification->getAmount();;
//            }
//        }
//
//
//        // create item total
//        $paymentDetails->ItemTotal = new stdClass();
//        $paymentDetails->ItemTotal->_ = $itemTotal;
//        $paymentDetails->ItemTotal->currencyID = $currency;


        return $paymentDetails;
    }
}

