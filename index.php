<?php
// Start PHP code
require_once "common_browser.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];
$cwd = dirname($_SERVER["PHP_SELF"]);

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
    $cache_time > filemtime("./config.php")  && 
    $cache_time > filemtime("$image_folder/$path") &&
    $cache_time > filemtime("$thumb_folder/$path")) {
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
  <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen" charset="utf-8" />
  <link rel="stylesheet" href="css/prettyPhoto.css" type="text/css" media="screen" charset="utf-8" /> 
  <script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>		
  <script src="js/jquery.tools-1.2.1.min.js" type="text/javascript"></script>
  <script src="js/flowplayer-3.2.2.min.js" type="text/javascript"></script>
  <script src="js/jquery.prettyPhoto.js" type="text/javascript" charset="utf-8"></script> 
  <script src="js/jquery.imageLoader.js" type="text/javascript" charset="utf-8"></script>
  <script src="http://lite.piclens.com/current/piclens_optimized.js" type="text/javascript"></script>
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
      jQuery('.dynamic-thumbnail').loadImages();
      $.tools.tooltip.conf.relative = true;
      $.tools.tooltip.conf.cancelDefault = false;
      $.tools.tooltip.conf.predelay = 1000;
      $(".dynamic-thumbnail").tooltip();
      $("ul.tabs").tabs("ul.gallery", {
        event: 'mouseover'
      });
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
/*
if (count($m_folder->element) > 0) {  
   echo "  <link rel=\"alternate\" href=\"".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."\" type=\"application/rss+xml\" title=\"\" id=\"gallery_bis\" />\n";
}
*/
echo "</head>\n";
echo "<body>\n";

echo "<div id=\"wrap\">\n";
echo "<div id=\"sidebar\">\n";

// Build menu with all sub-directories
displaySubFolderMenu($m_folder_id, $m_db);

if ($path != "") {
  echo "<h3>Albums Voisins</h3>\n";
  $neighborlist = $m_db->getNeighborFolders($m_folder_id);
  if (count($neighborlist) > 0) {
    echo "<ul class=\"menu\">\n";
    foreach($neighborlist as $neighbor) {
      echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($neighbor))."\" >".htmlentities($m_db->getFolderTitle($neighbor))."</a></li>\n";
    }
    echo "</ul>\n";
  }
}
if ($path == "") {
    $latestfolderlist = $m_db->getLatestUpdatedFolder($latest_album_count);
    echo "<h3>Nouveaut&eacute;s</h3>\n";
    echo "<ul class=\"menu\">\n";
    foreach($latestfolderlist as $latestfolder) {
        echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($latestfolder))."\" >".htmlentities($m_db->getFolderTitle($latestfolder))."</a></li>\n";
    }
    echo "</ul>\n";
}
echo "</div>\n";
echo "<div id=\"content-container\">\n"; 
echo "<ul class=\"submenu\">\n"; 
echo "<li><a href=\"".$_SERVER["PHP_SELF"]."\"><b>Accueil</b></a></li>\n"; 
// Build menu with only top-level directories
$topfolderlist = $m_db->getTopLevelFolders();
foreach($topfolderlist as $topfolder) {
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($topfolder))."\" >".htmlentities($m_db->getFolderTitle($topfolder))."</a> </li>\n";
}
echo "</ul>\n"; 
echo "<div id=\"content\">\n"; 
echo "<h1>".htmlentities($gal_title)."</h1>\n"; 

// Path dirs and link
echo "<h3 id=\"gallery\">\n";
if ($path != "") {
  echo "<a href=\"".$_SERVER["PHP_SELF"]."\">Accueil</a>";
  $folderhierarchy = $m_db->getFolderHierarchy($m_folder_id);
  foreach($folderhierarchy as $fhier) {
      if ($fhier == 1) continue; // Discard top-level
      echo "/<a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($fhier))."\">".htmlentities($m_db->getFolderTitle($fhier))."</a>";
  }
}
//if (count($m_folder->element) > 0) {
//  echo "      [ <a href=\"javascript:PicLensLite.start({feedUrl:'http://".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."', delay:6});\">Diaporama</a> ]\n";
//}

echo "</h3>\n";

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
    echo "<br /><br /><a href=\"".$back_link."\">Niveau sup&eacute;rieur</a>";
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
<li>Gallerie v2.0.0 - H. Raffard &amp; C. Laury - 2011/03/15</li>
</ul>
<br clear="all" /> 
</div>
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
