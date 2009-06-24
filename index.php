<?php
// Start PHP code
require "common.php";

$path = $_GET["path"];
$path = safeDirectory($path);
$dirlist = getFileList($path);

$cache = "./cache/$path/index.html";

if (file_exists($cache) && 
    filemtime($cache) > filemtime("./gallery/$path/.") &&
    filemtime($cache) > filemtime("./thumbnails/$path/.")) {
  readfile($cache);
} else {
  ob_start();
?>
<html>
<head> 
  <title>Photos de Catherine & Cyril</title> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <script type="text/javascript" src="js/jquery.min.js"></script>		
  <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="css/prettyPhoto.css" type="text/css" media="screen" charset="utf-8"> 
  <script src="js/jquery.prettyPhoto.js" type="text/javascript" charset="utf-8"></script> 
  <script src="http://lite.piclens.com/current/piclens.js" type="text/javascript"></script>
  <!--[if IE 6]>
    <script src="js/DD_belatedPNG_0.0.7a-min.js"></script>
    <script>
      DD_belatedPNG.fix('.pp_left,.pp_right,a.pp_close,a.pp_arrow_next,a.pp_arrow_previous,.pp_content,.pp_middle'); 
    </script>
  <![endif]--> 
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
      $("a[rel^='prettyPhoto']").prettyPhoto({
        animationSpeed: 'fast', /* fast/slow/normal */
	padding: 40, /* padding for each side of the picture */
	opacity: 0.35, /* Value betwee 0 and 1 */
	showTitle: true, /* true/false */
	allowresize: true, /* true/false */
	counter_separator_label: '/', /* Separator for gallery counter 1 "of" 2 */
	theme: 'dark_rounded' /* light_rounded / dark_rounded / light_square / dark_square */
      });
    });
  </script>		
<?php
// Create thumbnail in current directory
if (!file_exists("./thumbnails/$path")) { mkdir("./thumbnails/$path"); }
createThumbs( "./gallery/$path", "./thumbnails/$path", 150 ) ;

// Issue Cooliris header
if ($path != "") {
  echo "  <link rel=\"alternate\" href=\"".$_SERVER["SERVER_NAME"]."/photos.rss.php?path=".$path."\" type=\"application/rss+xml\" title=\"\" id=\"gallery\" />\n";
}
echo "</head>\n";
echo "<body>\n";

echo "<div id=\"wrap\">\n";
echo "<div id=\"sidebar\">\n";
echo "<h3>Sous-albums</h3>\n";
echo "<ul class=\"menu\">\n";
// Build menu with all sub-directories
foreach($dirlist as $file) {
  if ($file[type] == "dir")
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".$file['title']."</a></li>\n";
}
echo "</ul>\n";
echo "</div>\n";
echo "<div id=\"content-container\">\n"; 
echo "<ul class=\"submenu\">\n"; 
echo "<li><a href=\"".$_SERVER["PHP_SELF"]."\"><b>Accueil</b></a></li>\n"; 
// Build menu with only top-level directories
$topdirlist = getFileList("");
foreach($topdirlist as $file) {
  if ($file[type] == "dir")
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".$file['title']."</a>
</li>\n";
}
if ($path != "") {
  echo "<li>&nbsp;</li>\n";
  echo "<li><a href=\"javascript:PicLensLite.start({feedUrl:'http://".$_SERVER["SERVER_NAME"]."/photos.rss.php?path=".$path."', delay:6});\">Diaporama</a></li>\n";
}
echo "</ul>\n"; 
echo "<div id=\"content\">\n"; 
echo "<h1>Photos de Catherine &amp; Cyril</h1>\n"; 

// Path dirs and link
echo "<h3 id=\"gallery\">\n";
if ($path != "") {
  echo "<a href=\"".$_SERVER["PHP_SELF"]."\">Accueil</a>";
  $pathArr = explode("/", $path);
  for ($i = 0; $i < count($pathArr); $i++) {
    $dirLink = getPathLink(join("/", array_slice($pathArr, 0, $i + 1)));
    echo "/<a href=\"$dirLink\">".htmlentities($pathArr[$i])."</a>";
  }
}
echo "</h3>\n";

// Show list of subfolders
$subfoldercount = 0;
echo "<ul class=\"galleryfolder clearfix\">\n";
foreach($dirlist as $file) {
  if ($file[type]=="dir") {
    $subfoldercount++;
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($file['fullname'])."\"";
    $subdirlist    = getFileList($file['fullname'], true, 1);
    $found_thumb   = 0;
    $foldercaption = "";
    foreach($subdirlist as $file_thumb) {
      // Could count the number of thumbnail or image in directory
      if ($file_thumb[type] != "dir") { 
        $thumbfilename = "./thumbnails/".$file_thumb['fullname'];
        if (file_exists($thumbfilename)) { 
          $found_thumb = 1;
          if (!strstr($file_thumb['name'], "00ALBUM") === false) {
            $exif = exif_read_data("./gallery/".$file_thumb['fullname']);
            if (!$exif === false) {
              $foldercaption = $exif["COMPUTED"]["UserComment"];
            }
          }
          if ($foldercaption != "") {
            echo " title=\"".$foldercaption."\" >";
          } else {
            echo " title=\"".$file['name']."\" >";
          }
          echo "<img src=\"".$thumbfilename."\" />";
          break; 
        }
      }
    }
    if ($found_thumb == 0) {
      echo " title=\"".$file['name']."\" ><img src=\"./images/nothumb.jpg\" />";
    }
    if ($foldercaption == "") {
      echo "<br />".htmlentities($file['name'])."</a></li>\n";
    } else {
      echo "<br />".htmlentities($foldercaption)."</a></li>\n";
    } 
  } 
}
echo "</ul>\n";
if ($subfoldercount > 0)
  echo "<h3></h3>\n";

// Show list of pictures
echo "<ul class=\"gallery clearfix\">\n";
foreach($dirlist as $file) {
  // Don't show album thumbnails
  if (strpos($file['fullname'], "00ALBUM") !== false) continue;
  if ($file[type] != "dir") {
    echo "<li><a href=\"./gallery/".$file['fullname']."\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($file['name'])."\"><img src=\"./thumbnails/".$file['fullname']."\" alt=\"".htmlentities($file['title'])."\" title=\"".htmlentities($file['title'])."\" /></a></li>\n";
  }
}
echo "</ul>\n";

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
</div>
<ul class="submenu">
<li>Gallerie v0.2 - H. Raffard &amp; C. Laury - 2009</li>
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
    mkdir("./cache/$path");

  file_put_contents($cache, $page);
  echo $page;
}
?>
