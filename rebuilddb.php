<?php

require_once "common_db.php";

$folder = new mediaFolder(NULL);

$folder->loadFromPath("");

print_r($folder);

print("=========================================\n");

$media_db = new mediaDB();

$media_db->storeMediaFolder($folder);

$new_folder = new mediaFolder(NULL);

$media_db->loadMediaFolder($new_folder, 1);

print_r($new_folder);

print("=========================================\n");

print("ID OF Photos        = ".$media_db->getMediaFolderID('Photos')."\n");
print("ID OF Activity 2007 = ".$media_db->getMediaFolderID('Activity_2007')."\n");

/*
$media_db = new mediaDB();

$media_obj = new mediaObject(NULL);

$media_obj->loadFromFile('2009_07_11-18_Treguier-138.JPG');

$media_db->storeMediaObject($media_obj);

print_r($media_obj);

$new_obj = new mediaObject(NULL);
$media_db->loadMediaObject($new_obj, 1);

print_r($new_obj);*/
?>
