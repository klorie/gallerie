<?php
// Start PHP code
require_once "common_browser.php";
require_once "googlemaps.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

if (isset($_GET['path']))
    $path = $_GET["path"];
else
    $path = "";
$path = safeDirectory($path);

$cache = "$cache_folder/$path/index.html";
if (file_exists($cache))
    $cache_time = filemtime($cache);
else
    $cache_time = false;

if ($cache_time !== false && 
    $enable_cache == true &&
    $cache_time > filemtime("./config.php")) {
  readfile($cache);
} else {
  ob_start();
  $m_db        = new mediaDB();
  $m_folder_id = $m_db->getFolderID($path);
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
  <script src="js/jquery.tools-1.2.1.min.js" type="text/javascript"></script>
  <script src="js/flowplayer-3.2.2.min.js" type="text/javascript"></script>
  <script src="js/jquery.prettyPhoto.js" type="text/javascript"></script> 
  <script src="js/jquery.imageLoader.js" type="text/javascript"></script>
  <script src="js/navigation.js" type="text/javascript"></script>
  <script src="http://lite.piclens.com/current/piclens_optimized.js" type="text/javascript"></script>
  <script type="text/javascript">
    $(document).ready(function(){
      jQuery('.dynamic-thumbnail').loadImages();
      $.tools.tooltip.conf.relative = true;
      $.tools.tooltip.conf.cancelDefault = false;
      $.tools.tooltip.conf.predelay = 1000;
      $(".dynamic-thumbnail").tooltip();
      $("ul.tabs").tabs("ul.gallery");
      $("a[rel^='prettyPhoto']").prettyPhoto({
        animationSpeed: 'fast',
    	padding: 30,
	    opacity: 0.65,
	    showTitle: true,
	    allowresize: true,
	    counter_separator_label: '/',
	    theme: '<?php echo $gal_theme; ?>' 
      });
    });
    $(function() {
      $("a[rel^='#video']").overlay({
         expose: '#111',
         effect: 'apple',
         onLoad: function(content) {
            this.getOverlay.find("a.player").flowplayer(0).load();
         }
      });
      $("a.player").flowplayer("./swf/flowplayer-3.2.2.swf", { clip: { scaling: 'fit' } });
    });
 </script>

<?php
// Issue Cooliris header
echo "  <link rel=\"alternate\" href=\"".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"].'/.')."/photos.rss.php?id=$m_folder_id\" type=\"application/rss+xml\" title=\"\" id=\"gallery_bis\" />\n";
echo "</head>\n";
echo "<body>\n";

displayTopFoldersMenu($m_db);
displaySideMenu($m_folder_id, $m_db);

echo "<div id=\"content\">\n"; 
echo "<h1>".htmlentities($gal_title)."</h1>\n"; 

// Path dirs and link
displayFolderHierarchy($m_folder_id, $m_db);

// Show list of subfolders
displaySubFolderList($m_folder_id, $m_db);

// Show list of pictures
displayElementList($m_folder_id, $m_db);

// Show a link to upper folder
if ($path != "") {
    $pathArr = explode("/", $path, -1);
    $link = implode("/", $pathArr);
    if ($link == "") {
        // we're already in $baseDir, so skip the file
        $back_link = $_SERVER["PHP_SELF"];
    } else {
        $back_link = $_SERVER["PHP_SELF"]."?path=".$link;
    }
    echo "<a href=\"".$back_link."\">Niveau sup&eacute;rieur</a>";
}

?>

<div class="clearfix"></div> 
<br/>
<?php
$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
$today = date('Y/m/d \a\t H:i:s');
printf('Page generated in %.3f seconds on %s', $totaltime, $today);
?>
</div>
<ul class="submenu">
<li>Gallerie v2.0.0 - H. Raffard &amp; C. Laury</li>
</ul>
<br clear="all" /> 
</div>
</body> 
</html>
<?php
$page = ob_get_contents();
ob_end_clean();

if (!(file_exists("$cache_folder/$path") && is_dir("$cache_folder/$path")))
    mkdir("$cache_folder/$path", 0777, true);
file_put_contents($cache, $page);
echo $page;
}
?>
