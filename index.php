<?php
// Start PHP code
require_once "common_db.php";
require_once "thumbnail.php";

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
    $cache_time > filemtime("./index.php") &&
    $cache_time > filemtime("./common_db.php") && 
    $cache_time > filemtime("./config.php")  && 
    $cache_time > filemtime("$image_folder/$path") &&
    $cache_time > filemtime("$thumb_folder/$path")) {
  readfile($cache);
} else {
  ob_start();
//  $dirlist  = getFileList($path);
  $m_db     = new mediaDB();
  $m_folder = new mediaFolder();
  $m_db->loadMediaFolder($m_folder, $m_db->getMediaFolderID($path));
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
<?php if(count($m_folder->element) > 0)
    echo "  <script src=\"http://lite.piclens.com/current/piclens_optimized.js\" type=\"text/javascript\"></script>\n";
?>
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
if (count($m_folder->element) > 0) {  
   echo "  <link rel=\"alternate\" href=\"".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."\" type=\"application/rss+xml\" title=\"\" id=\"gallery_bis\" />\n";
}
echo "</head>\n";
echo "<body>\n";

echo "<div id=\"wrap\">\n";
echo "<div id=\"sidebar\">\n";

//output <ul tag only if something inside to be w3c compliant
if (count($m_folder->subfolder) > 0) {
  echo "<h3>Sous-Albums</h3>\n";  
  echo "<ul class=\"menu\">\n";
  // Build menu with all sub-directories
  foreach($m_folder->subfolder as $subfolder) {
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$subfolder->fullname()."\" >".htmlentities($subfolder->title)."</a></li>\n";
  }
  echo "</ul>\n";
}
if ($path != "") {
  echo "<h3>Albums Voisins</h3>\n";
  $neighborlist = $m_db->getNeighborFolders($m_folder->db_id);
  if (count($neighborlist) > 0) {
    echo "<ul class=\"menu\">\n";
    foreach($neighborlist as $neighbor) {
      echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$m_db->getMediaFolderPath($neighbor)."\" >".htmlentities($m_db->getMediaFolderTitle($neighbor))."</a></li>\n";
    }
    echo "</ul>\n";
  }
}
if ($path == "") {
    $latestfolderlist = $m_db->getLatestUpdatedFolder($latest_album_count);
    echo "<h3>Nouveaut&eacute;s</h3>\n";
    echo "<ul class=\"menu\">\n";
    foreach($latestfolderlist as $latestfolder) {
        echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$m_db->getMediaFolderPath($latestfolder)."\" >".htmlentities($m_db->getMediaFolderTitle($latestfolder))."</a></li>\n";
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
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$m_db->getMediaFolderPath($topfolder)."\" >".htmlentities($m_db->getMediaFolderTitle($topfolder))."</a> </li>\n";
}
echo "</ul>\n"; 
echo "<div id=\"content\">\n"; 
echo "<h1>".htmlentities($gal_title)."</h1>\n"; 

// Path dirs and link
echo "<h3 id=\"gallery\">\n";
//if ($path != "") {
//  echo "<a href=\"".$_SERVER["PHP_SELF"]."\">Accueil</a>";
//  $pathArr = explode("/", $path);
//  for ($i = 0; $i < count($pathArr); $i++) {
//    // Get directory title
//    $dirPath  = implode("/", array_slice($pathArr, 0, $i+1));
//    $dirList  = getFileList(dirname($dirPath));
//    $dirTitle = "";
//    foreach($dirList[dir] as $file) {
//      if (strcmp($file['name'], $pathArr[$i]) == 0)
//        $dirTitle = $file['title'];
//    }
//    $dirLink = getPathLink($dirPath);
//    echo "/<a href=\"$dirLink\">".htmlentities($dirTitle)."</a>";
//  }
//}
if (count($m_folder->element) > 0) {
  echo "      [ <a href=\"javascript:PicLensLite.start({feedUrl:'http://".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."', delay:6});\">Diaporama</a> ]\n";
}

echo "</h3>\n";

// Show list of subfolders, output <ul tag only if something inside to be w3c compliant
if (count($m_folder->subfolder) > 0) {
    echo "<ul class=\"galleryfolder\">\n";
    foreach($m_folder->subfolder as $subfolder) {
        echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($subfolder->fullname())."\" title=\"".htmlentities($subfolder->title)."\" >";
        echo "<img src=\"./getthumb.php?folder=$subfolder->db_id\" alt=\"".$subfolder->name."\"/>";
        echo "<br />".htmlentities($subfolder->title)."</a></li>\n";
    }
    echo "</ul>\n";
    // Separate Directory list and Pictures
    echo "<div class=\"clearfix\"></div>\n";
    echo "<h3></h3>\n";
}

// Show list of pictures
if (count($m_folder->element) > 0)   {
    $tabcount = count($m_folder->element) / $thumbs_per_page;
    if (count($m_folder->element) > $thumbs_per_page) {
        echo "<ul class=\"tabs\">\n";
        for ($t = 0; $t < $tabcount; $t++) {
            echo "\t<li><a href=\"#\">".($t+1)."</a></li>\n";
        }
        echo "</ul>\n";
        echo "<div class=\"clearfix\"></div>\n";
    }
    echo "<ul class=\"gallery\">\n";
    $tabthumb = 0;
    $videoid  = 0;
    foreach($m_folder->element as $element) {
        if ($element->type == 'movide') {
            // Video
            echo "<li><a href=\"#\" rel=\"#video".$videoid."\">";
            $videoid++;
        } else {
            // Images
            echo "<li><a href=\"./getresized.php?id=$element->db_id\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($element->camera).htmlentities($element->lastmod)."&lt;br /&gt;T&eacute;l&eacute;charger: &lt;a href=&quot;".$image_folder."/".htmlentities($element->folder->fullname())."/".htmlentities($element->filename)."&quot;&gt;".htmlentities($element->title)."&lt;/a&gt; ".htmlentities($element->filesize)."\">";
        }
        echo "<div class=\"dynamic-thumbnail\" src=\"./getthumb.php?id=$element->db_id\" title=\"".htmlentities($element->title)."\"></div><div class=\"tooltip\">".htmlentities($element->title)."<br />".htmlentities($element->lastmod);
//        if ($file['tags'] != '')
//            echo "<br /><i>".htmlentities($file['tags'])."</i>";
//        else if (($file['type'] == 'video/x-flv') && ($file['size'] != ''))
//            echo "<br /><i>".$file['size']."</i>";
        echo "</div></a></li>\n";
        $tabthumb++;
        if ($tabthumb >= $thumbs_per_page) {
            $tabthumb = 0;
            echo "</ul>\n<ul class=\"gallery\">\n";
        }
    }
    if (($tabthumb < $thumbs_per_page) && ($tabcount > 1)) {
        while($tabthumb < $thumbs_per_page) {
            echo "<li></li>\n";
            $tabthumb++;
        }
    }
    echo "</ul>\n";
    // Videos overlay
    $videoid  = 0;
    foreach($m_folder->element as $element) {
        if($element->type == 'movie') {
            echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
            echo "<a class=\"player\" href=\"".getObjectResizedPath($element->db_id)."\"></a>";
            echo "</div>\n";
            $videoid++;
        }
    }
    echo "<div class=\"clearfix\"></div>\n";
    echo "<h3></h3>\n";
}
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
<li>Gallerie v1.9.1 - H. Raffard &amp; C. Laury - 2010/08/27</li>
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
