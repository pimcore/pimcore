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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Analytics\Piwik\Event\TrackingDataEvent;
use Pimcore\Analytics\Piwik\Tracker;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Event\Analytics\PiwikEvents;
use Pimcore\Event\Targeting\TargetingEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Targeting\TargetGroupResolver;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TargetingListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;
    use ResponseInjectionTrait;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $personas = [];

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var TargetGroupResolver
     */
    private $targetGroupResolver;

    public function __construct(
        DocumentResolver $documentResolver,
        TargetGroupResolver $targetGroupResolver
    )
    {
        $this->documentResolver    = $documentResolver;
        $this->targetGroupResolver = $targetGroupResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PiwikEvents::CODE_TRACKING_DATA => 'onPiwikTrackingData',
            KernelEvents::REQUEST           => 'onKernelRequest',
            KernelEvents::RESPONSE          => ['onKernelResponse', -106],
            TargetingEvents::POST_RESOLVE   => 'onPostTargetingResolve'
        ];
    }

    /**
     * @param $key
     * @param $value
     */
    public function addEvent($key, $value)
    {
        $this->events[] = ['key' => $key, 'value' => $value];
    }

    /**
     * @param $id
     */
    public function addPersona($id)
    {
        $this->personas[] = $id;
    }

    /**
     * @return bool
     */
    public function disable()
    {
        $this->enabled = false;

        return true;
    }

    /**
     * @return bool
     */
    public function enable()
    {
        $this->enabled = true;

        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function onPiwikTrackingData(TrackingDataEvent $event)
    {
        $event->getBlock(Tracker::BLOCK_BEFORE_SCRIPT_TAG)->append(
            '<script type="text/javascript" src="/pimcore/static6/js/frontend/targeting_id.js"></script>'
        );

        $event->getBlock(Tracker::BLOCK_AFTER_TRACK)->append(
            '_paq.push([ function() { pimcore.Targeting.setVisitorId(this.getVisitorId()); } ]);'
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$this->targetGroupResolver->isTargetingConfigured()) {
            return;
        }

        $visitorInfo = $this->targetGroupResolver->resolve($request);

        // propagate response (e.g. redirect) to request handling
        if ($visitorInfo->hasResponse()) {
            $event->setResponse($visitorInfo->getResponse());
        }
    }

    public function onPostTargetingResolve(TargetingEvent $event)
    {
        $visitorInfo = $event->getVisitorInfo();

        if ($visitorInfo->hasResponse()) {
            return;
        }

        if (0 === count($visitorInfo->getTargetGroups())) {
            return;
        }

        $request = $event->getRequest();

        // do not redirect multiple times
        if (!empty($request->get('_ptp'))) {
            return;
        }

        // load available persona IDs from document
        $personaIds = $this->getDocumentPersonaIds($request);

        /** @var Model\Tool\Targeting\Persona $redirectPersona */
        $redirectPersona = null;

        foreach ($visitorInfo->getTargetGroups() as $persona) {
            if (in_array($persona->getId(), $personaIds)) {
                $redirectPersona = $persona;
                break;
            }
        }

        if (null === $redirectPersona) {
            return;
        }

        $redirectUrl = $this->addUrlParam($request->getRequestUri(), '_ptp', $redirectPersona->getId());

        $visitorInfo->setResponse(new RedirectResponse($redirectUrl, 302));
    }

    private function getDocumentPersonaIds(Request $request): array
    {
        // TODO cache this
        $document = $this->documentResolver->getDocument($request);
        if (!$document || !($document instanceof Document\Page) || null !== Model\Staticroute::getCurrentRoute()) {
            return [];
        }

        $personas = [];
        foreach ($document->getElements() as $key => $tag) {
            $pattern = '/^' . Document\Page::PERSONA_ELEMENT_PREFIX_PREFIXPART . '([0-9]+)' . Document\Page::PERSONA_ELEMENT_PREFIX_SUFFIXPART . '/';
            if (preg_match($pattern, $key, $matches)) {
                $personas[] = (int)$matches[1];
            }
        }

        $personas = array_unique($personas);
        $personas = array_filter($personas, function ($id) {
            return Model\Tool\Targeting\Persona::isIdActive($id);
        });

        return $personas;
    }

    private function addUrlParam(string $url, string $param, $value): string
    {
        // add _ptr parameter
        if (false !== strpos($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        $url .= sprintf('%s=%d', $param, $value);

        return $url;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $event->getResponse();

        if ($this->isEnabled() && Tool::useFrontendOutputFilters() && $this->isHtmlResponse($response)) {
            $db = \Pimcore\Db::get();
            $personasAvailable = $db->fetchOne('SELECT id FROM targeting_personas UNION SELECT id FROM targeting_rules LIMIT 1');
            if ($personasAvailable) {
                $targets = [];
                $personas = [];
                $dataPush = [
                    'personas' => $this->personas,
                    'method' => strtolower($request->getMethod())
                ];

                $document = $this->documentResolver->getDocument();

                if (count($this->events) > 0) {
                    $dataPush['events'] = $this->events;
                }

                if ($document instanceof Document\Page && !Model\Staticroute::getCurrentRoute()) {
                    $dataPush['document'] = $document->getId();
                    if ($document->getPersonas()) {
                        if ($_GET['_ptp']) { // if a special version is requested only return this id as target group for this page
                            $dataPush['personas'][] = (int) $_GET['_ptp'];
                        } else {
                            $docPersonas = explode(',', trim($document->getPersonas(), ' ,'));

                            //  cast the values to int
                            array_walk($docPersonas, function (&$value) {
                                $value = (int) trim($value);
                            });
                            $dataPush['personas'] = array_merge($dataPush['personas'], $docPersonas);
                        }
                    }

                    // check for persona specific variants of this page
                    $personaVariants = [];
                    foreach ($document->getElements() as $key => $tag) {
                        if (preg_match('/^' . Document\Page::PERSONA_ELEMENT_PREFIX_PREFIXPART . '([0-9]+)' . Document\Page::PERSONA_ELEMENT_PREFIX_SUFFIXPART . '/', $key, $matches)) {
                            $id = (int) $matches[1];
                            if (Model\Tool\Targeting\Persona::isIdActive($id)) {
                                $personaVariants[] = $id;
                            }
                        }
                    }

                    if (!empty($personaVariants)) {
                        $personaVariants = array_values(array_unique($personaVariants));
                        $dataPush['personaPageVariants'] = $personaVariants;
                    }
                }

                // no duplicates
                $dataPush['personas'] = array_unique($dataPush['personas']);
                $activePersonas = [];
                foreach ($dataPush['personas'] as $id) {
                    if (Model\Tool\Targeting\Persona::isIdActive($id)) {
                        $activePersonas[] = $id;
                    }
                }
                $dataPush['personas'] = $activePersonas;

                if ($document) {
                    // @TODO: cache this
                    $list = new Model\Tool\Targeting\Rule\Listing();
                    $list->setCondition('active = 1');

                    foreach ($list->load() as $target) {
                        $redirectUrl = $target->getActions()->getRedirectUrl();
                        if (is_numeric($redirectUrl)) {
                            $doc = Document::getById($redirectUrl);
                            if ($doc instanceof Document) {
                                $target->getActions()->redirectUrl = $doc->getFullPath();
                            }
                        }

                        $targets[] = $target;
                    }

                    $list = new Model\Tool\Targeting\Persona\Listing();
                    $list->setCondition('active = 1');
                    foreach ($list->load() as $persona) {
                        $personas[] = $persona;
                    }
                }
                $code = '';
                // check if persona or target group requires geoip to be included
                if ($this->checkPersonasAndTargetGroupForGeoIPRequirement($personas, $targets)) {
                    $code .= '<script type="text/javascript" src="/pimcore/static6/js/frontend/geoip.js/index.php"></script>';
                }

                $code .= '<script type="text/javascript">';
                $code .= 'var pimcore = pimcore || {};';
                $code .= 'pimcore["targeting"] = {};';
                $code .= 'pimcore["targeting"]["dataPush"] = ' . json_encode($dataPush) . ';';
                $code .= 'pimcore["targeting"]["targetingRules"] = ' . json_encode($targets) . ';';
                $code .= 'pimcore["targeting"]["personas"] = ' . json_encode($personas) . ';';
                $code .= '</script>';
                $code .= '<script type="text/javascript" src="/pimcore/static6/js/frontend/targeting.js"></script>';
                $code .= "\n";
                // analytics
                $content = $response->getContent();

                // search for the end <head> tag, and insert the google analytics code before
                // this method is much faster than using simple_html_dom and uses less memory
                $headEndPosition = stripos($content, '<head>');
                if ($headEndPosition !== false) {
                    $content = substr_replace($content, "<head>\n".$code, $headEndPosition, 7);
                }

                $response->setContent($content);
            }
        }
    }

    /**
     * Checks if the passed List of Personas and List of Targets use geopoints as condition
     *
     * @param $personas
     * @param $targets
     *
     * @return bool
     */
    private function checkPersonasAndTargetGroupForGeoIPRequirement($personas, $targets)
    {
        foreach ($personas as $persona) {
            foreach ($persona->getConditions() as $condition) {
                if ($condition['type'] == 'geopoint' || $condition['type'] == 'country') {
                    return true;
                }
            }
        }
        foreach ($targets as $target) {
            foreach ($target->getConditions() as $condition) {
                if ($condition['type'] == 'geopoint' || $condition['type'] == 'country') {
                    return true;
                }
            }
        }

        return false;
    }
}
