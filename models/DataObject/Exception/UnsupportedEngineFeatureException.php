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

namespace Pimcore\Model\DataObject\Exception;

use Exception;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;

/**
 * Raised when a value is written to a store whose engine does not offer the capability that value
 * needs - never a silent skip and never a fallback to a value the storage does happen to hold.
 *
 * A capability an engine does not offer is answered with `false` everywhere else. This is the one
 * place that throws instead: the alternative is a value the storage silently drops, and a key
 * definition is written once while the value it loses is written on every save.
 *
 * Two refusal sites raise it, and each states what it knows:
 *  - a key whose field type the engine behind its store cannot hold, which knows the type, the
 *    store and the engine's name but not the key it is about to write
 *    ({@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()} and both gates of
 *    {@see \Pimcore\Model\DataObject\Classificationstore\Engine\EavEngine}), and
 *  - a value needing a named capability, which knows the feature and the key it belongs to
 *    ({@see \Pimcore\Model\DataObject\Classificationstore\RelationResolver::assertStorable()}).
 *
 * Hence the two call shapes below. The constructor is the one it shipped with, unchanged down to the
 * names of its parameters, and the first argument says which shape it is being handed;
 * {@see self::forUnholdableType()} is the second shape under a name, and is what the type refusals
 * are written with.
 */
class UnsupportedEngineFeatureException extends Exception
{
    /**
     * The parameter NAMES are the ones this exception shipped with, and the second call shape reads
     * them differently rather than renaming them: the class carries no `@internal`, so `feature:`,
     * `groupId:`, `keyId:` and `type:` are a public API that a named argument anywhere may be
     * passing. {@see self::forUnholdableType()} is how the second shape is written at a call site,
     * so that no caller has to read a store id out of a parameter called `$groupId`.
     *
     * @param EngineFeature|string $feature the capability the value needs, or - in the type shape -
     *                                      the field type of a key the store's engine cannot hold
     * @param int                  $groupId the group the key belongs to, or the store whose engine
     *                                      refused the type
     * @param int|string           $keyId   the key needing the capability, or the name of the engine
     *                                      that cannot hold the type
     * @param string|null          $type    the field type of that key, for the feature shape
     */
    public function __construct(
        EngineFeature|string $feature,
        int $groupId,
        int|string $keyId,
        ?string $type = null
    ) {
        parent::__construct(
            $feature instanceof EngineFeature
                ? self::describeMissingFeature($feature, $groupId, (int) $keyId, (string) $type)
                : self::describeUnholdableType($feature, $groupId, (string) $keyId)
        );
    }

    /**
     * The type shape, named: a key of this field type cannot be stored by the engine of this store.
     *
     * @param string $type    the field type the engine cannot hold
     * @param int    $storeId the store whose engine refused it
     * @param string $engine  the name of that engine
     */
    public static function forUnholdableType(string $type, int $storeId, string $engine): self
    {
        return new self($type, $storeId, $engine);
    }

    private static function describeMissingFeature(
        EngineFeature $feature,
        int $groupId,
        int $keyId,
        string $type
    ): string {
        return sprintf(
            'The storage engine of this classification store does not support %s, which key %d of group %d needs:'
            . ' its type is "%s". Flip the store to an engine that supports %s, or give the key a type the engine'
            . ' can store.',
            $feature->name,
            $keyId,
            $groupId,
            $type,
            $feature->name
        );
    }

    private static function describeUnholdableType(string $type, int $storeId, string $engine): string
    {
        return sprintf(
            'A classification store key of type "%s" cannot be stored by the storage engine "%s" of store %d,'
            . ' which holds a value only as a scalar. Put the key on a store using an engine that holds'
            . ' structured values - move store %d over by running pimcore:classificationstore:flip for it and'
            . ' then drain, verify and cleanup; the flip creates the document tables before it switches the'
            . ' engine, so do not write the engine column by hand - or choose a type whose value is a scalar.',
            $type,
            $engine,
            $storeId,
            $storeId
        );
    }
}
