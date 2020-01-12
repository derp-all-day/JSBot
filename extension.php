<?php
if(file_exists('extension.zip')) {
  unlink('extension.zip');
}
$files = array('extension/manifest.json', 'extension/server.js');
$zipname = 'extension.zip';
$zip = new ZipArchive;
$zip->open($zipname, ZipArchive::CREATE);
foreach ($files as $file) {
  $zip->addFile($file);
}
$zip->close();
header('Content-Type: application/zip');
header('Content-disposition: attachment; filename='.$zipname);
header('Content-Length: ' . filesize($zipname));
readfile($zipname);
?>
