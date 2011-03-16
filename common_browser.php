<?php

require_once "common_db.php";
require_once "resized.php";

function displayElementList($id, mediaDB &$db = NULL)
{
    global $thumbs_per_page;
    global $image_folder;
    global $resized_folder;

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $element_list = $m_db->getFolderElements($id);

    if (count($element_list) > 0) {
        $tabcount = count($element_list) / $thumbs_per_page;
        if (count($element_list) > $thumbs_per_page) {
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
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if ($element->type == 'movie') {
                // Video
                echo "<li><a href=\"#\" rel=\"#video".$videoid."\">";
                $videoid++;
            } else {
                // Images
                echo "<li><a href=\"./getresized.php?id=$current_id\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($element->getSubTitle())."\">";
            }
            echo "<div class=\"dynamic-thumbnail\" src=\"./getthumb.php?id=$current_id\" title=\"".htmlentities($element->title)."\"></div><div class=\"tooltip\">".htmlentities($element->title)."<br />".strftime('%d/%m/%Y %Hh%M', strtotime($element->originaldate));
            if (count($element->tags) > 0) {
                echo "<br /><i>";
                $tagline = "";
                foreach($element->tags as $tag) {
                    if ($tagline != "")
                        $tagline .= ", ";
                    $tagline .= htmlentities($tag);
                }
                echo "$tagline</i>";
            } else if (($element->type == 'movie') && ($element->duration != ''))
                echo "<br /><i>".$element->duration."</i>";
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
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if($element->type == 'movie') {
                echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
                echo "<a class=\"player\" href=\"$resized_folder/".getResizedPath($current_id)."\"></a>";
                echo "</div>\n";
                $videoid++;
            }
        }
        echo "<div class=\"clearfix\"></div>\n";
        echo "<h3></h3>\n";
    }
}

function displaySubFolderList($id, mediaDB &$db = NULL)
{ 
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul class=\"galleryfolder\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($m_db->getFolderTitle($subfolder));
            echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >";
            echo "<img src=\"./getthumb.php?folder=$subfolder\" alt=\"".$m_db->getFolderName($subfolder)."\"/>";
            echo "<br />$subfolder_title</a></li>\n";        
        }
        echo "</ul>\n";
        // Separate Directory list and Pictures
        echo "<div class=\"clearfix\"></div>\n";
        echo "<h3></h3>\n";
    }

    if ($db == NULL)
        $m_db->close();
}

function displaySubFolderMenu($id, mediaDB &$db = NULL)
{ 
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<h3>Sous-Albums</h3>\n";  
        echo "<ul class=\"menu\">\n";
        foreach($subfolder_list as $subfolder) {
            echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($subfolder))."\" >".htmlentities($m_db->getFolderTitle($subfolder))."</a></li>\n";
        }
        echo "</ul>\n";
    }

    if ($db == NULL)
        $m_db->close();
}
?>
