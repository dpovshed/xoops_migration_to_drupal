<?php

class Configuration {
  var $data;

  function Configuration($cfg) {
    if (!isset($cfg['xoops_prefix'])) {
      $cfg['xoops_prefix'] = '';
    }
    $this->data = $cfg;

    // shall we change media URLs?
    if ($cfg['xoops_media_dirs_http'] !== $cfg['drupal_media_dirs_http']) {
      $this->process_media_url = TRUE;
    }
    else {
      $this->process_media_url = FALSE;
    }
  }

  // accessors
  function isFiles()   { return $this->data['process_files']; }

  function validate() {
    try {
      // other logic checks
      // ....

      // check DB connection. Both DB must be available even if we not committing result
      $sql_src = new mysqli($this->data['xoops_server'], $this->data['xoops_user'], $this->data['xoops_pass'], $this->data['xoops_db']);
      if (mysqli_connect_errno()) { throw new Exception('Cannot connect to source: ' . mysqli_connect_error()); }

      if (!module_exists('taxonomy')) { throw new Exception('Taxonomy support is not enabled'); }

      $types = node_get_types();
      $passed = isset($types[$this->data['contenttype_name']]);
      if (!$passed) { throw new Exception('Drupal - not exist selected content type: ' . $this->data['contenttype_name']); }

      $vocabularies = taxonomy_get_vocabularies();

      $passed = isset($vocabularies[$this->data['taxonomy_cat']]);
      if (!$passed) { throw new Exception('Drupal - Vocabulary for tagging not exist, ID: ' . $this->data['taxonomy_cat']); }

      // check for existence
      $result = $sql_src->query("SELECT * FROM " . $this->data['xoops_topics'] );
      if (!$result) {
        throw new Exception($sql_src->error);
      }
      $result->close();
      $result = $sql_src->query("SELECT * FROM " . $this->data['xoops_stories'] );
      if (!$result) {
        throw new Exception($sql_src->error);
      }
      $result->close();

      if ($this->data['process_files']) {
        if (count($this->data['xoops_media_dirs']) != count($this->data['drupal_media_dirs'])) {
          throw new Exception('Number of media directories mismatch: ' .
            count($this->data['xoops_media_dirs']) . '/' . count($this->data['drupal_media_dirs']));
        }
      }

      foreach ($this->data['xoops_media_dirs'] as $dirname) {
        if (!is_dir($dirname)) {
          throw new Exception('xoops - media directory inaccessible: ' . $dirname);
        }
      }
      foreach ($this->data['drupal_media_dirs'] as $dirname) {
        if (!is_dir($dirname)) {
          throw new Exception('Drupal - media directory inaccessible: ' . $dirname .
            ' . Please ensure that directory exists and writeable.');
        }
      }
    }
    catch(Exception $e) {
      printf('Configuration error: ' . $e->getMessage());
      return FALSE;
    }
    $sql_src->close();
    return TRUE;
  }
};


?>