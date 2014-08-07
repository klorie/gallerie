<?php
// Start PHP code
require_once "include.php";
require_once "browser/header.php";
require_once "browser/display.php";

global $BASE_URL;

// Decode parameters of the url
$submode = 0;
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
    } elseif ($mode == 'date') {
        $date_year  = 0;
        if (isset($_GET['year'])) {
            $date_year = $_GET['year'];
            $submode   = 1;
        }
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
} else {
    $m_folder_id = -1;
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'."\n";
echo "<head>\n";
displayHeader($mode, $submode);
echo "</head>\n";
echo "<body>\n";

displaySideMenu($m_folder_id, $m_db);

echo "<div id=\"content\">\n"; 
if ($mode == 'home') echo "<div style=\"text-align: center;\">\n";
echo "<h1><a href=\"index.php\">".htmlentities($gal_title)."</a></h1>\n"; 
if ($mode == 'home') echo "</div>\n";

if ($mode == 'home') {
    // Display top folder grid
    $folderlist = $m_db->getSubFolders(-1);
    echo "<div id=\"container\">\n";
    foreach($folderlist as $folder) {
        $folder_title = htmlentities($m_db->getFolderTitle($folder));
        echo "<div class=\"box\">\n";
        echo "<a href=\"index.php?path=".urlencode($m_db->getFolderPath($folder))."\">";
        echo "<div class=\"home_caption_wrapper\">\n";
        echo "<img src=\"".getThumbnailPath($folder, true)."\" />";
        echo "<div class=\"home_caption\">$folder_title</div></div>";
        echo "</a>\n";
        echo "</div>\n";
    }
    echo "</div>\n";
    echo "<div style=\"text-align: center;\"><h1 id=\"title-text\"></h1></div>\n";
} else if ($mode == 'folder') {
    // Path dirs and link
    displayFolderHierarchy($m_folder_id, $m_db);

    echo "<div id=\"nanoGallery\">\n";
    // Show list of subfolders
    displaySubFolderList($m_folder_id, $m_db);
    // Show list of pictures
    displayElementList($m_folder_id, $m_db);
    echo "</div>\n";
} else if ($mode == 'tags') {
    // Browse by tags
    displayTagSelector($m_db);
    if (isset($_GET['param'])) {
        echo "<div id=\"nanoGallery\">\n";
        displayTagElements($param, $m_db);
        echo "</div>\n";
    }
} elseif ($mode == 'date') {
    // Browse by date
    if ($date_year == 0) {
        echo "<h2>Images par ann&eacute;es</h2>\n";
        echo "<div id=\"nanoGallery\">\n";
        displayYearFolderList($m_db);
    } else {
        echo "<h2>Images pour l'ann&eacute;e $date_year:</h2>\n";
        echo "<div id=\"nanoGallery\">\n";
        displayYearFolders($date_year, $m_db);
    }
    echo "</div>\n";

}
?>

<div class="clearfix"></div> 
</div>
<?php displayFooter(); ?>
</body> 
</html>
