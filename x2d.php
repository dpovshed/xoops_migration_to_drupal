<?php

// main script v 1.1
// Importing xoops 1.x to Drupal 6.15

$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require("x2d_config.php");
require("x2d_drupal_helpers.php");
require("x2d_helpers.php");

// load config
require("x2d.cfg.php");

file_put_contents('errors.log', sprintf("\n*********************\n%s started\n", date('d, H:i:s ')), FILE_APPEND);

// validate config
$config = new Configuration($cfg);

if (!$config->validate()) die();

db_query('SET NAMES "utf8"');
db_query('SET CHARACTER SET "utf8"');


///////////////////////////////////////////////
// xoops core data storage
$xusers        = array();
$xcat          = array();
$xcat_reverted = array();
$xfailed       = array();

///////////////////////////////////////////////
// Drupal core data storage
$dusers        = array();
$dtax          = array();
$dtax_reverted = array();

///////////////////////////////////////////////
// Conversion dictionaries
$xcat_dtax     = array(); // xoops category id => drupal term id
$xstory_dnode   = array(); // xoops item id => drupal node id

// establish connection to source
$sql_src = new mysqli($config->data['xoops_server'], $config->data['xoops_user'], $config->data['xoops_pass'], $config->data['xoops_db']);if (mysqli_connect_errno()) { throw new Exception('Cannot connect to source: ' . mysqli_connect_error()); }
$xprefix = $config->data['xoops_prefix'];

$sql_src->query('SET NAMES "utf8"');
$sql_src->query('SET CHARACTER SET "utf8"');

// get Drupal users
$result = db_query('SELECT uid, name FROM {users} WHERE uid>0 ORDER BY uid');
while ($u = db_fetch_object($result)) {
  //printf("%2d %s\n", $u->uid, $u->name);
  $dusers[$u->uid] = $u->name;
}
$dusers_reverted = array_flip($dusers);
printf("Step 1/4 - users. Drupal DB have %d users\n", count($dusers));

// Migrate users
$added_users = 0;
if ($result = $sql_src->query("SELECT * FROM " . $xprefix . "_users ORDER BY uid")) {
  printf("Select returned %d rows.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    $uid = $row["uid"];
    $xname = $row["uname"];
    printf("%2d %s ", $uid, $xname);
    $xusers[$uid] = (object) array('name' => $xname, 'uid' => $uid);
    // compare with Drupal users
    if (isset($dusers_reverted[$xname])) {
      if ($dusers_reverted[$xname] == $uid) {
        printf("- user already exists with the same id\n");
        continue;
      }
    }
    db_query("DELETE FROM {users} WHERE name = '%s'", $xname);
    db_query("DELETE FROM {users} WHERE uid = %d", $uid);

    $new_user = /*(object)*/ array('uid' => $uid, 'name' => $xname, /*'pass' => $row["pass"], */
      'mail' => $row["email"], 'init' => $row["email"], 'status' => 1,
      'signature' => parse_xoops_markup($row["user_sig"]),
      'created' => $row["user_regdate"], 'access' => $row["last_login"],
      'login' => $row["last_login"], 'timezone' => 3600*$row["timezone_offset"]);

    $adding = user_save('', $new_user);
    // unfortunatelly we cannot rely on user_save result, so lets recheck

    $checksql = sprintf("SELECT * FROM {users} WHERE `uid` = %d AND `name` = '%s'",
      $new_user['uid'], $new_user['name']);
    if ($result_check = db_query($checksql)) {
      if (db_fetch_array($result_check)) {
        $adding = TRUE;
      }
    }

    if (!$adding) {
      var_dump($new_user);
      die("Fatal: cannot add user " . $xname);
    }
    // patch the password to make it the same as in xoops
    db_query("UPDATE {users} SET pass = '%s' WHERE uid = %d", $row["pass"], $uid);
    printf("- ADDED Drupal user %s with user ID %d\n", $xname, $uid);
    $dusers_reverted[$xname] = $uid;
    ++$added_users;
  }

  /* free result set */
  $result->close();
}

printf("Added users: %d\n", $added_users);

////////////////////////////////////////////////////////////////////////////////////

$sql = sprintf("SELECT topic_id, topic_title FROM `%s`", $config->data['xoops_topics']);

$result = $sql_src->query($sql);
while ($row = $result->fetch_assoc()) {
  $cid = $row["topic_id"];
  $cname = $row["topic_title"];
  $xcat[$cid] = $cname;
  $xcat_reverted[$cname] = $cid;
}

load_drupal_tax($config->data['taxonomy_cat'], $dtax, $dtax_reverted);
printf("Step 2/4 - Topics to Taxonomy Vocabulary migration. Xoops/Drupal entries: %d/%d\n", count($xcat), count($dtax));

/*var_dump($xcat); print("\n");
var_dump($xcat_reverted); print("\n");
var_dump($dtax); print("\n");
var_dump($dtax_reverted); print("\n");*/

$added_cats = 0;
foreach($xcat as $cid => $cname) {
  if (isset($dtax_reverted[$cname])) {
    continue;
  }
  $term_new = array('vid' => $config->data['taxonomy_cat'], 'name' => $cname);
  taxonomy_save_term($term_new);
  $tid = db_last_insert_id('term_data', 'tid');
  $added_cats++;
  $dtax[$tid] = $cname;
  $dtax_reverted[$cname] = $tid;
}
printf("Migration dictionary prepared. Added terms: %d\n", $added_cats);

foreach($xcat as $cid => $cname) {
  $xcat_dtax[$cid] = $dtax_reverted[$cname];
}

//var_dump($xcat_dtax);

////////////////////////////////////////////////////////////////////////////////////

if (!($result = $sql_src->query("SELECT * FROM " . $config->data['xoops_stories'] ))) {
  die("Fatal: cannot get Xoops posts.");
}

printf("Step 3/4 - stories migration. " . $result->num_rows . " stories will be added.\n");

// New author for 'orphan' stories (when author deleted)
$author_for_orphans_id = 0; // Anonymous is a default
if (isset($cfg['author_for_orphan_posts'])) {
  if (in_array($cfg['author_for_orphan_posts'], $dusers)) {
    $author_for_orphans_id = $dusers_reverted[$cfg['author_for_orphan_posts']];
  }
}
$author_for_orphans_name = $author_for_orphans_id ? $cfg['author_for_orphan_posts'] : 'Anonymous';

while ($story = $result->fetch_assoc()) {
  // combine teaser and body
  if (trim($story['hometext']) != "") {
    $teaser = $story['hometext'];
    $body = $teaser . "<br /><br />" . $story['bodytext'];
  } else {
     $teaser = '';
     $body = $story['bodytext'];
  }

  // make pictures manageable - convert media, if any
  $teaser = parse_xoops_markup($teaser);
  $body   = parse_xoops_markup($body);

  $urls_adjusted = 0;
  if ($config->process_media_url) {
    $teaser = adjust_own_media($teaser, $config->data, $urls_adjusted);
    $body = adjust_own_media($body, $config->data, $urls_adjusted);
  }

  $time_created = $story['created'];
  $time_published = $story['published'];
  if ($time_published <=0 ) {
    // Thats means it is Draft in xoops, but Drupal prefer to have valid time there
    $time_created = time();
  }
  if (!in_array($story['uid'], $dusers_reverted)) {
    $uid = $author_for_orphans_id;
    $is_orphan = TRUE;
  }
  else {
    $uid = $story['uid'];
    $is_orphan = FALSE;
  }
  $new_node = array(
    'nid'     => NULL,
    'vid'     => NULL,
    'uid'     => $uid,
    'name'    => $dusers[$story['uid']],
    'created' => $time_created,
    'changed' => $time_published ? $time_published : $time_created,
    'title'   => trim(strip_tags($story['title'])),
    'body'    => $body,
    'format'  => 2, // full html
    'comment' => 0, // disabled
    'status'  => (($time_published == 0) || ($story['expired'])) ? 0 : 1,
    'promote' => 0,
    'sticky'  => 0,
    'taxonomy'=> array(),
  );

  // retrieve Drupal taxonomy term for xoops category
  $term_id = $xcat_dtax[$story['topicid']];
  $tagger = array();
  $tagger[$config->data['taxonomy_cat']] = $dtax[$term_id];

  $new_node['type'] = $config->data['contenttype_name'];
  $new_node['taxonomy']['tags'] = $tagger;

  $new_node_obj = (object) $new_node;
  node_save($new_node_obj);
  if (!$new_node) {
    die("Fatal: cannot add a node " . $new_node['title']);
  }
  $new_id = $new_node_obj->nid;
  if (!$new_id) {
    printf( 'Error: cannot process Xoops post #' . $story['storyid'] . "\n");
    file_put_contents('errors.log', 'Cannot process Xoops post #' . $story['storyid'] . "\n", FILE_APPEND);
    $xfailed[$story['storyid']] = $story['storyid'];
    continue;
  }
  $xstory_dnode[$story['storyid']] = $new_id;
  db_query("UPDATE {node} SET changed = '%d' WHERE nid = %d", $time_created, $new_id);
  db_query("UPDATE {node_revisions} SET timestamp = '%d' WHERE nid = %d", $time_created, $new_id);
  printf("Xoops story %d imported into Drupal as node %d", $story['storyid'], $new_id);
  if ($urls_adjusted) {
    printf(" (%d media URLs adjusted)", $urls_adjusted);
  }
  if ($is_orphan) {
    printf(" (orphan story, reparented to %s)", $author_for_orphans_name);
  }
  printf("\n");
}

$sql_src->close();

////////////////////////////////////////////////////////////////////////////////////

if ($config->isFiles()) {
  try {
    printf("\nStep 4/4 - copying files.");
    for ($i=0; $i<count($config->data['xoops_media_dirs']); $i++) {
      $xdir = $config->data['xoops_media_dirs'][$i];
      $ddir = $config->data['drupal_media_dirs'][$i];
      if ($xdir != $ddir) {
        printf("%s => %s\n", $xdir, $ddir);
        recurse_copy($xdir, $ddir);
      }
    }
  }
  catch(Exception $e) {
    printf('Media data copying error: ' . $e->getMessage());
    // if something goes wrong with files - do not need to modify SQL at all
    die('Processing terminated.');
  }
  printf("\nCopying files done.\n");
}
else {
  printf("\nStep 4/4 - copying files - skipped.\n");
}

$bad_nodes = check_nodes();
if (count($bad_nodes)) {
  file_put_contents('errors.log', 'Malformed nodes found, erasing: ' . implode(', ', $bad_nodes) . "\n", FILE_APPEND);
  printf("Clean up malformed nodes...\n");
  erase_nodes($bad_nodes);
}

exit();

?>