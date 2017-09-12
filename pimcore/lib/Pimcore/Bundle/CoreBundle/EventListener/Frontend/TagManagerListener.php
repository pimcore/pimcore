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

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Site;
use Pimcore\Model\Tool\Tag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class TagManagerListener
{
    use PimcoreContextAwareTrait;
    use ResponseInjectionTrait;

    /**
     * @var bool
     */
    protected $enabled = true;

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

        // It's standard industry practice to exclude tracking if the request includes the header 'X-Purpose:preview'
        $serverVars = $event->getRequest()->server;
        if ($serverVars->get('HTTP_X_PURPOSE') == 'preview') {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response) || !$this->isEnabled()) {
            return;
        }

        $list = new Tag\Config\Listing();
        $tags = $list->load();

        if (empty($tags)) {
            return;
        }

        $html = null;
        $content = $response->getContent();
        $requestParams = array_merge($_GET, $_POST);

        /** @var $tag Tag\Config */
        foreach ($tags as $tag) {
            if ($tag->isDisabled()) {
                continue;
            }
            $method = strtolower($tag->getHttpMethod());
            $pattern = $tag->getUrlPattern();
            $textPattern = $tag->getTextPattern();

            // site check
            if (Site::isSiteRequest() && $tag->getSiteId()) {
                if (Site::getCurrentSite()->getId() != $tag->getSiteId()) {
                    continue;
                }
            } elseif (!Site::isSiteRequest() && $tag->getSiteId() && $tag->getSiteId() != 'default') {
                continue;
            }

            $requestPath = rtrim($request->getPathInfo(), '/');

            if (($method == strtolower($request->getMethod()) || empty($method)) &&
                (empty($pattern) || @preg_match($pattern, $requestPath)) &&
                (empty($textPattern) || strpos($content, $textPattern) !== false)
            ) {
                $paramsValid = true;
                foreach ($tag->getParams() as $param) {
                    if (!empty($param['name'])) {
                        if (!empty($param['value'])) {
                            if (!array_key_exists($param['name'], $requestParams) || $requestParams[$param['name']] != $param['value']) {
                                $paramsValid = false;
                            }
                        } else {
                            if (!array_key_exists($param['name'], $requestParams)) {
                                $paramsValid = false;
                            }
                        }
                    }
                }

                if (is_array($tag->getItems()) && $paramsValid) {
                    foreach ($tag->getItems() as $item) {
                        if ($item['disabled']) {
                            continue;
                        }
                        if (!empty($item['element']) && !empty($item['code']) && !empty($item['position'])) {
                            if (in_array($item['element'], ['body', 'head'])) {
                                // check if the code should be inserted using one of the presets
                                // because this can be done much faster than using a html parser
                                if ($html) {
                                    // reset simple_html_dom if set
                                    $html->clear();
                                    unset($html);
                                    $html = null;
                                }

                                if ($item['position'] == 'end') {
                                    $regEx = '@</' . $item['element'] . '>@i';
                                    $content = preg_replace($regEx, "\n\n" . $item['code'] . "\n\n</" . $item['element'] . '>', $content, 1);
                                } else {
                                    $regEx = '/<' . $item['element'] . '([^a-zA-Z])?( [^>]+)?>/';
                                    $content = preg_replace($regEx, '<' . $item['element'] . "$1$2>\n\n" . $item['code'] . "\n\n", $content, 1);
                                }
                            } else {
                                // use simple_html_dom
                                if (!$html) {
                                    include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');
                                    $html = str_get_html($content);
                                }

                                if ($html) {
                                    $element = $html->find($item['element'], 0);
                                    if ($element) {
                                        if ($item['position'] == 'end') {
                                            $element->innertext = $element->innertext . "\n\n" . $item['code'] . "\n\n";
                                        } else {
                                            // beginning
                                            $element->innertext = "\n\n" . $item['code'] . "\n\n" . $element->innertext;
                                        }

                                        // we havve to reinitialize the html object, otherwise it causes problems with nested child selectors
                                        $content = $html->save();

                                        $html->clear();
                                        unset($html);

                                        $html = null;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($html && method_exists($html, 'clear')) {
            $html->clear();
            unset($html);
        }

        $response->setContent($content);
    }
}
