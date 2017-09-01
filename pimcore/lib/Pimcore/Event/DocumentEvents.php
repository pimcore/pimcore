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
     * Modify the processing options (displayed in the Pimcore admin interface)
     *
     * Arguments:
     *  - options | array for configuration settings
     *
     * @Event("Pimcore\Event\Model\PrintConfigEvent")
     *
     * @var string
     */
    const PRINT_MODIFY_PROCESSING_OPTIONS = 'pimcore.document.print.processor.modifyProcessingOptions';

    /**
     * Modify the configuration for the processor (when the pdf gets created)
     *
     * Arguments:
     * WkHtmlToPdfAdapter:
     *  - wkhtmltopdfBin | path to wkhtmltopdf binary
     *  - options | configuration options
     *  - srcUrl | path tho source html file
     *  - dstFile | path to the output pdf file
     *  - config | configuration which is passed from the pimcore admin interface
     *
     * PDFReactor:
     *  - config | configuration which is passed from the pimcore admin interface
     *  - reactorConfig | configuration which is passed to PDFReactor
     *  - document | Pimcore document that is converted
     *
     * @Event("Pimcore\Event\Model\PrintConfigEvent")
     *
     * @var string
     */
    const PRINT_MODIFY_PROCESSING_CONFIG = 'pimcore.document.print.processor.modifyConfig';

    /**
     * Arguments:
     *  - base_element | Pimcore\Model\Document | contains the base document used in copying process
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_COPY = 'pimcore.document.postCopy';

    /**
     * The TAG_NAME event is triggered when a document tag name is built.
     *
     * @Event("Pimcore\Event\Model\Document\TagNameEvent")
     */
    const TAG_NAME = 'pimcore.document.tag.name';
}
