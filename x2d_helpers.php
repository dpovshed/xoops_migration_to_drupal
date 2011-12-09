<?php

function recurse_copy($src, $dst) {
  $dir = opendir($src);
  @mkdir($dst);
  while(false !== ( $file = readdir($dir)) ) {
    if (($file == '.' ) || ( $file == '..' )) {
      continue;
    }
    if (is_dir($src . '/' . $file)) {
      recurse_copy($src . '/' . $file, $dst . '/' . $file);
    }
    else {
      copy($src . '/' . $file, $dst . '/' . $file);
      printf('.');
    }
  }
  closedir($dir);
}


// see, for example,
// http://mark.boyden.name/smartsection.item.55/xoops-codes.html
function parse_xoops_markup($data) {
  $xoops = array("/\[b\]/i", "/\[\/b\]/i", "/\[i\]/i", "/\[\/i\]/i", "/\[d\]/i", "/\[\/d\]/i", "/\[u\]/i", "/\[\/u\]/i",
    "/\[url=(.+)\](.+)\[\/url\]/i",
    "/\[pagebreak\]/i",
    "/\[img(.*)\](.+)\[\/img\]/i",
  );
  
  $html =  array("<b>",      "</b>",     "<i>",      "</i>",     "<del>",      "</del>", "<u>",      "</u>",
    "<a href=\"$1\" target=\"_blank\">$2</a>",
    "<br style=\"page-break-before:always\"/>",
    "<img src=\"$2\"$1>",
  );
  
  $data = preg_replace($xoops, $html, $data);

  return $data;
}

function adjust_own_media($data, $cfg, &$counter) {
  $replacements = 0;
  $result = str_replace($cfg['xoops_media_dirs_http'], $cfg['drupal_media_dirs_http'], $data, $replacements);
  $counter += $replacements;
  return $result;
}

?>
