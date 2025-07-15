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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Logger;
use Pimcore\Model\Document;

/**
 * @internal
 */
trait AllowDocumentRelationTrait
{
    /**
     * Checks if a document is an allowed relation
     *
     * @internal
     */
    protected function allowDocumentRelation(Document $document): bool
    {
        if ($document->getId() <= 0) {
            return false;
        }

        $allowedDocumentTypes = $this->getDocumentTypes();

        $allowed = true;
        if (!$this->getDocumentsAllowed()) {
            $allowed = false;
        } elseif (count($allowedDocumentTypes) > 0) {
            //check for allowed asset types
            $allowedTypes = [];
            foreach ($allowedDocumentTypes as $t) {
                if ($t['documentTypes']) {
                    $allowedTypes[] = $t['documentTypes'];
                }
            }

            if (!in_array($document->getType(), $allowedTypes, true) && count($allowedTypes) > 0) {
                $allowed = false;
            }
        } else {
            //don't check if no allowed document types set
        }

        Logger::debug('checked object relation to target document [' . $document->getId() . '] in field [' . $this->getName() . '], allowed:' . $allowed);

        return $allowed;
    }
}
