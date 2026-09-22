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

namespace Pimcore\Tests\Unit\Document\Editable;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Extension\Document\Areabrick\AreabrickInterface;
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Http\ResponseStack;
use Pimcore\HttpKernel\BundleLocator\BundleLocatorInterface;
use Pimcore\HttpKernel\WebPathResolver;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Model\Translation;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool;
use Pimcore\Translation\Translator;
use ReflectionClassConstant;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Areabrick names and descriptions are labels of the editing UI. In editmode they used to be
 * translated through the default ("messages") domain, which also auto-creates every missing key
 * there - so opening a document with an areablock polluted the website translations with UI
 * strings. These tests pin down that the labels are now resolved via the Studio UI domain when
 * it is available, that already existing "messages" translations keep working as a read-only
 * fallback, and that classic-only installations keep their previous behavior.
 */
final class EditableHandlerTest extends TestCase
{
    private const STUDIO_DOMAIN = 'studio';

    private const LOCALE = 'de';

    private Translator&MockObject $translator;

    private EditmodeResolver&MockObject $editmodeResolver;

    /**
     * @var array<string, MessageCatalogue>
     */
    private array $catalogues = [];

    /**
     * @var list<string> "<domain>/<locale>" pairs the handler asked the translator to initialize
     */
    private array $initializedCatalogues = [];

    /**
     * @var list<string> "<domain>/<id>" pairs the handler translated via trans()
     */
    private array $translated = [];

    /**
     * @var list<string> "<domain>/<id>@<locale>" keys the translator stand-in had to create
     */
    private array $createdKeys = [];

    private string $locale = self::LOCALE;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogues = [];
        $this->initializedCatalogues = [];
        $this->translated = [];
        $this->createdKeys = [];
        $this->locale = self::LOCALE;

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trans', 'getCatalogue', 'lazyInitialize', 'getLocale'])
            ->getMock();
        $this->translator->method('getLocale')->willReturnCallback(fn (): string => $this->locale);
        $this->translator->method('getCatalogue')->willReturnCallback(
            fn (?string $locale = null): MessageCatalogue => $this->catalogue($locale ?? $this->locale)
        );
        $this->translator->method('lazyInitialize')->willReturnCallback(
            function (string $domain, string $locale): void {
                $this->initializedCatalogues[] = $domain . '/' . $locale;
            }
        );

        $this->editmodeResolver = $this->createMock(EditmodeResolver::class);
        $this->editmodeResolver->method('isEditmode')->willReturn(true);
    }

    protected function tearDown(): void
    {
        RuntimeCache::set($this->getValidDomainsCacheKey(), []);

        parent::tearDown();
    }

    public function testLabelsAreTranslatedViaStudioDomainWhenAvailable(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->with($this->anything(), [], self::STUDIO_DOMAIN)
            ->willReturnMap([
                ['Teaser', [], self::STUDIO_DOMAIN, null, 'Teaser (Studio)'],
                ['A teaser brick', [], self::STUDIO_DOMAIN, null, 'Ein Teaser-Baustein'],
            ]);

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (Studio)', $areas['teaser']['name']);
        self::assertSame('Ein Teaser-Baustein', $areas['teaser']['description']);
    }

    public function testStudioTranslationEqualToItsKeyTakesPrecedenceOverDefaultDomain(): void
    {
        $this->markStudioDomainAsRegistered(true);

        // a Studio translation that intentionally equals the key must not be mistaken for a missing one
        $this->catalogue(self::LOCALE)->set('Teaser', 'Teaser', self::STUDIO_DOMAIN);
        $this->catalogue(self::LOCALE)->set('Teaser', 'Vorspann', Translation::DOMAIN_DEFAULT);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->with($this->anything(), [], self::STUDIO_DOMAIN)
            ->willReturnArgument(0);

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser', $areas['teaser']['name']);
    }

    public function testExistingDefaultDomainTranslationIsUsedWithoutCreatingKeysThere(): void
    {
        $this->markStudioDomainAsRegistered(true);

        // an installation that already maintains the labels in "messages" (e.g. a classic install
        // that later added Studio) must keep its translations ...
        $this->catalogue(self::LOCALE)->set('Teaser', 'Teaser (messages)', Translation::DOMAIN_DEFAULT);

        // ... but a label that is unknown to the default domain must never go through trans() with
        // that domain, because that is what auto-creates the missing keys in "messages".
        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (messages) (via trans)', $areas['teaser']['name']);
        self::assertSame('A teaser brick', $areas['teaser']['description']);
        self::assertContains(
            Translation::DOMAIN_DEFAULT . '/' . self::LOCALE,
            $this->initializedCatalogues,
            'The database translations of the default domain must be loaded before looking the label up.'
        );
        self::assertSame(
            [
                self::STUDIO_DOMAIN . '/Teaser',
                Translation::DOMAIN_DEFAULT . '/Teaser',
                self::STUDIO_DOMAIN . '/A teaser brick',
            ],
            $this->translated,
            'Only the label known to the default domain may be translated through it.'
        );
        self::assertSame(
            [self::STUDIO_DOMAIN . '/Teaser@de', self::STUDIO_DOMAIN . '/A teaser brick@de'],
            $this->createdKeys,
            'Missing keys are created in the Studio domain only.'
        );
    }

    public function testRepeatedLabelKeepsUsingTheDefaultDomainFallback(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $this->catalogue(self::LOCALE)->set('Teaser', 'Teaser (messages)', Translation::DOMAIN_DEFAULT);

        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        // the same label twice (name and description) and the whole lookup twice (two areablocks): the
        // key the first translation created in the Studio catalogue must not shadow the fallback
        $handler = $this->createHandler(null, 'Teaser');
        $handler->getAvailableAreablockAreas(new Areablock(), []);
        $areas = $handler->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (messages) (via trans)', $areas['teaser']['name']);
        self::assertSame('Teaser (messages) (via trans)', $areas['teaser']['description']);
    }

    public function testResolvedLabelsAreCachedPerLocale(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $this->catalogue('de')->set('Teaser', 'Teaser (de)', Translation::DOMAIN_DEFAULT);
        $this->catalogue('en')->set('Teaser', 'Teaser (en)', Translation::DOMAIN_DEFAULT);

        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        // the handler is a shared service and the translator locale is switched while rendering
        // documents of different languages, so a resolved label must not leak into another locale
        $handler = $this->createHandler();
        $this->locale = 'de';
        $german = $handler->getAvailableAreablockAreas(new Areablock(), []);
        $this->locale = 'en';
        $english = $handler->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (de) (via trans)', $german['teaser']['name']);
        self::assertSame('Teaser (en) (via trans)', $english['teaser']['name']);
    }

    public function testLabelWithSurroundingWhitespaceIsLookedUpTrimmed(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $this->catalogue(self::LOCALE)->set('Teaser', 'Teaser (messages)', Translation::DOMAIN_DEFAULT);

        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        $areas = $this->createHandler(null, 'A teaser brick', ' Teaser ')
            ->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (messages) (via trans)', $areas['teaser']['name']);
    }

    public function testConfiguredFallbackLanguageOfDefaultDomainIsUsed(): void
    {
        $this->markStudioDomainAsRegistered(true);

        // the test system settings configure "de" as fallback language of "de_AT"
        self::assertSame(['de'], Tool::getFallbackLanguagesFor('de_AT'));
        $this->locale = 'de_AT';

        // an empty "de_AT" value falls back to the configured "de" translation, like trans() does
        $this->catalogue('de_AT')->set('Teaser', '', Translation::DOMAIN_DEFAULT);
        $this->catalogue('de')->set('Teaser', 'Vorspann', Translation::DOMAIN_DEFAULT);

        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Vorspann (via trans)', $areas['teaser']['name']);
        self::assertContains(Translation::DOMAIN_DEFAULT . '/de', $this->initializedCatalogues);
        self::assertSame(
            [self::STUDIO_DOMAIN . '/Teaser@de_AT', self::STUDIO_DOMAIN . '/A teaser brick@de_AT'],
            $this->createdKeys,
            'No key may be created in the default domain.'
        );
    }

    public function testTranslationOnlyKnownToAFallbackLanguageIsTranslatedInThatLanguage(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $this->locale = 'de_AT';

        // no "de_AT" entry at all (e.g. a translation file only for "de"): translating in "de_AT"
        // would create the key there, so the label is translated explicitly in "de"
        $this->catalogue('de')->set('Teaser', 'Vorspann', Translation::DOMAIN_DEFAULT);

        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Vorspann (via trans)', $areas['teaser']['name']);
        self::assertSame(
            [self::STUDIO_DOMAIN . '/Teaser@de_AT', self::STUDIO_DOMAIN . '/A teaser brick@de_AT'],
            $this->createdKeys
        );
    }

    public function testEmptyDefaultDomainTranslationFallsBackToTheLabel(): void
    {
        $this->markStudioDomainAsRegistered(true);

        // an auto-created key without a text must not blank out the label
        $this->catalogue(self::LOCALE)->set('Teaser', '', Translation::DOMAIN_DEFAULT);

        $this->translator->method('trans')->willReturnCallback($this->translateFromCatalogues(...));

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser', $areas['teaser']['name']);
        self::assertSame(
            [self::STUDIO_DOMAIN . '/Teaser', self::STUDIO_DOMAIN . '/A teaser brick'],
            $this->translated
        );
    }

    public function testClassicOnlyInstallationKeepsTranslatingViaDefaultDomain(): void
    {
        $this->markStudioDomainAsRegistered(false);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->with($this->anything(), [], Translation::DOMAIN_DEFAULT)
            ->willReturnMap([
                ['Teaser', [], Translation::DOMAIN_DEFAULT, null, 'Teaser (messages)'],
                ['A teaser brick', [], Translation::DOMAIN_DEFAULT, null, 'Ein Teaser-Baustein'],
            ]);

        $areas = $this->createHandler()->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (messages)', $areas['teaser']['name']);
        self::assertSame('Ein Teaser-Baustein', $areas['teaser']['description']);
        self::assertSame([], $this->initializedCatalogues);
    }

    public function testLabelsAreNotTranslatedOutsideOfEditmode(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $editmodeResolver->method('isEditmode')->willReturn(false);

        $this->translator->expects($this->never())->method('trans');

        $areas = $this->createHandler($editmodeResolver)->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser', $areas['teaser']['name']);
        self::assertSame('A teaser brick', $areas['teaser']['description']);
    }

    public function testEmptyDescriptionIsNotTranslated(): void
    {
        $this->markStudioDomainAsRegistered(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Teaser', [], self::STUDIO_DOMAIN)
            ->willReturn('Teaser (Studio)');

        $areas = $this->createHandler(null, '')->getAvailableAreablockAreas(new Areablock(), []);

        self::assertSame('Teaser (Studio)', $areas['teaser']['name']);
        self::assertSame('', $areas['teaser']['description']);
    }

    private function catalogue(string $locale): MessageCatalogue
    {
        return $this->catalogues[$locale] ??= new MessageCatalogue($locale);
    }

    /**
     * Stand-in for Translator::trans(): records the call, resolves the id from the catalogues of the
     * locale and its configured fallback languages and marks the result as post-processed. Like the
     * real translator, an unknown id is "created": recorded, and put into the in-memory catalogue of
     * the locale as its own translation.
     */
    private function translateFromCatalogues(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $domain ??= Translation::DOMAIN_DEFAULT;
        $locale ??= $this->locale;
        $this->translated[] = $domain . '/' . $id;

        foreach ([$locale, ...Tool::getFallbackLanguagesFor($locale)] as $lookupLocale) {
            $catalogue = $this->catalogue($lookupLocale);
            if ($catalogue->has($id, $domain) && $catalogue->get($id, $domain) !== '') {
                return $catalogue->get($id, $domain) . ' (via trans)';
            }
        }

        if (!$this->catalogue($locale)->has($id, $domain)) {
            $this->createdKeys[] = $domain . '/' . $id . '@' . $locale;
            $this->catalogue($locale)->set($id, $id, $domain);
        }

        return $id;
    }

    private function createHandler(
        ?EditmodeResolver $editmodeResolver = null,
        string $description = 'A teaser brick',
        string $name = 'Teaser'
    ): EditableHandler {
        $brick = $this->createMock(AreabrickInterface::class);
        $brick->method('getId')->willReturn('teaser');
        $brick->method('getName')->willReturn($name);
        $brick->method('getDescription')->willReturn($description);
        $brick->method('getIcon')->willReturn('/bundles/app/areas/teaser/icon.png');
        $brick->method('needsReload')->willReturn(false);

        $brickManager = $this->createMock(AreabrickManagerInterface::class);
        $brickManager->method('getBricks')->willReturn([$brick]);

        return new EditableHandler(
            $brickManager,
            $this->createMock(EngineInterface::class),
            $this->createMock(BundleLocatorInterface::class),
            $this->createMock(WebPathResolver::class),
            $this->createMock(RequestHelper::class),
            $this->translator,
            $this->createMock(ResponseStack::class),
            $editmodeResolver ?? $this->editmodeResolver,
            new HttpKernelRuntime(new FragmentHandler(new RequestStack())),
            $this->createMock(FragmentRendererInterface::class),
            new RequestStack()
        );
    }

    /**
     * Translation::isAValidDomain() memoizes its result per request, so seeding the memo is the
     * way to simulate an installation with or without the Studio translation domain without
     * touching the database or the system configuration.
     */
    private function markStudioDomainAsRegistered(bool $registered): void
    {
        RuntimeCache::set($this->getValidDomainsCacheKey(), [
            self::STUDIO_DOMAIN => $registered,
            Translation::DOMAIN_DEFAULT => true,
        ]);
    }

    private function getValidDomainsCacheKey(): string
    {
        return (new ReflectionClassConstant(Translation\Dao::class, 'VALID_DOMAINS_CACHE_KEY'))->getValue();
    }
}
