<?php

namespace Pimcore\Event;

final class DocumentEvents
{
    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.document.preAdd';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const POST_ADD = 'pimcore.document.postAdd';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.document.preUpdate';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const POST_UPDATE = 'pimcore.document.postUpdate';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.document.preDelete';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.document.postDelete';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const PRINT_PRE_PDF_GENERATION = 'pimcore.document.print.prePdfGeneration';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const PRINT_POST_PDF_GENERATION = 'pimcore.document.print.postPdfGeneration';

    /**
     * @Event("Pimcore\Event\Element\DocumentEvent")
     * @var string
     */
    const POST_COPY = 'pimcore.document.postCopy';
}