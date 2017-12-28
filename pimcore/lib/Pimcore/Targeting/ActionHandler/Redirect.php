<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\ActionHandler;

use Pimcore\Model\Document;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Redirect implements ActionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function apply(VisitorInfo $visitorInfo, array $action, Rule $rule = null)
    {
        $url = $action['url'] ?? null;
        if (!$url) {
            return;
        }

        $request = $visitorInfo->getRequest();

        // only redirect GET requests
        if ($request->getMethod() !== 'GET') {
            return;
        }

        // don't redirect multiple times to avoid loops
        if (!empty($request->get('_ptr'))) {
            return;
        }

        if (is_numeric($url)) {
            $document = Document::getById($url);
            if (!$document) {
                return;
            }

            $url = $document->getRealFullPath();
        }

        if ($rule) {
            $url = $this->addUrlParam($url, '_ptr', $rule->getId());
        } else {
            $url = $this->addUrlParam($url, '_ptr', 0);
        }

        $code = $action['code'] ?? RedirectResponse::HTTP_FOUND;

        $visitorInfo->setResponse(new RedirectResponse($url, $code));
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
}
