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
$zipname = $m_db->getFolderName($folder_id).'.zip';
if ($zip->open($zipname, ZipArchive::CREATE) !== TRUE)
    die("Failed to create $zipname");

$element = new mediaObject();
foreach($elements_list as $element_id) {
    $m_db->loadMediaObject($element, $element_id);
    $resized = "$BASE_DIR/$resized_folder/".getResizedPath($element_id, $m_db);
    if (!$zip->addFile($resized, $element->filename))
        die("Failed to add $resized");
}
$zip->close();
header('Content-Transfert-Encoding: binary');
header('Content-Disposition: attachement; filename="'.$zipname.'"');
header('Content-Length: '.filesize($zipname));
readfile($zipname);
unlink($zipname);
?>
