XOOPS to Drupal migration script
================================

Purpose of this toolkit is to assist you in migrate Xoops blog to Drupal CMS.
However there are other various approaches to do the job, idea is to use simple
commandline script.

Written and tested for versions: Xoops 1.x(?) (http://xoops.org) and Drupal 6.15 (http://drupal.org).


Environment
-----------

Apache/2.2.9 + PHP/5.2.6 + MySQL/5.0.67

The files
----------

- x2d.cfg.php      - configuration file
- x2d.php          - main conversion script
- x2d_dinfo.php    - gather key info about destination Drupal DB, readonly acess.
- x2d_xinfo.php    - gather key info about source Xoops DB, readonly acess.


Usage
-----

- prepare a Drupal installation in standard way with http://drupal.org;
- place x2d files into the root of Drupal folder;
- configure x2d.cfg.php;
- make Drupal DB backup (optional);
- copy source site media files to Drupal destination directory
  manually (recommended) if you didn't enabled this option in config file;
- start x2d.php in commandline by running "php x2d.php"


Contacts
--------

This script was originally made by Dennis Povshedny (http://drupal.org/user/117896)
by request of http://weblab.tk .

Known bugs and limitations
-------------------------

- Please make sure that source and destination databases are in UTF-8.
- Modification of Drupal DB is made mostly by Drupal API.
   Small disadvantage of this aproach is that import cannot be fully
   enclosed in transaction. So please backup Drupal SQL data just in case
   before processing, if DB already contains useful info (as a result of
   previous imports, for example)


Changelog
---------

20-Apr-2010 v1.1 Fix processing posts without authors

13-Mar-2010 v1.01 Fix processing [img] tag in contents

21-Feb-2010 v1.0 Initial release

Ideas and todos
---------------
