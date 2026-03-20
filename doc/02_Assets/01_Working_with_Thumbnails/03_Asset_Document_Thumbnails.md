---
title: Document Thumbnails
description: Generating thumbnail previews for PDF, Office, and other document formats.
---

# Asset Document Thumbnails (PDF, DOCX, ODF, ...)

This feature creates image thumbnails from nearly any document format, including DOC(X), PPT(X),
PDF, XLS(X), ODT, ODS, ODP, and many others.

You can use existing image thumbnail configurations to create a thumbnail of your choice.

This feature requires Ghostscript and at least
[Gotenberg](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/02_System_Setup_and_Hosting/07_Additional_Tools_Installation.md#gotenberg)
or
[LibreOffice](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/02_System_Setup_and_Hosting/07_Additional_Tools_Installation.md#libreoffice-pdftotext-inkscape)
to be installed on the server.

> **Important**
> Thumbnail processing for documents runs asynchronously as part of the
> [Symfony Messenger queue](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/03_Advanced_Installation_Topics/01_Symfony_Messenger.md),
> under the `pimcore_asset_update` messenger queue.

## Examples

```php
$asset = Asset::getById(123);
if($asset instanceof Asset\Document) {

   // get a thumbnail of the first page, resized to the configuration of "myThumbnail"
   echo $asset->getImageThumbnail("myThumbnail");


   // get the thumbnail for the second page (1-based) using a dynamic configuration
   echo $asset->getImageThumbnail(["width" => 230, "contain" => true], 2);


   // get the thumbnail URL for all pages, but do not generate them immediately (see third parameter)
   // the thumbnails are then generated on request
   $thumbnailUrls = [];
   for($i=1; $i<=$asset->getPageCount(); $i++) {
      $thumbnailUrls[] = $asset->getImageThumbnail("myThumbnail", $i, true);
   }

}
```

## Building Thumbnails for a List of Assets

> It is recommended to use named thumbnails for caching purposes.

```php
$list = new Asset\Listing();
$assets = $list->getAssets();
foreach ($assets as $asset) {
   echo match (true) {
      $asset instanceof Asset\Image => $asset->getThumbnail('myThumbnail')?->getPath(),
      $asset instanceof Asset\Document => $asset->getImageThumbnail('myThumbnail')?->getPath(),
      default => '',
   };
}
```

## Configuration

Asset documents depend on background processes to generate thumbnails and extract search text.
These processes require page count processing, which can consume unnecessary resources if the
feature is not used.

You can disable or fine-tune these background processes:

```yaml
pimcore:
    assets:
        document:
            thumbnails:
                enabled: true # process thumbnails for asset documents (default: true)
            process_page_count: true # process & store page count, required for thumbnails & text generation (default: true)
            process_text: true # process text for asset documents, used by search (default: true)
            scan_pdf: true # scan PDF documents for unsafe JavaScript (default: true)
            open_pdf_in_new_tab: only-unsafe # 'all-pdfs' | 'only-unsafe' | 'none' (default: only-unsafe)
```

| Option | Default | Description |
|--------|---------|-------------|
| `thumbnails.enabled` | `true` | Process thumbnails for asset documents. |
| `process_page_count` | `true` | Process and store page count. Internally required for thumbnails and text generation. |
| `process_text` | `true` | Extract text from asset documents (used by search). |
| `scan_pdf` | `true` | Scan PDF documents for unsafe JavaScript. |
| `open_pdf_in_new_tab` | `only-unsafe` | Controls PDF display: `all-pdfs` (show thumbnail for all PDFs), `only-unsafe` (show thumbnail only for PDFs with JavaScript), `none` (show all PDFs inline, not recommended). |
