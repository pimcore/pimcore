# Additional Tools Installation

Pimcore uses some 3rd party applications for certain functionalities, such as video transcoding (FFMPEG), image optimization (advpng, cjpeg, ...), and many others. For a full list of additional tools required or recommended for Pimcore, please visit [Pimcore System Requirements](../01_System_Requirements.md). 

The installation of some of the tools is covered in this guide and should work at least on every Debian based Linux (Debian, Ubuntu, Mint, ...). 
For other Linux distributions you might have to adopt some commands to your platform-specific environment, but we try to use as many statically linked software as possible, that can be used on any x64 Linux platform.  

> It's important that all tools (incl. `composer`) are added to the `$PATH` env. variable, so that Pimcore is able to find the executables. 
If you're not able to control the `$PATH` variable, you can also [manually configure the paths for each application](https://github.com/pimcore/skeleton/blob/10.2/config/services.yaml).


## Composer 
Please visit the official install guide for Composer: [https://getcomposer.org/](https://getcomposer.org/)

## FFMPEG

Please keep in mind that many Linux/GNU distributions ship FFMPEG only with free codecs, 
so they do not support commonly used video codecs such as mpeg4 and many others.   

```bash
sudo apt-get install ffmpeg
```

## LibreOffice, pdftotext, Inkscape, ...

```bash
apt-get install libreoffice libreoffice-script-provider-python libreoffice-math xfonts-75dpi poppler-utils inkscape libxrender1 libfontconfig1 ghostscript
```

## Chromium (Chrome Headless)
Please visit: [https://www.chromium.org/getting-involved/download-chromium](https://www.chromium.org/getting-involved/download-chromium)

## Wkhtmltopdf (Deprecated)
Please visit: [http://wkhtmltopdf.org/downloads.html](http://wkhtmltopdf.org/downloads.html)


## Image Optimizers

### JPEGOptim

```bash
wget https://github.com/imagemin/jpegoptim-bin/raw/main/vendor/linux/jpegoptim -O /usr/local/bin/jpegoptim
chmod 0755 /usr/local/bin/jpegoptim
```

## PngQuant

```bash
apt-get install pngquant
```

## OptiPng

```bash
apt-get install optipng
```

## Exiftool

```bash
apt-get install libimage-exiftool-perl
```

## WebP

Install webp for [WebP-Support](../../04_Assets/03_Working_with_Thumbnails/01_Image_Thumbnails.md)

```bash
apt-get install webp
```

## Graphviz

Install graphviz for [Workflow](../../07_Workflow_Management/README.md)

```bash
apt-get install graphviz
```

## Facedetect

Install facedetect for [image thumbnails focal point](../../04_Assets/03_Working_with_Thumbnails/01_Image_Thumbnails.md)

```bash
apt-get install facedetect
```


# Check your installation

You can check system requirements via Admin UI `Tools` / `System Info & Tools` / `System-Requirements Check` menu.

Or via following CLI command:

```bash
bin/console pimcore:system:requirements:check
```
