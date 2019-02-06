<?php
// OPTIONS - PLEASE CONFIGURE THESE BEFORE USE!

$yourEmail = "joeymillard@hotmail.co.uk"; // the email address you wish to receive these mails through
$yourWebsite = "joemillard.com"; // the name of your website
$thanksPage = ''; // URL to 'thanks for sending mail' page; leave empty to keep message on the same page 
$maxPoints = 4; // max points a person can hit before it refuses to submit - recommend 4
$requiredFields = "name,email,comments"; // names of the fields you'd like to be required as a minimum, separate each field with a comma


// DO NOT EDIT BELOW HERE
$error_msg = array();
$result = null;

$requiredFields = explode(",", $requiredFields);

function clean($data) {
	$data = trim(stripslashes(strip_tags($data)));
	return $data;
}
function isBot() {
	$bots = array("Indy", "Blaiz", "Java", "libwww-perl", "Python", "OutfoxBot", "User-Agent", "PycURL", "AlphaServer", "T8Abot", "Syntryx", "WinHttp", "WebBandit", "nicebot", "Teoma", "alexa", "froogle", "inktomi", "looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory", "Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot", "crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz");

	foreach ($bots as $bot)
		if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false)
			return true;

	if (empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] == " ")
		return true;
	
	return false;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	if (isBot() !== false)
		$error_msg[] = "No bots please! UA reported as: ".$_SERVER['HTTP_USER_AGENT'];
		
	// lets check a few things - not enough to trigger an error on their own, but worth assigning a spam score.. 
	// score quickly adds up therefore allowing genuine users with 'accidental' score through but cutting out real spam :)
	$points = (int)0;
	
	$badwords = array("adult", "beastial", "bestial", "blowjob", "clit", "cum", "cunilingus", "cunillingus", "cunnilingus", "cunt", "ejaculate", "fag", "felatio", "fellatio", "fuck", "fuk", "fuks", "gangbang", "gangbanged", "gangbangs", "hotsex", "hardcode", "jism", "jiz", "orgasim", "orgasims", "orgasm", "orgasms", "phonesex", "phuk", "phuq", "pussies", "pussy", "spunk", "xxx", "viagra", "phentermine", "tramadol", "adipex", "advai", "alprazolam", "ambien", "ambian", "amoxicillin", "antivert", "blackjack", "backgammon", "texas", "holdem", "poker", "carisoprodol", "ciara", "ciprofloxacin", "debt", "dating", "porn", "link=", "voyeur", "content-type", "bcc:", "cc:", "document.cookie", "onclick", "onload", "javascript");

	foreach ($badwords as $word)
		if (
			strpos(strtolower($_POST['comments']), $word) !== false || 
			strpos(strtolower($_POST['name']), $word) !== false
		)
			$points += 2;
	
	if (strpos($_POST['comments'], "http://") !== false || strpos($_POST['comments'], "www.") !== false)
		$points += 2;
	if (isset($_POST['nojs']))
		$points += 1;
	if (preg_match("/(<.*>)/i", $_POST['comments']))
		$points += 2;
	if (strlen($_POST['name']) < 3)
		$points += 1;
	if (strlen($_POST['comments']) < 15 || strlen($_POST['comments']) > 1500)
		$points += 2;
	if (preg_match("/[bcdfghjklmnpqrstvwxyz]{7,}/i", $_POST['comments']))
		$points += 1;
	// end score assignments

	if ( !empty( $requiredFields ) ) {
		foreach($requiredFields as $field) {
			trim($_POST[$field]);
			
			if (!isset($_POST[$field]) || empty($_POST[$field]) && array_pop($error_msg) != "Please fill in all the required fields and submit again.\r\n")
				$error_msg[] = "Please fill in all the required fields and submit again.";
		}
	}

	if (!empty($_POST['name']) && !preg_match("/^[a-zA-Z-'\s]*$/", stripslashes($_POST['name'])))
		$error_msg[] = "The name field must not contain special characters.\r\n";
	if (!empty($_POST['email']) && !preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', strtolower($_POST['email'])))
		$error_msg[] = "That is not a valid e-mail address.\r\n";
	if (!empty($_POST['url']) && !preg_match('/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i', $_POST['url']))
		$error_msg[] = "Invalid website url.\r\n";
	
	if ($error_msg == NULL && $points <= $maxPoints) {
		$subject = "Automatic Form Email";
		
		$message = "You received this e-mail message through your website: \n\n";
		foreach ($_POST as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $subval) {
					$message .= ucwords($key) . ": " . clean($subval) . "\r\n";
				}
			} else {
				$message .= ucwords($key) . ": " . clean($val) . "\r\n";
			}
		}
		$message .= "\r\n";
		$message .= 'IP: '.$_SERVER['REMOTE_ADDR']."\r\n";
		$message .= 'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
		$message .= 'Points: '.$points;

		if (strstr($_SERVER['SERVER_SOFTWARE'], "Win")) {
			$headers   = "From: $yourEmail\r\n";
		} else {
			$headers   = "From: $yourWebsite <$yourEmail>\r\n";	
		}
		$headers  .= "Reply-To: {$_POST['email']}\r\n";

		if (mail($yourEmail,$subject,$message,$headers)) {
			if (!empty($thanksPage)) {
				header("Location: $thanksPage");
				exit;
			} else {
				$result = 'Your mail was successfully sent.';
				$disable = true;
			}
		} else {
			$error_msg[] = 'Your mail could not be sent this time. ['.$points.']';
		}
	} else {
		if (empty($error_msg))
			$error_msg[] = 'Your mail looks too much like spam, and could not be sent this time. ['.$points.']';
	}
}
function get_data($var) {
	if (isset($_POST[$var]))
		echo htmlspecialchars($_POST[$var]);
}
?>

<html>

  <head>
    
    <title>PHP Test</title>
    
    <script src="site_libs/jquery-1.11.3/jquery.min.js"></script>
    <script src="site_libs/bootstrap-3.3.5/js/bootstrap.min.js"></script>
    
    <link rel='stylesheet' type='text/css' href="site_libs/bootstrap-3.3.5/css/bootstrap.min.css">
    <link rel='stylesheet' type='text/css' href="styles.css">
  
  </head>

<body>
<div class="container-fluid main-container">
<div class="navbar navbar-default  navbar-fixed-top" role="navigation">
  <div class="container">
    <div class="navbar-header active">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand hidden-xs hidden-sm" href="index.html"><img src="Images/Small-no-text-logo_box.png" width=""></a>
      <a class="navbar-brand hidden-md hidden-lg" href="index.html"><img src="Images/Small-no-text-logo_box.png" width="50" height="50"></a>
    </div>
    <div id="navbar" class="navbar-collapse collapse">
      <ul class="nav navbar-nav"></ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="index.html">Home</a></li>
        <li class="dropdown">
          <a href="#" data-toggle="dropdown" class="dropdown-toggle">About <b class="caret"></b></a>
            <ul class="dropdown-menu">
              <a class="dropdown-item" href="about.html">Why Graph Start?</a>
              <br>
              <a class="dropdown-item" href="team.html">Team</a>
              <br>
              <a class="dropdown-item" href="apply.html">Apply</a>
            </ul>
        </li>
        <li class="dropdown">
          <a href="#" data-toggle="dropdown" class="dropdown-toggle">Start <b class="caret"></b></a>
            <ul class="dropdown-menu">
              <a class="dropdown-item" href="start.html">Introduction</a>
              <br>
              <a class="dropdown-item" href="install.html">Install</a>
              <br>
              <a class="dropdown-item" href="rstudio.html">R Studio</a>
            </ul>
        </li>
        <li class="dropdown">
          <a href="#" data-toggle="dropdown" class="dropdown-toggle">Plots <b class="caret"></b></a>
            <ul class="dropdown-menu">
              <a class="dropdown-item" href="plots.html">Introduction</a>
              <br>
              <a class="dropdown-item" href="plot_tutorials.html">Plot Tutorials</a>
            </ul>
        </li>
        <li class="dropdown">
          <a href="#" data-toggle="dropdown" class="dropdown-toggle">Maps <b class="caret"></b></a>
            <ul class="dropdown-menu">
              <a class="dropdown-item" href="maps.html">Introduction</a>
              <br>
              <a class="dropdown-item" href="gis_tutorials.html">GIS Tutorials</a>
            </ul>
        </li>
      </ul>
    </div><!--/.nav-collapse -->
  </div><!--/.container -->
</div>
<!--/.nav-collapse -->
<br>
<br class="hidden-xs hidden-sm">
<br class="hidden-xs hidden-sm">


<br>
<br>

<?php
if (!empty($error_msg)) {
	echo '<p class="error">ERROR: '. implode("<br />", $error_msg) . "</p>";
}
if ($result != NULL) {
	echo '<p class="success">'. $result . "</p>";
}
?>



<form action="<?php echo basename(__FILE__); ?>" method="post">

<div class="jumbotron jumbotron-fluid">

<h1>So you want to work with Graph Start?</h1>      

<p>At Graph Start we're always looking for keen volunteers. If you're looking to get into social            media marketing, or just enjoy sending the odd whimsical tweet, we want you on board.</p>
<noscript>
		<p><input type="hidden" name="nojs" id="nojs" /></p>
</noscript>
<p>
	<label for="name">Name: *</label> 
		<input class="form-control" type="text" name="name" id="name" value="<?php get_data("name"); ?>" /><br />
	
	<label for="email">E-mail: *</label> 
		<input class="form-control" type="text" name="email" id="email" value="<?php get_data("email"); ?>" /><br />
	
	<label for="url">Website URL:</label> 
		<input class="form-control" type="text" name="url" id="url" value="<?php get_data("url"); ?>" /><br />
		
	<label for="location">Location:</label>
		<input class="form-control" type="text" name="location" id="location" value="<?php get_data("location"); ?>" /><br />
	
	<label for="comments">Comments: *</label>
		<textarea class="form-control" rows="5" name="comments" id="comments" rows="5" cols="20"><?php get_data("comments"); ?></textarea><br />
</p>
<p>
	<input class="btn btn-default btn-md" type="submit" name="submit" id="submit" value="Send" <?php if (isset($disable) && $disable === true) echo ' disabled="disabled"'; ?> />
</p>

<div class="navbar navbar-default navbar-fixed-bottom">Graph Start (2015)
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<a href="https://twitter.com/graph_start" class="fa fa-twitter pull-right fa-2x"></a>
<a href="https://www.facebook.com/graphstart" class="fa fa-facebook pull-right fa-2x"></a>

</div>
</div>

</div>
</form>

</body>
</html>

