<?php
/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/
session_set_cookie_params(0); 		
session_name("think_4u_graph");
session_start();

require_once('data_type.php');
require_once('funciones.php');
require_once('brain.php');


require_once('bd.php');


cabecera("Carga de datos");


$estructura = new datos($_SESSION['uploadfile'], $_SESSION['id_username'] , false );
$res = $estructura->validate_file(" ms-excel");

$bd = new BD();
$cerebro = new brain($estructura , $bd);
$cerebro->get_chart_type_step_1();



?>