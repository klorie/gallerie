<?php

$NEW_BASE_DIR = dirname($_SERVER['SCRIPT_FILENAME']);
$NEW_BASE_URL = "http://".$_SERVER['SERVER_NAME'].rtrim(dirname($_SERVER['PHP_SELF']), "/");
$new_config  = '<?php'."\n";
$new_config .= '// Generated File ! Do not edit !'."\n";
$new_config .= '$BASE_DIR = "'.$NEW_BASE_DIR."\";\n";
$new_config .= '$BASE_URL = "'.$NEW_BASE_URL."\";\n";
$new_config .= '?>'."\n";
file_put_contents('config.local.php', $new_config);

require_once "include.php";
require_once "browser/display.php";

echo "<html xmlns=\"http://www.w3.org/1999/xthml\" xml:lang=\"en\">\n";
echo "<link rel=\"stylesheet\" href=\"css/layout.css\" type=\"text/css\" media=\"screen\" />\n";
echo "<link type=\"text/css\" href=\"css/jquery-ui-1.8.11.custom.css\" rel=\"stylesheet\" />\n";	
echo "<script src=\"http://code.jquery.com/jquery-1.7.2.min.js\"></script>\n";
echo "<script src=\"js/jquery-ui-1.8.11.custom.min.js\"></script>\n";
echo "<script src=\"js/update.js\"></script>\n";
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

echo "<h3>Debug</h3>\n";
echo "BASE_DIR = $NEW_BASE_DIR <br />\n";
echo "BASE_URL = $NEW_BASE_URL <br />\n";

echo "<h3><a href=\"index.php\">Back to Gallerie</a></h3>\n";

echo "</div>\n";

displayFooter();

echo "</body>\n";
echo "</html>\n";
?>
