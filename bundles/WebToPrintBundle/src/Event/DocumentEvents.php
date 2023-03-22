<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\WebToPrintBundle\Event;

final class DocumentEvents
{
    /**
     * Processor contains the processor object used to generate the PDF
     *
     * Arguments:
     *  - processor | instance of the PDF processor Pimcore\Bundle\WebToPrintBundle\Processor\{ProcessorName}
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
     * @Event("Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent")
     *
     * @var string
     */
    const PRINT_MODIFY_PROCESSING_OPTIONS = 'pimcore.document.print.processor.modifyProcessingOptions';

    /**
     * Modify the configuration for the processor (when the pdf gets created)
     *
     * Arguments:
     *
     * PDFReactor:
     *  - config | configuration which is passed from the pimcore admin interface
     *  - reactorConfig | configuration which is passed to PDFReactor
     *  - document | Pimcore document that is converted
     *
     *
     * @Event("Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent")
     *
     * @var string
     */
    const PRINT_MODIFY_PROCESSING_CONFIG = 'pimcore.document.print.processor.modifyConfig';
}
