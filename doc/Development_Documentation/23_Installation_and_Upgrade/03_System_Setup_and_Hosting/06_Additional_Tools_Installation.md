# Additional Tools Installation

Pimcore uses some 3rd party applications for certain functionalities, such as video transcoding (FFMPEG), image optimization (advpng, cjpeg, ...), and many others. For a full list of additional tools required or recommended for Pimcore, please visit [Pimcore System Requirements](../01_System_Requirements.md). 

The installation of some of the tools is covered in this guide and should work at least on every Debian based Linux (Debian, Ubuntu, Mint, ...). 
For other Linux distributions you might have to adopt some commands to your platform-specific environment, but we try to use as many statically linked software as possible, that can be used on any x64 Linux platform.  

> It's important that all tools (incl. `composer`) are added to the `$PATH` env. variable, so that Pimcore is able to find the executables. 
If you're not able to control the `$PATH` variable, you can also [manually configure the paths for each application](https://github.com/pimcore/skeleton/blob/master/app/config/parameters.example.yml).


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

## Wkhtmltoimage / Wkhtmltopdf
Please visit: [http://wkhtmltopdf.org/downloads.html](http://wkhtmltopdf.org/downloads.html)

## Image Optimizers

### ZopfliPNG
```bash
wget https://github.com/imagemin/zopflipng-bin/raw/master/vendor/linux/zopflipng -O /usr/local/bin/zopflipng
chmod 0755 /usr/local/bin/zopflipng
```

### PngCrush 
```bash
wget https://github.com/imagemin/pngcrush-bin/raw/master/vendor/linux/pngcrush -O /usr/local/bin/pngcrush
chmod 0755 /usr/local/bin/pngcrush
```

### JPEGOptim
```bash
wget https://github.com/imagemin/jpegoptim-bin/raw/master/vendor/linux/jpegoptim -O /usr/local/bin/jpegoptim
chmod 0755 /usr/local/bin/jpegoptim
```

### PNGOut
```bash
wget https://github.com/imagemin/pngout-bin/raw/master/vendor/linux/x64/pngout -O /usr/local/bin/pngout
chmod 0755 /usr/local/bin/pngout
```

### AdvPNG
```bash
wget https://github.com/imagemin/advpng-bin/raw/master/vendor/linux/advpng -O /usr/local/bin/advpng
chmod 0755 /usr/local/bin/advpng
```

### MozJPEG
```bash
wget https://github.com/imagemin/mozjpeg-bin/raw/master/vendor/linux/cjpeg -O /usr/local/bin/cjpeg
chmod 0755 /usr/local/bin/cjpeg
```

### SQIP / SVG Placeholder
Though not stricly a Image Optimizer, [SQIP](https://github.com/technopagan/sqip) is a SVG-based implementation of the [Low Quality Image Placeholders (LQIP)](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders/) technique, that need some Image-Processing tools to be installed - and therefore their installation is described here.

I'm assuming you've NodeJS up and running using either `npm` or `yarn` as package manager.

Install SQIP globally:
```bash
# with npm
npm install -g sqip
# or with yarn
yarn global add sqip
```
This should install `sqip` somewhere in your path.

Since SQIP requires [Primitive](https://github.com/fogleman/primitive) - which is written in [Go](https://golang.org/) - you need that [set-up](https://www.digitalocean.com/community/tutorials/how-to-install-go-on-ubuntu-18-04) also.

Then run
```bash
go get -u github.com/fogleman/primitive
```
Usually that should install the binary to `/usr/lib/go/bin/primitive` - which may not be in your path. I prefer to add a symlink:

```bash
cd /usr/local/bin
ln -s /usr/lib/go/bin/primitive
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
