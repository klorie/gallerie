<?php
// Start PHP code
require_once "include.php";
require_once "browser/display.php";

global $BASE_DIR;
global $BASE_URL;

if (isset($_GET['path']))
    $path = $_GET["path"];
else
    $path = "";
$path = safeDirectory($path);

$m_db        = new mediaDB();
$m_folder_id = $m_db->getFolderID($path);
if ($m_folder_id == -1)
    $m_folder_id = 1; // If path not found, go back to home page
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head> 
<?php echo "<title>".$gal_title."</title>\n"; ?> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen"  />
  <link rel="stylesheet" href="css/prettyPhoto.css" type="text/css" media="screen" /> 
  <link rel="stylesheet" href="css/toplevelmenu.css" type="text/css" media="screen" />
  <link rel="stylesheet" href="css/sidemenu.css" type="text/css" media="screen" />
  <script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>		
  <script src="js/jquery.tools-1.2.5.min.js" type="text/javascript"></script>
<?php
    if ($m_folder_id == 1) {
        echo "  <script src=\"js/jquery.mousewheel.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/cloud-carousel.1.0.4.min.js\" type=\"text/javascript\"></script>\n";
    } else {
        echo "  <script src=\"js/jquery-ui-1.8.11.custom.min.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/vertical.slider.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/flowplayer-3.2.2.min.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/jquery.prettyPhoto.js\" type=\"text/javascript\"></script>\n";
    }
?>
  <script src="js/jquery.imageLoader.js" type="text/javascript"></script>
  <script src="js/navigation.js" type="text/javascript"></script>
  <script type="text/javascript">
    $(document).ready(function(){
      jQuery('.dynamic-thumbnail').loadImages();
      $.tools.tooltip.conf.relative = true;
      $.tools.tooltip.conf.cancelDefault = false;
      $.tools.tooltip.conf.predelay = 1000;
      $.tools.tooltip.conf.offset = [110, 0];
      $(".dynamic-thumbnail").tooltip();
<?php
    if ($m_folder_id == 1) {
        echo "    $(\"#carousel\").CloudCarousel({\n";
        echo "      xPos: 240, yPos: 30, mouseWheel: true,\n";
        echo "      buttonLeft: $(\"#left-button\"), buttonRight: $(\"#right-button\"),\n";
        echo "      titleBox: $(\"#title-text\"),\n";
        echo "      reflHeight: 50, minScale: 0.3\n";
        echo "    });\n";
    } else {
        echo "    $(\"a[rel^='prettyPhoto']\").prettyPhoto({\n";
        echo "      animationSpeed: 'fast',\n";
    	echo "      padding: 30,\n";
	    echo "      opacity: 0.65,\n";
	    echo "      showTitle: true,\n";
	    echo "      allowresize: true,\n";
	    echo "      counter_separator_label: '/',\n";
	    echo "      theme: '$gal_theme'\n";
        echo "    });\n";
        echo "  });\n";
        echo "  $(function() {\n";
        echo "    $(\"a[rel^='#video']\").overlay({\n";
        echo "       expose: '#111',\n";
        echo "       effect: 'apple',\n";
        echo "       onLoad: function(content) {\n";
        echo "          this.getOverlay.find(\"a.player\").flowplayer(0).load();\n";
        echo "       }\n";
        echo "    });\n";
        echo "    $(\"a.player\").flowplayer(\"./swf/flowplayer-3.2.2.swf\", { clip: { scaling: 'fit' } });\n";
    }
    echo "  });\n";
?>
 </script>

<?php
echo "</head>\n";
echo "<body>\n";

// Do not display top level on the home page (redundant)
if ($m_folder_id != 1)
    displayTopFoldersMenu($m_db);

displaySideMenu($m_folder_id, $m_db);

echo "<div id=\"content\">\n"; 
if ($m_folder_id == 1) echo "<div style=\"text-align: center;\">\n";
echo "<h1><a href=\"$BASE_URL/index.php\">".htmlentities($gal_title)."</a></h1>\n"; 
if ($m_folder_id == 1) echo "</div>\n";

if ($m_folder_id == 1) {
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
} else {
    // Path dirs and link
    displayFolderHierarchy($m_folder_id, $m_db);

    echo "<div id=\"scroll-pane\">\n";
    echo "<div id=\"scroll-content\">\n";
    // Show list of subfolders
    displaySubFolderList($m_folder_id, $m_db);

    // Show list of pictures
    displayElementList($m_folder_id, $m_db);
    echo "</div></div>\n";
}
?>

<div class="clearfix"></div> 
</div>
<?php displayFooter(); ?>
</div>
</body> 
</html>
