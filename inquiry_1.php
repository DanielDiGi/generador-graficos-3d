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

error_reporting(E_ERROR);

require_once('funciones.php');
require_once('bd.php');

cabecera("Carga de datos");

if ( $_GET['Cancelar'] == 'Cancelar' ) {

	$bd = new BD();
	$bd->modificar("INSERT INTO valoraciones VALUES ( null , '".$_SESSION['id_username']."', NOW(), '".$_SESSION['valoracion']."' , '".$_SESSION['val_tipo']."' , '".$_SESSION['val_estetica']."' , '".$_SESSION['tipo_grafico']."' , 'No constesta' )  ");

	echo '
	<script>
		location.replace("./index.php");
	</script>
	';
	exit();
}
else if ( $_GET['grafico'] != '' ) {

	switch ($_GET['grafico']) {
		case 'b1':
			$res = "barras";
			break;
		case 'b2':
			$res = "barras multiple";
			break;
		case 'b3':
			$res = "barras agrupadas";
			break;
		case 'b4':
			$res = "barras apiladas";
			break;
		case 'b5':
			$res = "barras piramide";
			break;
		case 'l':
			$res = "lineas";
			break;
		case 'lm':
			$res = "lineas multiples";
			break;
		case 'd':
			$res = "dispersion";
			break;
		case 'p':
			$res = "tarta";
			break;
		case 'na':
			$res = "No sabe";
			break;
		case 'ne':
			$res = "Ninguno";
			break;
		
		
		default:
			$res = "";
			break;

	}

	if ($res != '') {
		//Lo guardamos en la BD
		$bd = new BD();
		$bd->modificar("INSERT INTO valoraciones VALUES ( null , '".$_SESSION['id_username']."', NOW(), '".$_SESSION['valoracion']."' , '".$_SESSION['val_tipo']."' , '".$_SESSION['val_estetica']."'  , '".$_SESSION['tipo_grafico']."' , '".$res."' )  ");

		echo '
		<script>
			var res = 0; 
			swal("¡Grácias por su valoración!", {
			  buttons: {
				  confirm: {
				    text: "Inicio",
				    value: "SI",
				  },
			  },
			  closeOnClickOutside: false,
			  closeOnEsc: false,
			})
			.then( (value) => {
			  switch (value) {
			 
			    case "SI": location.replace("./index.php");
			  } 
			} );
		</script>
		';
	}
	
} else {

	
?>

<form enctype="multipart/form-data" action="" >
<br><br>
  <div class="explicacion">¿Qué tipo de gráfico le hubiera parecido más correcto?. </div>
  <br><center>
  <table width="300">
  	<tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=b1')" > Gráfico de barras </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=b2')" > Múltiples gráfico de barras </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=b3')" > Gráfico de barras agrupadas </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=b4')" > Gráfico de barras aplidas </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=b5')" > Gráfico de barras de pirámide </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=l')" > Gráfico de líneas </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=lm')" > Múltiples gráficos de líneas</td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=d')" > Gráfico de dispersión </td>
  	</tr><tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=p')" > Gráfico de tarta </td>
  	</tr>
  	<tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=ne')" > Ninguno de estos </td>
  	</tr>
  	<tr>
  		<td class="valoracion_tipo_grafico"  onclick= "location.replace('./inquiry_1.php?grafico=na')" > No lo sé </td>
  	</tr>
  </table>

  <br>
  <br>
  <div class='nota_legal' >Esta encuenta es totalmente anónima y su función es exclusivamente académica.</div>
  <br>
  <input type="submit" value="Cancelar" name="Cancelar"/>
</form>

<?php
}
?>
