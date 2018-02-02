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
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OGone
 * Payment integration for Ingenico OGone
 *
 * @see https://payment-services.ingenico.com/int/en/ogone/support/guides/integration%20guides/e-commerce/introduction
 *
 * @package Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment
 */
class OGone implements IPayment
{
    private static $OGONE_SERVER_URL_TEST = 'https://secure.ogone.com/ncol/test/orderstandard_utf8.asp';
    private static $OGONE_SERVER_URL_LIVE = 'https://secure.ogone.com/ncol/prod/orderstandard_utf8.asp';

    /**
     * @var string
     */
    private $serverURL;

    /**
     * @var string[]
     */
    protected $authorizedData;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var string[]
     */
    private $providerOptions;

    /**
     * @var string[] parameter list with allowed parameters for SHA - in generation
     */
    protected static $_SHA_IN_PARAMETERS = [
        'ACCEPTURL',                       'ADDMATCH',          'ADDRMATCH',
        'ALIAS',                           'ALIASOPERATION',                   'ALIASUSAGE',
        'ALLOWCORRECTION',                 'AMOUNT',                           'AMOUNTHTVA',
        'AMOUNTTVA',                       'BACKURL',                          'BGCOLOR',
        'BRAND',                           'BRANDVISUAL',                      'BUTTONBGCOLOR',
        'BUTTONTXTCOLOR',                  'CANCELURL',                        'CARDNO',
        'CATALOGURL',                      'CERTID',                           'CHECK_AAV',
        'CIVILITY',                        'CN',                               'COM',
        'COMPLUS',                         'COSTCENTER',                       'CREDITCODE',
        'CUID',                            'CURRENCY',                         'CVC',
        'DATA',                            'DATATYPE',                         'DATEIN',
        'DATEOUT',                         'DECLINEURL',                       'DISCOUNTRATE',
        'ECI',                             'ECOM_BILLTO_POSTAL_CITY',          'ECOM_BILLTO_POSTAL_COUNTRYCODE',
        'ECOM_BILLTO_POSTAL_NAME_FIRST',   'ECOM_BILLTO_POSTAL_NAME_LAST',     'ECOM_BILLTO_POSTAL_POSTALCODE',
        'ECOM_BILLTO_POSTAL_STREET_LINE1', 'ECOM_BILLTO_POSTAL_STREET_LINE2',  'ECOM_BILLTO_POSTAL_STREET_NUMBER',
        'ECOM_CONSUMERID',                 'ECOM_CONSUMERORDERID',             'ECOM_CONSUMERUSERALIAS',
        'ECOM_PAYMENT_CARD_EXPDATE_MONTH', 'ECOM_PAYMENT_CARD_EXPDATE_YEAR',   'ECOM_PAYMENT_CARD_NAME',
        'ECOM_PAYMENT_CARD_VERIFICATION',  'ECOM_SHIPTO_COMPANY',              'ECOM_SHIPTO_DOB',
        'ECOM_SHIPTO_ONLINE_EMAIL',        'ECOM_SHIPTO_POSTAL_CITY',          'ECOM_SHIPTO_POSTAL_COUNTRYCODE',
        'ECOM_SHIPTO_POSTAL_NAME_FIRST',   'ECOM_SHIPTO_POSTAL_NAME_LAST',     'ECOM_SHIPTO_POSTAL_POSTALCODE',
        'ECOM_SHIPTO_POSTAL_STREET_LINE1', 'ECOM_SHIPTO_POSTAL_STREET_LINE2',  'ECOM_SHIPTO_POSTAL_STREET_NUMBER',
        'ECOM_SHIPTO_TELECOM_FAX_NUMBER',  'ECOM_SHIPTO_TELECOM_PHONE_NUMBER', 'ECOM_SHIPTO_TVA',
        'ED',                              'EMAIL',                            'EXCEPTIONURL',
        'EXCLPMLIST',                      'FIRSTCALL',                        'FLAG3D',
        'FONTTYPE',                        'FORCECODE1',                       'FORCECODE2',
        'FORCECODEHASH',                   'FORCETP',                          'GENERIC_BL',
        'GIROPAY_ACCOUNT_NUMBER',          'GIROPAY_BLZ',                      'GIROPAY_OWNER_NAME',
        'GLOBORDERID',                     'GUID',                             'HDFONTTYPE',
        'HDTBLBGCOLOR',                    'HDTBLTXTCOLOR',                    'HEIGHTFRAME',
        'HOMEURL',                         'HTTP_ACCEPT',                      'HTTP_USER_AGENT',
        'INCLUDE_BIN',                     'INCLUDE_COUNTRIES',                'INVDATE',
        'INVDISCOUNT',                     'INVLEVEL',                         'INVORDERID',
        'ISSUERID',                        'LANGUAGE',                         'LEVEL1AUTHCPC',
        'LIMITCLIENTSCRIPTUSAGE',          'LINE_REF',                         'LIST_BIN',
        'LIST_COUNTRIES',                  'LOGO',                             'MERCHANTID',
        'MODE',                            'MTIME',                            'MVER',
        'OPERATION',                       'OR_INVORDERID',                    'OR_ORDERID',
        'ORDERID',                         'ORIG',                             'OWNERADDRESS',
        'OWNERADDRESS2',                   'OWNERCTY',                         'OWNERTELNO',
        'OWNERTOWN',                       'OWNERZIP',                         'PAIDAMOUNT',
        'PARAMPLUS',                       'PARAMVAR',                         'PAYID',
        'PAYMETHOD',                       'PM',                               'PMLIST',
        'PMLISTPMLISTTYPE',                'PMLISTTYPE',                       'PMLISTTYPEPMLIST',
        'PMTYPE',                          'POPUP',                            'POST',
        'PSPID',                           'PSWD',                             'REF',
        'REF_CUSTOMERID',                  'REF_CUSTOMERREF',                  'REFER',
        'REFID',                           'REFKIND',                          'REMOTE_ADDR',
        'REQGENFIELDS',                    'RTIMEOUT',                         'RTIMEOUTREQUESTEDTIMEOUT',
        'SCORINGCLIENT',                   'SETT_BATCH',                       'SID',
        'TAAL',                            'TBLBGCOLOR',                       'TBLTXTCOLOR',
        'TID',                             'TITLE',                            'TOTALAMOUNT',
        'TP',                              'TRACK2',                           'TXTBADDR2',
        'TXTCOLOR',                        'TXTOKEN',                          'TXTOKENTXTOKENPAYPAL',
        'TYPE_COUNTRY',                    'UCAF_AUTHENTICATION_DATA',         'UCAF_PAYMENT_CARD_CVC2',
        'UCAF_PAYMENT_CARD_EXPDATE_MONTH', 'UCAF_PAYMENT_CARD_EXPDATE_YEAR',   'UCAF_PAYMENT_CARD_NUMBER',
        'USERID',                          'USERTYPE',                         'VERSION',
        'WBTU_MSISDN',                     'WBTU_ORDERID',                     'WEIGHTUNIT',
        'WIN3DS',                          'WITHROOT'
    ];

    /** @var string[] parameters that can be used for the creation of the SHA fingerprint */
    private static $_SHA_OUT_PARAMETERS = [
            'AAVADDRESS',               'AAVCHECK',             'AAVMAIL',
            'AAVNAME',                  'AAVPHONE',             'AAVZIP',
            'ACCEPTANCE',               'ALIAS',                'AMOUNT',
            'BIC',                      'BIN',                  'BRAND',
            'CARDNO',                   'CCCTY',                'CN',
            'COLLECTOR_BIC',            'COLLECTOR_IBAN',       'COMPLUS',
            'CREATION_STATUS',          'CREDITDEBIT',          'CURRENCY',
            'CVCCHECK',                 'DCC_COMMPERCENTAGE',   'DCC_CONVAMOUNT',
            'DCC_CONVCCY',              'DCC_EXCHRATE',         'DCC_EXCHRATESOURCE',
            'DCC_EXCHRATETS',           'DCC_INDICATOR',        'DCC_MARGINPERCENTAGE',
            'DCC_VALIDHOURS',           'DEVICEID',             'DIGESTCARDNO',
            'ECI',                      'ED',                   'EMAIL',
            'ENCCARDNO',                'FXAMOUNT',             'FXCURRENCY',
            'IP',                       'IPCTY',                'MANDATEID',
            'MOBILEMODE',               'NBREMAILUSAGE',        'NBRIPUSAGE',
            'NBRIPUSAGE_ALLTX',         'NBRUSAGE',             'NCERROR',
            'ORDERID',                  'PAYID',                'PAYIDSUB',
            'PAYMENT_REFERENCE',        'PM',                   'SCO_CATEGORY',
            'SCORING',                  'SEQUENCETYPE',         'SIGNDATE',
            'STATUS',                   'SUBBRAND',             'SUBSCRIPTION_ID',
            'TRXDATE',                  'VC'
    ];

    public function __construct(array $options, FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
        $this->authorizedData = [];
        $options['encryptionType'] = isset($options['encryptionType']) ? $options['encryptionType'] : 'SHA256';
        $this->configureOptions(new OptionsResolver())->resolve($options);
        $this->serverURL = $options['mode'] == 'live' ? self::$OGONE_SERVER_URL_LIVE : self::$OGONE_SERVER_URL_TEST;
        $this->providerOptions = $options;
    }

    /**
     * Start payment and build form, including fingerprint for Ogone.
     *
     * @param IPrice $price
     * @param array $config
     *
     * @return FormBuilderInterface
     *
     * @throws \Exception
     */
    public function initPayment(IPrice $price, array $config)
    {
        //form name needs to be null in order to make sure the element names are correct - and not FORMNAME[ELEMENTNAME]
        $form = $this->formFactory->createNamedBuilder(null, FormType::class, [], [
            'attr' => ['id' => 'payment_ogone_form']
        ]);

        /** @var $paymentInfo \OnlineShop\Framework\Model\AbstractPaymentInformation $paymentInfo * */
        $paymentInfo = $config['paymentInfo'];
        //$order = $paymentInfo->getObject();

        $form->setAction($this->serverURL);
        $form->setMethod('post');
        $form->setAttribute('accept-charset', 'UTF-8');
        $form->setAttribute('data-currency', 'EUR');

        $params = [
            'PSPID'         => $this->getProviderOption('pspid'),
            'ORDERID'       => $config['orderIdent'],
            'AMOUNT'        => $price->getAmount()->asNumeric() * 100,
            'CURRENCY'      => $price->getCurrency()->getShortName(),
            'LANGUAGE'      => $config['language'],
            'ACCEPTURL'     =>  $config['successUrl'],
            'CANCELURL'     => $config['cancelUrl'],
            'DECLINEURL'    => $config['errorUrl'],
            'TP'            =>  $this->getProviderOption('TP', 'paymenttemplate.html'),
        ];

        if (isset($config['customerStatement'])) {
            $params['TITLE'] = $config['customerStatement'];
        }

        $additionalParams = $this->mapAdditionalPaymentData($params, $config);
        $params = $this->processAdditionalPaymentData($params, $config, $additionalParams);

        foreach ($params as $key => $value) {
            $this->addHiddenField($form, $key, $value);
        }

        // new sha verification method (all parameters)
        $params = $this->getRawSHA($params, self::$_SHA_IN_PARAMETERS, $this->getProviderOption('secret'));
        $sha = $this->getSHA($this->getProviderOption('encryptionType'), $params);
        $this->addHiddenField($form, 'SHASIGN', $sha);

        return $form;
    }

    /**
     * Handles response of payment provider and creates payment status object. Fingerprint must match.
     *
     * @param array $response
     *
     * @return IStatus
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {
        $cleanedResponseParams = $response;
        unset($cleanedResponseParams['orderId']);

        $params = $this->getRawSHA($cleanedResponseParams, self::$_SHA_OUT_PARAMETERS, $this->getProviderOption('secret'));
        $verificationSha = $this->getSHA($this->getProviderOption('encryptionType'), $params);

        if ($verificationSha != $response['SHASIGN']) {
            throw new \Exception('The verification of the response data was not successful.');
        }

        $currency       = $response['currency'];
        $amount         = $response['amount']; //e.g., 512, not 51200
        $paymentMethod  = $response['PM']; //e.g. sofortüberweisung.de
        $customerName   = $response['CN'];
        $oGonePaymentId = $response['PAYID'];
        $ip             = $response['IP'];
        $orderId        = $response['orderID'];
        $state          = $response['state']; //success,

        // restore price object for payment status
        $price = new Price(Decimal::create($amount), new Currency($currency));

        $this->setAuthorizedData([
            'orderNumber'       => $orderId,
            'paymentMethod'     => $paymentMethod,
            'paymentId'         => $oGonePaymentId,
            'amount'            => $amount,
            'currency'          => $currency,
            'ip'                => $ip,
            'customerName' >= $customerName
        ]);

        $responseStatus = new Status(
            $orderId, //internal Payment ID
            $orderId, //paymentReference
            '',
            !empty($orderId) && $state === 'success' ? IStatus::STATUS_AUTHORIZED : IStatus::STATUS_CANCELLED,
            [
                'ogone_amount'          => (string)$price,
                'ogone_paymentId'       => $oGonePaymentId,
                'ogone_paymentState'    => $state,
                'ogone_paymentType'     => $paymentMethod,
                'ogone_response'        => $response
            ]
        );

        return $responseStatus;
    }

    /**
     * Check options that have been passed by the main configuration
     *
     * @param OptionsResolver $resolver
     *
     * @return OptionsResolver
     */
    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver->setRequired([
            'pspid',
            'secret',
            'encryptionType',
            'mode'
        ]);
        $resolver->setAllowedValues('encryptionType', ['SHA1', 'SHA256', 'SHA512']);
        $notEmptyValidator = function ($value) {
            return !empty($value);
        };
        foreach ($resolver->getRequiredOptions() as $requiredProperty) {
            $resolver->setAllowedValues($requiredProperty, $notEmptyValidator);
        }

        return $resolver;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getName()
    {
        return 'OGone';
    }

    /**
     * Helper method for adding hidden fields to a form.
     *
     * @param FormBuilderInterface $form
     * @param $name
     * @param $value
     *
     * @return FormBuilderInterface
     */
    private function addHiddenField(FormBuilderInterface &$form, $name, $value)
    {
        return $form->add($name, HiddenType::class, ['data' => $value]);
    }

    /**
     * Helper method to get a value from the main provider configuration based on a string.
     *
     * @param string $key the name of the provider option.
     * @param string $default if given (not empty) then the default value will be used if there is no array entry. If empty, then a missing key
     *        will result in an error.
     *
     * @return mixed|string
     */
    private function getProviderOption(string $key, $default = '')
    {
        return empty($default) ? $this->providerOptions[$key] : (isset($this->providerOptions[$key]) ? $this->providerOptions[$key] : $default);
    }

    /**
     * Overwrite this method if you want to pass additional parmeters to Ogone during the @link(initPayment) method.
     * The parameters must be one of @code(self::$_SHA_IN_PARAMETERS)
     *
     * @param array $params
     * @param array $config
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function mapAdditionalPaymentData(array $params, array $config)
    {
        /* Example fields: EMAIL, "CN", "OWNERADDRESS", "OWNERZIP", "OWNERCITY", etc. */
        $additionalParams = []; //@map onto additional params from config
        return $additionalParams;
    }

    /**
     * Process additional parameters, such as customer data and throw an exception if invalid parameters have been
     * passed (invalid = not known by the oGone register).
     *
     * @param array $params
     * @param array $config
     * @param array $additionalParams
     *
     * @return array
     *
     * @throws \Exception
     */
    private function processAdditionalPaymentData(array $params, array $config, array $additionalParams)
    {
        /* Example fields: EMAIL, "CN", "OWNERADDRESS", "OWNERZIP", "OWNERCITY", etc. */
        foreach ($additionalParams as $key => $value) {
            if (!in_array($key, self::$_SHA_IN_PARAMETERS)) {
                throw new \Exception('Unknown parameter "%s" for oGone. Please only use parameters that are specified by oGone. Also see "%s".',
                    $key, 'https://payment-services.ingenico.com/int/en/ogone/support/guides/integration%20guides/e-commerce/link-your-website-to-the-payment-page#formparameters');
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
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
     * Executes payment
     *
     *  if price is given, recurPayment command is executed
     *  if no price is given, amount from authorized Data is used and deposit command is executed
     *
     * @param IPrice $price
     * @param string $reference
     *
     * @return IStatus
     *
     * @throws \Exception
     */
    public function executeDebit(IPrice $price = null, $reference = null)
    {
        throw new NotImplementedException('executeDebit is not implemented yet.');
    }

    /**
     * Executes credit
     *
     * @param IPrice $price
     * @param string $reference
     * @param $transactionId
     *
     * @return IStatus
     *
     * @throws \Exception
     */
    public function executeCredit(IPrice $price, $reference, $transactionId)
    {
        throw new NotImplementedException('executeCredit is not implemented yet.');
    }

    /**
     * Get the data to be digested.
     *
     * @param  array  $parameters The parameters.
     * @param  array  $include    Which parameters to include.
     * @param  string $passphrase The passphrase to use.
     *
     * @return string
     */
    protected function getRawSHA($parameters, $include, $passphrase)
    {
        uksort($parameters, 'strnatcasecmp'); //sort by keys, case insensitivity
        $params = [];
        // add required params to our digest
        foreach ($parameters as $key => $value) {
            $upperKey = strtoupper($key);
            if (in_array($upperKey, $include) && $value != '') {
                $params[$upperKey] = $upperKey .'='. $value;
            }
        }
        // add secret key and return
        return implode($passphrase, $params) . $passphrase;
    }

    /**
     * Encode a raw string into a SHA fingerprint.
     *
     * @param string $encryptionType SHA1, SHA256, SHA512
     * @param string $rawString the raw string that should be encoded
     *
     * @return string the encoded string
     *
     * @throws \Exception
     */
    private function getSHA(string $encryptionType, string $rawString)
    {
        switch ($encryptionType) {
            case 'SHA1':
                return mb_strtoupper(sha1($rawString));
            case 'SHA256':
                if (function_exists('hash')) {
                    return mb_strtoupper(hash('sha256', $rawString));
                }
                break;
            case 'SHA512':
                if (function_exists('hash')) {
                    return mb_strtoupper(hash('sha512', $rawString));
                }
        }
        throw new \Exception(sprintf('Algorithm "%s" not available in OGone payment provider.', $encryptionType));
    }
}
