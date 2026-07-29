---
title: Optional System Dependencies
description: Which external CLI tools and services Pimcore shells out to, what each one actually does, and what happens when it is missing.
---

# Optional System Dependencies

Pimcore itself runs on PHP and MySQL/MariaDB. A number of asset and document features, however,
delegate work to external command line tools or HTTP services. These are *optional* in the sense
that Pimcore installs, boots, and serves content without them — but the individual feature they
back is unavailable or degraded when they are absent.

The system requirements check lists these tools by name only, which makes it easy to install the
wrong thing or to worry about a warning that does not matter for your project. This page explains,
for each dependency, **which feature it powers, whether it has a fallback, and what actually breaks
without it**.

For the platform-wide list of supported versions and installation instructions, see
[System Requirements](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/01_System_Requirements.md).

## Checking What Is Installed

```bash
# all checks
bin/console pimcore:system:requirements:check

# only what is not OK
bin/console pimcore:system:requirements:check -l warning
```

The same information is available in the admin interface as *System Requirements Check*, in the
tools menu. Results use three states:

| State | Meaning |
|-------|---------|
| OK | Detected and usable. |
| Warning | Recommended, but not required. Pimcore runs without it; the feature it backs does not. |
| Error | Required. Pimcore will not work correctly without it. |

Everything in the *CLI Tools & Applications* section is reported as a warning, except `php` and
`composer`. A warning is therefore not a problem to fix blindly — it is only relevant if you use
the feature described below.

## Overview

| Dependency | Powers | Without it |
|------------|--------|------------|
| `gs` (Ghostscript) | Rendering PDF pages to images, counting PDF pages | No document preview thumbnails, no page count |
| `pdftotext` (poppler-utils) | Extracting plain text from PDFs | Text extraction falls back to Ghostscript (less accurate) |
| `soffice` (LibreOffice) *or* Gotenberg | Converting Office documents to PDF | Only PDF assets get thumbnails and text |
| Gotenberg (Chromium) | Rendering HTML to a screenshot image | HTML-to-image conversion unavailable |
| `ffmpeg` | Video transcoding, poster frames, duration/dimensions | Video assets are not transcoded or previewed |
| `exiftool` | Reading embedded asset metadata | Falls back to PHP's EXIF/IPTC/XMP readers (less complete) |
| `jpegoptim`, `pngquant`, `optipng`, `cwebp` | Recompressing generated thumbnails | Thumbnails are served unoptimized (larger files) |
| `dot` (Graphviz) | Rendering workflow graphs from DOT source | Pimcore still emits DOT source; nothing renders it |
| Imagick (PHP extension) | Image processing backend | Falls back to GD: fewer formats, lower quality |
| Redis | Cache and Messenger transport backend | Doctrine/database cache and transports are used instead |
| OpenSearch / Elasticsearch | Generic Data Index search and grid filtering | Search and filtering backed by the index are unavailable |

The sections below give the detail behind each row.

## Document and PDF Processing

This is the most frequently misunderstood group, because three different tools cooperate on what
looks like one feature. The pipeline is:

```
Office file (docx, xlsx, pptx, odt, ...)
        │
        │  LibreOffice (soffice) or Gotenberg
        ▼
      PDF file
        │
        ├─ Ghostscript (gs) ──► PNG page image  (document thumbnails)
        ├─ Ghostscript (gs) ──► page count
        └─ pdftotext ─────────► plain text      (fallback: Ghostscript txtwrite)
```

### `gs` — Ghostscript

Ghostscript is the engine behind asset document previews. `Pimcore\Document\Adapter\Ghostscript`
uses it to:

- render a single page of a PDF to a PNG, which is the source image for
  [document thumbnails](../02_Assets/01_Working_with_Thumbnails/03_Asset_Document_Thumbnails.md)
- count the pages of a PDF (stored as the `document_page_count` custom setting)
- extract plain text, but only as a fallback — see `pdftotext` below

Ghostscript is a hard dependency of the LibreOffice and Gotenberg document adapters as well: both
extend the Ghostscript adapter and are considered unavailable if Ghostscript is missing, because
they only produce an intermediate PDF that Ghostscript still has to render.

**Without it:** `Pimcore\Document::getDefaultAdapter()` finds no usable adapter. Document
thumbnails resolve to an empty thumbnail and page count processing logs an error and records the
page count as `failed`. Nothing crashes — the feature is simply not available.

### `pdftotext` — poppler-utils

`pdftotext` is used for **one thing only: extracting plain text from PDF pages**. It is invoked by
`Ghostscript::convertPdfToText()`, which is reached exclusively through `Asset\Document::getText()`.
That text is what feeds document search indexing and any code that reads the textual content of an
asset document.

It plays no part in generating, rendering, or converting PDFs. Despite being listed next to
Ghostscript in the requirements check, it is not involved in producing thumbnails or page counts.

`pdftotext` is also optional *for text extraction itself*. When it is not installed, Pimcore falls
back to Ghostscript's `txtwrite` device. Poppler is preferred because it produces more accurate
results.

**Without it:** text extraction still works via Ghostscript, at lower fidelity. If Ghostscript is
missing as well, `getText()` logs an error and returns nothing.

Text extraction can be turned off entirely, in which case neither tool is used for it:

```yaml
pimcore:
    assets:
        document:
            process_text: false
```

### `soffice` — LibreOffice

LibreOffice converts Office formats (`doc`, `docx`, `odt`, `xls`, `xlsx`, `ods`, `ppt`, `pptx`,
`odp`) to PDF in headless mode, so that Ghostscript can then render, count, and extract from them.
Conversions are serialized with a lock, and the resulting PDF is cached in asset storage so a
document is converted only once.

Conversion output is logged to `libreoffice-pdf-convert.log` in the Pimcore log directory
(`var/log` by default).

**Without it (and without Gotenberg):** PDF assets are still fully supported; other document
formats get no thumbnail, no page count, and no extracted text.

### Gotenberg

[Gotenberg](https://gotenberg.dev/) is an HTTP service rather than a local binary, and covers two
separate jobs:

- **Office to PDF conversion** — an alternative to a locally installed LibreOffice. When reachable
  it is preferred over local LibreOffice; the adapter order is Gotenberg, LibreOffice, Ghostscript.
- **HTML to image** — `Pimcore\Image\HtmlToImage` uses Gotenberg's Chromium module to screenshot a
  URL into a PNG. There is no alternative implementation, so this feature depends on Gotenberg
  exclusively.

```yaml
pimcore:
    gotenberg:
        base_url: 'http://gotenberg:3000'
        ping_cache_ttl: 60
```

Availability is determined by polling the service's `/health` endpoint. A successful check is
cached as available for `ping_cache_ttl` seconds. A failure is not cached immediately: the ping is
retried after 15 seconds, and only three consecutive failures mark the service unavailable for
`ping_cache_ttl` seconds. This keeps a restarting Gotenberg container from disabling document
conversion for the full TTL.

**Without it:** document conversion falls back to local LibreOffice; HTML-to-image conversion is
reported as unsupported.

## Image Processing

### Imagick vs. GD

Imagick (the PHP extension over ImageMagick) is the preferred image backend and is selected
automatically when the extension is loaded. GD is the fallback and supports far fewer formats at
lower quality. Two related checks appear in the requirements list:

- **ImageMagick LCMS delegate** — required for accurate ICC color profile conversion.
- **WebP / AVIF support** — reported per active adapter; it determines whether Pimcore can generate
  those thumbnail variants at all.

WebP encoding through ImageMagick goes through a delegate that calls `cwebp`. If the delegate
definition omits the `-q` flag, the quality configured on the thumbnail is ignored and `cwebp`'s
own default is used. See
[Advanced Image Thumbnails](../02_Assets/01_Working_with_Thumbnails/04_Advanced_Image_Thumbnails.md)
for the delegate example.

### Image Optimizers

Generated thumbnails are recompressed asynchronously by
`Pimcore\Image\Optimizer\SpatieImageOptimizer`, which runs a chain of:

| Binary | Applies to |
|--------|------------|
| `jpegoptim` | JPEG (`--strip-all --all-progressive`) |
| `pngquant` | PNG |
| `optipng` | PNG |
| `cwebp` | WebP |

Every step is best-effort: a missing binary means that step is skipped, and the smallest result
produced by any step is the one written back to thumbnail storage. Optimization is triggered
through the `pimcore_image_optimize` queue and can be run manually:

```bash
bin/console pimcore:thumbnails:optimize-images
```

**Without them:** thumbnails are correct but larger. This affects bandwidth, not functionality.

> **Note**
> The requirements check lists `exiftool` alongside the optimizers. It is not part of the optimizer
> chain — see [Embedded Metadata](#embedded-metadata).

## Video Processing

`ffmpeg` backs the entire video asset feature set. `Pimcore\Video\Adapter\Ffmpeg` uses it to:

- transcode uploaded videos to web formats (MP4/H.264, WebM/VP8, MPEG-DASH, MPEG)
- extract a single frame as the poster/preview image of a video asset
- read duration and dimensions from the source file

There is no alternative adapter, so this is effectively an all-or-nothing dependency for video.

**Without it:** video thumbnails resolve to an error placeholder, duration and dimensions stay
empty, and the asset is flagged as failed processing. Uploading and downloading the original video
file still works.

Video thumbnails can be generated in bulk with:

```bash
bin/console pimcore:thumbnails:video
```

See [Video Thumbnails](../02_Assets/01_Working_with_Thumbnails/02_Video_Thumbnails.md) for the
transformation options.

## Embedded Metadata

`exiftool` reads embedded metadata from uploaded assets (`exiftool -j`) and is the source of the
metadata shown on an asset's metadata panel — camera EXIF, IPTC, XMP, and format-specific tags
including video metadata.

**Without it:** Pimcore falls back to PHP's own readers (`exif_read_data()`, `iptcparse()`, and its
XMP parser) and merges the results. The common EXIF/IPTC/XMP fields are still read; maker notes,
video metadata, and less common tags are not.

## Workflow Graphs

Pimcore generates workflow graphs as Graphviz **DOT source** — it does not call the `dot` binary
itself in the core framework. The `graphviz` package provides `dot`, which turns that source into
an image.

```bash
bin/console pimcore:workflow:dump <workflow-name> | dot -Tpng > workflow.png
```

The requirements check reports Graphviz so that administrators know whether graph rendering is
possible on the machine.

## Backing Services

These are configured rather than detected, and are covered in their own chapters:

- **MySQL / MariaDB** — required. Configured through `DATABASE_URL`.
- **Redis** — optional cache backend and Messenger transport. See
  [Cache](../08_Development_Details/03_Cache/README.md). Note that if Redis is configured as the
  cache backend and becomes unreachable, Pimcore stops working — it is not a soft dependency once
  enabled.
- **OpenSearch / Elasticsearch** — backs the Generic Data Index, which powers search and grid
  filtering for data objects. Configured through `PIMCORE_OPENSEARCH_DSN` or
  `PIMCORE_ELASTICSEARCH_DSN`.

## Configuring Binary Locations

`Pimcore\Tool\Console::getExecutable()` resolves the binaries Pimcore invokes itself — `gs`,
`pdftotext`, `soffice`, `ffmpeg`, `exiftool`, `php`, `composer`, `nice`, `nohup` — in this order:

1. **A container parameter** named `pimcore_executable_<name>`, where `<name>` is the binary name
   from the list above:

   ```yaml
   # config/services.yaml
   parameters:
       pimcore_executable_gs: /opt/ghostscript/bin/gs
       pimcore_executable_pdftotext: /opt/poppler/bin/pdftotext
       pimcore_executable_soffice: /opt/libreoffice/bin/soffice
       pimcore_executable_ffmpeg: /opt/tools/ffmpeg
   ```

2. **Additional search paths** from the system configuration, colon-separated and searched before
   the environment's `PATH`:

   ```yaml
   pimcore:
       general:
           path_variable: '/opt/tools/bin:/usr/local/custom/bin'
   ```

3. **The `PATH` of the process**, via Symfony's `ExecutableFinder`.

Results are cached per request, so a newly installed binary is picked up on the next request.

> **Important**
> The web server process and the CLI process often have different `PATH` values. A tool that the
> requirements check finds on the command line may still be invisible to PHP-FPM. Use
> `pimcore_executable_*` parameters when the two environments differ.

Where it exists, `nice` is used to run these subprocesses at low priority; it is skipped silently
if unavailable.

> **Note**
> The image optimizer binaries (`jpegoptim`, `pngquant`, `optipng`, `cwebp`) are located by the
> `spatie/image-optimizer` library, not by `Console::getExecutable()`. They must be on the `PATH` of
> the worker process; `pimcore_executable_*` parameters have no effect on them.
