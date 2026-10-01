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

namespace Pimcore\Tests\Unit\Templating\Renderer;

use Pimcore\Bundle\CoreBundle\Controller\PublicServicesController;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\ActionRenderer;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Console;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

/**
 * pimcore/pimcore#19477: the controller of a document (or of a document rendered via pimcore_inc /
 * a snippet) is document data, so the controller reference built for it must let Symfony's
 * controller resolver accept registered controllers only.
 */
final class ActionRendererTest extends TestCase
{
    public function testDocumentReferenceOnlyAllowsRegisteredControllers(): void
    {
        $reference = $this->createDocumentReference(
            Console::class . '::execInBackground',
            ['cmd' => 'id > /tmp/pwned', '_check_controller_is_allowed' => false]
        );

        self::assertTrue($reference->attributes['_check_controller_is_allowed']);

        $this->expectException(BadRequestException::class);
        $this->resolve($reference);
    }

    public function testDocumentReferenceToARegisteredControllerIsResolved(): void
    {
        $reference = $this->createDocumentReference(PublicServicesController::class . '::robotsTxtAction');

        self::assertIsCallable($this->resolve($reference));
    }

    private function createDocumentReference(string $controller, array $attributes = []): ControllerReference
    {
        $document = $this->createMock(PageSnippet::class);
        $document->method('getController')->willReturn($controller);

        $renderer = new ActionRenderer(new HttpKernelRuntime(new FragmentHandler(new RequestStack())));

        return $renderer->createDocumentReference($document, $attributes);
    }

    /**
     * Resolves the reference like the inline fragment renderer's sub-request would, with the
     * controller types FrameworkBundle allows by default.
     */
    private function resolve(ControllerReference $reference): callable|false
    {
        $request = Request::create('/');
        $request->attributes->add($reference->attributes);
        $request->attributes->set('_controller', $reference->controller);

        $resolver = new ControllerResolver();
        $resolver->allowControllers([AbstractController::class]);

        return $resolver->getController($request);
    }
}

