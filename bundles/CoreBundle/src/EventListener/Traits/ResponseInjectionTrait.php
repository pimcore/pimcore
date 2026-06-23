<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Traits;

use Pimcore\Http\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 */
trait ResponseInjectionTrait
{
    private ResponseHelper $responseHelper;

    #[Required]
    public function setResponseHelper(ResponseHelper $responseHelper): void
    {
        $this->responseHelper = $responseHelper;
    }

    protected function isHtmlResponse(Response $response): bool
    {
        return $this->responseHelper->isHtmlResponse($response);
    }

    protected function injectBeforeHeadEnd(Response $response, string $code): void
    {
        $content = $response->getContent();

        // search for the end <head> tag, and insert the code before
        // this method is much faster than using simple_html_dom and uses less memory
        $headEndPosition = strripos($content, '</head>');

        if (false !== $headEndPosition) {
            $content = substr_replace($content, $code . '</head>', $headEndPosition, 7);
        }

        $response->setContent($content);
    }
}
