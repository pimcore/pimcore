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

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * "Paste only contents here" keeps the values of the languages the user may not edit, which it does
 * by rebuilding the items of the store it is about to save. The qualified slots of a key - its
 * variants and the positions beyond the one value the items carry - are dropped by
 * Classificationstore::setItems(), so they have to be restored beside the items rather than left to
 * it.
 *
 * @group model.dataobject.service
 */
class ServiceRestoreClassificationstoreLanguagesTest extends TestCase
{
    private const GROUP_ID = 930001;

    private const KEY_ID = 940001;

    private const IMPERIAL = 'imperial';

    /**
     * The restore asks which of the field's keys are localized, and that walk loads the key
     * configurations through {@see DefinitionCache::load()} - a statement, for keys this test does
     * not seed. Declared rather than relied upon: the base case defaults to false, so without this
     * the test reaches a database it has said it does not need and passes only where one happens
     * to be up.
     */
    protected function needsDb(): bool
    {
        return true;
    }

    public function testTheQualifiedSlotsSurviveTheRestoreLanguageByLanguage(): void
    {
        $new = $this->buildStore('new');
        $target = $this->buildStore('target');

        $this->restore($new, $target, ['en' => 1]);

        $variantItems = $new->getVariantItems();

        $this->assertSame(
            'new en imperial',
            $variantItems[self::GROUP_ID][self::KEY_ID]['en'][self::IMPERIAL][0] ?? null,
            'the variant of the language the user may edit is the one they left'
        );
        $this->assertSame(
            'target de imperial',
            $variantItems[self::GROUP_ID][self::KEY_ID]['de'][self::IMPERIAL][0] ?? null,
            'and the variant of the language they may not edit is kept from the stored object'
        );
    }

    /**
     * The items keep answering as they did, so the restore of the slots is additive rather than a
     * change to what it already did.
     */
    public function testTheItemsAreStillRestoredLanguageByLanguage(): void
    {
        $new = $this->buildStore('new');
        $target = $this->buildStore('target');

        $this->restore($new, $target, ['en' => 1]);

        $items = $new->getItems();

        $this->assertSame('new en', $items[self::GROUP_ID][self::KEY_ID]['en'] ?? null);
        $this->assertSame('target de', $items[self::GROUP_ID][self::KEY_ID]['de'] ?? null);
    }

    /**
     * A store holding no qualified slot at all is restored as it was before they existed.
     */
    public function testAStoreWithoutQualifiedSlotsIsRestoredAsItWas(): void
    {
        $new = $this->buildStore('new', false);
        $target = $this->buildStore('target', false);

        $this->restore($new, $target, ['en' => 1]);

        $this->assertSame([], $new->getVariantItems());
        $this->assertSame(
            ['en' => 'new en', 'de' => 'target de'],
            $new->getItems()[self::GROUP_ID][self::KEY_ID]
        );
    }

    /**
     * A key that holds ONLY qualified slots - a named variant, or a position above zero, and no
     * value in the items at all - is still a localized key, and the languages the user may not edit
     * are still theirs.
     *
     * The set of localized keys was built from the items trees alone, so such a key was in none of
     * them: the restore then left it exactly as the user submitted it, which deletes the German
     * variant of a user who may edit English only. Nothing warns, and the value is gone on the next
     * save.
     */
    public function testAKeyHoldingOnlyQualifiedSlotsIsRestoredLanguageByLanguage(): void
    {
        $new = $this->buildVariantOnlyStore('new');
        $target = $this->buildVariantOnlyStore('target');

        $this->restore($new, $target, ['en' => 1]);

        $variantItems = $new->getVariantItems();

        $this->assertSame(
            'new en imperial',
            $variantItems[self::GROUP_ID][self::KEY_ID]['en'][self::IMPERIAL][0] ?? null,
            'the language the user may edit is the one they left'
        );
        $this->assertSame(
            'target de imperial',
            $variantItems[self::GROUP_ID][self::KEY_ID]['de'][self::IMPERIAL][0] ?? null,
            'the language they may not edit is kept from the stored object'
        );
    }

    /**
     * One key with a variant per language and no value in the items: the shape a key holding only
     * named variants has.
     */
    private function buildVariantOnlyStore(string $label): Classificationstore
    {
        $store = new Classificationstore();
        $store->setActiveGroups([self::GROUP_ID => true]);
        $store->setVariantItems([
            self::GROUP_ID => [
                self::KEY_ID => [
                    'en' => [self::IMPERIAL => [0 => $label . ' en imperial']],
                    'de' => [self::IMPERIAL => [0 => $label . ' de imperial']],
                ],
            ],
        ]);

        return $store;
    }

    /**
     * @param array<string, int> $editableLanguages
     */
    private function restore(
        Classificationstore $new,
        Classificationstore $target,
        array $editableLanguages
    ): void {
        $service = new Service();
        $method = new ReflectionMethod($service, 'restoreClassificationstoreLanguages');
        $method->setAccessible(true);
        $method->invoke($service, $new, $target, $editableLanguages, $this->localizedField());
    }

    /**
     * One key holding a value per language, with a variant of its own beside each of them.
     */
    private function buildStore(string $label, bool $withVariants = true): Classificationstore
    {
        $store = new Classificationstore();
        $store->setActiveGroups([self::GROUP_ID => true]);
        $store->setItems([
            self::GROUP_ID => [
                self::KEY_ID => ['en' => $label . ' en', 'de' => $label . ' de'],
            ],
        ]);

        if ($withVariants) {
            $store->setVariantItems([
                self::GROUP_ID => [
                    self::KEY_ID => [
                        'en' => [self::IMPERIAL => [0 => $label . ' en imperial']],
                        'de' => [self::IMPERIAL => [0 => $label . ' de imperial']],
                    ],
                ],
            ]);
        }

        return $store;
    }
    /**
     * A field every key of which is localized, which is what the language restriction is about:
     * {@see LocalizationResolver::isKeyLocalized()} falls back to the field's own flag for a key
     * that states none, and no key here states one.
     */
    private function localizedField(): ClassificationstoreField
    {
        $definition = new ClassificationstoreField();
        $definition->setLocalized(true);

        return $definition;
    }

}
