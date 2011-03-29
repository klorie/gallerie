<?php

require_once "common_browser.php";

echo "<html xmlns=\"http://www.w3.org/1999/xthml\" xml:lang=\"en\">\n";
echo "<link rel=\"stylesheet\" href=\"css/layout.css\" type=\"text/css\" media=\"screen\" />\n";
echo "<link type=\"text/css\" href=\"css/jquery-ui-1.8.11.custom.css\" rel=\"stylesheet\" />\n";	
echo "<script src=\"js/jquery-1.5.1.min.js\" type=\"text/javascript\"></script>\n";
echo "<script src=\"js/jquery-ui-1.8.11.custom.min.js\" type=\"text/javascript\"></script>\n";
echo "<script src=\"js/update.js\" type=\"text/javascript\"></script>\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
echo "</head>\n";
echo "<body>\n";
echo "<div id=\"content\">\n"; 

@session_start();
$_SESSION['status']   = "";
$_SESSION['progress'] = 0;

echo "<h1>Gallerie Administration</h1>\n";

echo "<h3><a href=\"#\" id=\"update_db\">Update Gallerie Database</a></h3>\n";
echo "<div id=\"update_db_progress\"></div>\n";
echo "<div id=\"update_db_status\"></div>\n";

echo "<h3><a href=\"#\" id=\"update_theme\">Update Gallerie Theme</a></h3>\n";
echo "<div id=\"update_theme_progress\"></div>\n";
echo "<div id=\"update_theme_status\"></div>\n";

echo "<h3><a href=\"#\" id=\"update_thumb\">Update Thumbnails and Resized pictures</a></h3>\n";
echo "<div id=\"update_thumb_progress\"></div>\n";
echo "<div id=\"update_thumb_status\"></div>\n";

echo "<h3><a href=\"#\" id=\"clean_thumb\">Clean Thumbnails and Resized pictures</a></h3>\n";
echo "<div id=\"clean_thumb_progress\"></div>\n";
echo "<div id=\"clean_thumb_status\"></div>\n";

echo "<h3><a href=\"#\" id=\"clear_thumb\">Clear Thumbnails and Resized pictures</a></h3>\n";
echo "<div id=\"clear_thumb_progress\"></div>\n";
echo "<div id=\"clear_thumb_status\"></div>\n";

echo "<h3><a href=\"#\" id=\"clear_cache\">Clear HTML cache</a></h3>\n";
echo "<div id=\"clear_cache_progress\"></div>\n";
echo "<div id=\"clear_cache_status\"></div>\n";

echo "<h3>Debug</h3>\n";
echo "BASE_DIR = ".baseDir()."<br />\n";
echo "BASE_URL = ".baseURL()."<br />\n";

echo "<h3><a href=\"index.php\">Back to Gallerie</a></h3>\n";

echo "</div>\n";

displayFooter();

echo "</body>\n";
echo "</html>\n";
?>
