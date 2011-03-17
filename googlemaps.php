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

function getElementIcon($id, mediaDB &$db = NULL)
{
    $result = '';

    $m_db = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;

    $tags = $m_db->query("SELECT name FROM media_tags WHERE media_id=$id;");
    if ($tags === false) throw new Exception($m_db->lastErrorMsg());

    while($tag = $tags->fetchArray()) {
        if      (stristr($tag['name'], 'Paysage') != false) {
            $result = 'images/markers/paysage.png';
            break;
        } else if (stristr($tag['name'], 'Flore')   != false) {
            $result = 'images/markers/flore.png';
            break;
        } else if (stristr($tag['name'], 'Faune')   != false) {
            $result = 'images/markers/faune.png';
            break;
        } else if (stristr($tag['name'], 'Eglise')  != false) {
            $result = 'images/markers/eglise.png';
            break;
        } else if (stristr($tag['name'], 'Pano')    != false) {
            $result = 'images/markers/panorama.png';
            break;
        } else if (stristr($tag['name'], 'Portrait') != false) {
            $result = 'images/markers/portrait.png';
            break;
        }
    }
    $tags->finalize();

    if ($result == '')
        $result = 'images/markers/picture.png';

    if ($db == NULL)
        $m_db->close();

    return $result;
}

function getFolderGeolocalizedBounds($id, mediaDB &$db = NULL)
{    
    $m_db          = NULL;
    $longitude_min =  180.0;
    $longitude_max = -180.0;
    $latitude_min  =   90.0;
    $latitude_max  =  -90.0;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $results = $m_db->query("SELECT latitude, longitude FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;");
    if ($results === false) throw new Exception($m_db->lastErrorMsg());
    while($row = $results->fetchArray()) {
        if ($row['longitude'] < $longitude_min) $longitude_min = $row['longitude'];
        if ($row['longitude'] > $longitude_max) $longitude_max = $row['longitude'];
        if ($row['latitude']  < $latitude_min)  $latitude_min  = $row['latitude'];
        if ($row['latitude']  > $latitude_max)  $latitude_max  = $row['latitude'];
    }
    $results->finalize();

    if ($db == NULL)
        $m_db->close();

    return array('sw_lat' => $latitude_min, 'sw_lon' => $longitude_min, 'ne_lat' => $latitude_max, 'ne_lon' => $longitude_max);
}

?>
