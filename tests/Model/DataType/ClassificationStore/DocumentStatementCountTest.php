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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

/**
 * The load counts against a store on the document engine: one document per group is read in one
 * statement, so none of the read paths scales with groups, keys or languages either.
 *
 * @group model.datatype.classificationstore
 */
class DocumentStatementCountTest extends StatementCountTest
{
    protected function getStoreEngine(): ?string
    {
        return 'document';
    }
}
