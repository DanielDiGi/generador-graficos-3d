<?php
/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/

require_once('graficos.php');
require_once('pantalla_dashboard.php');

class brain {


	private $datos;
	private $bd;

	private $MAX_barras = 15;

	public function __construct($datos , $bd) {

		$this->datos = $datos;
		$this->bd = $bd;
		
	}

	public function get_chart_type() {

		$estructura_datos = $this->datos->get_header();

		$n_categoricas = 0;
		$n_numerales = 0;
		$n_fecha = 0;

		$alertas = [];
		$graficos = [];
		$num_chart = 1; 

		$pantalla = new pantalla_dashboard();

		//generamos el grafico base con sus parametros 
		$chart = new chart($this->datos, $num_chart );

		foreach ($estructura_datos as $h) {

			if ($h['tipo'] == "numerico") $n_numerales++;
			else if ($h['tipo'] == "fecha") $n_fecha++;
			else $n_categoricas++;
		}

		//PAra ver si hay valores multiples 
		//miramos si todos los tipos de datos numericos son iguales
		$multiples = 0;
		$numericos_iguales = true;
		$numericos_tipo = '';

		foreach ($estructura_datos as $e ) {
			if ($e['n_diferentes'] < count($this->datos->get_datos()) && $e['tipo'] == 'texto') {
				$multiples = 1;
			}

			if ($e['tipo'] == 'numerico') {
				if ($numericos_tipo == '' ) $numericos_tipo = $e['subtipo'];
				else if ($numericos_tipo != $e['subtipo'] ) $numericos_iguales=false;
			}
		}

		
		$categoria = '';
		//Miramos si existe categoria
		foreach ($estructura_datos as $e ) {

			if ($e['categoria'] != '' ) {
				$categoria = $e['categoria'];
			}

		}

		$q = "SELECT * FROM `brain_charts` WHERE `nominales` = $n_categoricas  AND  `numerales` = $n_numerales AND `fechas` = $n_fecha AND multiples = $multiples  ";
		
		$res_aux = $this->bd->consultar($q);
		$res = $res_aux[0]['grafico'];

		//POR CATEGORIAS ///////////////////////////////////////////////////////////////////////////////
		if ($categoria == 'dia semana' || $categoria == 'meses' ) {

			if ($estructura_datos[1]['categoria'] == '' ) {

				echo "<h1>ERROR: no es posible pintar esta información";
				exit;
			} 
			else if ($estructura_datos[0]['n_diferentes'] != count($this->datos->get_datos()) ) {
				//hay registros repetidos para el campo texto
				echo "<h1>ERROR: no es posible pintar esta información. Información duplicada";
				exit;

			} else if ($estructura_datos[0]['n_diferentes'] == count($this->datos->get_datos()) ) {

				if (count($this->datos->get_datos()) <= 5 ) {
					//De uno en uno 
					$graficos[] = $chart->draw_line_predefinidos_cat_chart();

					$_SESSION['tipo_grafico'] = "lineas";
				}  else {

					$datos = $this->datos->get_datos();
					//calculamos el tamaño del los graficos
					$medidas = $pantalla->get_charts_size(count($datos), 1200, 800);

					$num_chart=0;
					for ($i = 0; $i < count($datos) ; $i++ ) {

						//Hacemos una copia de la estructura solo el un valor de datos

						$new_estructura = $this->datos;
						$aux = [];
						$aux[0] = $datos[$i]; 
						$new_estructura->set_datos($aux) ;

						$chart = new chart($new_estructura, $num_chart , $medidas['ancho'], $medidas['alto'] , "30", "50", "20", "10" );
						$graficos[] = $chart->draw_line_predefinidos_cat_chart(1);

						$num_chart++;
					} 
					$_SESSION['tipo_grafico'] = "lineas multiples";
					
					
				}
				

			}
			
		}


		///////////////////////////////////////////////////////////////////////
		//Graficos de barras 
		else if ($res == "barras") {


			if ( COUNT($estructura_datos) == 2 ) {

				if ($estructura_datos[0] == "numerico" ) {
					$var_num = $estructura_datos[0]['nombre'];
					$var_text = $estructura_datos[1]['nombre'];

					if ($estructura_datos[0]['n_diferentes'] > $this->MAX_barras ) {

						$alertas[count($alertas)] = 'Demasiados elementos a representar.'; 
					}

				} else {
					$var_num = $estructura_datos[1]['nombre'];
					$var_text = $estructura_datos[0]['nombre'];

					if ($estructura_datos[1]['n_diferentes'] > $this->MAX_barras ) {

						$alertas[count($alertas)] = 'Demasiados elementos a representar.'; 
					}
				} 		
						
				$graficos[] = $chart->draw_bar_chart($var_text,$var_num);
				$_SESSION['tipo_grafico'] = "barras";
			

			} else if ( COUNT($this->datos->get_header()) == 3 ) {

 				$_SESSION['tipo_grafico'] = $res;
				
				$array_var= null;
 				foreach ( $this->datos->get_header() as $h ) {
 					if ($h['tipo'] == 'numerico' ) {
 						$array_var[]=$h['nombre'];
 					}
 				}

 				$x_left_col_name="";
				$x_right_col_name="";
				$y_col_name="";

				foreach ( $this->datos->get_header() as $h ) {
					if ($h['tipo'] == 'numerico' && $x_right_col_name == "" ) {
 						$x_right_col_name=$h['nombre'];
 					} else if ($h['tipo'] == 'numerico' && $x_left_col_name == "" ) {
 						$x_left_col_name=$h['nombre'];
 					}  else {
 						$y_col_name=$h['nombre'];
 					}
 				}


 				if (!$this->misma_escala($x_left_col_name, $x_right_col_name) ) {
					$chart->cambiar_dimensiones_barras(COUNT($this->datos->get_datos())); 
					$graficos[] = $chart->draw_doble_bar_chart($x_left_col_name, $x_right_col_name, $y_col_name); 

					$_SESSION['tipo_grafico'] = "barras piramide";
 				} else if ($numericos_iguales) {
					
					$this->preguntar_juntas($array_var);
					exit();

				} 
			} else if ( COUNT($this->datos->get_header()) == 4 ) {

					//Preguntar si es el mismo concepto
					$array_var= null;
	 				foreach ( $this->datos->get_header() as $h ) {
	 					if ($h['tipo'] == 'numerico' ) {
	 						$array_var[]=$h['nombre'];
	 					}
	 				}


					if ($numericos_iguales) {
						//las variables numericas son parecidas
						//PReguntar si las quiere agrupadas, agregadas o separadas
						$_SESSION['tipo_grafico'] = $res;


						$this->preguntar_juntas($array_var);
						exit();

					} else {

						//Varios graficos diferentes por variable

					}
		

					
			} else if ( COUNT($this->datos->get_header()) > 4 ) {
			
				if ($n_categoricas == 1 && $multiples == 0 && $n_fecha == 0 ) {
					//haremos n graficos de barras 
	 				$var_text = "";
					foreach ( $this->datos->get_header() as $h ) {
						if ($h['tipo'] == 'texto' && $var_text == "" ) {
	 						$var_text=$h['nombre'];
	 					} 
	 				}

	 				$num_chart = 0;
	 				$datos = $this->datos->get_datos();

	 				foreach ( $this->datos->get_header() as $h ) {


						if ($h['tipo'] != 'texto' ) {

							
							//calculamos el tamaño del los graficos
							$medidas = $pantalla->get_charts_size( COUNT($this->datos->get_header()) - 1, 1200, 800 , COUNT($this->datos->get_datos()) );

							$chart = new chart($this->datos, $num_chart , $medidas['ancho'], $medidas['alto'] , $margen_superior = "30", $margen_izquierda = "60", $margen_inferior = "20", $margen_derecho = "20" );
							$graficos[] = $chart->draw_bar_chart($var_text,$h['nombre'],$h['nombre'] );
							$num_chart++;

	 	
	 					} 
	 				}
	 				$_SESSION['tipo_grafico'] = "barras multiples";
	 			}				
			} 
		} //FIN graficos barras
		else if ($res == "lineas_tiempo" ) {

			if ( COUNT($this->datos->get_header()) == 2 ) {
				

				if ($estructura_datos[0]['tipo'] == "fecha" ) {
					$var_date = $estructura_datos[0]['nombre'];
					$var_num = $estructura_datos[1]['nombre'];

				} else {
					$var_date = $estructura_datos[1]['nombre'];
					$var_num = $estructura_datos[0]['nombre'];
				} 		



				$graficos[] = $chart->draw_line_time_chart("",$var_date, $var_num);
				$_SESSION['tipo_grafico'] = "lineas";

			}
			else if ( COUNT($this->datos->get_header()) == 3 ) {
				
				$var_cat = $var_num = $var_date = '';
				$n_diferentes=0;
				foreach ($estructura_datos as $e) {
	
					if ($e['tipo'] == 'texto' ) { 	$var_cat = $e['nombre']; $n_diferentes = $e['n_diferentes']; }
					else if ($e['tipo'] == 'numerico' ) { $var_num =$e['nombre']; 	} 
					else {	$var_date =$e['nombre']; }
				}


				if($n_diferentes > 5 ) {

					$valores = null;
					foreach ($this->datos->get_datos() as $h) {
						$valores[$h[$var_cat] ] = 1;
					}

					$medidas = $pantalla->get_charts_size( $n_diferentes , 1200, 800);
					$num_chart=0;
					foreach (array_keys($valores) as $k) {

						$chart_line = new chart($this->datos, $num_chart , $medidas['ancho'], $medidas['alto'] ,  "30",  "60",  "20", "20" );
						$filtro = $k;
						$graficos[] = $chart_line->draw_line_time_chart($var_cat,$var_date, $var_num, $filtro );
						
						$num_chart++;

					}

					$_SESSION['tipo_grafico'] = "lineas multiples";

				} else {
					$graficos[] = $chart->draw_line_time_chart($var_cat,$var_date, $var_num);
					$_SESSION['tipo_grafico'] = "lineas";

				}

			}
			
		} else if ($res == "dispersion") {
			
			//DISPERSION/////////////////////////////////////////////////////////
			if ( COUNT($this->datos->get_header()) == 2 ) {


				$var_num_2 = $estructura_datos[1]['nombre'];
				$var_num_1 = $estructura_datos[0]['nombre'];
			
				$graficos[] = $chart->draw_scatter_plot($var_num_1,$var_num_2, "");
				$_SESSION['tipo_grafico'] = "dispersion";

			}
			else if ( COUNT($this->datos->get_header()) == 3 ) {
				
				$n_texto= 0;

				foreach ($estructura_datos as $e) {
	
					if ($e['tipo'] == 'texto' ) {
						$n_texto++;

					}
				}

				if ($n_texto > 0 ) {

					$var_num_1 = '';
					$var_num_2 = '';
					$var_text = '';
					foreach ($estructura_datos as $e) {
		
						if ($e['tipo'] == 'texto' ) { 	$var_text = $e['nombre']; }
						else if ($e['tipo'] == 'numerico' && $var_num_1 == '') { $var_num_1 =$e['nombre']; 	} 
						else {	$var_num_2 =$e['nombre'];}
					}

					$graficos[] = $chart->draw_scatter_plot($var_num_1,$var_num_2, $var_text);
					$_SESSION['tipo_grafico'] = "dispersion";

				} else {
					$var_num_1 = $estructura_datos[0]['nombre'];
					$var_num_2 = $estructura_datos[1]['nombre'];
					$var_num_3 = $estructura_datos[2]['nombre'];
				
					$graficos[] = $chart->draw_scatter_plot($var_num_1,$var_num_2, "", $var_num_3);

				}

				$_SESSION['tipo_grafico'] = "dispersion";


			} else if ( COUNT($this->datos->get_header()) == 4 ) {

				$var_num_1 = '';
				$var_num_2 = '';
				$var_text = '';
				foreach ($estructura_datos as $e) {
	
					if ($e['tipo'] == 'texto' ) {
						$var_text = $e['nombre'];

					} else if ($e['tipo'] == 'numerico' && $var_num_1 == '') {

						$var_num_1 =$e['nombre']; 
					} else if ($e['tipo'] == 'numerico' && $var_num_2 == '') {

						$var_num_2 =$e['nombre']; 

					} else {
						$var_num_3 =$e['nombre'];
					}
				}
					
				$graficos[] = $chart->draw_scatter_plot($var_num_1,$var_num_2, $var_text, $var_num_3);
				$_SESSION['tipo_grafico'] = "dispersion";
			}

		}//FIN dispersion 
		else if ( $res == '' ) {

			//OTROS GRAFICOS ---------------------
			if ($n_categoricas == 1 && $multiples == 0 && $n_fecha == 0  && COUNT($this->datos->get_header()) > 4 ) {

				//haremos n graficos de barras 
 				$var_text = "";
				foreach ( $this->datos->get_header() as $h ) {
					if ($h['tipo'] == 'texto' && $var_text == "" ) {
 						$var_text=$h['nombre'];
 					} 
 				}

 				$num_chart = 0;
 				$datos = $this->datos->get_datos();

 				foreach ( $this->datos->get_header() as $h ) {

					if ($h['tipo'] != 'texto' ) {

						//calculamos el tamaño del los graficos
						$medidas = $pantalla->get_charts_size( COUNT($this->datos->get_header()) - 1, 1200, 800);

						$chart = new chart($this->datos, $num_chart , $medidas['ancho'], $medidas['alto'] , $margen_superior = "30", $margen_izquierda = "40", $margen_inferior = "20", $margen_derecho = "10" );
						$graficos[] = $chart->draw_bar_chart($var_text,$h['nombre'],$h['nombre']);
						$num_chart++;
 	
 					} 
 				}

 				$_SESSION['tipo_grafico'] = "barras multiples";

			} 

		} 

		if ($graficos == null ) {
			echo "No se ha encontrado una solución al grafico: 
			<br>Numero de columnas = ".COUNT($this->datos->get_header())."
			<br>EStructura <pre>"; print_r($estructura_datos); echo "</pre>
			<br>RES  <pre>"; print_r($res_aux); echo "</pre>
			<br>Categoria = $categoria 
			
			";
		} else {
	 		$pantalla->set_charts($graficos);
	        $pantalla->print_html();
    	}
		exit();

	}

	function get_chart_type_step_1(){

		$num_chart = 1; 

		$pantalla = new pantalla_dashboard();

		//generamos el grafico base con sus parametros 
		$chart = new chart($this->datos, $num_chart );


		//En caso de resolver preguntas
		if ($_GET['juntas'] != '' && ( $_SESSION['tipo_grafico'] == "barras" || $_SESSION['tipo_grafico'] == "barras agrupadas" || $_SESSION['tipo_grafico'] == "barras apiladas")) {
			

			if ($_GET['juntas'] == 'SI') {

				$y_col_name_1=$y_col_name_2=$y_col_name_3=$y_col_name_4=$x_col_name="";

				foreach ( $this->datos->get_header() as $h ) {
					if ($h['tipo'] == 'numerico' && $y_col_name_1 == "" ) {
 						$y_col_name_1=$h['nombre'];
 					} else if ($h['tipo'] == 'numerico' && $y_col_name_2 == "" ) {
 						$y_col_name_2=$h['nombre'];
 					} else if ($h['tipo'] == 'numerico' && $y_col_name_3 == "" ) {
 						$y_col_name_3=$h['nombre'];
 					} else if ($h['tipo'] == 'numerico' && $y_col_name_4 == "" ) {
 						$y_col_name_4=$h['nombre'];
 					} else {
 						$x_col_name=$h['nombre'];
 					}
 				}

				$graficos[] = $chart->draw_group_bar_chart($x_col_name, $y_col_name_1 , $y_col_name_2, $y_col_name_3, $y_col_name_4 ); 
				$_SESSION['tipo_grafico'] = "barras agrupadas";

			} else if ($_GET['juntas'] == 'NO' && COUNT($this->datos->get_header()) == 3 ) {

				$x_left_col_name="";
				$x_right_col_name="";
				$y_col_name="";

				foreach ( $this->datos->get_header() as $h ) {
					if ($h['tipo'] == 'numerico' && $x_left_col_name == "" ) {
 						$x_left_col_name=$h['nombre'];
 					} else if ($h['tipo'] == 'numerico' && $x_right_col_name == "" ) {
 						$x_right_col_name=$h['nombre'];
 					} else {
 						$y_col_name=$h['nombre'];
 					}
 				}

				$graficos[] = $chart->draw_doble_bar_chart($x_left_col_name, $x_right_col_name, $y_col_name); 
				$_SESSION['tipo_grafico'] = "barras piramide";
			

			} else if ($_GET['juntas'] == 'AG') {
				$x_col_name='';
				foreach ( $this->datos->get_header() as $h ) {
					if ($h['tipo'] == 'texto' && $x_col_name == "" ) {
 						$x_col_name=$h['nombre'];
 					} 
 				}

				$graficos[] = $chart->draw_stacked_bar_chart($x_col_name);
				$_SESSION['tipo_grafico'] = "barras apiladas";

			} else {

				//haremos n graficos de barras 
 				$var_text = "";
				foreach ( $this->datos->get_header() as $h ) {
					if ($h['tipo'] == 'texto' && $var_text == "" ) {
 						$var_text=$h['nombre'];
 					} 
 				}

 				$num_chart = 0;
 				$datos = $this->datos->get_datos();

 				foreach ( $this->datos->get_header() as $h ) {

					if ($h['tipo'] != 'texto' ) {

						//calculamos el tamaño del los graficos
						$medidas = $pantalla->get_charts_size( COUNT($this->datos->get_header()) - 1, 1200, 700, COUNT($this->datos->get_datos()) );

						$chart = new chart($this->datos, $num_chart , $medidas['ancho'], $medidas['alto'] , $margen_superior = "30", $margen_izquierda = "60", $margen_inferior = "20", $margen_derecho = "20" );
						$graficos[] = $chart->draw_bar_chart($var_text,$h['nombre'],$h['nombre']  );
						$num_chart++;
 	
 					} 
 				}

 				$_SESSION['tipo_grafico'] = "barras multiples";
			}

			$pantalla->set_charts($graficos);
        	$pantalla->print_html();

		} 

	}


	private function misma_escala($col1, $col2, $col3='', $col4='') {

		$cab = $this->datos->get_header();
		$tmp_max = null;
		$tmp_min = null;
		foreach($cab as $c ) {
			$tmp[$c['nombre']]['max'] = $c['max'];
			$tmp[$c['nombre']]['min'] = $c['min'];
			$tmp[$c['nombre']]['subtipo'] = $c['subtipo'];
		}

		
		if ($tmp[$col1]['subtipo'] != $tmp[$col2]['subtipo'] ) return false;

		$l1 = length($tmp[$col1]['max']);
		$l2 = length($tmp[$col2]['max']);

		if ($l1 <= ($l2 + 2 ) || ($l1+ 2) >= $l2  ) return true;

		return false;

	}

	private function preguntar_juntas($array_var) {

		$text = " ".$array_var[0];
		for ($i = 1; $i < COUNT($array_var); $i++ ) {
			$v = $array_var[$i];

			if ($i < ( COUNT($array_var) - 1) ) $text.= ", $v "; 
			else $text.= "y $v ";
		}

		echo '
	<script>
		var res = 0; 
		swal("¿Quiere repesentar las variables '.$text.' juntas?", {
		  buttons: {
			  cancel: {
			    text: "No, son conceptos separados",
			    value: "NO",
			    visible: true,

			  },
			  confirm: {
			    text: "Si, deseo ver las juntas",
			    value: "SI",
			  },
			  catch: {
			      	text: "Si, pero me interesa también el agregado",
			      	value: "AG",
			  }
		  },
		  closeOnClickOutside: false,
		  closeOnEsc: false,
		})
		.then((value) => {
		  switch (value) {
		 
		    case "NO":
		      location.replace("./result_1.php?juntas=NO");
		      res = 100;
		      break;
		 
		    case "SI":
		      location.replace("./result_1.php?juntas=SI");
		      res = 200;
		      break;
		    case "AG":
		      location.replace("./result_1.php?juntas=AG");
		      res = 200;
		      break;
		 
		    default:
		      swal("defaul");
		      res = 300;
		  }
		});
	</script>
	';
	}

	private function preguntar_agrupadas($array_var) {

		$text = " ".$array_var[0];
		for ($i = 1; $i < COUNT($array_var); $i++ ) {
			if ($i < ( COUNT($array_var) - 1) ) $text.= ", $v "; 
			else $text.= "y $v ";
		}

		echo '
	<script>
		var res = 0; 
		swal("¿Quiere repesentar agrupadas las variables '.$text.'?", {
		  buttons: {
			  cancel: {
			    text: "No, son conceptos separados",
			    value: "No",
			    visible: true,

			  },
			  confirm: {
			    text: "Si, me interesa también el agregado",
			    value: "Si",
			  },
			  catch: {
			      	text: "No se, elige tu",
			      	value: "NA",
			  }
		  },
		  closeOnClickOutside: false,
		  closeOnEsc: false,
		})
		.then((value) => {
		  switch (value) {
		 
		    case "No":
		      location.replace("./cargar_datos_3.php?agrupadas=NO");
		      res = 100;
		      break;
		 
		    case "Si":
		      location.replace("./cargar_datos_3.php?agrupadas=SI");
		      res = 200;
		      break;
		    case "NA":
		      location.replace("./cargar_datos_3.php?agrupadas=NA");
		      res = 200;
		      break;
		 
		    default:
		      swal("defaul");
		      res = 300;
		  }
		});
	</script>
	';
	}

}



?>