<?php

// Dumping essential info about Drupal 6.x database
//

$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

printf("=== x2d package: Drupal DB overview\n", count($types));

$types = array_keys(node_get_types());
printf("\n=== Registered node types: %d\n", count($types));
asort($types);
foreach ($types as $type) {
  printf($type . "\n");
}

$is_taxonomy = module_exists('taxonomy');
printf("\n=== Taxonomy support: %s\n", $is_taxonomy ? 'enabled' : 'NOT ENABLED');

if ($is_taxonomy) {
  $vocabularies = taxonomy_get_vocabularies();
  printf("\n=== Vocabularies in system: %d\n", count($vocabularies));
  ksort($vocabularies);
  foreach ($vocabularies as $voc) {
    printf("%2d %s:", $voc->vid, $voc->name);
    $terms = taxonomy_get_tree($voc->vid);
    foreach ($terms as $term) {
      if ($term->parents[0] != 0) {
        // skip non-first level items
        continue;
      }
      printf(" (%d, %s)", $term->tid, $term->name);
    }
    printf("\n");
  }
}

printf("\n=== Users:\n");
$result = db_query('SELECT * FROM {users} WHERE uid>0 ORDER BY uid');
while ($u = db_fetch_object($result)) {
  printf("%2d %s\n", $u->uid, $u->name);
}

$num_nodes = db_result(db_query('SELECT COUNT(*) FROM {node}'));
printf("\n=== Nodes: %d\n", $num_nodes);
if ($num_nodes) {
  printf(" ID Comments Title\n");
  $query = 'SELECT * FROM {node} ORDER BY nid';
  $result = db_query($query);
  while ($n = db_fetch_object($result)) {
    $nobj = node_load($n->nid);
    printf("%3d %8d %s\n", $nobj->nid, $nobj->comment_count, $nobj->title);
  }
}

exit();

?>