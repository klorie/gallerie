<?php

require "common.php";

$path = $_GET["path"];
$path = safeDirectory($path);
$dirlist = getFileList($path);

// Issue RSS header
header("Content-Type: application/xml");
echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?".">\n";
echo "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
echo "<channel>\n";
//echo "<title>Photos de Catherine et Cyril</title>\n";
//echo "<link>".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?path=".urlEncode($path)."</link>\n";
//echo "<atom:link ref=\"".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?path=".urlEncode($path)."\" rel=\"self\" type=\"application/rss+xml\" />\n";

// Issue items
foreach($dirlist as $file) {
  if ($file[type] != "dir") {
    echo "<item>\n";
    echo "  <title>".$file['name']."</title>\n";
    echo "  <media:description>".utf8_encode($file['title'])."</media:description>\n";
    echo "  <link>http://".$_SERVER["SERVER_NAME"]."/gallery/".$file['fullname']."</link>\n";
    echo "  <media:thumbnail url=\"http://".$_SERVER["SERVER_NAME"]."/thumbnails/".$file['fullname']."\"/>\n";
    echo "  <media:content url=\"http://".$_SERVER["SERVER_NAME"]."/gallery/".$file['fullname']."\" type=\"image/jpeg\" />\n";
    echo "</item>\n";
  }
}
echo "</channel>\n";
echo "</rss>\n";

?>
