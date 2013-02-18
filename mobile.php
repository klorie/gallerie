<?php
require_once "include.php";
require_once "mobile/display.php";

if (isset($_GET['path']))
    $path = $_GET["path"];
else
    $path = "";
$path = safeDirectory($path);
$m_db        = new mediaDB();
$m_folder_id = $m_db->getFolderID($path);
?>
<!DOCTYPE html> 
<html> 
  <head> 
<?php echo "<title>".$gal_title."</title>"; ?> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
  <link rel="stylesheet" href="css/photoswipe.css" />
  <script src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
  <script src="js/klass.min.js"></script>
  <script src="js/photoswipe-3.0.5.min.js"></script>
  <script type="text/javascript">
    $(document).bind('pageinit', function(){
      if ( $("#Gallery a").length > 0) {
        var myPhotoSwipe = $("#Gallery a").photoSwipe({ jQueryMobile: true, enableMouseWheel: false, enableKeyboard: false });
      }
    });
  </script>
</head> 
<body> 
<div data-role="page" data-theme="a" data-back-btn-text="Retour">
	<div data-role="header">
		<h1><?php echo "$gal_title"; ?></h1>
        <a href="mobile.php" data-icon="home" data-iconpos="notext" class="ui-btn-right" title="Accueil">Accueil</a>
	</div><!-- /header -->

	<div data-role="content">	
        <?php displaySubFolderList($m_folder_id, $m_db) ?>
        <?php displayElementList($m_folder_id, $m_db) ?>
	</div><!-- /content -->

	<div data-role="footer">
		<h1>Gallerie Mobile v2.6.0 - C. Laury</h1>
	</div><!-- /footer -->
</div><!-- /page -->

</body>
</html>
