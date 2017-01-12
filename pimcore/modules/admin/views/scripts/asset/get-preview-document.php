<?php

$pdfPath = null;

// add the PDF check here, otherwise the preview layer in admin is shown without content
if(\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($this->asset->getFilename())) {
    $document = \Pimcore\Document::getInstance();
    try {
        $pdfFsPath = $document->getPdf($this->asset->getFileSystemPath());
        $pdfPath = str_replace(PIMCORE_DOCUMENT_ROOT, "", $pdfFsPath);

        $results = \Pimcore::getEventManager()->trigger("frontend.path.asset.document.image-thumbnail", $this, [
            "filesystemPath" => $pdfFsPath,
            "frontendPath" => $pdfPath
        ]);

        if($results->count()) {
            $pdfPath = $results->last();
        }

    } catch (\Exception $e) {
        // nothing to do
    }
}

if (strpos($this->asset->getFilename(), ".pdf") !== false) {
    $pdfPath = $this->asset->getFullpath();
}

if($pdfPath && $this->getParam("native-viewer")) {
    header("Location: " . $pdfPath . "?_dc=" . time(), true, 301);
    exit;
} else {
    // we use the Google Apps Document Viewer instead
    ?><!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style type="text/css">
            html {
                height: 100%;
                overflow: hidden;
            }

            body {
                height: 100%;
                margin: 0;
                padding: 0;
            }
        </style>
    </head>

    <body>
        <iframe src="https://docs.google.com/viewer?embedded=true&url=<?= urlencode($this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() . $this->asset->getFullPath() . "?dc_=" . time()); ?>" frameborder="0" width="100%" height="100%"></iframe>
    </body>
    </html>
<?php
}
