<?php
require_once "../include.php";

if (isset($_GET['path']))
    $path = $_GET["path"];
else {
    $path = "";
}
$path = safeDirectory($path);

$m_db          = new mediaDB();
if ($path != "")
    $id        = $m_db->getFolderID($path);
else
    $id        = 1;
$elements_list = $m_db->getFolderElements($id);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
  <head>
    <title><?php echo $gal_title ?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    
    <link rel="stylesheet" href="<?php echo $BASE_URL?>/css/supersized.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?php echo $BASE_URL?>/css/supersized.shutter.css" type="text/css" media="screen" />
    
    <script type="text/javascript" src="<?php echo $BASE_URL?>/js/jquery-1.6.2.min.js"></script>
    <script type="text/javascript" src="<?php echo $BASE_URL?>/js/jquery.easing.min.js"></script>
    <script type="text/javascript" src="<?php echo $BASE_URL?>/js/supersized.3.2.4.min.js"></script>
    <script type="text/javascript" src="<?php echo $BASE_URL?>/js/supersized.shutter.js"></script>
    <script type="text/javascript">
    	
      jQuery(function($){
      	
      	$.supersized({
      	
        // Functionality
        fit_always      :   1,  
        slide_interval  :   6000,		// Length between transitions
        transition      :   1, 			// 0-None, 1-Fade, 2-Slide Top, 3-Slide Right, 4-Slide Bottom, 5-Slide Left, 6-Carousel Right, 7-Carousel Left
        transition_speed:	700,		// Speed of transition
        thumb_links     :   1,
        new_window      :   0,
        stop_loop       :   1,
        // Components					
        slide_links		:	false,      // Individual links for each slide (Options: false, 'number', 'name', 'blank')
        slides 			:  	[			// Slideshow Images
        <?php
          $element = new mediaObject();
          foreach($elements_list as $element_id) {
            $m_db->loadMediaObject($element, $element_id);
            if ($element->type == 'video') continue; // No video in slideshow
            $thumbnail = "$BASE_URL/$thumb_folder/".getThumbnailPath($element_id);
            echo "{image: '$BASE_URL/browser/getresized.php?id=$element_id', title: '".$element->title." - <small>".$element->getSubTitle(true)."</small>', thumb: '$BASE_URL/browser/getthumb.php?id=$element_id', url: '$BASE_URL/index.php?path=$path' },\n";
          }
        ?>
        ]});
      });
      
    </script>	
  </head>
<body>

	<!--Thumbnail Navigation-->
	<div id="prevthumb"></div>
	<div id="nextthumb"></div>
	
	<!--Arrow Navigation-->
	<a id="prevslide" class="load-item"></a>
	<a id="nextslide" class="load-item"></a>
	
	<div id="thumb-tray" class="load-item">
		<div id="thumb-back"></div>
		<div id="thumb-forward"></div>
	</div>
	
	<!--Time Bar-->
	<div id="progress-back" class="load-item">
		<div id="progress-bar"></div>
	</div>
	
	<!--Control Bar-->
	<div id="controls-wrapper" class="load-item">
		<div id="controls">
			
			<a id="play-button"><img id="pauseplay" src="<?php echo $BASE_URL?>/images/supersized/pause.png"/></a>
		
			<!--Slide counter-->
			<div id="slidecounter">
				<span class="slidenumber"></span> / <span class="totalslides"></span>
			</div>
			
			<!--Slide captions displayed here-->
			<div id="slidecaption"></div>
			
			<!--Thumb Tray button-->
			<a id="tray-button"><img id="tray-arrow" src="<?php echo $BASE_URL?>/images/supersized/button-tray-up.png"/></a>
			
			<!--Navigation-->
			<ul id="slide-list"></ul>
			
		</div>
	</div>

</body>
</html>
