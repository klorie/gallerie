<?php

class mediaDB extends mysqli
{
    public $lastupdate = '1970/01/01 00:00:00';
    
    function init_database()
    {
        // Method which initialize the database with proper structure
        $query  = "CREATE TABLE IF NOT EXISTS media_objects (id INTEGER PRIMARY KEY AUTO_INCREMENT, folder_id INTEGER, type ENUM('movie','picture') NOT NULL, ";
        $query .= "title VARCHAR(127), filesize INTEGER, duration VARCHAR(16), lastmod DATETIME, filename VARCHAR(127), download_path TEXT, ";
        $query .= "camera_id SMALLINT(5), focal INTEGER, lens_id SMALLINT(5), fstop VARCHAR(16), shutter VARCHAR(16), iso SMALLINT(6), originaldate DATETIME, ";
        $query .= "width SMALLINT(5) NOT NULL, height SMALLINT(5) NOT NULL, lens_is_zoom ENUM('-1','0','1') NOT NULL, longitude FLOAT, latitude FLOAT, altitude SMALLINT(5));";
        if ($this->query($query) === FALSE)
            die('-E- Failed query - '.$query.':'.$this->error);
        $query  = "CREATE TABLE IF NOT EXISTS media_folders (id INTEGER PRIMARY KEY AUTO_INCREMENT, parent_id INTEGER, title VARCHAR(127), ";
        $query .= "lastmod DATETIME, originaldate DATETIME, thumbnail_source VARCHAR(127), foldername VARCHAR(127));";
        if ($this->query($query) === FALSE)
            die('-E- Failed query - '.$query.':'.$this->error);
        if ($this->query('CREATE TABLE IF NOT EXISTS cameras (id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(127));') === FALSE)
            die('-E- Failed query:'.$this->error);
        if ($this->query('CREATE TABLE IF NOT EXISTS lenses (id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(127));') === FALSE)
            die('-E- Failed query:'.$this->error);
        if ($this->query('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(127));') === FALSE)
            die('-E- Failed query:'.$this->error);
        if ($this->query('CREATE TABLE IF NOT EXISTS media_tags (id INTEGER PRIMARY KEY AUTO_INCREMENT, tag_id INTEGER, media_id INTEGER);') === FALSE) 
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_tag_id ON media_tags(tag_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_tag_media_id ON media_tags(media_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_folders_parent_id ON media_folders(parent_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_folders_foldername ON media_folders(foldername(127));') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_objects_folder_id ON media_objects(folder_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_objects_camera_id ON media_objects(camera_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_objects_lens_id ON media_objects(lens_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE TABLE IF NOT EXISTS gallery_config (id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(64), value VARCHAR(127));')) === FALSE)
            die('-E- Failed query:'.$this->error);
        if (($this->query('INSERT INTO gallery_config (name, value) VALUES ("lastupdate", "1970/01/01 00:00:00");')) === FALSE)
            die('-E- Failed query:'.$this->error);
        if (($this->query('INSERT INTO gallery_config (name, value) VALUES ("db_schema_version", "1.0");')) === FALSE)
            die('-E- Failed query:'.$this->error);
    }

    function __construct()
    {   
        global $database_host, $database_name, $database_user, $database_pwd, $db_schema_version;
    
        parent::__construct($database_host, $database_user, $database_pwd, $database_name);
        if (mysqli_connect_error()) {
            if (mysqli_connect_errno() != 1049) {
                print $database_host.'/'.$database_user.'/'.$database_pwd.'/'.$database_name;
                die("-E-   Failed to open database : ".mysqli_connect_errno());
            } else {
                // Create DB
                parent::__construct($database_host, $database_user, $database_pwd);
                if ($this->query("CREATE DATABASE $database_name;") === FALSE)
                    die('-E-    Failed to create database'.$this->error);
                $this->select_db("$database_name");
                print "-I-    Creating gallerie database\n";
                $this->init_database();
            }
        } else {
            // Check DB schema
            $result = $this->query('SELECT value FROM gallery_config WHERE name = "db_schema_version";');
            if ($result === FALSE) {
                // No schema at all neither config table - we are on historical version
                print "-I-    Recreating database as we have changed schema\n";
                if ($this->query("DROP DATABASE $database_name;") === FALSE) die('-E- Failed to delete database'.$this->error);
                if ($this->query("CREATE DATABASE $database_name;") === FALSE) die('-E- Failed to create database'.$this->error);
                $this->select_db("$database_name");
                $this->init_database();
            } else {
                $row = $result->fetch_assoc();
                if ($row['value'] != $db_schema_version) {
                    // Schema found, but is too old
                    print "-I-    Recreating database as we have changed schema\n";
                    if ($this->query("DROP DATABASE $database_name") === FALSE) die('-E- Failed to delete database'.$this->error);
                    if ($this->query("CREATE DATABASE $database_name") === FALSE) die('-E- Failed to create database'.$this->error);
                    $this->select_db("$database_name");
                    $this->init_database();
                }
            }
        }

        // Get last database update
        $result = $this->query('SELECT value FROM gallery_config WHERE name = "lastupdate";');
        if ($result === FALSE) die("-E- Failed query: ".$this->error); else $row = $result->fetch_assoc();
        $this->lastupdate = $row['value'];
    }

    function updateTimeStamp()
    {
        $result = $this->query('REPLACE INTO gallery_config (id, name, value) VALUES (1, "lastupdate", "'.date("Y-m-d H:i:s").'");');
        if ($result === FALSE) die("-E- Failed query: ".$this->error);
    }

    function findMediaObjectID(mediaObject &$media)
    {
        // Return ID in database if the same object is already present (same filename, same folder)
        // return -1 on failure
        $query = "SELECT id FROM media_objects WHERE folder_id = ";
        if ($media->folder != NULL)
            $query .= $media->folder->db_id;
        else
            $query .= "-1";
        $query .= " AND filename = '".$this->escape_string($media->filename)."'";

        $result = $this->query($query);
        if ($result === FALSE) die("-E- Failed query: $query".$this->error); else $row = $result->fetch_assoc();

        if ($result->num_rows == 0)
            return -1;
        else
            return $row['id'];  
    }

    function storeMediaObject(mediaObject &$media, $update=false)
    {
        // Check pertinence of updating
        if (($update == true) && ($media->folder->lastupdate > $media->lastmod))
            return true;

        // Get Camera ID
        $camera_id = -1;
        if ($media->camera != '')
            $camera_id = $this->findCameraID($media->camera);

        // Get Lens ID
        $lens_id = -1;
        if ($media->lens != '')
            $lens_id = $this->findLensID($media->lens);

        // Store element in DB
        $store_id = -1;
        if ($update == true)
            $store_id = $this->findMediaObjectID($media);
        if ($store_id != -1)
            $query = 'REPLACE INTO media_objects (id, folder_id, type, title, filesize, lastmod, filename, ';
        else
            $query = 'INSERT INTO media_objects (folder_id, type, title, filesize, lastmod, filename, ';
        $query .= 'download_path, focal, lens_id, fstop, shutter, iso, originaldate, width, height, lens_is_zoom, ';
        $query .= 'camera_id, duration, longitude, latitude, altitude) VALUES (';
        if ($store_id != -1)
            $query .= $store_id. ', ';
        if ($media->folder != NULL) {
            $query .= $media->folder->db_id. ', ';
        } else
            $query .= ' -1, ';
        $query .= "'".$this->escape_string($media->type)         . "', ";
        $query .= "'".$this->escape_string($media->title)        . "', ";
        $query .= $media->filesize         . ', ';
        $query .= "'".$this->escape_string($media->lastmod)      . "', ";
        $query .= "'".$this->escape_string($media->filename)     . "', ";
        $query .= "'".$this->escape_string($media->download_path). "', ";
        $query .= $media->focal            . ', ';
        $query .= $lens_id                 . ', ';
        $query .= "'".$this->escape_string($media->fstop)        . "', ";
        $query .= "'".$this->escape_string($media->shutter)      . "', ";
        $query .= $media->iso              . ', ';
        $query .= "'".$this->escape_string($media->originaldate) . "', ";
        $query .= $media->width            . ', ';
        $query .= $media->height           . ', ';
        $query .= $media->lens_is_zoom     . ', ';
        $query .= $camera_id               . ', ';
        $query .= "'".$this->escape_string($media->duration)     . "', ";
        $query .= number_format($media->longitude, 12, '.', '')  . ', ';
        $query .= number_format($media->latitude,  12, '.', '')  . ', ';
        $query .= number_format($media->altitude,   1, '.', '')  . ');';
        if ($this->query($query) === FALSE) {
            print("-E- Failed query: $query:".$this->error);
        } else {
            $media->db_id = $this->insert_id;
        }
        // Process tags
        if ($store_id != -1) {
            // Get currently stored tags
            $db_tags = $this->getElementTags($store_id);
            foreach ($db_tags as $db_tag) {
                // Check DB tag against current tag
                if (in_array($db_tag, $media->tags) == true) {
                    // Tag already there, removing from list
                    $media->tags = array_diff($media->tags, array($db_tag));
                } else {
                    // Tag not there anymore, removing from DB
                    $tag_id = $this->findTagID($db_tag);
                    if ($this->query("DELETE FROM media_tags WHERE media_id=$store_id AND tag_id=$tag_id;") === FALSE)
                        die("-E-  Failed query: ".$this->error);
                }
            }
        }
        foreach ($media->tags as $tag) {
            $tag_id = $this->findTagID($tag);
            $query = "REPLACE INTO media_tags (media_id, tag_id) VALUES ($media->db_id, $tag_id)";
            if ($this->query($query) === FALSE) {
                die("-E-  Failed query was: $query:".$this->error);
            }
        }
    }

    function loadMediaObject(mediaObject &$media, $id)
    {
        $result = $this->query("SELECT * FROM media_objects WHERE id=$id;");

        if ($result === FALSE) {
            throw new Exception($this->error);
        } else {
            $row = $result->fetch_assoc();
            $result->free();
            $media->db_id         = $id;
            $media->folder_id     = $row['folder_id'];
            $media->title         = $row['title'];
            $media->type          = $row['type'];
            $media->camera        = $this->cameraName($row['camera_id']);
            $media->filesize      = $row['filesize'];
            $media->lastmod       = $row['lastmod'];
            $media->filename      = $row['filename'];
            $media->download_path = $row['download_path'];
            $media->focal         = $row['focal'];
            $media->lens          = $this->lensName($row['lens_id']);
            $media->fstop         = $row['fstop'];
            $media->shutter       = $row['shutter'];
            $media->iso           = $row['iso'];
            $media->originaldate  = $row['originaldate'];
            $media->width         = $row['width'];
            $media->height        = $row['height'];
            $media->lens_is_zoom  = $row['lens_is_zoom'];
            $media->duration      = $row['duration'];
            $media->longitude     = $row['longitude'];
            $media->latitude      = $row['latitude'];
            $media->altitude      = $row['altitude'];
        }
        // Load corresponding tags
        $media->tags = $this->getElementTags($id);
    }

    function getElementPath($id)
    {
        // Return element download path (which id is given in arg) 
        $result = $this->query("SELECT download_path FROM media_objects WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['download_path'];
    }

    function removeElement($id)
    {
        // Remove element from DB
        $result = $this->query("DELETE FROM media_objects WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error);    
    }

    function storeMediaFolder(mediaFolder &$media, $update=false)
    {
        $query = 'INSERT INTO media_folders (parent_id, title, lastmod, originaldate, foldername, thumbnail_source) VALUES (';
        if ($media->parent == NULL)
            $query .= "-1, ";
        else
            $query .= $media->parent->db_id.", ";
        $query .= "'".$this->escape_string($media->title)."', ";
        $query .= "'".$this->escape_string($media->lastmod)."', ";
        $query .= "'".$this->escape_string($media->originaldate)."', ";
        $query .= "'".$this->escape_string($media->name)."', ";
        $query .= "'".$this->escape_string($media->thumbnail)."');";
        if ($this->query($query) === FALSE) {
            print("-E-  Failed query: $query\n");
            throw new Exception($this->error);
        } else {
            $media->db_id = $this->insert_id;
        }
        if (count($media->subfolder) > 0) {
            foreach($media->subfolder as $subfolder)
                $this->storeMediaFolder($subfolder, $update);
        }
        if (count($media->element) > 0) {
            foreach($media->element as $element)
                $this->storeMediaObject($element, $update);
        }
    }

    function loadMediaFolder(mediaFolder &$media, $id, $depth = 1)
    {
        $result = $this->query("SELECT * FROM media_folders WHERE id=$id;");

        if ($result === FALSE) {
            throw new Exception($this->error);
        } else {
            $row = $result->fetch_assoc();
            $result->free();
            $media->db_id        = $id;
            $media->title        = $row['title'];
            $media->lastmod      = $row['lastmod'];
            $media->originaldate = $row['originaldate'];
            $media->name         = $row['foldername'];
            $media->thumbnail    = $row['thumbnail_source'];
        }
        // Fetch subfolders if required
        if ($depth > 0) {
            $depth--;
            $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$id;");
            if ($results === FALSE)
                throw new Exception($this->error);
            while($row = $results->fetch_assoc()) {
                if ($row['id'] != -1) {
                    $subfolder = new mediaFolder($media);
                    $this->loadMediaFolder($subfolder, $row['id'], $depth);
                    $media->subfolder[] = $subfolder;
                }
            }
            $results->free();
        }
        // Fetch elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id;");
        if ($results === FALSE) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            if ($row['id'] != -1) {
                $element = new mediaObject($media);
                $this->loadMediaObject($element, $row['id']);
                $media->element[] = $element;
            }
        }
        $results->free();
    }

    function removeFolder($id, $recurse = true)
    {
        // Remove folder from DB with associated element
        $element_list = $this->getFolderElements($id);
        foreach ($element_list as $element)
            $this->removeElement($element);
        if ($recurse == true) {
            $subfolder_list = $this->getSubFolder($id);
            foreach($subfolder_list as $subfolder) {
                $this->removeFolder($subfolder, true);
            }
        }
        $result = $this->query("DELETE FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error);    
    }

    function getFolderID($path)
    {
        // Top folder get db_id = -1 by construction
        if ($path == "") return -1;
        // Find folder ID in DB according to the path given (relative to the gallery)
        $path_array = explode('/', $path);
        $parent_id  = -1;
        $folder_id  = -1;

        foreach($path_array as $current_path_level) {
            $parent_id = $folder_id;
            $result = $this->query("SELECT id FROM media_folders WHERE parent_id=$parent_id AND foldername='".$this->escape_string($current_path_level)."';");
            if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_row();
            $result->free();
            if ($row[0] != NULL)
                $folder_id = $row[0];
            else
                return -1;
        }
        return $folder_id;
    }

    function getFolderPath($id)
    {
        // Return folder (which id is given in arg) full path
        // Sanity check
        if ($id == -1) return '.';
        $parent_id   = -1;
        $folder_path = "";
        $result = $this->query("SELECT parent_id, foldername FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        $parent_id = $row['parent_id'];
        if ($row['foldername'] != "")
            $folder_path = $row['foldername'];
        while($parent_id != -1) {
            $result = $this->query("SELECT parent_id, foldername FROM media_folders WHERE id=$parent_id;");
            if ($result === false) throw new Exception($this->error); else $row = $result->fetch_assoc();
            $parent_id = $row['parent_id'];
            if ($row['foldername'] != "")
                $folder_path = $row['foldername'].'/'.$folder_path; 
        }
        return $folder_path;
    }

    function getNeighborFolders($id)
    {
        $neighbor_array = array();
        $results        = $this->query("SELECT parent_id FROM media_folders WHERE id=$id;");
        if ($results === FALSE) throw new Exception($this->error); else $row = $results->fetch_assoc();
        $parent_id = $row['parent_id'];
        $results->free();
        // Returns array of id of neighbor folders
        $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$parent_id;");
        if ($results === FALSE) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            if ($row['id'] != $id)
                $neighbor_array[] = $row['id'];
        }
        $results->free();
        return $neighbor_array;
    }

    function getFolderTitle($id)
    {
        // Return folder (which id is given in arg) title
        $result = $this->query("SELECT title FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['title'];
    }

    function getFolderName($id)
    {
        // Return folder (which id is given in arg) name
        $result = $this->query("SELECT foldername FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['foldername'];
    }

    function getFolderDate($id)
    {
        setlocale(LC_ALL, "fr_FR.utf8");
        // Return folder (which id is given in arg) date
        $result = $this->query("SELECT originaldate FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return strftime("%e %B %Y", strtotime($row['originaldate']));
    }

    function getLatestUpdatedFolder($nb_latest)
    {
        $latest_array = array();
        // Return array of id of latest updated folders
        $results = $this->query("SELECT id FROM media_folders ORDER BY lastmod DESC;");
        if ($results === FALSE) throw new Exception($this->error);
        while(($row = $results->fetch_assoc()) && (count($latest_array) < $nb_latest)) {
            if ($row['id'] != 1)
                $latest_array[] = $row['id'];
        }
        $results->free();
        return $latest_array;
    }

    function getFolderHierarchy($id)
    {
        $folder_array[] = $id;
        // Return array of folders from top level up to id
        $result = $this->query("SELECT parent_id FROM media_folders WHERE id=$id;");
        if ($result === false) throw new Exception($this->error); else $row = $result->fetch_assoc();
        $parent_id = $row['parent_id'];
        while($parent_id != -1) {
            array_unshift($folder_array, $parent_id);
            $result = $this->query("SELECT parent_id FROM media_folders WHERE id=$parent_id;");
            if ($result === false) throw new Exception($this->error); else $row = $result->fetch_assoc();
            $parent_id = $row['parent_id'];
        }
        return $folder_array;
    }

    function getSubFolders($id, $recurse = false)
    {
        global $reverse_subalbum_sort;

        $sub_array = array();
        // Returns array of id of child folders
        $query = "SELECT id FROM media_folders WHERE parent_id=$id";
        if ($id == -1)                        $query .= " ORDER BY foldername ASC;";
        else if ($reverse_subalbum_sort != 0) $query .= " ORDER BY foldername DESC;";
        else                                  $query .= " ORDER BY foldername ASC;";
        $results = $this->query($query);
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $sub_array[] = $row['id'];
        }
        $results->free();
        if (($recurse == true) and (count($sub_array) > 0)) {
            foreach($sub_array as $subfolder) {
                $subfolders = $this->getSubFolders($subfolder, true);
                $sub_array  = array_merge($sub_array, $subfolders);
            }
        }
        return $sub_array;
    }

    function getFolderElements($id, $recurse = false)
    {
        $element_list = array();
        // Returns array of id of folder elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id ORDER BY filename ASC;");
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $element_list[] = $row['id'];
        }
        $results->free();
        if ($recurse == true) {
            $subfolders = $this->getSubFolders($id);
            foreach($subfolders as $subfolder) {
                $subelements = $this->getFolderElements($subfolder, true);
                $element_list = array_merge($element_list, $subelements);
            }
        }
        return $element_list;
    }

    function getFolderElementsCount($id, $recurse = false)
    {
        // Returns number of elements inside this folder (recursively if wanted)
        $results = $this->query("SELECT COUNT(*) FROM media_objects WHERE folder_id=$id;");
        if ($results === false) throw new Exception($this->error); else $row = $results->fetch_row();
        $count = $row[0];
        if ($recurse == true) {
            $subfolders = $this->getSubFolders($id);
            foreach($subfolders as $subfolder) {
                $count += $this->getFolderElementsCount($subfolder, true);
            }
        }
        return $count;
    }

    ///////////////////////// TAG MANAGEMENT /////////////////////////////////
    //! Get Tag ID in DB. If tag is not found, add it to the table
    function findTagID($tagname)
    {
        $result = $this->query("SELECT id FROM tags WHERE name = '".$this->escape_string($tagname)."'");
        if ($result === FALSE) die("-E-  Failed query".$this->error); else $row = $result->fetch_assoc();

        if ($result->num_rows == 0) {
            $query = "INSERT INTO tags (name) VALUES ('".$this->escape_string($tagname)."')";
            $this->query($query);
            return $this->insert_id;
        } else {
            return $row['id'];
        }
    }

    //! Return Tag Name associated to ID
    function tagName($tag_id)
    {
        $result = $this->query("SELECT name FROM tags WHERE id = $tag_id");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['name'];
    }

    //! Return array of id of elements which have the given tag(s) in their description
    function getElementsByTags($tag_array)
    {
        $element_list = array();
        $query_str = "SELECT media_tags.media_id FROM media_tags ";
        if (count($tag_array) == 1) {
            $tag_id = $this->findTagID($tag_array[0]);
            $query_str .= "WHERE media_tags.tag_id='".$tag_id."'";
        } else {
            foreach (range(1, count($tag_array) - 1) as $tag_idx) {
                $query_str .= "LEFT JOIN media_tags AS media".$tag_idx." ON media_tags.media_id = media".$tag_idx.".media_id ";
            }
            $tag_id     = $this->findTagID($tag_array[0]);
            $query_str .= "WHERE media_tags.tag_id='".$tag_id."' ";
            foreach (range(1, count($tag_array)- 1) as $tag_idx) {
                $tag_id     = $this->findTagID($tag_array[$tag_idx]);
                $query_str .= "AND media".$tag_idx.".name='".$tag_id."' ";
            }
        }
        $results = $this->query($query_str.";");
        // print($query_str);
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $element_list[] = $row['media_id'];
        }
        $results->free();
        return $element_list; 
    }

    //! Returns tags associated with the given element
    function getElementTags($element_id)
    {
        $results = $this->query("SELECT name FROM tags, media_tags WHERE media_id=$element_id AND tags.id=media_tags.tag_id;");
        if ($results === false) throw new Exception($this->error);

        $tag_list = array();
        while($tag = $results->fetch_assoc())
            $tag_list[] = $tag['name'];

        $results->free();
        return $tag_list;
    }

    //! Return list of all defined tags in database
    function getAvailableTags()
    {
        $tag_list = array();

        $results = $this->query("SELECT name FROM tags ORDER BY name;");
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $tag_list[] = $row['name'];
        }
        $results->free();
        return $tag_list;
    }

    /////////////////////// CAMERA MANAGEMENT ////////////////////////////////
    //! Get Camera ID in DB. If camera is not found, add it to the table
    function findCameraID($camera_name)
    {
        $result = $this->query("SELECT id FROM cameras WHERE name = '".$this->escape_string($camera_name)."'");
        if ($result === FALSE) die("-E-  Failed query".$this->error); else $row = $result->fetch_assoc();

        if ($result->num_rows == 0) {
            $query = "INSERT INTO cameras (name) VALUES ('".$this->escape_string($camera_name)."')";
            $this->query($query);
            return $this->insert_id;
        } else {
            return $row['id'];
        }
    }

    //! Return Camera Name associated to ID
    function cameraName($camera_id)
    {
        if ($camera_id == -1) return '';

        $result = $this->query("SELECT name FROM cameras WHERE id = $camera_id");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['name'];
    }

    /////////////////////// LENS MANAGEMENT ////////////////////////////////
    //! Get Lens ID in DB. If lens is not found, add it to the table
    function findLensID($lens_name)
    {
        $result = $this->query("SELECT id FROM lenses WHERE name = '".$this->escape_string($lens_name)."'");
        if ($result === FALSE) die("-E-  Failed query".$this->error); else $row = $result->fetch_assoc();

        if ($result->num_rows == 0) {
            $query = "INSERT INTO lenses (name) VALUES ('".$this->escape_string($lens_name)."')";
            $this->query($query);
            return $this->insert_id;
        } else {
            return $row['id'];
        }
    }

    //! Return Lens Name associated to ID
    function lensName($lens_id)
    {
        if ($lens_id == -1) return '';

        $result = $this->query("SELECT name FROM lenses WHERE id = $lens_id");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['name'];
    }

    function getTimedFolderList($top_id = 0)
    {
        $folder_list = array();

        // Return array of folders which have an original date and have photos inside
        $result = $this->query('SELECT * FROM media_folders WHERE originaldate <> "";');
        if ($result === false) throw new Exception($this->error);
        while($row = $result->fetch_assoc()) {
            if ($this->getFolderElementsCount($row['id']) != 0) {
                if ($top_id != 0) {
                    // Folder has to be a subfolder of the given one
                    $fhier = $this->getFolderHierarchy($row['id']);
                    if (array_search($row['id'], $fhier) === FALSE)
                        continue;
                }
                $folder_list[] = $row['id'];
            }
        }
        $result->free();
        return $folder_list;
    }
}

?>
