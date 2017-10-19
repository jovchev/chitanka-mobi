<?
		
$book = $_SERVER['QUERY_STRING'];
// TODO Add validation
$tokens = explode('/', $book);
$book_file = $tokens[sizeof($tokens)-1];
$epub = "tmp/".$book_file;
$mobi = $epub.".mobi";
$mobi_file_name = $book_file.".mobi";


if (!file_exists($mobi))
{

	if (!file_exists($epub))
	{
		downloadEpub($epub, $book);
		convertEpub2Mobi($epub, $mobi_file_name);
		downloadMobi($mobi_file_name);
	}
	else
	{
		convertEpub2Mobi($epub, $mobi_file_name);
		downloadMobi($mobi_file_name);
	}
}else{

	downloadMobi($mobi_file_name);
}

function downloadEpub($epub, $book)
{
	$fp = fopen ($epub, 'w+');
	$ch = curl_init("https://chitanka.info".$book);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	curl_setopt($ch, CURLOPT_FILE, $fp); 
	curl_exec($ch); 
	$redirectURL = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
	curl_close($ch);
	fclose($fp);        
	$zip = new ZipArchive;
	if ($zip->open($epub) === TRUE) {
		$fileToModify = 'OPS/content.opf';
		$oldContents = $zip->getFromName($fileToModify);
		//echo "<xmp>" . $oldContents . "</xmp>";
		$newContents = str_ireplace('<dc:language>български</dc:language>', '<dc:language>bg</dc:language>', $oldContents);
		//echo "<hr><xmp>" . $newContents . "</xmp>";		
		$zip->deleteName($fileToModify);
		$zip->addFromString($fileToModify, $newContents);
		$zip->close();
	} else {
		echo "ERROR!! <br />";
		echo "Redirect URL:" . $redirectURL . "<br />";
		echo "Deleting downloaded file. Try again later.";

		//echo "EPUB Contents:<xmp>";
		//readfile($epub);
		//echo "</xmp>";
		unlink($epub);

	}
}

function convertEpub2Mobi($epub, $mobi)
{
	$cmd = getcwd(). '/cgi-bin/kindlegen ' . $epub . ' -o '. $mobi;
	$a = exec($cmd);
//        echo $a;
}


function downloadMobi($file_name)
{
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($file_name));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize("tmp/".$file_name));
    ob_clean();
    flush();
    readfile("tmp/".$file_name);
    exit;
}

?>
