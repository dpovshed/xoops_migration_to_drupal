<?php

// Dumping essential info about xoops DB
//

require("x2d_config.php");
// main script

// load config
require("x2d.cfg.php");

// validate config
$config = new Configuration($cfg);

//if (!$config->validate()) die();

$sql_src = new mysqli($cfg['xoops_server'], $cfg['xoops_user'], $cfg['xoops_pass'], $cfg['xoops_db']);
if (mysqli_connect_errno()) { throw new Exception('Cannot connect to source: ' . mysqli_connect_error()); }
$nprefix = $config->data['xoops_prefix'];

if ($result = $sql_src->query("SELECT * FROM " . $nprefix . "users ORDER BY uid")) {
  printf("Number of users: %d.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    printf ("%2d %s\n", $row["uid"], $row["uname"]);
  }
  $result->close();
}

//foreach ($cfg['xoops_topics'] as $tablenam) {
for ($i=0; $i<count($cfg['xoops_topics']); $i++) {
  $topic_table = $cfg['xoops_topics'][$i];
  $story_table = $cfg['xoops_stories'][$i];
  $sql = sprintf("SELECT topicid, %s.topic_title, COUNT(*) AS num_posts FROM `%s` JOIN %s ON %s.topicid =  %s.topic_id GROUP BY `topicid`",
    $topic_table, $story_table, $topic_table, $story_table, $topic_table );
//  $sql = "SELECT topicid, " . $topic_table . ".topic_title, COUNT(*) AS num_posts FROM `" .
//    $story_table . "` JOIN " . $topic_table . " ON " . $story_table . ".topicid =  xoops_topics.topic_id GROUP BY `topicid`"
  printf("\nTables %s and %s:\n", $topic_table, $story_table);
  $result = $sql_src->query($sql);
  printf("Number of terms in vocabulary %d.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    printf ("%2d - %3d item - %s\n", $row["topicid"], $row["num_posts"], $row["topic_title"]);
  }
  $result->close();
  printf("\n");
}

$sql_src->close();
exit();

?>