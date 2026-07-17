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

namespace Pimcore\Tests\Service\Element;

use Normalizer;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

class ServiceTest extends TestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

    /**
     * Regression test: copying an object must not force-load the target folder's children listing.
     * Loading all children is prohibitively expensive for large folders and causes OOM / timeouts.
     *
     * @see \Pimcore\Model\Element\Service::updateChildren()
     */
    public function testCopyAsChildDoesNotLoadTargetChildren(): void
    {
        $folder = TestHelper::createObjectFolder('copy-target-');
        $source = TestHelper::createEmptyObject('copy-source-');

        // Sanity-check: listing is not yet loaded before the copy.
        $this->assertFalse($folder->getChildren()->isLoaded());

        $service = new DataObject\Service();
        $service->copyAsChild($folder, $source);

        $this->assertFalse(
            $folder->getChildren()->isLoaded(),
            'copyAsChild() must not force-load the target children listing'
        );
    }

    /**
     * Regression test: recursive copy must not force-load the top-level target folder's children listing.
     *
     * @see \Pimcore\Model\Element\Service::updateChildren()
     */
    public function testCopyRecursiveDoesNotLoadTargetChildren(): void
    {
        $folder = TestHelper::createObjectFolder('copy-target-recursive-');
        $source = TestHelper::createEmptyObject('copy-source-recursive-');

        $this->assertFalse($folder->getChildren()->isLoaded());

        $service = new DataObject\Service();
        $service->copyRecursive($folder, $source);

        $this->assertFalse(
            $folder->getChildren()->isLoaded(),
            'copyRecursive() must not force-load the target children listing'
        );
    }

    /**
     * When the target folder's children listing is already loaded, copyAsChild() must append
     * the new object to the in-memory listing so callers see the updated children without a
     * further DB round-trip.
     *
     * @see \Pimcore\Model\Element\Service::updateChildren()
     */
    public function testCopyAsChildAppearsInPreloadedTargetChildren(): void
    {
        $folder = TestHelper::createObjectFolder('copy-target-preloaded-');
        $source = TestHelper::createEmptyObject('copy-source-preloaded-');

        // Force-load the listing so updateChildren() takes the append path.
        $folder->getChildren()->load();
        $this->assertTrue($folder->getChildren()->isLoaded());

        $service = new DataObject\Service();
        $copy = $service->copyAsChild($folder, $source);

        $childIds = array_map(
            static fn ($child) => $child->getId(),
            $folder->getChildren()->getData()
        );

        $this->assertContains(
            $copy->getId(),
            $childIds,
            'The copied object must appear in the already-loaded children listing'
        );
    }

    /**
     * Regression test: macOS reports accented filenames in decomposed (NFD) Unicode form.
     * If that form is stored verbatim, paths built elsewhere from the same characters in
     * precomposed (NFC) form no longer match, which breaks operations relying on path
     * comparisons (e.g. relocating thumbnails after a folder move).
     *
     * @see \Pimcore\Model\Element\Service::getValidKey()
     */
    public function testGetValidKeyNormalizesToNfc(): void
    {
        $nfd = Normalizer::normalize('café', Normalizer::FORM_D);
        $nfc = Normalizer::normalize('café', Normalizer::FORM_C);

        $this->assertNotSame($nfd, $nfc, 'Test fixture setup issue: NFD and NFC forms should differ in bytes.');
        $this->assertSame($nfc, Service::getValidKey($nfd, 'asset'));
    }

    /**
     * correctPath() must leave the Unicode form of the path untouched. Unconditionally
     * rewriting it to NFC here (as opposed to as a getByPath() lookup fallback, see
     * getByPathWithNfcFallback()) would break the exact-path lookup for elements whose
     * key is still stored in decomposed (NFD) form, e.g. created before keys were normalized
     * to NFC on write.
     *
     * @see \Pimcore\Model\Element\Service::correctPath()
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testCorrectPathDoesNotChangeUnicodeForm(): void
    {
        $nfd = Normalizer::normalize('/Upload Folder/Special café', Normalizer::FORM_D);

        $this->assertSame($nfd, Service::correctPath($nfd));
    }

    /**
     * Records every path getByPathWithNfcFallback() attempts, without ever succeeding, so the
     * full candidate order can be asserted on.
     *
     * @return string[]
     */
    private function recordAttemptedPaths(string $lookupPath): array
    {
        $attempted = [];
        $attempt = function (string $candidate) use (&$attempted): void {
            $attempted[] = $candidate;

            throw new NotFoundException('not found: ' . $candidate);
        };

        try {
            Service::getByPathWithNfcFallback($attempt, $lookupPath);
            $this->fail('Expected a NotFoundException.');
        } catch (NotFoundException) {
            // expected - $attempt() never succeeds, so every candidate gets tried
        }

        return $attempted;
    }

    /**
     * Regression test: the common case - and the one motivating this fix - is an entire
     * subtree freshly created in the same operation (e.g. a Studio folder upload), where every
     * segment is NFC-stored but the lookup path arrives in NFD form. getByPathWithNfcFallback()
     * must try the fully NFC-normalized path first, so that common case costs one extra query,
     * not one per path depth. A legacy parent path still stored in decomposed (NFD) form is the
     * rarer case; its "preserve dirname, normalize only the key" candidate is tried after.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackTriesFullyNormalizedPathBeforePreservingDirname(): void
    {
        $nfdParent = Normalizer::normalize('/Legacy café Parent/', Normalizer::FORM_D);
        $nfcChildKey = Normalizer::normalize('New café Child', Normalizer::FORM_C);
        $nfdChildKey = Normalizer::normalize($nfcChildKey, Normalizer::FORM_D);
        $this->assertNotSame(
            $nfcChildKey,
            $nfdChildKey,
            'Test fixture setup issue: NFD and NFC forms should differ in bytes.'
        );

        $lookupPath = $nfdParent . $nfdChildKey;
        $dirnamePreservedCandidate = $nfdParent . $nfcChildKey;
        $fullyNormalizedCandidate = Normalizer::normalize($lookupPath, Normalizer::FORM_C);

        $attempted = $this->recordAttemptedPaths($lookupPath);

        $this->assertContains($fullyNormalizedCandidate, $attempted);
        $this->assertContains($dirnamePreservedCandidate, $attempted);
        $this->assertLessThan(
            array_search($dirnamePreservedCandidate, $attempted, true),
            array_search($fullyNormalizedCandidate, $attempted, true),
            'The fully NFC-normalized path must be tried before the dirname-preserving candidate.'
        );
    }

    /**
     * Regression test: a lookup path may have any number of trailing segments freshly created
     * (NFC-stored) below an ancestor still stored in legacy decomposed (NFD) form - the caller
     * (e.g. a browser submitting every accented segment in NFD) has no way to know where that
     * boundary is, so a mixed hierarchy more than one level deep must still resolve via one of
     * the candidates getByPathWithNfcFallback() tries.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackHandlesNestedMixedHierarchy(): void
    {
        $nfdLegacySegment = Normalizer::normalize('Legacy café', Normalizer::FORM_D);
        $nfcMiddleSegment = Normalizer::normalize('New café', Normalizer::FORM_C);
        $nfdMiddleSegment = Normalizer::normalize($nfcMiddleSegment, Normalizer::FORM_D);
        $nfcLastSegment = Normalizer::normalize('Child café', Normalizer::FORM_C);
        $nfdLastSegment = Normalizer::normalize($nfcLastSegment, Normalizer::FORM_D);

        // the actual stored path: legacy first segment still NFD, the two freshly created
        // segments below it NFC
        $storedPath = "/$nfdLegacySegment/$nfcMiddleSegment/$nfcLastSegment";

        // what a browser submits: every accented segment in NFD, since it cannot tell which
        // segments are legacy and which are freshly created
        $lookupPath = "/$nfdLegacySegment/$nfdMiddleSegment/$nfdLastSegment";
        $this->assertNotSame($storedPath, $lookupPath, 'Test fixture setup issue.');

        $attempted = $this->recordAttemptedPaths($lookupPath);
        $fullyNormalized = Normalizer::normalize($lookupPath, Normalizer::FORM_C);

        $this->assertContains(
            $storedPath,
            $attempted,
            'Normalizing only the trailing 2 segments (leaving the legacy first segment as NFD) must be tried.'
        );
        $this->assertLessThan(
            array_search($storedPath, $attempted, true),
            array_search($fullyNormalized, $attempted, true),
            'The fully normalized path is tried before the candidate preserving the legacy segment.'
        );
    }

    /**
     * If the path is already fully NFC-normalized, no fallback candidates are produced -
     * getByPathWithNfcFallback() must not retry with a path identical to the one that just
     * missed.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackDoesNotRetryWhenPathAlreadyNfc(): void
    {
        $nfc = Normalizer::normalize('/Upload Folder/Special café', Normalizer::FORM_C);

        $this->assertSame(
            [$nfc],
            $this->recordAttemptedPaths($nfc),
            'No fallback candidates should be attempted when the path is already fully NFC.'
        );
    }

    /**
     * Regression test: getByPath() is a public API that accepts arbitrary caller-supplied
     * strings, but ElementInterface::MAX_FULL_PATH_LENGTH is only enforced at save time - no
     * real element can ever be stored with a longer path. Without a bound here, an overlength,
     * heavily decomposed (NFD) path would still generate and attempt one fallback candidate per
     * path segment. getByPathWithNfcFallback() must skip fallback entirely once even the
     * shortest possible candidate - the fully NFC-normalized path - is already too long to
     * belong to any real element, rather than doing that work only to miss every candidate
     * anyway.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackSkipsFallbackForOverlengthPath(): void
    {
        // a run of the same precomposed accented character so that NFD-normalizing it doubles
        // its length (base + combining mark each), while keeping the NFC form compact
        $nfcSegment = str_repeat(Normalizer::normalize('é', Normalizer::FORM_C), 260);
        $nfdSegment = Normalizer::normalize($nfcSegment, Normalizer::FORM_D);
        $this->assertNotSame($nfcSegment, $nfdSegment, 'Test fixture setup issue.');

        $overlengthPath = "/$nfdSegment/$nfdSegment/$nfdSegment";
        $fullyNormalizedLength = mb_strlen(Normalizer::normalize($overlengthPath, Normalizer::FORM_C));
        $this->assertGreaterThan(
            ElementInterface::MAX_FULL_PATH_LENGTH,
            $fullyNormalizedLength,
            'Test fixture setup issue: even the fully NFC-normalized form must exceed the maximum length no real element could have.'
        );

        $this->assertSame(
            [$overlengthPath],
            $this->recordAttemptedPaths($overlengthPath),
            'No fallback candidates should be attempted when even the fully NFC-normalized path is longer than any real element could have.'
        );
    }

    /**
     * Regression test: NFD-decomposing a path can noticeably lengthen it (a single precomposed
     * character can become a base character plus a combining mark), so a path whose fully
     * NFC-normalized form fits within ElementInterface::MAX_FULL_PATH_LENGTH - and could
     * therefore be a real, storable element - may still exceed that length in its raw,
     * decomposed form. getByPathWithNfcFallback() must judge the length bound by the shortest
     * possible candidate (the fully normalized path), not the raw input, or it would incorrectly
     * refuse to even attempt a fallback that could otherwise resolve.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackStillAttemptsFallbackWhenOnlyDecomposedFormIsOverlength(): void
    {
        $nfcSegment = str_repeat(Normalizer::normalize('é', Normalizer::FORM_C), 250);
        $nfdSegment = Normalizer::normalize($nfcSegment, Normalizer::FORM_D);
        $this->assertNotSame($nfcSegment, $nfdSegment, 'Test fixture setup issue.');

        $lookupPath = "/$nfdSegment/$nfdSegment/$nfdSegment";
        $fullyNormalizedPath = Normalizer::normalize($lookupPath, Normalizer::FORM_C);

        $this->assertGreaterThan(
            ElementInterface::MAX_FULL_PATH_LENGTH,
            mb_strlen($lookupPath),
            'Test fixture setup issue: the raw decomposed path must exceed the maximum length.'
        );
        $this->assertLessThanOrEqual(
            ElementInterface::MAX_FULL_PATH_LENGTH,
            mb_strlen($fullyNormalizedPath),
            'Test fixture setup issue: the fully NFC-normalized form must still fit within the maximum length any real element could have.'
        );

        $this->assertContains(
            $fullyNormalizedPath,
            $this->recordAttemptedPaths($lookupPath),
            'A path whose decomposed form exceeds the limit but whose NFC-normalized form does not must still be attempted as a fallback candidate.'
        );
    }

    /**
     * Regression test: a path within the maximum real path length can still be split into far
     * more segments than any realistic element hierarchy would have (e.g. many short accented
     * segments). Without a cap independent of total path length, a miss on such a path would
     * generate and attempt one fallback candidate per segment. getByPathWithNfcFallback() must
     * bound the number of candidates attempted to a small, fixed number regardless of segment
     * count.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackCapsNumberOfCandidates(): void
    {
        $segment = Normalizer::normalize('é', Normalizer::FORM_D);
        $segmentCount = 100;
        $path = str_repeat('/' . $segment, $segmentCount);
        $this->assertLessThanOrEqual(
            ElementInterface::MAX_FULL_PATH_LENGTH,
            mb_strlen(Normalizer::normalize($path, Normalizer::FORM_C)),
            'Test fixture setup issue: the fully normalized path must still fit within the length limit.'
        );

        $attempted = $this->recordAttemptedPaths($path);

        $this->assertLessThan(
            $segmentCount,
            count($attempted),
            'The number of fallback candidates attempted must be bounded to a small fixed number, not one per path segment.'
        );
    }

    /**
     * Regression test: getByPathWithNfcFallback() must fall through the candidates in order
     * and succeed as soon as one of them resolves - this is what lets a freshly created
     * element (NFC-stored) still be found from an NFD-form lookup path, e.g. a browser's
     * webkitdirectory/File System Access API on macOS.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackTriesCandidatesInOrder(): void
    {
        $nfd = Normalizer::normalize('/Upload Folder/Special café', Normalizer::FORM_D);
        $nfc = Normalizer::normalize('/Upload Folder/Special café', Normalizer::FORM_C);

        $attempted = [];
        $attempt = function (string $candidate) use (&$attempted, $nfc): void {
            $attempted[] = $candidate;
            if ($candidate !== $nfc) {
                throw new NotFoundException('not found: ' . $candidate);
            }
        };

        Service::getByPathWithNfcFallback($attempt, $nfd);

        $this->assertSame(
            $nfc,
            end($attempted),
            'The lookup must eventually succeed with the fully NFC-normalized path.'
        );
    }

    /**
     * Regression test: if every candidate misses, getByPathWithNfcFallback() must rethrow the
     * original exact-path NotFoundException rather than swallowing it.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathWithNfcFallbackRethrowsWhenNoCandidateMatches(): void
    {
        $nfd = Normalizer::normalize('/Upload Folder/Special café', Normalizer::FORM_D);

        $this->expectException(NotFoundException::class);

        Service::getByPathWithNfcFallback(function (string $candidate): void {
            throw new NotFoundException('not found: ' . $candidate);
        }, $nfd);
    }

    public function testCloneMe(): void
    {
        // create object with property
        $object = TestHelper::createEmptyObject('', false);
        $object->setProperty('propertyA', 'input', 'valueA');
        $object->save();

        // copy object in the same folder
        $clonedObject = Service::cloneMe($object);
        $this->assertNull($clonedObject->getId());
        $this->assertNull($clonedObject->getParent());
        $this->assertNull($clonedObject->getParentId());
        $target = DataObject::getById(1);
        $clonedObject->setKey(Service::getSafeCopyName($clonedObject->getKey(), $target));
        $clonedObject->setParentId($target->getId());
        $clonedObject->save();

        // reload the new object from the db
        $clonedObject = DataObject::getById($clonedObject->getId(), ['force' => true]);

        $this->assertEquals($object->getKey() . '_copy', $clonedObject->getKey());
        $this->assertEquals('valueA', $clonedObject->getProperty('propertyA'));
    }
}
