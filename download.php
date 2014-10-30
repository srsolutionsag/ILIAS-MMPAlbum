<?php 
	$invalid_chars = array('<', '>', '?', '"', ':', '|', '\\', '/', '*', '&');

	$imgUrl = $_GET["img"];
	$imgName = $_GET["name"];
	
	// set filename to download file name
	// TODO: extension shouln't be hardcoded!!!
	$filename = $imgName;
	if (strpos(strtolower($imgName), ".jpg") === false &&
		strpos(strtolower($imgName), ".jpeg") === false)
		$filename .= ".jpg";
	
	$filename = str_replace($invalid_chars, "", $filename);
		
	// get headers and file handle
	$headers = get_headers($imgUrl, 1);
	$handle = fopen($imgUrl, 'rb');
	if ($handle)
	{
		// make keys lower case
		$headers = array_change_key_case($headers, CASE_LOWER);
		
		// get the mime type and file size
		$mimeType = $headers["content-type"];
		$length = $headers["content-length"];
		
		// output file
		header("Content-Type: $mimeType");
		header("Content-Length: $length");
		header("Content-Disposition: attachment;filename=\"$filename\"");
		fpassthru($handle);
		fclose($handle);
		exit;
	}
	else
	{
		header("HTTP/1.1 404 Not Found");
	}
?>
