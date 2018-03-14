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

if ( $_GET['rank'] != '' ) {


	if ($_GET['rank'] <= 2 || $_GET['val_1'] <= 2 ) { 

		$_SESSION['valoracion'] = $_GET['rank'];
		$_SESSION['val_tipo'] = $_GET['val_1'];
		$_SESSION['val_estetica'] = $_GET['val_2'];
		echo '<script> location.replace("./inquiry_1.php");</script>';
		exit();	
    } 

	//Lo guardamos en la BD
	$bd = new BD();
	$bd->modificar("INSERT INTO valoraciones VALUES ( null , '".$_SESSION['id_username']."', NOW(), '".$_GET['rank']."' ,  '".$_GET['val_1']."' ,  '".$_GET['val_2']."' ,'".$_SESSION['tipo_grafico']."' , '' )  ");

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
	
} else {

?>
<script>
	function pregunta_1(val) {

		document.getElementById('valoracion_0').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_1').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_2').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_3').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_4').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_5').classList.remove('valoracion_2_sel');

		document.getElementById('valoracion_'+val).classList.add('valoracion_2_sel');

		document.getElementById('val_1').value=val;
	}

	function pregunta_2(val) {

		document.getElementById('valoracion_1_0').classList.remove('valoracion_1_sel');
		document.getElementById('valoracion_1_1').classList.remove('valoracion_1_sel');
		document.getElementById('valoracion_1_2').classList.remove('valoracion_1_sel');
		document.getElementById('valoracion_1_3').classList.remove('valoracion_1_sel');
		document.getElementById('valoracion_1_4').classList.remove('valoracion_1_sel');
		document.getElementById('valoracion_1_5').classList.remove('valoracion_1_sel');

		document.getElementById('valoracion_1_'+val).classList.add('valoracion_1_sel');

		document.getElementById('val_2').value=val;
	}

	function pregunta_3(val) {

		document.getElementById('valoracion_2_0').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_2_1').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_2_2').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_2_3').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_2_4').classList.remove('valoracion_2_sel');
		document.getElementById('valoracion_2_5').classList.remove('valoracion_2_sel');

		document.getElementById('valoracion_2_'+val).classList.add('valoracion_2_sel');

		document.getElementById('rank').value=val;
	}

</script>
<form enctype="multipart/form-data" action="" method="GET"  >
<br><br>
	<div class="explicacion">Valore sí el tipo gráfico generado ha sido acertado. <br><br>Donde 0 significa nada adecuada y 5 totalmente adecuada.</div>
  <br>

  <center>
  <table width="300">
  	<tr>
  		<td class="valoracion_2" id="valoracion_0" onclick= "pregunta_1(0)"> 0 </td>
  		<td class="valoracion_2" id="valoracion_1" onclick= "pregunta_1(1)"> 1 </td>
  		<td class="valoracion_2" id="valoracion_2" onclick= "pregunta_1(2)"> 2 </td>
  		<td class="valoracion_2" id="valoracion_3" onclick= "pregunta_1(3)"> 3 </td>
  		<td class="valoracion_2" id="valoracion_4" onclick= "pregunta_1(4)"> 4 </td>
  		<td class="valoracion_2" id="valoracion_5" onclick= "pregunta_1(5)"> 5 </td>
  	</tr>
  </table>
    <br>
      <br>
  <div class="explicacion">Valore a nivel visual (la claridad y la estética) del gráfico. <br><br>Donde 0 significa nada adecuada y 5 totalmente adecuada.</div>
  <br>

  <center>
  <table width="300">
  	<tr>
  		<td class="valoracion_1" id="valoracion_1_0" onclick= "pregunta_2(0)"> 0 </td>
  		<td class="valoracion_1" id="valoracion_1_1" onclick= "pregunta_2(1)"> 1 </td>
  		<td class="valoracion_1" id="valoracion_1_2" onclick= "pregunta_2(2)"> 2 </td>
  		<td class="valoracion_1" id="valoracion_1_3" onclick= "pregunta_2(3)"> 3 </td>
  		<td class="valoracion_1" id="valoracion_1_4" onclick= "pregunta_2(4)"> 4 </td>
  		<td class="valoracion_1" id="valoracion_1_5"onclick= "pregunta_2(5)"> 5 </td>
  	</tr>
  </table>
    <br>
      <br>
  <div class="explicacion">¿Qué puntución general le daría al resultado? <br><br>Donde 0 significa muy malo y 5 excelente.</div>
  <br>

  <center>
  <table width="300">
  	<tr>
  		<td class="valoracion" id="valoracion_2_0" onclick= "pregunta_3(0)"> 0 </td>
  		<td class="valoracion" id="valoracion_2_1" onclick= "pregunta_3(1)"> 1 </td>
  		<td class="valoracion" id="valoracion_2_2" onclick= "pregunta_3(2)"> 2 </td>
  		<td class="valoracion" id="valoracion_2_3" onclick= "pregunta_3(3)"> 3 </td>
  		<td class="valoracion" id="valoracion_2_4" onclick= "pregunta_3(4)"> 4 </td>
  		<td class="valoracion" id="valoracion_2_5" onclick= "pregunta_3(5)"> 5 </td>
  	</tr>
  </table>
</center>
  <br>
  <br>
  <div class='nota_legal' >Esta encuenta es totalmente anónima y su función es exclusivamente académica.</div>
  <input type="hidden" name="val_1" id="val_1">
  <input type="hidden" name="val_2" id="val_2">
  <input type="hidden" name="rank" id="rank">
  <br>
  <input type="submit" value="Enviar" />
</form>

<?php
}
?>
