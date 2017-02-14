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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Plugin;

use Pimcore\Tool;
use Pimcore\Google\Analytics as AnalyticsHelper;

class EuCookieLawNotice extends \Zend_Controller_Plugin_Abstract
{
    /**
     * @var string
     */
    protected $templateCode = null;

    /**
     * @param $code
     */
    public function setTemplateCode($code)
    {
        $this->templateCode = $code;
    }

    /**
     * @return string
     */
    public function getTemplateCode()
    {
        if (!$this->templateCode) {
            $this->templateCode = file_get_contents(__DIR__ . "/EuCookieLawNotice/template.html");
        }

        return $this->templateCode;
    }

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        $config = \Pimcore\Config::getSystemConfig();

        if (!$config->general->show_cookie_notice || !Tool::useFrontendOutputFilters($this->getRequest()) || !Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        $template = $this->getTemplateCode();

        // cleanup code
        $template = preg_replace('/[\r\n\t]+/', ' ', $template); //remove new lines, spaces, tabs
        $template = preg_replace('/>[\s]+</', '><', $template); //remove new lines, spaces, tabs
        $template = preg_replace('/[\s]+/', ' ', $template); //remove new lines, spaces, tabs

        $translations = $this->getTranslations();

        foreach ($translations as $key => &$value) {
            $value = htmlentities($value, ENT_COMPAT, "UTF-8");
            $template = str_replace("%" . $key . "%", $value, $template);
        }

        $linkContent = "";
        if (array_key_exists("linkTarget", $translations)) {
            $linkContent = '<a href="' . $translations["linkTarget"] . '" data-content="' . $translations["linkText"] . '"></a>';
        }
        $template = str_replace("%link%", $linkContent, $template);

        $templateCode = \Zend_Json::encode($template);

        $code = '
            <script>
                (function () {
                    var ls = window["localStorage"];
                    if(ls && !ls.getItem("pc-cookie-accepted")) {

                        var code = ' . $templateCode . ';
                        var ci = window.setInterval(function () {
                            if(document.body) {
                                clearInterval(ci);
                                document.body.insertAdjacentHTML("beforeend", code);

                                document.getElementById("pc-button").onclick = function () {
                                    document.getElementById("pc-cookie-notice").style.display = "none";
                                    ls.setItem("pc-cookie-accepted", "true");
                                };
                            }
                        }, 100);
                    }
                })();
            </script>
        ';

        $body = $this->getResponse()->getBody();

        // search for the end <head> tag, and insert the google analytics code before
        // this method is much faster than using simple_html_dom and uses less memory
        $headEndPosition = stripos($body, "</head>");
        if ($headEndPosition !== false) {
            $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
        }

        $this->getResponse()->setBody($body);
    }

    /**
     * @return array
     */
    protected function getTranslations()
    {

        // most common translations
        $defaultTranslations = [
            "text" => [
                "en" => "Cookies help us deliver our services. By using our services, you agree to our use of cookies.",
                "de" => "Cookies helfen uns bei der Bereitstellung unserer Dienste. Durch die Nutzung unserer Dienste erklären Sie sich mit dem Einsatz von Cookies einverstanden.",
                "it" => "I cookie ci aiutano a fornire i nostri servizi. Utilizzando tali servizi, accetti l'utilizzo dei cookie da parte.",
                "fr" => "Les cookies assurent le bon fonctionnement de nos services. En utilisant ces derniers, vous acceptez l'utilisation des cookies.",
                "nl" => "Cookies helpen ons onze services te leveren. Door onze services te gebruiken, geef je aan akkoord te gaan met ons gebruik van cookies.",
                "es" => "Las cookies nos ayudan a ofrecer nuestros servicios. Al utilizarlos, aceptas que usemos cookies.",
                "zh" => "Cookie 可帮助我们提供服务。使用我们的服务即表示您同意我们使用 Cookie",
                "no" => "Informasjonskapsler hjelper oss med å levere tjenestene vi tilbyr. Ved å benytte deg av tjenestene våre, godtar du bruken av informasjonskapsler.",
                "hu" => "A cookie-k segítenek minket a szolgáltatásnyújtásban. Szolgáltatásaink használatával jóváhagyja, hogy cookie-kat használjunk.",
                "sv" => "Vi tar hjälp av cookies för att tillhandahålla våra tjänster. Genom att använda våra tjänster godkänner du att vi använder cookies.",
                "fi" => "Evästeet auttavat meitä palveluidemme tarjoamisessa. Käyttämällä palveluitamme hyväksyt evästeiden käytön.",
                "da" => "Cookies hjælper os med at levere vores tjenester. Ved at bruge vores tjenester accepterer du vores brug af cookies.",
                "pl" => "Nasze usługi wymagają plików cookie. Korzystając z nich, zgadzasz się na używanie przez nas tych plików.",
                "cs" => "Při poskytování služeb nám pomáhají soubory cookie. Používáním našich služeb vyjadřujete souhlas s naším používáním souborů cookie",
                "sk" => "Súbory cookie nám pomáhajú pri poskytovaní našich služieb. Používaním našich služieb vyjadrujete súhlas s používaním súborov cookie.",
                "pt" => "Os cookies nos ajudam a oferecer nossos serviços. Ao usar nossos serviços, você concorda com nosso uso dos cookies.",
                "hr" => "Kolačići nam pomažu pružati usluge. Upotrebom naših usluga prihvaćate našu upotrebu kolačića.",
                "sl" => "Piškotki omogočajo, da vam ponudimo svoje storitve. Z uporabo teh storitev se strinjate z našo uporabo piškotkov.",
                "sr" => "Колачићи нам помажу да пружамо услуге. Коришћењем услуга прихватате нашу употребу колачића.",
                "ru" => "Используя наши сервисы, Вы соглашаетесь на наше использование файлов cookie. Это необходимо для нормального функционирования наших сервисов.",
                "bg" => "„Бисквитките“ ни помагат да предоставяме услугите си. С използването им приемате употребата на „бисквитките“ от наша страна.",
                "et" => "Küpsised aitavad meil teenuseid pakkuda. Teenuste kasutamisel nõustute küpsiste kasutamisega.",
                "el" => "Τα cookie μάς βοηθούν να προσφέρουμε τις υπηρεσίες μας. Χρησιμοποιώντας τις υπηρεσίες μας, αποδέχεστε την από μέρους μας χρήση των cookie.",
                "lv" => "Mūsu pakalpojumos tiek izmantoti sīkfaili. Lietojot mūsu pakalpojumus, jūs piekrītat sīkfailu izmantošanai.",
                "lt" => "Slapukai naudingi mums, kad galėtume teikti paslaugas. Naudodamiesi paslaugomis, sutinkate, kad mes galime naudoti slapukus.",
                "ro" => "Cookie-urile ne ajută să vă oferim serviciile noastre. Prin utilizarea serviciilor, acceptați modul în care utilizăm cookie-urile.",
            ],
            "linkText" => [
                "en" => "Learn more",
                "de" => "Weitere Informationen",
                "it" => "Ulteriori informazioni",
                "fr" => "En savoir plus",
                "nl" => "Meer informatie",
                "es" => "Más información",
                "zh" => "了解详情",
                "no" => "Finn ut mer",
                "hu" => "További információ",
                "sv" => "Läs mer",
                "fi" => "Lisätietoja",
                "da" => "Få flere oplysninger",
                "pl" => "Więcej informacji",
                "cs" => "Další informace",
                "sk" => "Ďalšie informácie",
                "pt" => "Saiba mais",
                "hr" => "Saznajte više",
                "sl" => "Več o tem",
                "sr" => "Сазнајте више",
                "ru" => "Подробнее...",
                "bg" => "Научете повече",
                "et" => "Lisateave",
                "el" => "Μάθετε περισσότερα",
                "lv" => "Uzziniet vairāk",
                "lt" => "Sužinoti daugiau",
                "ro" => "Aflați mai multe",
            ],
            "ok" => [
                "en" => "OK",
                "de" => "Ok",
                "it" => "Ho capito",
                "fr" => "J'ai compris",
                "nl" => "Ik snap het",
                "es" => "De acuerdo",
                "zh" => "知道了",
                "no" => "Greit",
                "hu" => "Rendben",
                "sv" => "Uppfattat",
                "fi" => "Selvä",
                "da" => "Forstået",
                "pl" => "OK",
                "cs" => "OK",
                "sk" => "Rozumiem",
                "pt" => "Entendi",
                "hr" => "Shvaćam",
                "sl" => "V redu",
                "sr" => "Важи",
                "ru" => "OK",
                "bg" => "Разбрах",
                "et" => "Selge",
                "el" => "Το κατάλαβα",
                "lv" => "Sapratu",
                "lt" => "Supratau",
                "ro" => "Am înțeles",
            ]
        ];


        $translations = [];

        if (\Zend_Registry::isRegistered("Zend_Translate")) {
            foreach (["text", "linkText", "ok", "linkTarget"] as $key) {
                $translationKey = "cookie-policy-" . $key;

                $translator = \Zend_Registry::get("Zend_Translate");
                $translation = $translator->translate($translationKey);
                if ($translation != $translationKey) {
                    $translations[$key] = $translation;
                }
            }
        }

        $language = "en"; // default language
        if (\Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = \Zend_Registry::get("Zend_Locale");
            if ($locale instanceof \Zend_Locale && array_key_exists($locale->getLanguage(), $defaultTranslations["text"])) {
                $language = $locale->getLanguage();
            }
        }

        // set defaults in en or the language in Zend_Locale if registered (fallback)
        foreach ($defaultTranslations as $key => $values) {
            if (!array_key_exists($key, $translations)) {
                $translations[$key] = $values[$language];
            }
        }

        return $translations;
    }
}
