<?php
	$url="http://localhost/spotify_api/register";
	
	$postData=array("name"=>"malayan");
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);   
	echo "<pre>";
	print_r($response);
?>