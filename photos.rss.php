<?php

require "common.php";

function &xml_encode(&$xml) {
	$xml = str_replace(array('ü', 'Ü', 'ö',
				'Ö', 'ä', 'Ä',
				'ß'
				),
			array('&#252;', '&#220;', '&#246;',
				'&#214;', '&#228;', '&#196;',
				'&#223;'
			     ),
			$xml
			);

	$xml = preg_replace(array("/\&([a-z\d\#]+)\;/i",
				"/\&/",
				"/\#\|\|([a-z\d\#]+)\|\|\#/i",

				"/([^a-zA-Z\d\s\<\>\&\;\.\:\=\"\-\/\%\?\!\'\(\)\[\]\{\}\$\#\+\,\@_])/e"
				),
			array("#||\\1||#",
				"&amp;",
				"&\\1;",
				"'&#'.ord('\\1').';'"
			     ),
			$xml
			);

	return $xml;
} 

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
    echo "  <title>".utf8_encode(xml_encode($file['title']))."</title>\n";
    echo "  <media:description>".utf8_encode(xml_encode($file['name']))."</media:description>\n";
    echo "  <link>http://".$_SERVER["SERVER_NAME"]."/gallery/".$file['fullname']."</link>\n";
    echo "  <media:thumbnail url=\"http://".$_SERVER["SERVER_NAME"]."/thumbnails/".$file['fullname']."\"/>\n";
    echo "  <media:content url=\"http://".$_SERVER["SERVER_NAME"]."/gallery/".$file['fullname']."\" type=\"image/jpeg\" />\n";
    echo "</item>\n";
  }
}
echo "</channel>\n";
echo "</rss>\n";

?>
