<?php
include("include.php");


$mysqli = mysqli_connect("db.gmntt.com", "gmntt", "Chester123!@#", "gmntt");
if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}

$res = mysqli_query($mysqli, "set @row_number:=1");
$res = mysqli_query($mysqli, "set @section := '1'");
$res = mysqli_query($mysqli, "
select * from ( select *, @row_number :=CASE when @section = section then @row_number+1 else 1 end as a, @section := section from inventory where tmId='0C005381C20D3841' and tmStatus='AVAILABLE' and skyboxStatus != 'ON SKYBOX' and ticketPrice <= 1000000 and availability >= 2 order by section asc, row asc ) t1 where a <= 2 order by section asc, row asc;
");
echo "<pre>";
var_dump($res->num_rows);
while($row = $res->fetch_assoc())
{
    var_dump($row);
}

die();

$res = mysqli_multi_query($mysqli, "set @row_number:=1; set @section := '1'; 
select * from ( select *, @row_number :=CASE when @section = section then @row_number+1 else 1 end as a, @section := section from inventory where tmId='0C005381C20D3841' and tmStatus='AVAILABLE' and skyboxStatus != 'ON SKYBOX' and ticketPrice <= 1000000 and availability >= 2 order by section asc, row asc ) t1 where a <= 2 order by section asc, row asc;
");

    do {
        /* store first result set */
        if ($result = $mysqli->store_result()) {
            while ($row = $result->fetch_assoc()) {
		$counter++;
                echo "Section $row[section] $row[row]<br>";
            }
	    echo "counter : " . $counter;
            $result->free();
        }
        /* print divider */
        if ($mysqli->more_results()) {
            printf("<br>-----<br>");
        }
    } while ($mysqli->next_result());


die();
	
	
	
	
	
	
	
	
	
	
	$uri = 'https://www.vividseats.com/rest/v2/web/listings/2713093';
	
	$cookie = "userAgent=%7B%22headerString%22%3A%22Mozilla%2F5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_13_6%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F68.0.3440.106%20Safari%2F537.36%22%2C%22name%22%3A%22Chrome%22%2C%22majorVersion%22%3A68%2C%22minorVersion%22%3A0%2C%22patchVersion%22%3A3440%2C%22deviceType%22%3A%22desktop%22%2C%22osName%22%3A%22Mac%20OS%20X%22%2C%22osMajorVersion%22%3A10%2C%22osMinorVersion%22%3A13%2C%22osPatchVersion%22%3A6%2C%22supported%22%3Atrue%7D; lastvisit=2018-08-28%2002%3A09%3A44; ch=%5B%7B%22d%22%3A%222018-08-28%2002%3A09%3A44%22%2C%22h%22%3A%22Direct%22%2C%22v%22%3A0%2C%22b%22%3Afalse%2C%22ac%22%3Atrue%7D%5D; vtrk=v_ref%3Dnull%7Cv_camp%3Dnull%7Cv_cont%3Dnull%7Cv_med%3Dnull%7Cv_src%3Dnull%7Cv_trm%3Dnull%7Cv_kid%3Dnull; userData=%7B%22uuid%22%3A%221cad17a3-5565-4c93-a062-26d3fb37f0f7%22%2C%22regionId%22%3A29%2C%22secondaryRegionId%22%3A0%2C%22tertiaryRegionId%22%3A0%2C%22inboundPhoneNumber%22%3A%22866-848-8499%22%2C%22newSession%22%3Afalse%2C%22orInit%22%3Atrue%2C%22regionName%22%3A%22Los%20Angeles%22%7D; optimizely_uuid=1cad17a3-5565-4c93-a062-26d3fb37f0f7; JSESSIONID=0D7F0E90145558B654DC3211C87C9A17; AWSELB-INT=8DE9EF45181F6D4369DD3ECC2937A9284A771532EDEBAC2EBF046DAD9449263AB13B4ED9C69641C2827C0D95F95549F26DC3672302B101476138CDEF431F93CF76B1603027; VS_SID=s-1; cto_lwid=10e11f4c-974d-4dc3-9d03-d051b03a0496; _ga=GA1.2.1115923226.1535440186; _gid=GA1.2.1071477707.1535440186; _gat=1; D_IID=DF5A1868-9D02-3B6E-9728-4F2098B7F2CA; D_UID=7FCF244E-C7FF-361C-A6FD-7378448B9CB3; D_ZID=FD6C31D1-F8A0-3C5C-8BD9-27C2E8E42B13; D_ZUID=395DB18E-F86D-300C-8582-EC513AA17372; D_HID=DD524498-1932-328E-B6F8-F6C7DABC9D";
		
	$ghostKey = "5b74c5bed80211534379454";
	$proxyURL = "https://ghostproxies.com/proxies/api.json?key=$ghostKey";
	$proxyJSON = file_get_contents($proxyURL);
	$proxyList = json_decode($proxyJSON, true);
	
	$proxy = array_rand($proxyList['data']);
	$proxyIP = $proxy['panel_user'] . ":" . $proxy['panel_pass'] . "@" . $proxy['ip'] . ":" . $proxy['portNum'];

	error_log("Hitting skybox $uri");
	$ch = curl_init($uri);
	curl_setopt_array($ch, array(
	    CURLOPT_RETURNTRANSFER  =>true,
	    CURLOPT_SSL_VERIFYPEER => false,
	    CURLOPT_VERBOSE     => 1,
	    CURLINFO_HEADER_OUT => true,
	    CURLOPT_HTTPHEADER => array(                                                                          
	   // 'accept-encoding: gzip, deflate, br',
		//'accept-language: en-US,en;q=0.9',
		'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36',
		'accept: application/json, text/plain, */*',
		'referer: https://www.vividseats.com/buy/Production.action?productionId=2713093',
		'authority: www.vividseats.com',
		'cookie: __cfduid=d886eb3fc14af21c3449b2a25ab207f3a1533969267; VS_SID=s-1; lastvisit=2018-08-16+19%3A17%3A11; optimizely_uuid=8d2ab956-8168-4d11-ba0f-a2ab6c892a7a; cto_lwid=93346e5c-0c7d-4b47-a378-25c147752d45; _ga=GA1.2.238896392.1534465033; D_SID=71.92.207.52:6PgZ+UEc3c4DMdxcC+1Uo07xXrSBbjWJpjZZTPfIXCI; _mibhv=anon-1534465033436-6059558883_7340; notice_behavior=none; firstPageViewed=https%3A%2F%2Fwww.vividseats.com%2Fconcerts%2Fbeyonce-tickets.html; AWSELB-INT=8DE9EF45181F6D4369DD3ECC2937A9284A771532EDEBAC2EBF046DAD9449263AB13B4ED9C639307F9457FC6734AAB16822A0654C9BB101476138CDEF431F93CF76B1603027; ch=%5B%7B%22d%22%3A%222018-08-16+19%3A17%3A11%22%2C%22h%22%3A%22Direct%22%2C%22v%22%3A0%2C%22b%22%3Afalse%7D%2C%7B%22s%22%3A%22criteo%22%2C%22m%22%3A%22cpc%22%2C%22c%22%3A%22SCA-Lower-Funnel%22%2C%22t%22%3A%22Childish+Gambino+with+Vince+Staples%22%2C%22d%22%3A%222018-08-21+13%3A51%3A47%22%2C%22h%22%3A%22Paid+Search%22%2C%22a%22%3A%22ads.us.criteo.com%22%2C%22k%22%3A%22%22%2C%22v%22%3A0%2C%22b%22%3Afalse%7D%5D; vtrk=v_ref%3Dhttps%3A%2F%2Fads.us.criteo.com%2Fdelivery%2Fr%2Fafr.php%3Fdid%3D5b7c5e5292ac0289ee6716122a044800%26z%3DW3xeUgAGsqQKwMdSAAYGmeq5IP8YCz0ks0ezxw%26u%3D%257ChLKfDrqH2IUNquxKQhnFhCYzk%252F0Tvhr1TkyhGDvFOsA%253D%257C%26c1%3DM5BADJe1UR3zJ2HNju9b10FggySKKMK0AoYTtPDcqDnSIQIZUQPlDgE35LWzeblU9jHjcBSr3BR1HV_t08X0o4VaIMwY-DH6eMs3oBCCKwwe0IrcCDqIdsI2BfvXv9-b3O7ABHCFq7P50VMHPlDfIwG5RzX4briuwk3t2YCTvHrT68LYSdvOm8Z_vUEnsCpqtHAHrOX0AXNDov8pyG4ym2qnEO0U721KQNZqVkCwtx4lzpXo2CveZ2VK5bZDJIM30che_oBbv0U0aopuUfhNy8Z6N3UfHxKK-srr0K42U3WJlR-ZyaXsmj3up0ZyCpTS766iJf9PCWzqgGrKVgS5She5qyPw5vgc5e7vv83RTyPaT0xVvPy8771l-K71BSocZD7GLhLqFK1z-UnjtEPO2J6A4xn6yFm2b0-WiiZOSmM%26ct0%3Dhttps%3A%2F%2Fadclick.g.doubleclick.net%2Faclk%253Fsa%253Dl%2526ai%253DCCmIQUl58W6TlGtKOgwaZjZiQC-7lmPBNsu23nZ0BwI23ARABIABgyc6Ch8ijkBmCARdjYS1wdWItNDE3Nzg2MjgzNjU1NTkzNKABrN3-6APIAQngAgCoAwGqBOIBT9DW1Z9clhM8sQP2PA29J1p2yUf2Lku1-9hpem_29ydYi5i5_BD9SSGa7949tRmyEsBeSnOxGdoanx5-qABP1SWECEPKNIMMcxWWi0A0vmmxO3_2V8LIh0KpkB_YF8Pr5ZJTKJUY7ZkknO6rAPsb9nCAITLrmlcAifE9v4kAo1X3rKSD6wNbg9MyPceVGF2YkZf1UH5IYarVSPlUXLWiMMjoBqbSgq7oRLCuQcg0LFmTdbkjXHwmV41IgnokQ7TRn-K9r8Xa6rqhide4MLDejomdBwbpl8kYpJ_NeoT2gTr2vOAEAYAGxu2P0_fctaZPoAYhqAemvhuoB9nLG6gHz8wb2AcA0ggFCIAhEAE%2526num%253D1%2526sig%253DAOD64_103CH5pzgamthal9u0IJoIIr_scw%2526client%253Dca-pub-4177862836555934%2526adurl%253D%7Cv_camp%3DSCA-Lower-Funnel%7Cv_cont%3Dnull%7Cv_med%3Dcpc%7Cv_src%3Dcriteo%7Cv_trm%3DChildish+Gambino+with+Vince+Staples%7Cv_kid%3Dnull; D_IID=DF5A1868-9D02-3B6E-9728-4F2098B7F2CA; D_UID=7FCF244E-C7FF-361C-A6FD-7378448B9CB3; D_ZID=FD6C31D1-F8A0-3C5C-8BD9-27C2E8E42B13; D_ZUID=395DB18E-F86D-300C-8582-EC513AA17372; D_HID=DD524498-1932-328E-B6F8-F6C7DABC9D0A; _br_uid_2=uid%3D8611598941110%3Av%3D12.0%3Ats%3D1534490326562%3Ahc%3D2; __insp_wid=1561680148; __insp_nv=true; __insp_targlpu=aHR0cHM6Ly9za3lib3gudml2aWRzZWF0cy5jb20vaW52ZW50b3J5; __insp_targlpt=SW52ZW50b3J5IC0gU2t5Ym94; __insp_norec_sess=true; __insp_slim=1535436918135; userAgent=%7B%22headerString%22%3A%22Mozilla%2F5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_13_6%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F68.0.3440.106%20Safari%2F537.36%22%2C%22name%22%3A%22Chrome%22%2C%22majorVersion%22%3A68%2C%22minorVersion%22%3A0%2C%22patchVersion%22%3A3440%2C%22deviceType%22%3A%22desktop%22%2C%22osName%22%3A%22Mac%20OS%20X%22%2C%22osMajorVersion%22%3A10%2C%22osMinorVersion%22%3A13%2C%22osPatchVersion%22%3A6%2C%22supported%22%3Atrue%7D; userData=%7B%22uuid%22%3A%228d2ab956-8168-4d11-ba0f-a2ab6c892a7a%22%2C%22regionId%22%3A29%2C%22secondaryRegionId%22%3A0%2C%22tertiaryRegionId%22%3A0%2C%22inboundPhoneNumber%22%3A%22800-504-2851%22%2C%22utmPromo%22%3A%2210%20off%22%2C%22newSession%22%3Afalse%2C%22orInit%22%3Atrue%2C%22regionName%22%3A%22Los%20Angeles%22%2C%22promoDiscount%22%3A%22%2410%22%2C%22promoBannerImage%22%3A%2210-off-50-max.png%22%2C%22promoExpiration%22%3A1967864400000%7D; JSESSIONID=1425DD16C48DE4B099D3AC6D3A76A46D; _gid=GA1.2.115458392.1535437763; _gat=1',
	//	'x-distil-ajax: uurrvyqyyqxrtytrzw',

		
		),
	    CURLOPT_REFERER => "https://www.vividseats.com/buy/Production.action?productionId=2713093",
	    CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36",
	    CURLOPT_ENCODING => "gzip",
	    
	));
	
	curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
        curl_setopt($ch, CURLOPT_PORT, $proxy['portNum']);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['panel_user'] . ":" . $proxy['panel_pass']);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$out = curl_exec($ch);
	var_dump($out);
	$info = curl_getinfo($ch);
	print "<pre>";
	print_r($info['request_header']);
	if(!curl_error($ch))
	{
	    switch($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE))
	    {
		case 200: #ok
		    
		    error_log("Skybox responded ($http_code): $out");
		    break;
		default: #error
		    die("$http_code" . curl_error($ch) . "yep$out");
		    error_log("Curl Error Response ($http_code): $out " . curl_error($ch));
		    return false;
	    }
	}
	
	curl_close($ch);
?>

