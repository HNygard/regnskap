<?php

// MySQL
$mysql_server   = 'localhost';
$mysql_db       = 'regnskap';
$mysql_username = 'regnskap';
$mysql_passwd   = 'sfdasdafdf25hdfgd4A4';

// Koble til MySQL server
if(!$database = @mysql_connect($mysql_server, $mysql_username, $mysql_passwd))
{
	echo 'Kan ikke koble til MySQL-tjeneren. Siden har stoppet på grunn av dette.'.chr(10);
	echo '<br><br>'.chr(10);
	echo '<br>MySQL error code '.mysql_errno().' - mysql_connect faild:<br>'.chr(10);
	echo mysql_error();
	exit();
}
if(!@mysql_select_db($mysql_db,$database))
{
	echo 'Kan ikke koble til MySQL-tjeneren. Siden har stoppet på grunn av dette.'.chr(10);
	echo '<br><br>'.chr(10);
	echo '<br>MySQL error code '.mysql_errno().' - mysql_select_db faild:<br>'.chr(10);
	echo mysql_error();
	exit();
}

?>
