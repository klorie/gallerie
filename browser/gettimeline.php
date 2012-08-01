<?php
require_once "../include.php";
require_once "display.php";

if (isset($_GET['id']))
    $top_id = $_GET["id"];
else {
    $top_id = 1;
}

global $BASE_DIR;
global $BASE_URL;

$m_db = new mediaDB();
$json = "$BASE_DIR/cache/timeline-$top_id.json";
$jurl = "$BASE_URL/cache/timeline-$top_id.json";
if ((file_exists($json) === FALSE ) || (filemtime("$BASE_DIR/cache/timeline-1.json") > filemtime($json)))
    genTimelineData($m_db, $top_id);
?>
<!DOCTYPE html>
<html>
<head>
<?php echo "<title>".$gal_title."</title>\n"; ?> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="<?php echo $BASE_URL?>/css/layout.css" type="text/css" media="screen" charset="utf-8" />
  <link rel="stylesheet" href="<?php echo $BASE_URL?>/css/toplevelmenu.css" type="text/css" media="screen" />
  <script src="http://code.jquery.com/jquery-1.7.2.min.js"></script>		
  <script src="<?php echo $BASE_URL?>/js/navigation.js"></script>
</head>
<body>
<div id="timeline_content">
<?php
echo "<h1><a href=\"$BASE_URL/index.php\">".htmlentities($gal_title)."</a></h1>\n"; 
// Path dirs and link
displayFolderHierarchy($top_id, $m_db, false);
?>
<div id="timeline-embed"></div>
<script type="text/javascript">
  var timeline_config = {
      width:  "100%",
      height: "90%",
      source: '<?php echo $jurl?>',
      lang:   'fr',
      <?php if ($top_id != 1) echo "start_at_end: true,\n"; ?>
      // hash_bookmark: true,       
      css:    '<?php echo $BASE_URL?>/css/timeline-dark.css',
      js:     '<?php echo $BASE_URL?>/js/timeline-min.js'   
  }
</script>
<script type="text/javascript" src="<?php echo $BASE_URL?>/js/timeline-embed.js"></script>
</div>
</body>
</html>
