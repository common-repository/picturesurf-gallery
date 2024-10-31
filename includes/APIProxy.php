<?php
function apiProxy ($data, $key, $url)
{
	$JSON = new PSGalleryServicesJSON();
	if (! $data['furl'])
	{
		echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'Invalid call url'));
	}
	$data['ps_api_key'] = urlencode($key);
	$data['ps_api_url'] = urlencode($url);
	ksort($data);
	$postvars = '';
	foreach ($data as $key => $val)
	{
		if ($key != "furl")
			$postvars .= $key . '=' . rawurlencode($val) . '&';
	}
	$method = 'POST';
	$path = '/' . $data['furl'];
	
	$rv = ''; //return of a call
	if(function_exists(curl_init)) //if CURL is installed
	{	
		$session = curl_init(PICTURESURF_URL .$data['furl']); // Open the Curl session
		curl_setopt ($session, CURLOPT_POST, true);
		curl_setopt ($session, CURLOPT_POSTFIELDS, $postvars);
		// Don't return HTTP headers. Do return the contents of the call
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$rv = curl_exec($session); // Make the call
		curl_close($session);
	}
	else
	{
		$fp = fsockopen(PICTURESURF_HOST, 80);
		$requestHeader = "$method $path HTTP/1.1\r\n";
		$requestHeader .= 'Host: ' . PICTURESURF_HOST . "\r\n";
		$requestHeader .= "Content-type: application/x-www-form-urlencoded\r\n";
		$requestHeader .= 'Content-length: ' . strlen($postvars) . "\r\n";
		$requestHeader .= "Connection: close\r\n\r\n";
		$requestHeader .= $postvars;
		fputs($fp, $requestHeader);
		$ret = '';
		while (! feof($fp))
		{
			$ret .= fgets($fp, 128);
		}
		fclose($fp);
		$hunks = explode("\r\n\r\n",trim($ret));
		$rv = $hunks[1]?$hunks[1]:'';
	}
	if($rv && (strstr($rv, 'result') || strstr($rv, 'status')))
	{
		return $rv;
	}
	else
	{
		echo $rv;
		return '{result: ""}';
	}
}
?>