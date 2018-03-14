<?php
/***
* Autor:        Daniel Díaz Gil
* Universidad:  UNIR Universidad Internacional de la Rioja
* Titulación:   Máster Universitario en Visual Analytics y Big Data
* TFM :         Sistema experto para la generación semiautomática de gráficos
*/
session_set_cookie_params(0); 		
session_name("think_4u_graph");
session_start();

require_once('data_type.php');
require_once('funciones.php');
require_once('brain.php');


require_once('bd.php');

error_reporting(E_ERROR);
cabecera("Carga de datos");

$uploaddir = './tmp_files/';
$uploadfile = $uploaddir."".$_SESSION['id_username']."_".basename($_FILES['userfile']['name']);


if ( move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {

   
$_SESSION['uploadfile']=$uploadfile;
chmod($uploadfile, 0777 );


    $estructura = new datos($uploadfile, $_SESSION['id_username']);
    $res = $estructura->validate_file($_FILES['userfile']['type']);

    if ($res['error']) {

    	echo "Se ha producido un error => ".$res['message'] ;

    } else {

		
    	//miramos que grafico es el más adecuado
        $bd = new BD();
    	$cerebro = new brain($estructura , $bd);
    	$cerebro->get_chart_type();
        
    }

} else {
	echo "No ha sido posible subir el fichero\n";

	echo 'info:';
	print_r($_FILES);
	echo "<br />";
	print_r($_FILES['userfile']['error']);
	print "</pre>";
 }

?>