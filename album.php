<?php
echo '<html>
<head><title>Welcome</title>
	<link rel="stylesheet" href="temp.css">
</head>
<body>
		<div class="outer_div">
		<div id="output1">
		<h3>FILE UPLOAD</h3>
			<form action="album.php" method="POST" enctype="multipart/form-data">
	  		 		<input type="file" name="uploadFile" id="uploadFile"></br></br>
		  		 	<input type="submit" value="Upload">
			</form>
		</div>
	</div>';
echo '</body>
	</html>';
// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit(0);

require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "?????",      // Put your Dropbox API key here
	'app_secret' => "?????",   // Put your Dropbox API secret here
	'app_full_access' => true,
),'en');


// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	//echo "loaded access token:";
	//print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}
//upload the file
if(!empty($_FILES['uploadFile'])){
		$upFile=$_FILES["uploadFile"]["name"];
		$ext=pathinfo($upFile,PATHINFO_EXTENSION);
		if($ext=='jpg'||$ext=='JPG'){
		$dropbox->UploadFile($_FILES["uploadFile"]["tmp_name"],$upFile);
		echo "Uploaded File - ".$upFile;
		}
		else{
			echo 'The File chosen is not a .jpg file</br>';
		}
}
//delete the file
if(isset($_POST['delete'])){
		$dName=$_POST['delete'];
		$dropbox->Delete($dName);
		header('Location:album.php');
		echo 'Deleted File is - '.$dName;
}
//download and display
echo '<div id="output3"><h4>IMAGE VIEWER</h4>';
if(isset($_GET['download'])){
		$test_file = basename($_GET['download']);
		$dropbox->DownloadFile($_GET['download'],$test_file);
		$disp=$dropbox->GetLink($_GET['download'],false);
		echo '</br><p>Downloaded File is- '.$test_file.'</p></br></br>
		<img src="'.$disp.'" height="80%" width="80%">';
}
echo '</div>';
//show current files on dropbox
$files = $dropbox->GetFiles("",false);
$file = reset($files);
		echo '<div id="output2">';
		echo '<center><h4>FILES ON DROPBOX</h4></center>';
		foreach ($files as $k) {
			$imgName=basename($k->path);
			$iLink=$dropbox->GetLink($imgName,false);
			echo 'NAME- '.$imgName.'</br>';
			echo 'DOWNLOAD LINK- <a href="album.php?download='.$imgName.'">'.$iLink.'</a></br>';
			echo '<form action="album.php" method="POST">	
					<input type="hidden" value='.$imgName.' name="delete">
					<input class="butdel" type="submit" value="Remove"> 
				  </form>';
			echo '</br></br>';
		}
		echo '</div>';
function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}

function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}
// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','On');
?>