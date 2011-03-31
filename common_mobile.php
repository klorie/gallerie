<?php

require_once "common_db.php";
require_once "resized.php";

function displaySubFolderList($id, mediaDB &$db = NULL)
{ 
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul data-role=\"listview\" data-theme=\"a\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($m_db->getFolderTitle($subfolder));
            echo "<li>";
            echo "<img src=\"".baseURL()."/getthumb.php?folder=$subfolder\" title=\"".$subfolder_title."\" />";
            echo "<a href=\"".baseURL()."/mobile.php?path=".urlencode($m_db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >$subfolder_title</a>";
            echo "<div class=\"ui-li-aside\"><p>".$m_db->getFolderDate($subfolder)."</p></div>";
            echo "<div class=\"ui-li-count\">".$m_db->getFolderElementsCount($subfolder, true)."</div>";
            echo "</li>\n";        
        }
        echo "</ul>\n";
//        // Separate Directory list and Pictures
//        if ($m_db->getFolderElementsCount($id) > 0) {
//            echo "<div class=\"clearfix\"></div>\n";
//            echo "<h2></h2>\n";
//        }
    }

    if ($db == NULL)
        $m_db->close();
}

function displayElementList($id, mediaDB &$db = NULL)
{
    global $image_folder;
    global $resized_folder;

    setlocale(LC_ALL, "fr_FR.utf8");

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $element_list = $m_db->getFolderElements($id);

    $col_idx = 'a';
    if (count($element_list) > 0) {
        echo "<div class=\"ui-grid-b\">\n";
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if ($element->type == 'picture') {
                echo "<div class=\"ui-block-$col_idx\">\n";
                if      ($col_idx == 'a') $col_idx = 'b';
                else if ($col_idx == 'b') $col_idx = 'c';
                else                      $col_idx = 'a';   
                echo "<a href=\"".baseURL()."/getresized.php?id=$current_id\" rel=\"external\" title=\"".htmlentities($element->getSubTitle())."\">";
                echo "<img src=\"".baseURL()."/getthumb.php?id=$current_id\" title=\"".htmlentities($element->title)."\" />";
                echo "</a></div>\n";
            }
        }
        echo "</div>\n";
    }
    if ($db == NULL)
        $m_db->close();
}

