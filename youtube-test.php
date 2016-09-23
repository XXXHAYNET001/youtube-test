<?php
//header('Content-Type: application/json');
if (empty($_GET)) {
echo 'No request found';
}else	{
if ($_GET['url']){
$data = YouTuBe($_GET['url']);
echo '<textarea>'.$data.'</textarea>';
echo '<br />';
echo '<audio src="'.$data.'" controls type="audio/mp4" async></audio><br />';
}}

function curls($url) {
	$ch = @curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	$head[] = "Connection: keep-alive";
	$head[] = "Keep-Alive: 300";
	$head[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
	$head[] = "Accept-Language: en-us,en;q=0.5";
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	$page = curl_exec($ch);
	curl_close($ch);
	return $page;
}

function YouTuBe($link) {
	$content = curls($link);
	if(preg_match('#ytplayer\.config = (\{.+\});#U', $content, $matches)) {
		$jsonData = json_decode($matches[1], true);
		gAlgorithm($jsonData['assets']['js']);
		$streamMap = $jsonData['args']['adaptive_fmts'];
		$streamMap = explode(',', $streamMap);
		$streamMap = @array_reverse($streamMap);
		foreach ($streamMap as $url)
		{
			$url = str_replace('\u0026', '&', $url);
			$url = urldecode($url);
			parse_str($url, $value);
			$value['url'] = preg_replace('#([^:\/\s]+)((\/*.googlevideo))#', hex2bin('72656469726563746f72').'$2', $value['url']);
			if(isset($value['s']))
			{
				$value['url'] .= '&signature='.gSign($value['s']);
				unset($value['s']);
			}
			unset($value['xtags']);
/* 			unset($value['init']);
			unset($value['projection_type']);
			unset($value['bitrate']);
			unset($value['type']);
			unset($value['index']);
			unset($value['requiressl']);
			$value['gcr'] ='us';
			$value['cms_redirect'] = 'yes'; */
			
			//unset($value['initcwndbps']);
			$dataURL = $value['url'];
			unset($value['url']);
			if(in_array($value['itag'],array(140))) {
				$data = str_replace('"', "'", $dataURL.'&'.urldecode(http_build_query($value)));
			}
		}
	}
	if($data) {
		$js = $data;
	}else {
return YouTuBe('https://www.youtube.com/watch?v=CqfI0AKEgdE');
}
return $js;
}

function gSign($s)
	{
		global $cipher;
		foreach($cipher as $value)
		{
			if($value['method'] == 'switch')
			{
				$t = $s[0];
				
				$s[0] = $s[$value['value']%strlen($s)];
				
				$s[$value['value']] = $t;
			}
			else if($value['method'] == 'half')
			{
				$s = substr($s, $value['value']);
			}
			else if($value['method'] == 'throw')
			{
				$s = strrev($s);
			}
		}
		return $s;
	}

function gAlgorithm($lct){
		global $cipher;
		$context = array('ssl' => array('verify_peer' => false));
		$contents = file_get_contents('http:'.$lct, false, stream_context_create($context));
		preg_match('#"signature",([A-Za-z]+)#', $contents, $match);
		$function = $match[1];
		
		preg_match('#([A-Za-z0-9]+):function\(a\)\{a\.reverse\(\)\}#', $contents, $match);
		$method[$match[1]] = 'throw';
		
		preg_match('#([A-Za-z0-9]+):function\(a,b\)\{a\.splice\(0,b\)\}#', $contents, $match);
		$method[$match[1]] = 'half';
		
		preg_match('#([A-Za-z0-9]+):function\(a,b\)\{var c=a\[0\];a\[0\]=a\[b%a\.length\];a\[b\]=c\}#', $contents, $match);
		$method[$match[1]] = 'switch';
		
		preg_match('#'.$function.'=function\(a\)\{a=a\.split\(""\);([^\}]+)return a\.join\(""\)}#', $contents, $match);
		$contents = $match[1];
		
		preg_match_all('#[A-Za-z0-9]+\.([A-Za-z0-9]+)\(a,([0-9]+)\)#', $contents, $match);
		
		foreach($match[0] as $key => $temp)
		{
			
			$cipher[$key] = array
			(
				'method' => $method[$match[1][$key]],
				'value' => $match[2][$key]
			);

		}
}
