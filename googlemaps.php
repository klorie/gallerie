<?php

require_once "common_db.php";

function getFolderGeolocalizedCount($id, mediaDB &$db = NULL)
{    
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $result = $m_db->querySingle("SELECT COUNT(*) FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;");
    if ($result === false) throw new Exception($m_db->lastErrorMsg());

    if ($db == NULL)
        $m_db->close();

    return $result;
}

function getFolderGeolocalizedElements($id, mediaDB &$db = NULL)
{    
    $m_db         = NULL;
    $element_list = array();

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $results = $m_db->query("SELECT id FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;");
    if ($results === false) throw new Exception($m_db->lastErrorMsg());
    while($row = $results->fetchArray()) {
        $element_list[] = $row['id'];
    }
    $results->finalize();

    if ($db == NULL)
        $m_db->close();

    return $element_list;
}

function getElementIcon(mediaObject $element)
{
    foreach($element->tags as $tag) {
        if      (stristr($tag, 'Paysage') != false)
            return 'images/markers/paysage.png';
        else if (stristr($tag, 'Flore')   != false)
            return 'images/markers/flore.png';
        else if (stristr($tag, 'Faune')   != false)
            return 'images/markers/faune.png';
        else if (stristr($tag, 'Eglise')  != false)
            return 'images/markers/eglise.png';
        else if (stristr($tag, 'Pano')    != false)
            return 'images/markers/panorama.png';
        else if (stristr($tag, 'Portrait') != false)
            return 'images/markers/portrait.png';
    }
    if ($element->type == 'movie')
        return 'images/markers/movie.png';
    else
        return 'images/markers/picture.png';
}
?>
