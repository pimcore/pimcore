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

use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;

/**
 * Raised when the DECLARATION of a classification store key needs a capability the engine behind its
 * store does not offer - a `multiValue` key, whose value is addressed under qualifiers an EAV value
 * row has no column for.
 *
 * A subclass rather than a third call shape of {@see UnsupportedEngineFeatureException}: that class
 * documents its constructor parameter names as a public API down to how each shape reads them, and
 * what is being said here is neither of its two shapes - no value is being written, no group and no
 * key id exist yet, and the engine's own name is the thing worth printing. Extending it keeps every
 * caller that already catches engine refusals catching this one.
 *
 * Raised from {@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()} only, and only
 * for a configuration that introduces the need: a key already stored with it keeps loading and
 * keeps being saveable, or renaming, disabling and deleting it through the editor would fail and the
 * only way out of such a key would be deleting its row by hand.
 */
class UndeclarableEngineFeatureException extends UnsupportedEngineFeatureException
{
    /**
     * @param EngineFeature $feature the capability the declaration needs
     * @param string        $type    the field type of the key being defined
     * @param int           $storeId the store whose engine does not offer it
     * @param string        $engine  the name that engine is registered under
     */
    public static function forUndeclarableFeature(
        EngineFeature $feature,
        string $type,
        int $storeId,
        string $engine
    ): self {
        return new self($feature, $storeId, $engine, $type, sprintf(
            'A classification store key of type "%s" cannot be defined in store %d: its declaration needs'
            . ' %s, which the storage engine "%s" of that store does not offer. Keys already declared that way'
            . ' are untouched. Move the store over to an engine that offers it - run'
            . ' pimcore:classificationstore:flip for store %d and then drain, verify and cleanup; the flip'
            . ' creates the document tables before it switches the engine, so do not write the engine column'
            . ' by hand - or give the key a type whose value the key addresses on its own.',
            $type,
            $storeId,
            $feature->name,
            $engine,
            $storeId
        ));
    }
}
