<?php

/**********************************************************************
 *
 * General processing settings
 *
 * process_files - shall script process files as well
 *   default is FALSE because manual copying is more manageable.
 *   In most cases moving data or establishing a filesystem link
 *   shall be enough.
 *
 *********************************************************************/
$cfg['process_files']    = FALSE;

/**********************************************************************
 * DB settings
 *********************************************************************/
$cfg['xoops_server']   = '127.0.0.1';
$cfg['xoops_db']       = 'xoops_db';
$cfg['xoops_user']     = 'root';
$cfg['xoops_pass']     = 'root-pass';
$cfg['xoops_prefix']   = 'xoops';

// please note - you do not need to enter a Drupal DB info,
// because it is already exists in Drupal configuration file.


/**********************************************************************
 * Migration tuning
 *********************************************************************/
// In case if you have several tables with entries and categories,
// select what exactly shall be imported.
//
// Default is
// $cfg['xoops_topics'] = '_topics';
// $cfg['xoops_stories'] ='_stories';
$cfg['xoops_topics']      = 'xoops_topics';
$cfg['xoops_stories']     = 'xoops_stories';

// data will be imported into specific content type
// select content type name here.
// you can find a list of available contenttypes in Drupal by
//   - visiting Drupal URL /admin/content/types;
//   - running script x2d_xinfo.php;
$cfg['contenttype_name'] = 'blog';

// xoops categories - called 'topics' there - will became another
// Drupal taxonomy. Please provide vocabulary ID here
$cfg['taxonomy_cat']     = 1;

/**********************************************************************
 *
 * Files section
 *
 * xoops_media_dirs
 * drupal_media_dirs - data directories in OS-depending format.
 *   A must if files processing is enabled, ignored otherwise.
 *   If enabled, please ensure you have the same number of entries
 *   in both arrays.
 * Please ensure that destination (Drupal) directories are exist and
 *   writeable.
 *
 *********************************************************************/

$cfg['xoops_media_dirs'] = array(
    '/Apache/htdocs/xoopssite/images',
    '/Apache/htdocs/xoopssite/img',
    '/Apache/htdocs/xoopssite/uploads',
  );
$cfg['drupal_media_dirs']  = array(
//    '/Apache/htdocs/xoops_d6/sites/default/files/images',
//    '/Apache/htdocs/xoops_d6/sites/default/files/img',
//    '/Apache/htdocs/xoops_d6/sites/default/files/uploads',
    '/Apache/htdocs/xoops_d6/images',
    '/Apache/htdocs/xoops_d6/img',
    '/Apache/htdocs/xoops_d6/uploads',
  );


/**********************************************************************
 *
 * Files URL section
 *
 * If you are relocating files and/or changing website address, use
 * parameters below. This will replace all URL used in your stories to new
 * locations.
 *
 * If you do not need this because files are in the same places (RECOMMENDED),
 * just set those two arrays to same value.
 *
 *
 *********************************************************************/

$cfg['xoops_media_dirs_http'] = array(
    'http://www.example.com/images',
    'http://www.example.com/img',
    'http://www.example.com/uploads',
  );

$cfg['drupal_media_dirs_http'] = array(
//    'http://www.example.com/sites/default/files/images',
//    'http://www.example.com/sites/default/files/img',
//    'http://www.example.com/sites/default/files/uploads',
    'http://www.example.com/images',
    'http://www.example.com/img',
    'http://www.example.com/uploads',
  );

/////////////////////////////////////////////////////////////
// Sample for copy site to 127.0.0.1 WITH files relocation //
/////////////////////////////////////////////////////////////
/*$cfg['process_files']    = TRUE;
$cfg['drupal_media_dirs_http'] = array(
    'http://127.0.0.1/xoops_d6/sites/default/files/images',
    'http://127.0.0.1/xoops_d6/sites/default/files/img',
    'http://127.0.0.1/xoops_d6/sites/default/files/uploads',
  );
$cfg['drupal_media_dirs']  = array(
    '/Apache/htdocs/xoops_d6/sites/default/files/images',
    '/Apache/htdocs/xoops_d6/sites/default/files/img',
    '/Apache/htdocs/xoops_d6/sites/default/files/uploads',
  );
*/


////////////////////////////////////////////////////////////////////
// If author of post was deleted, post will belong to author
// stated below (case sensitive).
// Usernames are in field 'uname' in Xoops table 'users'.
// If not set or set up incorrectly - orphan post will belong to
// the user 'Anonymous', id = 0.
/////////////////////////////////////////////////////////////////////
//$cfg['author_for_orphan_posts'] = 'admin';

?>
