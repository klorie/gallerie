<?php
// Start PHP code
require_once "include.php";
require_once "common/indexer.php";
require_once "browser/display.php";

set_time_limit(9999);

$gallery_db = new mediaDB();

print "-I-    Step 1. Updating DB\n";
update_database($gallery_db);
print "-I-    Step 2. Cleaning DB\n";
clean_database($gallery_db);
print "-I-    Step 3. Updating Thumbnails\n";
update_thumbnails($gallery_db);
print "-I-    Step 4. Cleaning Thumbnails\n";
clean_thumbnails($gallery_db);
print "-I-    Step 5. Updating Resizeds\n";
update_resized($gallery_db);
print "-I-    Step 6. Cleaning Resizeds\n";
clean_resized($gallery_db);
print "-I-    Step 7. Updating Theme files\n";
updateTopFolderMenuThumbnail($gallery_db);
generateTopFolderStylesheet($gallery_db);
print "-I-    Step 8. Finishing\n";
$gallery_db->updateTimeStamp();
$gallery_db->close();
exit("-I-    Gallery update complete\n");
?>
