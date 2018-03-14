<?php
/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/

class pantalla_dashboard {

	private $estructura_divs;
	private $scripts;
	private $call_functions;

	private $graficos; //array con los id's de los graficos
	private $ancho_individual ;
	private $alto_individual;


	public function get_charts_size($n, $total_width, $total_height, $num_elementos = 0) {

		$res = null;

		if ($n <= 3 ) {
			$res['ancho'] = $total_width;
			$res['alto'] = $total_height / $n;
			$this->filas = $n;
			$this->columnas = 1;

		} else if ($n <= 4 ) {
			$res['ancho'] = $total_width / 2;
			$res['alto'] = $total_height / 2;
			$this->filas = 2;
			$this->columnas = 2;

		} else if ($n <= 6 ) {
			$res['ancho'] = $total_width / 2;
			$res['alto'] = $total_height / 3;
			$this->filas = 3;
			$this->columnas = 2;

		} else if ($n <= 8 ) {
			$res['ancho'] = $total_width / 2;
			$res['alto'] = $total_height / 4;
			$this->filas = 4;
			$this->columnas = 2;

		} else if ($n <= 9 ) {
			$res['ancho'] = $total_width / 3;
			$res['alto'] = $total_height / 3;
			$this->filas = 3;
			$this->columnas = 3;

		} else if ($n <= 12 ) {
			$res['ancho'] = $total_width / 3;
			$res['alto'] = $total_height / 4;
			$this->filas = 4;
			$this->columnas = 3;
			

		} else if ($n <= 16 ) {
			$res['ancho'] = $total_width / 4;
			$res['alto'] = $total_height / 4;
			$this->filas = 4;
			$this->columnas = 4;

		} 

		if ($num_elementos != 0 ) {
			$margen_izquierdo = 60;
			$margen_derecho = 20;
			$max_width = $margen_izquierdo + $margen_derecho + (100 * $num_elementos); 
			if ($res['ancho'] > $max_width) {
				$res['ancho'] = $max_width;
			} 
		}

		$this->ancho_individual = $res['ancho'];
		$this->alto_individual = $res['alto'];

		return $res;			

	}

	public function set_charts($graficos){
		$this->graficos = $graficos;
		$this->generar_plantilla_divs();

		$this->call_functions="";
		$this->scripts="";

		foreach ($graficos as $g) {
				$this->call_functions.="
".$g['llamada'];
				$this->scripts.="
".$g['script'];
		}


	}

	public function print_html() {
echo"		
<body>

".$this->estructura_divs."

</body>
";

echo "
<script>";
		//llamadas a 
		echo $this->call_functions;

		echo $this->scripts;

echo "
</script>";
	}

	private function generar_plantilla_divs() {

		$res = "";

		if (count($this->graficos) == 1 ) {

			$res = "
		<div id='div_".$this->graficos[0]['id']."' class='div_grafico_unico' >
			<svg id='grafico_".$this->graficos[0]['id']."'>
		</div>
<br><br><br><br>
		<center>
<table width='600px' style='text-align:center;'>
<tr>
	<td  width='200px' >
		<span class = 'pie_pagina' > <a href='./inquiry.php'> Opinar </a>  </span> 
	</td>
	<td width='200px' >
		<span class = 'pie_pagina' width='200px' > <a href='./index.php'> Cargar otro fichero </a> </span>
	</td>
</tr>
</table>";

		} else {

			$res = "
			<table width='1200' height='800' cellpadding='10'>";
			$n = 0; 
			for ($f = 0; $f < $this->filas; $f++) {
				$res .= "
				<tr>";
				for ($c = 0; $c < $this->columnas; $c++) {
					$res .= "
					<td>
							<svg id='grafico_".$n."'>
						
					</td>
					";
					$n++;

				}
				$res .= "
				</tr>";

			}
			$res .= "
				</table><center>
<table width='600px' style='text-align:center;'>
<tr>
	<td  width='200px' >
		<span class = 'pie_pagina' > <a href='./inquiry.php'> Opinar </a>  </span> 
	</td>
	<td width='200px' >
		<span class = 'pie_pagina' width='200px' > <a href='./index.php'> Cargar otro fichero </a> </span>
	</td>
</tr>
</table>
 ";

			
		}


		$this->estructura_divs=$res;
	}

	
}

?>