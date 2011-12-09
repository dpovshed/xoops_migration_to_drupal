<?php

// get top-level terms for a given vocabulary ID
// $terms[] looks like '3' => 'Horror'
// $terms_reverted[] looks like 'Horror' => 3
function load_drupal_tax($vocabulary_id, &$terms, &$terms_reverted) {
  $items = taxonomy_get_tree($vocabulary_id);
  foreach ($items as $item) {
    if ($item->parents[0] != 0) {
      // skip non-top level items
      continue;
    }
    $terms[$item->tid] = $item->name;
    $terms_reverted[$item->name] = $item->tid;
  }
}

// returns an array of malformed nodes
function check_nodes() {
  $bad_nodes = array();
  $result = db_query('SELECT * FROM {node} WHERE vid = 0');
  while ($entry = $result->fetch_assoc()) {
    $bad_nodes[] = $entry['nid'];
  }
  return $bad_nodes;
}

// malformed node cannot be deleted by standard way
// that's the reason why we need this - clean up after failures
// parameter shall be an array
function erase_nodes($nodes) {
  if (!is_array($nodes) ) return;
  if (!count($nodes) ) return;
  db_query("DELETE FROM {node} WHERE nid IN (" . implode(',', $nodes) . ")");
  db_query("DELETE FROM {node_revisions} WHERE nid IN (" . implode(',', $nodes) . ")");
  db_query("DELETE FROM {comments} WHERE nid IN (" . implode(',', $nodes) . ")");
}


?>