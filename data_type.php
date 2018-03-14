<?php
/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/
require_once("bd.php");

class datos extends BD {
	
	private $file_path = '';
	private $header = null;
	private $datos = null;
	private $username = null;
	
	/**
	 * $file = path del fichero a examinar
	 */ 
	public function __construct($file, $username = '' , $first = true) {

		$this->file_path = $file;
		if ($username == '' ) $this->username = "bd_".rand(0,99999);
		else $this->username = $username;


		parent::__construct();
		//$this->file_path = "tmp_files/1_Book1-coma.csv";
	}
	
	/**
	 * Verifica si el tipo del fichero (csv) 
	 * Varifica si la estructura es correcta (Nombres de columnas)
	 **/
	public function validate_file($type = '' ){

		$error = null;

		if (strpos($type,"ms-excel") > 0 ) {

			////////////////////////////////////////////////////////
			//csv de tipo ; separado
			$delimitador =";";
			$enclosure ='"'; 

			if (strtolower(substr($this->file_path, -4)) != '.csv' ) {
				
				$error['error']="1";
				$error['message'] = "No es un fichero csv";
				return $error;
			}

			$file = fopen($this->file_path,"r");
			

			$array = array();
			$header = fgetcsv($file, 8192,$delimitador,$enclosure);

			while (($line = fgetcsv($file, 8192,$delimitador,$enclosure)) !== FALSE) {

				array_push( $array, $line);
			  
			}

			fclose($file);
			//eliminamos espacios en blanco en el nombre - sino la BD da error
			for($i = 0; $i < COUNT($header); $i++ ) {
				$header[$i]= trim($header[$i]);
			}


			function _combine_array(&$row, $key, $header) {
			  	$row = array_combine($header, $row);
			}

			array_walk($array, '_combine_array', $header);
			
			$this->datos = $array;
			$this->header = $header;

		}

		$this->get_data_types();
		return true;

	}

	public function get_header(){

		return $this->header;
	}

	public function get_datos(){

		return $this->datos;
	}

	public function set_datos($d){

		$this->datos = null;
		$this->datos = $d;
	}


	private function get_data_types(){

		//para cada valor de los atributo se revisa que tipo de datos es Numero (entero, real), texto (general, ubicacion, pais, etc), fecha		
		$header_aux=null;
		for ($i =0; $i< COUNT($this->header); $i++ ) {
			$h = $this->header[$i];	
			$tipo = "";
			$subtipo = "";

			for ($j = 0; $j<COunt($this->datos); $j++) {

				$d = $this->datos[$j];
				$aux = $this->es_fecha($d[$h]) ;

				$num_value = $this->formato_numerico($d[$h]);
			
				if (is_numeric($num_value ) && ($tipo == "" || $tipo == "numerico" ) ) {

					$tipo = "numerico";

					if ( strpos($num_value, ".")  >= 1 ) {
						$subtipo = "decimal"; 
					} else if ( strpos($num_value, ".") === false && $subtipo != "decimal" )  {

						$subtipo = "int"; 
					} 

					$this->datos[$j][$h.'_original']=$d[$h];
					$this->datos[$j][$h]=$num_value;

				} else if (is_numeric($num_value) && ( $tipo == "fecha" ) ) {
					$this->datos[$j][$h.'_original']=$d[$h];
					$this->datos[$j][$h]=$num_value;
					

				} else if ( is_numeric($num_value) && ( $tipo == "texto" ) ) {
					//no hacer nada, texto prevalece sobre numero

				} else if ($aux) {
					if ($tipo == "" || $tipo == "fecha" ) {
						$tipo = "fecha";
						$this->datos[$j][$h.'_original']=$d[$h];
						$this->datos[$j][$h]=$aux;

					}
					else $tipo = "texto";
				} else {
					$tipo = "texto";
				}

			}

			$header_aux[$i]['nombre']= $h;
			$header_aux[$i]['tipo']= $tipo;
			$header_aux[$i]['subtipo']= $subtipo;

		}


		$cat = '';

		
		for ($i =0; $i< COUNT($header_aux); $i++ ) {

			if ($header_aux[$i]['tipo'] == 'numerico' ) {

				$q = "SELECT categoria , orden FROM columnas_especificas WHERE nombre LIKE '%".strtolower($header_aux[$i]['nombre'])."%' ";
				$res = $this->consultar($q);

				if ($res[0]['categoria'] != ''  ) {
					if ($cat == '' ) {
						$cat = $res[0]['categoria'];
						$header_aux[$i]['categoria']= $cat;
						$header_aux[$i]['orden']= $res[0]['orden'];
					} else if ( $cat != $res[0]['categoria'] ) {
						//mezcla de categoria 
						$cat = "mix";
					} else  if ($cat == $res[0]['categoria'] ) {
						$header_aux[$i]['categoria']= $cat;
						$header_aux[$i]['orden']= $res[0]['orden'];
					}
				}
			}
		
		}	



		if ($cat == 'mix' ) {
			//categorias inconexas
			for ($i =0; $i< COUNT($header_aux); $i++ ) {
				unset($header_aux[$i]['categoria']);
			}

		} else if ($cat != '' ) {
			$header_tmp = null;

			//Ordemanos las cabeceras, primero los que no son de la categoria
			foreach( $header_aux as $h ) {
				if (is_null($h['categoria']) ) {
					$header_tmp[]=$h;
				} 
			}

			$min = 1; 
			foreach( $header_aux as $h ) {

				foreach ($header_aux as $h2 ) {
					if ($h2['orden'] == $min ) {
						$header_tmp[]=$h2;
					}

				}
				$min++;				
			}

			$header_aux = null;
			$header_aux = $header_tmp;

		}

		//Si no hay errores ==> borramos los originales
		for($i = 0; $i < COUNT($this->datos) ; $i++) {

			$keys = array_keys($this->datos[$i]);

			foreach ($keys as $k ) {
				if ( strpos($k, "_original") > 0 ) {
						unset($this->datos[$i][$k]);
				}
			}
		}

		$this->header = null;
		$this->header = $header_aux;
 		$this->save_bd();
 		$this->valores_diferentes();

	}

	private function es_fecha($date)
	{

		/**
		 * el tema hora no funciona bien si la hora solo tiene un digito
		 **/
		if (! (strpos($date,"-") > 0  || strpos($date,"/") > 0  ) ) return null;
		$formato_bueno = 'Y-m-d';
		$formatos_fecha=[ 'Y-n-d', 'Y/n/d', 'd-n-Y', 'd/n/Y' , 'Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y' ] ;

		foreach($formatos_fecha as $format ) { 
			
		    $d = DateTime::createFromFormat($format, $date);
		    
		    if ($d && $d->format($format) != '1970-01-01' ) {
		    	return $d->format($formato_bueno);
		    }
		}
	   
	    $formatos_fecha_hora=['Y-m-d H:i:s', 'Y/m/d H:i:s', 'Y-m-d H:i', 'Y/m/d H:i', 
	    'd-m-Y H:i:s', 'd/m/Y H:i:s' , 'd-m-Y H:i', 'd/m/Y H:i'] ;
		
		
	    $formato_bueno = 'Y-m-d H:i:s';

		foreach($formatos_fecha_hora as $format ) { 
			
		    $d = DateTime::createFromFormat($format, $date);
		    if ($d && $d->format($format) != '1970-01-01 00:00:00' ) {
		    	return $d->format($formato_bueno);
		    }
		    
		}
	
	    return null;
	}

	function save_bd() {

		$this->modificar("DROP TABLE IF EXISTS `".$this->username."` ");

		$create = "CREATE TABLE `".$this->username."` ( ";

		foreach ($this->header as $h ) {

			$create .= " `".trim($h['nombre'])."` ";

			if ($h['tipo'] == "numerico" && $h['subtipo'] == "decimal" ) {
				$create.=" decimal(16 , 8), ";
			} else if ($h['tipo'] == "numerico") {
				$create.=" int(11), ";
			} else if ($h['tipo'] == "fecha") {
				$create.=" datetime , ";	
			} else {
				$create.=" varchar(150) COLLATE utf8_spanish2_ci , ";
			} 
		}

		$create = substr($create, 0, -2 );
		$create .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;";
		$this->modificar($create);
		
		
		foreach ($this->datos as $d){

			$ins = "INSERT INTO `".$this->username."` VALUES (";
			for ($c = 0; $c < count($this->header); $c++ ) {
				$ins.='"'.$d[$this->header[$c]['nombre']].'", ';
			} 
			$ins = substr($ins,0,-2).");";

			$this->modificar($ins);
		}
		
	}

	function valores_diferentes(){

		for ($i = 0; $i < COUNT($this->header) ; $i++  ) {

			$nombre = $this->header[$i]['nombre'];

			$q= "SELECT COUNT(DISTINCT(`".$nombre."`)) as value , MAX(`".$nombre."`) as max , MIN(`".$nombre."`) as min FROM `".$this->username."` ";
			$aux = $this->consultar($q); 
			$this->header[$i]['n_diferentes']= $aux[0]["value"];
			$this->header[$i]['max']= $aux[0]["max"];
			$this->header[$i]['min']= $aux[0]["min"];

		}

	}

	function ordenar_valores($col_primaria, $asc = true){



		if ($asc == true) $q= "SELECT * FROM `".$this->username."` ORDER BY `$col_primaria` ASC ";
		else $q= "SELECT * FROM `".$this->username."` ORDER BY `$col_primaria` DESC ";

		$aux = $this->consultar($q); 

		$res =null;
		for ($i = 0; $i < COUNT($aux) ; $i++) {
			$n = count($res);
			for ($h = 0; $h < COUNT($this->header) ; $h++  ) {

				$name= $this->header[$h]['nombre'];
				$res[$n][$name ] = $aux[$i][$name ];
				
			}

		}

		$this->datos = null;
		$this->datos = $res;
		
	}

	function ordenar_valores_agrupados($col_literal, $asc = true){

		$totales = null;
		//recorremos todos los datos para obtener el total 
		for ($d = 0; $d < COUNT($this->datos) ; $d++  ) {

			$t = 0;
			for ($h = 0; $h < COUNT($this->header) ; $h++  ) {

				if ($this->header[$h]['nombre'] != $col_literal ) {
					$t += $this->datos[$d][$this->header[$h]['nombre']];
				} 
				
			}
			$totales[$d] = $t;

		}

		$datos_aux = null;
		for ($i = 0; $i < COUNT($totales); $i++ ) {

			$max = 0;
			$pos = 0;
			for ($j = 0; $j < COUNT($totales); $j++  ) {
				if ($totales[$j] > $max ) {
					$max = $totales[$j];
					$pos = $j;
				}
			
			}

			$datos_aux[]= $this->datos[$pos];
			$totales[$pos] = -1000;
		}

		$this->datos = null;
		$this->datos = $datos_aux;
		
	}


	function formato_numerico($num)
	{
	  	if ( strpos($num,".") > "-1"  || strpos($num,",") > "-1" ) {
		    if ( strpos($num,",") > "-1" && strpos($num,".") > "-1" ) {
		      	if (strpos($num,".") > strpos($num,",")){
		          return str_replace(",","",$num);
		    	} else {
		          return str_replace(",",".",str_replace(".","",$num));
		      	}
		  	} else {
		      	if (strpos($num,".") > "-1") {
		        	if (strpos($num,".") == strrpos($num,".")) {
			            return $num;
			      	} else {
			          return str_replace(".","",$num);          
		        	} 
		    	} else {
			        if (strpos($num,",") == strrpos($num,",")) {
			          	return str_replace(",",".",$num);
			      	} else {
		          		return str_replace(",","",$num);          
		        	} 
		    	} 
			}
		} else {
		    return $num;
		} 
	}


	function ultimos_valores($col_max, $col_join, $col_val){

		$nombre = $this->header[$i]['nombre'];

		$q= "SELECT * FROM `".$this->username."` as t1 WHERE fecha = (SELECT max(`".$col_max."`) from `".$this->username."` as t2 WHERE t2.`".$col_join."` = t1.`".$col_join."`) ORDER BY `".$col_val."` DESC ";
		$aux = $this->consultar($q); 

		return $aux;

	}

}


?>