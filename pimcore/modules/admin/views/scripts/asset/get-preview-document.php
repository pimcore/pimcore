<?php

// add the PDF check here, otherwise the preview layer in admin is shown without content
if(\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($this->asset->getFilename())) {
    $document = \Pimcore\Document::getInstance();
    try {
        $pdfPath = $document->getPdf($this->asset->getFileSystemPath());
        $pdfPath = str_replace(PIMCORE_DOCUMENT_ROOT, "", $pdfPath);
    } catch (\Exception $e) {
        // nothing to do
    }
}

if (strpos($this->asset->getFilename(), ".pdf") !== false) {
    $pdfPath = $this->asset->getFullpath();
}

if($pdfPath) {
    if($this->getParam("native-viewer")) {
        header("Location: " . $pdfPath, true, 301);
        exit;
    } else {
        include("document-preview/pdfjs.php");
    }
} else {
    include("document-preview/google-docs.php");
}
