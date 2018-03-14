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

require_once('funciones.php');
cabecera("Carga de datos");

if( is_null($_SESSION['id_username']) ) {

	$_SESSION['id_username'] = "bd_".rand(0,9999999);

}

?>
<form enctype="multipart/form-data" action="result.php" method="POST">
<br><br>
  <div class="explicacion">Seleccione un fichero de tipo csv</div>
  <br>
  <input type="hidden" name="MAX_FILE_SIZE" value="300000" />
    Archivo: <input name="userfile" type="file" accept=".csv" />
  <input type="submit" value="Enviar" />
</form>
