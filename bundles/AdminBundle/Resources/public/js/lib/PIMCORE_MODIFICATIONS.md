# CKEDITOR (use builder: http://ckeditor.com/builder);
current version: 4.16.2
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

Remove: samples/

# EXT JS

current version: 7.0.0

First, register at Sencha.

You need [NPM](https://github.com/npm/cli) for this.

From your project root, call:
```
npm login --registry=https://sencha.myget.org/F/gpl/npm/ --scope=@sencha

# you will be prompted for Sencha username and password.

From the Pimcore project root execute the following commands

# see https://docs.sencha.com/extjs/7.0.0/guides/using_systems/using_npm/extjs_packages.html
npm install --prefix . -g @sencha/ext-gen
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-core
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic-theme-neptune
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic-theme-triton
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-ux
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-charts
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-classic-locale
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-font-awesome
npm install --prefix web/bundles/pimcoreadmin/js/ -g @sencha/ext-font-ext
```

From the generated `theme` directories (triton, ...) remove the inner node_modules directory. 

Create a sample classic app with triton theme (some temporary folder)
```
bin/ext-gen app --template classicdesktop --classictheme theme-triton --name PimcoreApp
```

* Make sure that the charts are listed in package.json

```
"dependencies": {
"@sencha/ext-classic": "~7.0.0",
"@sencha/ext-classic-theme-triton": "~7.0.0",
"@sencha/ext": "~7.0.0",
"@sencha/ext-charts": "~7.0.0"
},
```

* Modify app.json - add the charts dependency

```
  "dependencies": {
  "requires": [
    "font-awesome",
    "charts"
  },
```

* Start & Stop the application 

```
npm start
```

* Inside the `generatedFiles` folder a new `bootstrap.js` file can be found.
Merge it with the existing `/bundles/AdminBundle/Resources/public/js/ext-js/bootstrap-ext-all.js` if needed.

* Run the ExtJSCommand

this will generate a combined ext-all file.

* Remove all node_modules
- except ext-ux

* Replace the CSS

CSS for the given theme will also be generated, e.g

```
pimcore-app/build/development/PimcoreApp/desktop/resources/PimcoreApp-all*.css
```

Replace the current ones in
```
bundles\AdminBundle\Resources\public\css\ext-js\PimcoreApp-*.css
```
medi
and adapt the styles as needed (see color map below). Also the resource paths need to be updated.

 # Manifest file
 
 `ext-gen` will also generate a manifest file `generated/desktop.json` which is needed
 for bootstrapping the application.
 Remove everything that is sample-app-specific and merge it with the
 existing `dev/pimcore/pimcore/bundles/AdminBundle/Resources/public/js/ext-js/pimcore*.json` files.
 Note that there is a special one for the the editmode.
 Be careful with the indices, the have to be consecutively numbered.
 Add everything that should synchronously loaded and not already in the list (have a look at your browser's console)


# styling (see commit 8.9.2015 - rev. 4f2d49a8f567fb1b7978022f644a748a9e0b6afb)

```
#5fa2dd => #3c3f41
#eaeff4 => #e4e4e4
#7fb5e4 => #3c3f41
```

# Moment JS
current version: 2.29.1

# Leaflet JS
current version: 1.7.1

# miniPaint
current version: 4.2.4

Build instructions:
- Follow: https://github.com/viliusle/miniPaint/wiki/Build-instructions
- open & customize src/js/config-menu.js (Add Save option & Replace File option)
```html
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
```

# Ace editor
current version: 1.4.13

Build instructions:
 - Download latest build from https://github.com/ajaxorg/ace-builds and extract `/src-min-noconflict`
 - Remove all theme-*.js files except `theme-chrome.js` & `theme-twilight.js`
 - Replace the current folder with extracted one here `/bundles/AdminBundle/Resources/public/js/lib/ace/src-min-noconflict`
