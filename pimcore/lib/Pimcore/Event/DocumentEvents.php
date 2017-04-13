<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class DocumentEvents
{
    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.document.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.document.postAdd';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.document.preUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.document.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.document.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.document.postDelete';

    /**
     * Processor contains the processor object used to generate the PDF
     *
     * Arguments:
     *  - processor | instance of the PDF processor Pimcore\Web2Print\Processor\{ProcessorName}
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRINT_PRE_PDF_GENERATION = 'pimcore.document.print.prePdfGeneration';

    /**
     * Filename contains the filename of the generated pdf on filesystem, pdf contains generated pdf as string
     *
     * Arguments:
     *  - filename | contains the path of the generated pdf on filesystem
     *  - pdf | contains generated pdf as string
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRINT_POST_PDF_GENERATION = 'pimcore.document.print.postPdfGeneration';

    /**
     * Arguments:
     *  - base_element | Pimcore\Model\Document | contains the base document used in copying process
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_COPY = 'pimcore.document.postCopy';
}
