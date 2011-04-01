<?php

require_once "include.php";

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

$id           = $_GET["id"];
$m_db         = new mediaDB();
$element_list = $m_db->getFolderElements($id);
$cwd          = dirname($_SERVER["PHP_SELF"]);

// Issue RSS header
header("Content-Type: application/xml");
echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?".">\n";
echo "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
echo "<channel>\n";
echo "<atom:link href=\"http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]."\" rel=\"self\" type=\"application/rss+xml\" />\n";

// Issue items
foreach($element_list as $element_id) {
    $element = new mediaObject();
    $m_db->loadMediaObject($element, $element_id);
    echo "<item>\n";
    echo "  <title>".utf8_encode(xml_encode($element->title))."</title>\n";
    echo "  <media:description>".utf8_encode(xml_encode($element->getSubTitle(true)))."</media:description>\n";
    echo "  <link>".$image_folder."/".$element->download_path."</link>\n";
    echo "  <media:thumbnail url=\"$BASE_URL/$thumb_folder/".getThumbnailPath($element_id)."\" />\n";
    if ($element->type == 'movie')
        echo "  <media:content url=\"$BASE_URL/$resized_folder/".getResizedPath($element_id)."\" type=\"video/x-flv\" />\n";
    else
        echo "  <media:content url=\"$BASE_URL/$resized_folder/".getResizedPath($element_id)."\" type=\"image/jpeg\" />\n";
    echo "</item>\n";
}
echo "</channel>\n";
echo "</rss>\n";

?>
