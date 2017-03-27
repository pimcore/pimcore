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
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Config\Config;

class PayPal implements IPayment
{
    /**
     * @var string
     */
    protected $endpointUrlPart;

    /**
     * @var \SoapClient
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
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        // init
        $credentials = $config->config->{$config->mode};
        if ($config->mode == 'live') {
            $this->endpointUrlPart = "paypal";
        } else {
            $this->endpointUrlPart = "sandbox.paypal";
        }

        // create paypal interface
        $wsdl = 'https://www.' . $this->endpointUrlPart . '.com/wsdl/PayPalSvc.wsdl';
        $location = 'https://api-3t.' . $this->endpointUrlPart . '.com/2.0';
        $this->client = new \SoapClient($wsdl, ['location' => $location]);

        // auth
        $auth = new \stdClass();
        $auth->Credentials = new \stdClass();
        $auth->Credentials->Username = $credentials->api_username;
        $auth->Credentials->Password = $credentials->api_password;
        $auth->Credentials->Signature = $credentials->api_signature;

        $header = new \SoapHeader('urn:ebay:api:PayPalAPI', 'RequesterCredentials', $auth);

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
     * @param IPrice $price
     * @param array                       $config
     *
     * @return string
     * @throws \Exception
     * @link https://devtools-paypal.com/apiexplorer/PayPalAPIs
     */
    public function initPayment(IPrice $price, array $config)
    {
        // check params
        $required = [  'ReturnURL' => null
                       , 'CancelURL' => null
                       , 'OrderDescription' => null
                       , 'InvoiceID' => null
        ];
        $config = array_intersect_key($config, $required);

        if (count($required) != count($config)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
        }


        // create request
        $x = new \stdClass;
        $x->SetExpressCheckoutRequest = new \stdClass();
        $x->SetExpressCheckoutRequest->Version = $this->protocol;
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails = new \stdClass();
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->ReturnURL = $config['ReturnURL'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->CancelURL = $config['CancelURL'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->NoShipping = "1";
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->AllowNote = "0";
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->PaymentDetails = $this->createPaymentDetails($price);
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->OrderDescription = $config['OrderDescription'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->InvoiceID = $config['InvoiceID'];


        // add optional config
        foreach ($config as $name => $value) {
            $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->{$name} = $value;
        }


        // execute request
        $ret = $this->client->SetExpressCheckout($x);


        // check Ack
        if ($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning') {
            // pay url
            return 'https://www.' . $this->endpointUrlPart . '.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $ret->Token;
        } else {
            $messages = null;
            $errors = is_array($ret->Errors)
                ? $ret->Errors
                : [$ret->Errors];
            foreach ($errors as $error) {
                $messages .= $error->LongMessage ."\n";
            }
            throw new \Exception($messages);
        }
    }


    /**
     * execute payment
     * @param mixed $response
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus
     * @throws \Exception
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
        if (count($required) != count($response)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $response)))));
        }


        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData($authorizedData);


        // restore price object for payment status
        $price = new Price($response['amount'], new Currency($response['currency']));


        // execute
        //TODO do not call this in handle response, but call it in the controller!
        //TODO return a 'intermediate' status
        // see https://github.com/pimcore-partner/ecommerce-framework/issues/118
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
     * @param IPrice $price
     * @param string                      $reference
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus
     */
    public function executeDebit(IPrice $price = null, $reference = null)
    {
        // Execute payment
        $x = new \stdClass;
        $x->DoExpressCheckoutPaymentRequest = new \stdClass();
        $x->DoExpressCheckoutPaymentRequest->Version = $this->protocol;
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails = new \stdClass();
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->Token = $this->authorizedData['token'];
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PayerID = $this->authorizedData['PayerID'];
        $x->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PaymentDetails = $this->createPaymentDetails($price);


        // execute
        $ret = $this->client->DoExpressCheckoutPayment($x);


        // check Ack
        if ($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning') {
            // success

            $paymentInfo = $ret->DoExpressCheckoutPaymentResponseDetails->PaymentInfo;

            return new Status(
                $reference, $paymentInfo->TransactionID, null, AbstractOrder::ORDER_STATE_COMMITTED, [
                    'paypal_TransactionType' => $paymentInfo->TransactionType
                    , 'paypal_PaymentType' => $paymentInfo->PaymentType
                    , 'paypal_amount' => (string)$price
                ]
            );
        } else {
            // failed

            $message = '';
            $errors = is_array($ret->Errors)
                ? $ret->Errors
                : [$ret->Errors];
            foreach ($errors as $error) {
                $message .= $error->LongMessage . "\n";
            }


            return new Status(
                $reference, $ret->CorrelationID, $message, AbstractOrder::ORDER_STATE_ABORTED
            );
        }
    }

    /**
     * execute credit
     *
     * @param IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus
     */
    public function executeCredit(IPrice $price, $reference, $transactionId)
    {
        // TODO: Implement executeCredit() method.
    }


    /**
     * @param IPrice $price
     *
     * @return \stdClass
     */
    protected function createPaymentDetails(IPrice $price) // \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order
    {
        // create order total
        $paymentDetails = new \stdClass();
        $paymentDetails->OrderTotal = new \stdClass();
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
