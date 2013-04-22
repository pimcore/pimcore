P     H     I     N     G
=========================

  [![Build Status](https://secure.travis-ci.org/phingofficial/phing.png)](http://travis-ci.org/phingofficial/phing)

  (PH)ing (I)s (N)ot (G)NU make; it's a PHP project build system or build
  tool based on Apache Ant. You can do anything with it that you could do
  with a traditional build system like GNU make, and its use of simple XML
  build files and extensible PHP "task" classes make it an easy-to-use and
  highly flexible build framework.

  Features include running PHPUnit and SimpleTest unit tests (including test
  result and coverage reports), file transformations (e.g. token replacement,
  XSLT transformation, Smarty template transformations),
  file system operations, interactive build support, SQL execution,
  CVS/SVN/GIT operations, tools for creating PEAR packages, documentation
  generation (DocBlox, PhpDocumentor) and much, much more. 

  If you find yourself writing custom scripts to handle the packaging,
  deploying, or testing of your applications, then we suggest looking at Phing.
  Phing comes packaged with numerous out-of-the-box operation modules (tasks),
  and an easy-to-use OO model to extend or add your own custom tasks.

  Phing provides the following features:

  * Simple XML buildfiles
  * Rich set of provided tasks
  * Easily extendable via PHP classes
  * Platform-independent: works on UNIX, Windows, Mac OSX
  * No required external dependencies
  * Built for PHP5 

The Latest Version
------------------

  Details of the latest version can be found on the Phing homepage
  <http://www.phing.info/>.

Installation
------------

  The preferred method to install Phing is through PEAR and the Phing PEAR
  channel. You can install Phing by adding the pear.phing.info channel
  to your PEAR environment and then installing Phing using the *phing*
  channel alias and *phing* package name: 

    $> pear channel-discover pear.phing.info
    $> pear install [--alldeps] phing/phing

Documentation
-------------

  Documentation is available in HTML format in the *docs* directory. In particular,
  open the *docs/phing_guide/book/index.html* in a browser to see the
  Phing User Guide.

  For online documentation, you can also visit the Phing website: http://www.phing.info/

Licensing
---------

  This software is licensed under the terms you may find in the file
  named "LICENSE" in this directory.

  Thank you for using PHING!
