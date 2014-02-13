<?php
require_once "../include.php";

if (isset($_GET['id']))
    $folder_id = $_GET['id'];
else {
    die('Folder ID is required !');
}

global $resized_folder;

$m_db          = new mediaDB();
$elements_list = $m_db->getFolderElements($folder_id);
    
$zip     = new ZipArchive();
$zipname = '/tmp/'.$m_db->getFolderName($folder_id).'.zip';
if ($zip->open($zipname, ZIPARCHIVE::CREATE) !== TRUE)
    die("Failed to create $zipname");

$element = new mediaObject();
foreach($elements_list as $element_id) {
    $m_db->loadMediaObject($element, $element_id);
    $resized = "$BASE_DIR/".getResizedPath($element_id, $m_db);
    if (is_readable($resized))
        if (!$zip->addFile($resized, $element->filename))
            die("Failed to add $resized");
}
if ($zip->close() !== TRUE)
    die("Failed to write $zipname:".$zip->getStatusString());

header('Content-Transfert-Encoding: application/zip');
header('Content-Disposition: attachement; filename="'.$zipname.'"');
header('Content-Length: '.filesize($zipname));
readfile($zipname);
unlink($zipname);
?>
