# CKEDITOR (use builder: http://ckeditor.com/builder);
current version: 4.15.0
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
current version: 6.0.0.640

needed components:
\build\ext-all.js
\build\ext-all-debug.js
\build\classic\theme-triton
\build\classic\locale
\packages\ux\classic\src => ux
\packages\build\charts\classic\ => packages\charts\classic (only JS)
\packages\build\charts\classic\triton\ copy over ext\classic\theme-triton

+ OpenSans Fonts from the installation package have been modified since they need the 'embed' flag for IE11

# styling (see commit 8.9.2015 - rev. 4f2d49a8f567fb1b7978022f644a748a9e0b6afb)

#5fa2dd => #3c3f41
#eaeff4 => #e4e4e4
#7fb5e4 => #3c3f41

# fixes the problem with font-rendering in chrome (gray instead of black)
[see commit](https://github.com/pimcore/pimcore/commit/3c641580dbe2efa30e539006ee7166b519ffd832#diff-250264cb98391c6be4d4720f7d887c86)
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

