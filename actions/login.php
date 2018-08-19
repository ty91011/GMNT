<?php

session_start();

// Start security
$validAccounts = array(
  "mason" => array(
      "password" => "q1w2e3r4t5y6",
      "name" => "Mason"
      )
);


if(is_null($_SESSION['user']))
{

    if(!is_null($_POST['username']) && $validAccounts[$_POST['username']]['password'] == $_POST['password'])
    {

	$_SESSION['user'] = $validAccounts[$_POST['username']];
    }
    else
    {

	header('Location: /login.php');
	exit;
    }
}
// End Security

	header('Location: /index.php');
	exit;