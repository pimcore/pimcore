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
 * Hence the two call shapes below; the first argument says which one is in use.
 */
class UnsupportedEngineFeatureException extends Exception
{
    /**
     * @param EngineFeature|string $featureOrType the capability the value needs, or the field type
     *                                            of a key the store's engine cannot hold
     * @param int                  $groupOrStoreId the group the key belongs to, or the store whose
     *                                             engine refused the type
     * @param int|string           $keyIdOrEngine  the key needing the capability, or the name of
     *                                             the engine that cannot hold the type
     * @param string|null          $type           the field type of that key, for the feature form
     */
    public function __construct(
        EngineFeature|string $featureOrType,
        int $groupOrStoreId,
        int|string $keyIdOrEngine,
        ?string $type = null
    ) {
        parent::__construct(
            $featureOrType instanceof EngineFeature
                ? self::describeMissingFeature($featureOrType, $groupOrStoreId, (int) $keyIdOrEngine, (string) $type)
                : self::describeUnholdableType($featureOrType, $groupOrStoreId, (string) $keyIdOrEngine)
        );
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
