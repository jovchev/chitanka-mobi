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
	$image = file_get_contents("http://chitanka.info".$book);
	file_put_contents($epub, $image);

}

function convertEpub2Mobi($epub, $mobi)
{
	$cmd = getcwd(). '/cgi-bin/kindlegen ' . $epub . ' -o '. $mobi;
	$a = exec($cmd);
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