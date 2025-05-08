<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_myconnection = "localhost";
$database_myconnection = "rq_id";
$username_myconnection = "root";
$password_myconnection = "";
$myconnection = mysql_pconnect($hostname_myconnection, $username_myconnection, $password_myconnection) or trigger_error(mysql_error(),E_USER_ERROR); 







mysql_query("SET character_set_results=utf8");
mysql_query("SET character_set_client=utf8");
mysql_query("SET character_set_connection=utf8");


?>