<?php

require_once "config.php";
require_once "common.php";

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
$cwd = dirname($_SERVER["PHP_SELF"]);

// Issue RSS header
header("Content-Type: application/xml");
echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?".">\n";
echo "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
echo "<channel>\n";

// Issue items
foreach($dirlist[file] as $file) {
    if (strpos($file['fullname'], "00ALBUM") !== false) continue;
    $info = pathinfo("./gallery/".$file['fullname']);
    $ext = strtolower($info['extension']);
    $fname_noext = $info['filename'];
    echo "<item>\n";
    echo "  <title>".utf8_encode(xml_encode($file['name']))."</title>\n";
    echo "  <media:description>".utf8_encode(xml_encode($file['title']))."</media:description>\n";
    echo "  <link>./gallery/".$file['fullname']."</link>\n";
    echo "  <media:thumbnail url=\"http://".$_SERVER["SERVER_NAME"].$cwd."/thumbnails/".$path."/".$fname_noext.".".$thumb_create."\" />\n";
    echo "  <media:content url=\"http://".$_SERVER["SERVER_NAME"].$cwd."/gallery/".$file['fullname']."\" type=\"image/jpeg\" />\n";
    echo "</item>\n";
}
echo "</channel>\n";
echo "</rss>\n";

?>
