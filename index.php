<?php
// Start PHP code
require_once "common.php";
require_once "config.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];
$cwd = dirname($_SERVER["PHP_SELF"]);

$path = $_GET["path"];
$path = safeDirectory($path);
$dirlist = getFileList($path);

$cache = "./cache/$path/index.html";
if (file_exists($cache) && ($dir_thumb_mode != "RANDOM"))
    $cache_time = filemtime($cache);
else
    $cache_time = false;

if ($cache_time !== false && 
    $cache_time > filemtime("./index.php") &&
    $cache_time > filemtime("./common.php") && 
    $cache_time > filemtime("./config.php")  &&
    $cache_time > filemtime_r("./gallery/$path/.") &&
    $cache_time > filemtime_r("./thumbnails/$path/.")) {
  readfile($cache);
} else {
  ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head> 
<?php echo "<title>".$gal_title."</title>"; ?> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen" charset="utf-8" />
  <link rel="stylesheet" href="css/prettyPhoto.css" type="text/css" media="screen" charset="utf-8" /> 
  <script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>		
  <script src="js/jquery.tools-1.2.1.min.js" type="text/javascript"></script>
  <script src="js/jquery.prettyPhoto.js" type="text/javascript" charset="utf-8"></script> 
  <script src="js/jquery.imageLoader.js" type="text/javascript" charset="utf-8"></script>
  <script src="http://lite.piclens.com/current/piclens.js" type="text/javascript"></script>
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
      jQuery('.dynamic-thumbnail').loadImages();
      $.tools.tooltip.conf.relative = true;
      $.tools.tooltip.conf.cancelDefault = false;
      $(".dynamic-thumbnail").tooltip();
      $("ul.tabs").tabs("ul.gallery", {event:'mouseover'});
      $("a[rel^='prettyPhoto']").prettyPhoto({
        animationSpeed: 'fast',
    	padding: 40,
	    opacity: 0.65,
	    showTitle: true,
	    allowresize: true,
	    counter_separator_label: '/',
	    theme: '<?php echo $gal_theme; ?>' 
      });
    });
  </script>		

<?php
// Create thumbnail in current directory
if (!file_exists("./thumbnails/$path")) { mkdir("./thumbnails/$path"); }

// Issue Cooliris header
if (count($dirlist[file]) != 0) {  
   echo "  <link rel=\"alternate\" href=\"".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."\" type=\"application/rss+xml\" title=\"\" id=\"gallery_bis\" />\n";
}
echo "</head>\n";
echo "<body>\n";

echo "<div id=\"wrap\">\n";
echo "<div id=\"sidebar\">\n";
echo "<h3>Sous-Albums</h3>\n";

//output <ul tag only if something inside to be w3c compliant
if (count($dirlist[dir]) > 0) {
  echo "<ul class=\"menu\">\n";
  // Build menu with all sub-directories
  foreach($dirlist[dir] as $file) {
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".htmlentities($file['title'])."</a></li>\n";
  }
  echo "</ul>\n";
}
if ($path != "") {
  echo "<h3>Albums Voisins</h3>\n";
  if (dirname($path) === ".") 
    $neighborpath = "";
  else
    $neighborpath = dirname($path);
  $neighborlist = getFileList($neighborpath);
  if (count($neighborlist[dir]) > 0) {
    echo "<ul class=\"menu\">\n";
    foreach($neighborlist[dir] as $file) {
      if (strpos($file['fullname'], $path) !== false) continue;
      echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".htmlentities($file['title'])."</a></li>\n";
    }
  }
}
echo "</div>\n";
echo "<div id=\"content-container\">\n"; 
echo "<ul class=\"submenu\">\n"; 
echo "<li><a href=\"".$_SERVER["PHP_SELF"]."\"><b>Accueil</b></a></li>\n"; 
// Build menu with only top-level directories
$topdirlist = getFileList("");
foreach($topdirlist[dir] as $file) {
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".htmlentities($file['title'])."</a> </li>\n";
}
echo "</ul>\n"; 
echo "<div id=\"content\">\n"; 
echo "<h1>".htmlentities($gal_title)."</h1>\n"; 

// Path dirs and link
echo "<h3 id=\"gallery\">\n";
if ($path != "") {
  echo "<a href=\"".$_SERVER["PHP_SELF"]."\">Accueil</a>";
  $pathArr = explode("/", $path);
  for ($i = 0; $i < count($pathArr); $i++) {
    // Get directory title
    $dirPath  = implode("/", array_slice($pathArr, 0, $i+1));
    $dirList  = getFileList(dirname($dirPath));
    $dirTitle = "";
    foreach($dirList[dir] as $file) {
      if (strcmp($file['name'], $pathArr[$i]) == 0)
        $dirTitle = $file['title'];
    }
    $dirLink = getPathLink($dirPath);
    echo "/<a href=\"$dirLink\">".htmlentities($dirTitle)."</a>";
  }
}
if (count($dirlist[file]) != 0) {
  echo "      [ <a href=\"javascript:PicLensLite.start({feedUrl:'http://".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."', delay:6});\">Diaporama</a> ]\n";
}

echo "</h3>\n";

// Show list of subfolders, output <ul tag only if something inside to be w3c compliant
if (count($dirlist[dir]) > 0) {
  echo "<ul class=\"galleryfolder\">\n";
  foreach($dirlist[dir] as $file) {
    echo "<li><div class=\"galleryfolderalign\"><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($file['fullname'])."\" title=\"".htmlentities($file['title'])."\" >";
    $thumb = GetThumbsForDir($file['fullname'], $dir_thumb_mode);  
    echo "<img src=\"".$thumb."\" class=\"galleryfolderimg\" alt=\"".$file['name']."\"/>";
    echo "<br />".htmlentities($file['title'])."</a></div></li>\n";
  }
  echo "</ul>\n";
  //Separate Directory list and Pictures
  echo "<div class=\"clearfix\"></div>\n";
  echo "<h3></h3>\n";
}

// Show list of pictures
if (count($dirlist[file]) > 0) {
    $tabcount = count($dirlist[file]) / $thumbs_per_page;
    if (count($dirlist[file]) > $thumbs_per_page) {
        echo "<ul class=\"tabs\">\n";
        for ($t = 0; $t < $tabcount; $t++) {
            echo "\t<li><a href=\"#\">".($t+1)."</a></li>\n";
        }
        echo "</ul>\n";
        echo "<div class=\"clearfix\"></div>\n";
    }
    echo "<ul class=\"gallery\">\n";
    $tabthumb = 0;
    foreach($dirlist[file] as $file) {
        // Don't show album thumbnails
        if (strpos($file['fullname'], "00ALBUM") !== false) continue;
        $tmp_fthumb = substr($file['name'], 0, strlen($file['name'])-3).$thumb_ext; 
        if ($resize_preview === 1) 
            echo "<li><a href=\"./resize.php?src=./gallery/".urlencode($file['fullname'])."&w=".$resize_width."&h=0\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($file['subtitle']).htmlentities($file['lastmod'])."&lt;br /&gt;T&eacute;l&eacute;charger: &lt;a href=./gallery/".urlencode($file['fullname'])."&gt;".htmlentities($file['name'])."&lt;/a&gt; ".htmlentities($file['size'])."\">";
        else
            echo "<li><a href=\"./gallery/".urlencode($file['fullname'])."\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($file['subtitle']).htmlentities($file['lastmod'])."&lt;br /&gt;T&eacute;l&eacute;charger: &lt;a href=./gallery/".urlencode($file['fullname'])."&gt;".htmlentities($file['name'])."&lt;/a&gt;\">";

        echo "<div class=\"dynamic-thumbnail\" src=\"./getthumb.php?dir=".urlencode($file['dir'])."&file=".urlencode($file['name'])."\" title=\"".htmlentities($file['title'])."\"></div><div class=\"tooltip\">".htmlentities($file['title'])."<br />".htmlentities($file['lastmod']);
        if ($file['tags'] != '')
            echo "<br /><i>".htmlentities($file['tags'])."</i>";
        echo "</div></a></li>\n";
        $tabthumb++;
        if ($tabthumb >= $thumbs_per_page) {
            $tabthumb = 0;
            echo "</ul>\n<ul class=\"gallery\">\n";
        }
    }
    echo "</ul>\n";
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
<li>Gallerie v1.7.5 - H. Raffard &amp; C. Laury - 2010/05/20</li>
</ul>
<br clear="all" /> 
</div>
</div>
</body> 
</html>
<?php
  $page = ob_get_contents();
  ob_end_clean();

  if (!(file_exists("./cache/$path") && is_dir("./cache/$path")))
    mkdir("./cache/$path", 0777, true);

  file_put_contents($cache, $page);
  echo $page;
}
?>
