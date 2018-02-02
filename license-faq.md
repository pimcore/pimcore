# Pimcore - Licensing FAQ

## What is the license for Pimcore?
Pimcore and all contributed files hosted on pimcore.org or in the official Git repository are available under two different licenses: 
* GNU General Public License version 3 (GPLv3)
* Pimcore Enterprise License (PEL)
If you don't have a separate written licensing agreement between you and Pimcore GmbH then always GPLv3 applies to you.  

The following FAQ covers only GPLv3. 

# GPLv3 FAQ

## What does licensed under the GPLv3 actually mean? 
That means you are free to download, reuse, modify, and distribute 
any files hosted in pimcore.org's Git repositories under the terms of the GPL version 3, and to run Pimcore in 
combination with any code with any license that is compatible with GPL version 3, such as the 
Affero General Public License (AGPL) version 3.

## Does the license cover just PHP, or everything?
We require that all files (PHP, JavaScript, CSS, images, etc.) that are not part of a bundled 3rd party library 
(see [3rd-party-licenses.md](3rd-party-licenses.md) ) are under the terms of the GPLv3.

## Copyright & contributions
All Pimcore contributors retain copyright on their code, but agree to release it under the same licenses as Pimcore. 
If you are unable or unwilling to contribute a patch under the GPL version 3 and the Pimcore enterprise license, do not submit a patch.

## I want to release my work under a different license than GPLv3, is that possible? 
No. You can only release your work under any GPL version 3 or later compatible license. 

## The GPL requires that I distribute the "source code" of my files. What does that mean for a web application?
The "source code" of a file means the format that is intended for people to edit. 
What that means depends on the file in question.

For PHP code, the PHP file itself, without any compression or obfuscation, is its own source code. 
Note that for Pimcore, controller / view files are PHP code.
For JavaScript code, the JavaScript file itself, without any compression or obfuscation, is its own source code.
For CSS code, the CSS file itself, without any compression or obfuscation, is its own source code.
For images, the "source code" varies. Depending on the image, that could mean the production version of the file as 
a PNG or GIF, or an original high-resolution JPG, or a Photoshop, Illustrator, or GIMP file. 
The "source code" is whichever version is intended to be edited by people.

## If I write a module, plugin or custom code for my application, do I have to license it under the GPL?
Yes. Pimcore modules and plugins as well as custom code for your application are a derivative work of Pimcore. 
If you distribute them, you must do so under the terms of the GPL version 3 or later. 
You are not required to distribute them at all, however. 

However, when distributing your own Pimcore-based work, it is important to keep in mind what the GPLv3 applies to. 
The GPLv3 on code applies to code that interacts with that code, but not to data. 
That is, Pimcore's PHP code is under the GPLv3, and so all PHP code that interacts with it must also be 
under the GPLv3 or GPLv3 compatible. Images, JavaScript, and Flash files that PHP sends to the browser are not 
affected by the GPL because they are data. However, Pimcore's JavaScript, including the copy of jQuery that is 
included with Pimcore, is itself under the GPLv3 as well, so any Javascript that interacts with Pimcore's JavaScript 
in the browser must also be under the GPLv3 or a GPLv3 compatible license.

When distributing your own plugin, module or theme, therefore, 
the GPLv3 applies to any pieces that directly interact with parts of Pimcore that are under the GPLv3. 
Images and Flash files you create yourself are not affected. However, if you make a new image based off of an image 
that is provided by Pimcore under the GPL, then that image must also be under the GPLv3.

If you commit that module or plugin to a Pimcore Git repository, however, then all parts of it must be 
under the GPL version 3 or later, and you must provide the source code. 
That means the editable form of all files, as described above.

## If I write a plugin, module or custom code for my application, do I have to give it away to everyone?
No. The GPL requires that if you make a derivative work of Pimcore and distribute it to someone else, 
you must provide that person with the source code under the terms of the GPLv3 so that they may modify and redistribute 
it under the terms of the GPLv3 as well. However, you are under no obligation to distribute the code to anyone else. 
If you do not distribute the code but use it only within your organization, 
then you are not required to distribute it to anyone at all.

However, if your plugin is of general use then it is often a good idea to contribute it back to the community anyway. 
You can get feedback, bug reports, and new feature patches from others who find it useful.

## Is it permitted for me to sell Pimcore or a Pimcore plugin or a Pimcore theme?
Yes. However, you must distribute it under the GPL version 3 or later, 
so those you sell it to must be allowed to modify and redistribute it as well. See questions above.

## Can I write a "bridge module" to interface between Pimcore and another system or library?
That depends on the other system.

It is possible to distribute a module that communicates with a 3rd party system over HTTP, XML-RPC, SOAP, 
or some other wire protocol, that leaves the 3rd party system unaffected. 
Examples of such systems include Flickr, Mollom, or certain legacy systems.

It is possible to distribute a module that integrates with a 3rd party PHP or JavaScript library, 
as long as the library is under either a GPL or GPL-compatible license. 
Examples of compatible licenses include BSD/MIT-style "permissive" licenses or the Lesser General Public License (LGPL). 
The Free Software Foundation maintains a list of popular GPL-compatible licenses.

It is not possible to distribute a module that integrates a non-GPL compatible library with Pimcore, 
because it would be a derivative work of both Pimcore and that other library and would therefore violate either the GPL 
or the license of the other library. Please be aware that includes some open source licenses that are incompatible 
with the GPL for one reason or another, such as the PHP license used for most PEAR packages.

If you wish to contribute a bridge module to a Pimcore Git repository, please do not check in the 3rd party library 
itself. Doing so creates a fork of that 3rd party library, which makes it more difficult to maintain and only serves to 
waste disk space. Instead, provide detailed instructions for users to download and install that 3rd party library for 
use with your module. If you believe that your module is a special case where it really does need to be included in a 
Git repository, usually only because you need to make substantial modifications to it in order for it to work, please 
file an issue with in the Licensing Working Group issue queue first to discuss it.

## Do I have to give the code for my web site to anyone who visits it?

No. The GPL does not consider viewing a web site to count as "distributing", 
so you are not required to share the code running on your server.


## I have a question not answered here. What should I do?
**If you have a question about your specific case, please consult with a copyright attorney in your area.**
**We cannot and will not offer legal advice.**

If you have a general question about Pimcore licensing or other legal issues, 
please post your question in the Pimcore discussion group.


### Credits / License of this FAQ page
This FAQ is based on https://www.drupal.org/licensing/faq (modified)- many thanks to the Drupal Association!  
License: Creative Commons Attribution-ShareAlike license 2.0 (http://creativecommons.org/licenses/by-sa/2.0/)   
