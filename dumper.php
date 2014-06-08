<?php
$queue = file('hosts.txt');
sort($queue);
$checked_domains = array();
$results = fopen('results.txt', 'w');
while(count($queue) > 0) {
	$domain = trim(array_pop($queue));

	if(empty($domain) or ($domain[0] === '#'))
		continue;
	
	if(substr($domain, 0, 2) === '*.')
		$domain = substr($domain, 2);

	if(!in_array($domain, $checked_domains)) {
		$checked_domains[] = $domain;
		fputs($results, $domain.PHP_EOL);
	} else {
		continue;
	}

	print($domain.PHP_EOL);
	$g = stream_context_create (array("ssl" => array("capture_peer_cert" => true), "http" => array('timeout' => 2)));
	$r = @fopen("https://".$domain."/", "rb", false, $g);
	if($r !== FALSE) {
	 	$cont = stream_context_get_params($r);
	 	if(isset($cont["options"]["ssl"]["peer_certificate"])) {
			$parsed = openssl_x509_parse($cont["options"]["ssl"]["peer_certificate"]);
			if(isset($parsed['extensions'],$parsed['extensions']['subjectAltName'])) {
				preg_match_all('/DNS:([a-z\.0-9_\-\*]+)/',$parsed['extensions']['subjectAltName'],$m);
				if(isset($m[1])) {
					$queue = array_merge($m[1],$queue);
					$queue = array_unique($queue);
					sort($queue);
				}
			}
		}
		fclose($r);
	}
}
fclose($results);
sort($checked_domains);
file_put_contents('results.txt',implode(PHP_EOL, $checked_domains));