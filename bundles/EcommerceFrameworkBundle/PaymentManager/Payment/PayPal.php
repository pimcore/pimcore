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
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\Fieldcollection\Data\OrderPriceModifications;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated since v6.8.0 and will be removed in Pimcore 10.
 */
class PayPal extends AbstractPayment
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

    public function __construct(array $options)
    {
        $this->processOptions(
            $this->configureOptions(new OptionsResolver())->resolve($options)
        );
    }

    protected function processOptions(array $options)
    {
        parent::processOptions($options);

        // set endpoint depending on mode
        if ('live' === $options['mode']) {
            $this->endpointUrlPart = 'paypal';
        } else {
            $this->endpointUrlPart = 'sandbox.paypal';
        }

        $this->client = $this->createClient($this->endpointUrlPart, $this->createClientCredentials(
            $options['api_username'],
            $options['api_password'],
            $options['api_signature']
        ));
    }

    protected function createClientCredentials(string $username, string $password, string $signature): \stdClass
    {
        $credentials = new \stdClass();
        $credentials->Credentials = new \stdClass();

        $credentials->Credentials->Username = $username;
        $credentials->Credentials->Password = $password;
        $credentials->Credentials->Signature = $signature;

        return $credentials;
    }

    protected function createClient(string $endpointUrlPart, \stdClass $credentials): \SoapClient
    {
        $wsdl = 'https://www.' . $endpointUrlPart . '.com/wsdl/PayPalSvc.wsdl';
        $location = 'https://api-3t.' . $endpointUrlPart . '.com/2.0';

        $client = new \SoapClient($wsdl, ['location' => $location]);
        $client->__setSoapHeaders(
            new \SoapHeader('urn:ebay:api:PayPalAPI', 'RequesterCredentials', $credentials)
        );

        return $client;
    }

    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            'mode',
            'api_username',
            'api_password',
            'api_signature',
        ]);

        $resolver
            ->setDefault('mode', 'sandbox')
            ->setAllowedValues('mode', ['sandbox', 'live']);

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
        return 'PayPal';
    }

    /**
     * Start payment
     *
     * @param PriceInterface $price
     * @param array $config
     *
     * @return string
     *
     * @throws \Exception
     *
     * @link https://devtools-paypal.com/apiexplorer/PayPalAPIs
     */
    public function initPayment(PriceInterface $price, array $config)
    {
        // check params
        $required = [
            'ReturnURL' => null,
            'CancelURL' => null,
            'OrderDescription' => null,
            'InvoiceID' => null,
        ];

        $config = array_intersect_key($config, $required);

        if (count($required) != count($config)) {
            throw new \Exception(sprintf('required fields are missing! required: %s', implode(', ', array_keys(array_diff_key($required, $config)))));
        }

        $order = $config['Order'] ?? null;

        // create request
        $x = new \stdClass;
        $x->SetExpressCheckoutRequest = new \stdClass();
        $x->SetExpressCheckoutRequest->Version = $this->protocol;
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails = new \stdClass();
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->ReturnURL = $config['ReturnURL'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->CancelURL = $config['CancelURL'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->AllowNote = '0';
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->PaymentDetails = $this->createPaymentDetails($price, $order);
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->OrderDescription = $config['OrderDescription'];
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->InvoiceID = $config['InvoiceID'];

        // add BN Code for Pimcore
        $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->ButtonSource = 'Pimcore_SP';

        // add optional config
        foreach ($config as $name => $value) {
            $x->SetExpressCheckoutRequest->SetExpressCheckoutRequestDetails->{$name} = $value;
        }

        // execute request
        $ret = $this->client->SetExpressCheckout($x);

        // check Ack
        if ($ret->Ack == 'Success' || $ret->Ack == 'SuccessWithWarning') {
            $url = 'https://www.%s.com/cgi-bin/webscr?cmd=_express-checkout&token=%s';

            return sprintf($url, $this->endpointUrlPart, $ret->Token);
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
     * Executes payment
     *
     * @param mixed $response
     *
     * @return StatusInterface
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {
        // check required fields
        $required = [
            'token' => null,
            'PayerID' => null,
            'InvoiceID' => null,
            'amount' => null,
            'currency' => null,
        ];

        $authorizedData = [
            'token' => null,
            'PayerID' => null,
        ];

        // check fields
        $response = array_intersect_key($response, $required);
        if (count($required) != count($response)) {
            throw new \Exception(sprintf(
                'required fields are missing! required: %s',
                implode(', ', array_keys(array_diff_key($required, $response)))
            ));
        }

        // handle
        $authorizedData = array_intersect_key($response, $authorizedData);
        $this->setAuthorizedData($authorizedData);

        // restore price object for payment status
        $price = new Price(Decimal::create($response['amount']), new Currency($response['currency']));

        // execute
        //TODO do not call this in handle response, but call it in the controller!
        //TODO return a 'intermediate' status
        // see https://github.com/pimcore-partner/ecommerce-framework/issues/118
        return $this->executeDebit($price, $response['InvoiceID']);
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
     * @inheritdoc
     */
    public function executeDebit(PriceInterface $price = null, $reference = null)
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
                $reference,
                $paymentInfo->TransactionID,
                null,
                AbstractOrder::ORDER_STATE_COMMITTED,
                [
                    'paypal_TransactionType' => $paymentInfo->TransactionType,
                    'paypal_PaymentType' => $paymentInfo->PaymentType,
                    'paypal_amount' => (string)$price,
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
                $reference,
                $ret->CorrelationID,
                $message,
                AbstractOrder::ORDER_STATE_ABORTED
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function executeCredit(PriceInterface $price, $reference, $transactionId)
    {
        // TODO: Implement executeCredit() method.
        throw new \Exception('not implemented');
    }

    /**
     * @param PriceInterface $price
     * @param null|AbstractOrder $order
     *
     * @return \stdClass
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException
     */
    protected function createPaymentDetails(PriceInterface $price, ?AbstractOrder $order = null)
    {
        // create order total
        $paymentDetails = new \stdClass();
        $paymentDetails->OrderTotal = new \stdClass();
        $paymentDetails->OrderTotal->_ = $price->getAmount()->asString(2);
        $paymentDetails->OrderTotal->currencyID = $price->getCurrency()->getShortName();

        if (!$order) {
            return $paymentDetails;
        }

        // add article
        $itemTotal = 0;
        $paymentDetails->PaymentDetailsItem = [];
        $orderCurrency = $order->getCurrency();

        foreach ($order->getItems() as $item) {
            $article = new \stdClass();
            $article->Name = $item->getProduct()->getOSName();
            $article->Number = $item->getProduct()->getOSProductNumber();
            $article->Quantity = $item->getAmount();
            $article->Amount = new \stdClass();
            $article->Amount->_ = $item->getProduct()->getOSPrice()->getGrossAmount()->asString(2);
            $article->Amount->currencyID = $orderCurrency;

            $paymentDetails->PaymentDetailsItem[] = $article;
            $itemTotal += $article->Amount->_ * $article->Quantity;
        }

        /** @var OrderPriceModifications $modification */
        foreach ($order->getPriceModifications()->getItems() as $modification) {
            if ($modification instanceof OrderPriceModifications &&
                $modification->getName() == 'shipping'
            ) {
                // add shipping charge
                $paymentDetails->ShippingTotal = new \stdClass();
                $paymentDetails->ShippingTotal->_ = $modification->getAmount();
                $paymentDetails->ShippingTotal->currencyID = $orderCurrency;
            } elseif ($modification instanceof OrderPriceModifications &&
                $modification->getAmount() !== 0
            ) {
                // add discount line
                $article = new \stdClass();
                $article->Name = $modification->getName();
                $article->Quantity = 1;
                $article->Amount = new \stdClass();
                $article->Amount->_ = $modification->getAmount();
                $article->Amount->currencyID = $orderCurrency;
                $paymentDetails->PaymentDetailsItem[] = $article;

                $itemTotal += $modification->getAmount();
            }
        }

        // create item total
        $paymentDetails->ItemTotal = new \stdClass();
        $paymentDetails->ItemTotal->_ = $itemTotal;
        $paymentDetails->ItemTotal->currencyID = $orderCurrency;

        return $paymentDetails;
    }
}
