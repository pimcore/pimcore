

# CKEDITOR (use builder: http://ckeditor.com/builder);
current version: 4.14.0
upload build-config.js

OR:

+ select "full" distribution

Add plugins:
+ plugin sourcedialog
+ plugin tableresize

Remove plugins:
- About CKEditor
- Content Templates
- File Browser
- Flash Dialog
- Form Elements
- Insert Smiley
- Magic Line
- Maximize
- New Page
- Preview
- Print
- Save
- SpellCheckAsYouType (SCAYT)

Delete: samples/

# EXT JS

current version: 7.0.0

First, register at Sencha.

You need [NPM](https://github.com/npm/cli) for this.

From your project root, call:
```
npm login --registry=https://sencha.myget.org/F/gpl/npm/ --scope=@sencha

# you will be prompted for Sencha username and password.

npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-core
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic-theme-material
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic-theme-neptune
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic-theme-triton
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-ux
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-charts
```


+ OpenSans Fonts from the installation package have been modified since they need the 'embed' flag for IE11

# styling (see commit 8.9.2015 - rev. 4f2d49a8f567fb1b7978022f644a748a9e0b6afb)

#5fa2dd => #3c3f41
#eaeff4 => #e4e4e4
#7fb5e4 => #3c3f41

# fixes the problem with font-rendering in chrome (gray instead of black)
fonts/OpenSans-LightItalic.ttf => fonts/OpenSans-Italic.ttf
fonts/OpenSans-Light.ttf => fonts/OpenSans-Regular.ttf

# Moment JS
current version: 2.24.0

# Leaflet JS
current version: 1.6.0

# miniPaint
current version: 4.2.4

Build instructions:
- Follow: https://github.com/viliusle/miniPaint/wiki/Build-instructions
- open & customize src/js/config-menu.js (Add Save option & Replace File option)
<li>
    <a class="trn" id="save_button" href="#">Save</a>
</li>
<li>
    <a class="trn" href="#">File</a>
    <ul>
        <li><a class="trn" data-target="file/new.new" href="#">New</a></li>
        <li><div class="mid-line"></div></li>
        <li class="more">
            <a class="trn" href="#">Open</a>
            <ul>
            <li><a class="trn dots" data-target="file/open.open_file" href="#">Open File</a></li>
            <li><a class="trn dots" data-target="file/open.open_dir" href="#">Open Directory</a></li>
            <li><a class="trn dots" data-target="file/open.open_url" href="#">Open URL</a></li>
            <li><a class="trn dots" data-target="file/open.open_data_url" href="#">Open Data URL</a></li>
            </ul>
        </li>
    </ul>
</li>

