<?php
// Start PHP code
require_once "include.php";
require_once "browser/header.php";
require_once "browser/display.php";

global $BASE_DIR;
global $BASE_URL;

// Decode parameters of the url
if (isset($_GET['path'])) {
    $mode = 'folder';
    $path = $_GET['path'];
    $path = safeDirectory($path);
} elseif (isset($_GET['browse'])) {
    $mode = $_GET['browse'];
    if ($mode == 'tags') {
        $param = array();
        if (isset($_GET['param']))
            $param = $_GET['param'];
    }
} else {
    $mode = 'home';
    $path = "";
}
$m_db        = new mediaDB();

if ($mode == 'folder') {
    $m_folder_id = $m_db->getFolderID($path);
    if ($m_folder_id == -1)
        $mode = 'home'; // If path not found, go back to home page
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head> 
<?php echo "  <title>".$gal_title."</title>";
displayHeader($mode);
echo "</head>\n";
echo "<body>\n";

// Do not display top level on the home page (redundant)
if ($mode != 'home')
    displayTopFoldersMenu($m_db);

displaySideMenu($m_folder_id, $m_db);

echo "<div id=\"content\">\n"; 
if ($mode == 'home') echo "<div style=\"text-align: center;\">\n";
echo "<h1><a href=\"$BASE_URL/index.php\">".htmlentities($gal_title)."</a></h1>\n"; 
if ($mode == 'home') echo "</div>\n";

if ($mode == 'home') {
    // Display carousel
    $folderlist = $m_db->getSubFolders(1);
    echo "<div id=\"carousel\" style=\"width:480px; height:380px; margin-left:auto; margin-right:auto;\">\n";
    foreach($folderlist as $folder) {
        $folder_title = htmlentities($m_db->getFolderTitle($folder));
        echo "<a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($folder))."\" title=\"$folder_title\">";
        echo "<img class=\"cloudcarousel\" src=\"$BASE_URL/browser/getthumb.php?folder=$folder\" title=\"$folder_title\"  style=\"border: none;\"/>";
        echo "</a>\n";
    }
    echo "</div>\n";
    echo "<input type=\"image\" src=\"$BASE_URL/images/carousel_left.png\" id=\"left-button\" style=\"float: left; margin-top: -200px;\" />";
    echo "<input type=\"image\" src=\"$BASE_URL/images/carousel_right.png\" id=\"right-button\" style=\"float: right; margin-top: -200px;\" />";
    echo "<div style=\"text-align: center;\"><h1 id=\"title-text\"></h1></div>\n";
} else if ($mode == 'folder') {
    // Path dirs and link
    displayFolderHierarchy($m_folder_id, $m_db);

    // Show list of subfolders
    displaySubFolderList($m_folder_id, $m_db);

    // Show list of pictures
    displayElementList($m_folder_id, $m_db);
} else if ($mode == 'tags') {
    // Browse by tags
    displayTagSelector($m_db);
    displayTagElements($param, $m_db);
}
?>

<div class="clearfix"></div> 
</div>
<?php displayFooter(); ?>
</div>
</body> 
</html>
