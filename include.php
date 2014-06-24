<?php
// Global variables static in project
$db_schema_version   = '1.0';
$gallery_release_tag = '3.0.0';
require_once "config.php";
@include     "export.php";
require_once "common/tools.php";
require_once "common/media.php";
require_once "common/database.php";
require_once "common/googlemaps.php";
require_once "common/thumbnail.php";
require_once "common/resized.php";
?>
