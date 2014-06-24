#!/usr/bin/php
<?php

// Start PHP code
require_once "include.php";
require_once "common/indexer.php";
require_once "browser/display.php";

$gallery_db = new mediaDB();

print "-I-    Step 1. Updating DB\n";
update_database($gallery_db);
print "-I-    Step 2. Cleaning DB\n";
clean_database($gallery_db);
clean_tags($gallery_db);
print "-I-    Step 3. Updating Thumbnails\n";
update_thumbnails($gallery_db);
print "-I-    Step 4. Cleaning Thumbnails\n";
clean_thumbnails($gallery_db);
print "-I-    Step 5. Updating Resizeds\n";
update_resized($gallery_db);
print "-I-    Step 6. Cleaning Resizeds\n";
clean_resized($gallery_db);
print "-I-    Step 7. Finishing\n";
$gallery_db->updateTimeStamp();
$gallery_db->close();
print "-I-    Gallery update complete\n";

// Export a static version into the export folder
if (($argc == 2) && ($argv[1] == '--export')) {
    print "-I-    Exporting static version of the gallery in ./export\n";
    if (is_dir('export') == false)
        mkdir('export');
    // Activate $disable_dynamic
    file_put_contents('export.php', '<?php $disable_dynamic = true; ?>');
    // run export
    exec("wget -q -P export -k -E -r -l 10 -p -N -F -nH -X */$image_folder $BASE_URL");
    unlink('export.php');
    print "-I-    Export complete\n";
}
exit(0);
?>
