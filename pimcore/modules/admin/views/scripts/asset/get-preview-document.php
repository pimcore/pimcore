<?php

// add the PDF check here, otherwise the preview layer in admin is shown without content
if(Pimcore_Document::isAvailable() && Pimcore_Document::isFileTypeSupported($this->asset->getFilename())) {
    $document = Pimcore_Document::getInstance();
    try {
        $pdfPath = $document->getPdf($this->asset->getFileSystemPath());
        $pdfPath = str_replace(PIMCORE_DOCUMENT_ROOT, "", $pdfPath);
    } catch (\Exception $e) {
        // nothing to do
    }
}

if($pdfPath) {
    include("document-preview/pdfjs.php");
} else if (strpos($this->asset->getFilename(), ".pdf") !== false) {
    $pdfPath = $this->asset->getFullpath();
    include("document-preview/pdfjs.php");
} else {
    include("document-preview/google-docs.php");
}
