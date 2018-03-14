<?php
/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/

class chart {

	public $informacion;
	private $id;

	private $alto, $ancho;
	private $margenes;

	private $colores_array = [ "#51B4F7" , "#FFA448" , "#A7FF4F" , "#FF5959" , "#E673E6"  ];
	private $colores_text = ' var paleta_propia = [ "#51B4F7" , "#FFA448" , "#A7FF4F" , "#FF5959" , "#E673E6"  ]';
	private $num_chart;

	public function __construct($datos, $id_grafico , $ancho = "1200", $alto = "500", $margen_superior = "25", $margen_izquierda = "60", $margen_inferior = "30", $margen_derecho = "180" ) {

		$this->informacion = $datos;
		$this->id = $id_grafico;
		

		$this->margenes = "
		var margin = {top: $margen_superior, right: $margen_derecho , bottom: $margen_inferior, left: $margen_izquierda};";
		
		$this->dimensiones = '
		var width  = '.$ancho.' ;
		var height = '.$alto.' ;
		var width_chart  = '.$ancho.' - margin.left - margin.right;
		var height_chart = '.$alto.' - margin.top - margin.bottom;';

		$this->margen_superior = $margen_superior;
		$this->margen_izquierda = $margen_izquierda;
		$this->margen_inferior = $margen_inferior;
		$this->margen_derecho = $margen_derecho;

		$this->num_chart = $id_grafico;

	}


	function cambiar_dimensiones_barras( $n_elementos = '' ) {	

		if($n_elementos > 0 ) {

			$alto = $n_elementos * 30 + $this->margen_superior + $this->margen_inferior;
			$ancho = 800;

		}

		$this->dimensiones = '
		var width  = '.$ancho.' ;
		var height = '.$alto.' ;
		var width_chart  = '.$ancho.' - margin.left - margin.right;
		var height_chart = '.$alto.' - margin.top - margin.bottom;';

	}

	function set_estructura($datos) {

		$this->informacion = null;
		$this->informacion = $datos;
	}

	function get_datos() {

		return $this->informacion->get_datos();
	}


	private function is_lines_to_close($col_max, $col_join , $col_val) {

		$res = $this->informacion->ultimos_valores($col_max, $col_join, $col_val);

		//Miramos si las lineas acaban en el mismo punto.
		$val = $res[0][$col_max];
		$ok = true;
		for ($i =1; $i < COUNT($res); $i++ ) {

			if ($val != $res[$i][$col_max]) {
				$ok = false;
				break;
			}
		}

		if ( ! $ok) {
			//Las lineas no acaban en el mismo punto. => no se pueden generar la legenda integrada porque se pueden solapar.
			return false;
		} else {

			$cabecera = $this->informacion->get_header();
			foreach ($cabecera as $h ) {
				if ( $h['nombre'] == $col_val ) {
					$max = $h['max'];
				}
			}
			$factor = $max * 1.03 / $max;

			$ant = $res[0][$col_val]; 
			//miramos si los numeros son proximos
			for ($i =1; $i < COUNT($res); $i++ ) {
				if ($ant  < $res[$i][$col_val] * $factor) {
					
					return false;
				}
				$ant = $res[$i][$col_val];

			}
			return true;

		}


	}


	/**
	 * Dibuja un grafico de lineas de tiempo. No deberia haber mas de 5 categorias
	 **/
	function draw_line_time_chart($categorica_col,$x_col_name, $y_col_name, $filtro = "") {


		if ($filtro == '' && $categorica_col!= '') {
			$legenda_lineas = $this->is_lines_to_close($x_col_name,$categorica_col , $y_col_name );	
		} else {
			$legenda_lineas = true;
		}
		

		$this->informacion->ordenar_valores($x_col_name, true);
		$max_date=$min_date=$max_value=$min_value="";

		foreach($this->informacion->get_header() as $h ) {
			if ($h['nombre'] == $x_col_name ) {
				$max_date=$h['max'];
				$min_date=$h['min'];
			} else if ($h['nombre'] == $y_col_name ) {
				$max_value=$h['max'];
				$min_value=$h['min'];
			}
		}



		if ($max_value >= 1000000) {
			$textFormato = 'formatoMillones';
			$n_aux = 1000000;
		} else if ($max_value >= 1000) {
			$textFormato = 'formatoMiles';
			$n_aux = 1000;
		} else {
			$textFormato = 'formatoNormal';
			$n_aux = 1;
		}

		if (ceil($max_value/$n_aux) <= 3 && $textFormato != 'formatoNormal' ) $decimales = 1;
		else $decimales = 0;


		$call_function = "
	draw_line_chart_".$this->id."();";

		$function = "
	function draw_line_chart_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones.'

		var parseDate = d3.timeParse("%Y-%m-%d");

		//Formato para ejes 
		var formatInteger = d3.format(".'.$decimales.'f"),
		formatoNormal = function(d) { return formatInteger(d); },
	    formatoMillones = function(d) { return formatInteger(d / 1e6) + "M"; },
	    formatoBillines = function(d) { return formatInteger(d / 1e9) + "B"; },
	    formatoMiles = function(d) { return formatInteger(d / 1e3) + "K"; };

	    //Para mostrar los ejes en formato español
		var formatoLocal = d3.timeFormatLocale({
		  "dateTime": "%d.%m.%Y %H:%M:%S",
		  "date": "%d.%m.%Y",
		  "time": "%H:%M:%S",
		  "periods": ["AM", "PM"],
		  "days": ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado", "Domingo"],
		  "shortDays": ["LU", "MA", "MI", "JU", "VI", "SA", "DO"],
		  "months":["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"],
		  "shortMonths": ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"]
		});

		var formatMillisecond = formatoLocal.format(".%L"),
	    formatSecond = formatoLocal.format(":%S"),
	    formatMinute = formatoLocal.format("%I:%M"),
	    formatHour = formatoLocal.format("%H:%I"),
	    formatDay = formatoLocal.format("%d %B "),
	    formatWeek = formatoLocal.format("%d %B %Y"),
	    formatMonth = formatoLocal.format("%B"),
	    formatYear = formatoLocal.format("%Y");

	   function multiFormat(date) {
		  return (d3.timeSecond(date) < date ? formatMillisecond
		      : d3.timeMinute(date) < date ? formatSecond
		      : d3.timeHour(date) < date ? formatMinute
		      : d3.timeDay(date) < date ? formatHour
		      : d3.timeMonth(date) < date ? (d3.timeWeek(date) < date ? formatDay : formatWeek)
		      : d3.timeYear(date) < date ? formatMonth
		      : formatYear)(date);
		}

		//contruimos los ejes, los dominios y los rangos
		var x = d3.scaleTime().range([0, width_chart]), 
		    y = d3.scaleLinear().range([height_chart, 0]);
			colores = d3.scaleOrdinal(d3.schemeCategory10);

		var d_min = new Date('.substr($min_date,0,4).','.(substr($min_date,5,2) - 1).','.substr($min_date,8,2).','.substr($min_date,11,2).','.substr($min_date,14,2).'),
			d_max = new Date('.substr($max_date,0,4).','.(substr($max_date,5,2) - 1).','.substr($max_date,8,2).','.substr($max_date,11,2).','.substr($max_date,14,2).');


		x.domain([d_min , d_max] ).nice();
		y.domain([ 0, '.$max_value.']).nice();

		var xAxis = d3.axisBottom(x).ticks(12).tickFormat(multiFormat),
		    yAxis = d3.axisLeft(y).ticks(5).tickFormat('.$textFormato.');

		///////////////
		var svg_lines = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height );


		var g = svg_lines.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


		var div_tooltip = d3.select("body")
			.append("div")	
		    .attr("class", "tooltip")				
		    .style("opacity", 0); 

		// Add the X Axis
		g.append("g")
		    .attr("class", "x axis")
		    .attr("transform", "translate(0," + height_chart + ")")
		    .call(xAxis);

		  // Add the Y Axis
		g.append("g")
			.attr("class", "y axis")
		    .call(yAxis); 

		//Titulo eje Y
		g.append("text")
			.attr("x", -60)
			.attr("y", -10)
			.attr("class", "axisTittle")
			.attr("font-size", "10")
			.text("'.$y_col_name.'");

		var valuelineH = d3.line()
			.x(function(d, i) { return x(new Date(d["'.$x_col_name.'"])); }) 
			.y(function(d, i) { return y(+d["'.$y_col_name.'"]); });   

		';

		if ($filtro != '' ) {

			$datos_array = $this->datos_to_string_line_filtrado($categorica_col, $x_col_name, $y_col_name,  $filtro );
		
		} else {

			$datos_array = $this->datos_to_string_line($categorica_col, $x_col_name, $y_col_name);

			$function.='
			//Titulo eje X
			g.append("text")
			.attr("x", width_chart + 15)
				.attr("y", height_chart + 8)
				.attr("text-anchor", "start")
				.attr("class", "axisTittle")
				.text("'.$x_col_name.'");
				';
		}

		$k = array_keys($datos_array);
		for ($i = 0; $i < COUNT($datos_array)  ; $i++) {

				$function .= '

				var datos_'.$i.' = '.$datos_array[$k[$i]].';

				/////////////////////////////////////

				var city_'.$i.' = g.selectAll(".lineas_'.$i.'")
				    .data([datos_'.$i.'])
				    .enter()
				    .append("g")
				    .attr("class", "lineas_'.$i.'");

				city_'.$i.'.append("path")
				      .attr("class", "lineas_'.$i.'")
				      .attr("d", valuelineH )
				      .style("stroke", function(d,i) { return colores('.$i.'); });;

				// Puntos en los años
				g.selectAll("dot")
				    .data(datos_'.$i.')
				  	.enter()
				  	.append("circle")
				    .attr("r", 1.5)
				    .attr("cx", function(d, i) { return x(new Date(d["'.$x_col_name.'"])); })
				    .attr("cy", function(d, i) { return y(d["'.$y_col_name.'"]); })
				    .attr("class", "punto")
				    .style("stroke", function(d,i) { return colores('.$i.'); })
				    .style("fill", function(d,i) { return colores('.$i.'); })
				    .on("mouseover", function(d) {
				    	
				    	div_tooltip.transition()		
				        .duration(200)		
				        .style("opacity", .9);		
				        div_tooltip.html( "'.$categorica_col.' : '.$k[$i].' <br>'.$x_col_name.' : "+ (d["'. $x_col_name.'"]) +"<br> '.$y_col_name.' : "+d["'. $y_col_name.'"] )	
				            .style("left", (d3.event.pageX) + 30 + "px")		
				            .style("top", (d3.event.pageY - 65) + "px");	      
				    })
				    .on("mouseout", function(d) {
				    	//Tooltips		
				        div_tooltip.transition()		
				            .duration(500)		
				            .style("opacity", 0);	
				    });
				    
		    ';
			if ($filtro == ''  ) {
				if ($legenda_lineas  ) {
					$function .= '
					g.append("text").datum(function(d) { return datos_'.$i.'[(datos_'.$i.'.length - 1)] ; })    
				      .attr("transform", function(d) { return "translate(" + x(new Date(d["'.$x_col_name.'"]) )  + " ," + y(d["'.$y_col_name.'"]) + ")"; })
				      .attr("x", 10)
				      .attr("dy", "0.35em")
				      .attr("class", "line_legend")
				      .text(function(d) { return "'.$k[$i].'"; })
				      .style("fill", colores('.$i.'));';
				} else {

					$function .= '
					g.append("rect")
	                .attr("fill", function (d, i) {
	                    return colores('.$i.');
	                })
	                .attr("width", 20)
	                .attr("height", 20)
	                .attr("y", function (d, i) {
	                    return '.$i.' * 30 +15 ;
	                })
	                .attr("x", width_chart + 10  );

	            	g.append("text").datum(function(d) { return datos_'.$i.'[(datos_'.$i.'.length - 1)] ; })
	                .attr("class", "legenda_bar")
	                .attr("y", function (d, i) {
	                    return '.$i.' * 30 + 30 ;
	                })
	                .attr("x", width_chart + 35 )
	                .attr("text-anchor", "start")
	                .text(function(d) { return "'.$k[$i].'"; });';
				}

			} else {


				$function .= '
				//Titulo 
				g.append("text").datum(function(d) { return datos_'.$i.'[(datos_'.$i.'.length - 1)] ; })    
			      .attr("x", width_chart / 2 )
			      .attr("y", -20)
			      .attr("dy", "0.35em")
			      .style("font", "16px Verdana")
			      .attr("text-anchor", "middle")
			      .text(function(d) { return "'.$k[$i].'"; })
			      .style("stroke", "#000000");';

			}
		}
	      
		$function .= '
}
		';
		$res = null;
		$res['llamada'] = $call_function;
		$res['script'] = $function;
		$res['id'] = $this->id;
		$res['tipo'] = "line";

		return $res;


	}



 function get_estructura_predefinidos_tiempo() {

	//buscamos el maximo y el minimo para el eje Y
	$max_abs= 0;
	$min_abs= 0;
	foreach($this->informacion->get_header() as $h ) {
		if ($h['categoria'] != '' ) {
			if ($h['max'] > $max_abs ) {
				$max_abs=$h['max'];
			} else if ($h['min'] < $min_abs ) {
				$min_abs=$h['min'];
			}			
		}
	}

	//Generamos un array con los datos en formato JSON
	$datos_json = null;
	$estructura = $this->informacion->get_header();

	$datos = $this->informacion->get_datos();

	for( $d = 0; $d < COUNT($datos) ; $d++ ) { 

		$json = "[ ";
		for($i = 1 ; $i < COUNT($estructura) ; $i++  ) {

			$nombre_var = $estructura[$i]['nombre'];
			$json .= ' { "categoria" : "'.strtoupper(substr($estructura[$i]['nombre'], 0, 3)).'" , "valor" : '.$datos[$d][$nombre_var].' } , ';
			
		}
		$json = substr($json, 0, -2)." ] ; "; 

		$datos_json[ $datos[$d][ $estructura[0]['nombre'] ] ] = $json;

	}

	
	$escala='';
	//Generamos la escala 
	for($i = 1 ; $i < COUNT($estructura) ; $i++  ) {
		$escala .= '"'.strtoupper(substr($estructura[$i]['nombre'], 0, 3)).'", ';
	
	}
	$escala = substr($escala, 0, -2); 

	$res = null;
	$res['max'] = $max_abs;
	$res['min'] = $min_abs;
	$res['datos_json'] = $datos_json;
	$res['escala_x'] = $escala;
	$res['n'] = COUNT($estructura) -1;
	$res['titulo'] = ($datos[0][$estructura[0]['nombre']]);

	return $res;

}



	function draw_line_predefinidos_cat_chart($multiple=0 ) {


		$res = $this->get_estructura_predefinidos_tiempo();

		$max_value=$res['max'];
		if ($max_value >= 1000000) {
			$textFormato = 'formatoMillones';
			$n_aux = 1000000;
		} else if ($max_value >= 1000) {
			$textFormato = 'formatoMiles';
			$n_aux = 1000;
		} else {
			$textFormato = 'formatoNormal';
			$n_aux = 1;
		}

		if (ceil($max_value/$n_aux) <= 3 && $textFormato != 'formatoNormal' ) $decimales = 1;
		else $decimales = 0;

		$call_function = "
	draw_line_chart_".$this->id."();";

		$function = "
	function draw_line_chart_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones.'



		//Formato para ejes 
		var formatInteger = d3.format(".'.$decimales.'f"),
		formatoNormal = function(d) { return formatInteger(d); },
	    formatoMillones = function(d) { return formatInteger(d / 1e6) + "M"; },
	    formatoBillines = function(d) { return formatInteger(d / 1e9) + "B"; },
	    formatoMiles = function(d) { return formatInteger(d / 1e3) + "K"; };


		//contruimos los ejes, los dominios y los rangos
		var x = d3.scaleBand().range([0, width_chart]), 
		    y = d3.scaleLinear().range([height_chart, 0]);
			colores = d3.scaleOrdinal(d3.schemeCategory10);

		x.domain([ '.$res['escala_x'].' ] );
		y.domain([ 0, '.$res['max'].']).nice();

		var xAxis = d3.axisBottom(x).ticks('.$res['n'].').tickSizeOuter(0),
		    yAxis = d3.axisLeft(y).ticks(5).tickFormat('.$textFormato.');

		///////////////
		var svg_lines = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height );

		var g = svg_lines.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


		var div_tooltip = d3.select("body")
			.append("div")	
		    .attr("class", "tooltip")				
		    .style("opacity", 0); 

		// Add the X Axis
		g.append("g")
		    .attr("class", "x axis")
		    .attr("transform", "translate(0," + height_chart + ")")
		    .call(xAxis);

		// Add the Y Axis
		g.append("g")
			.attr("class", "y axis")
		    .call(yAxis); 

		//Titulo eje Y
		g.append("text")
			.attr("x", - margin.left )
			.attr("y", -10)
			.attr("class", "axisTittle")
			.attr("font-size", "10")
			.text("'.$y_col_name.'");

		var valuelineH = d3.line()
			.x(function(d, i) { return x(d.categoria) + x.step() / 2 ; }) 
			.y(function(d, i) { return y(+d.valor); });   

		';


		if ($multiple != 0 ) {

			$function .= "

				//Titulo eje X
			svg_lines.append('text')
				.attr('x', width / 2 )
				.attr('y', 20)
				.attr('text-anchor', 'middle')
				.attr('class', 'label')
				.text('".$res['titulo']."');";
		}

		$datos_array = $res['datos_json'];

		$k = array_keys($datos_array);
		for ($i = 0; $i < COUNT($datos_array)  ; $i++) {

				$function .= '

		var datos_'.$i.' = '.$datos_array[$k[$i]].';

		/////////////////////////////////////

		var city_'.$i.' = g.selectAll(".lineas_'.$i.'")
		    .data([datos_'.$i.'])
		    .enter()
		    .append("g")
		    .attr("class", "lineas_'.$i.'");

		city_'.$i.'.append("path")
		      .attr("class", "lineas_'.$i.'")
		      .attr("d", valuelineH )
		      .style("stroke", function(d,i) { return colores('.$i.'); });;

		// Puntos para marcar los datos 
		g.selectAll("dot")
		    .data(datos_'.$i.')
		  	.enter()
		  	.append("circle")
		    .attr("r", 2.5)
		    .attr("cx", function(d, i) { return x(d.categoria) + x.step() / 2 ; })
		    .attr("cy", function(d, i) { return y(d.valor); })
		    .attr("class", "punto")
		    .style("stroke", function(d,i) { return colores('.$i.'); })
		    .style("fill", function(d,i) { return colores('.$i.'); });


';
			if ($multiple == 0 ) {


						// Añadimos el nombre de cada linea
					$function .= '	
					g.append("text").datum(function(d) { return datos_'.$i.'[(datos_'.$i.'.length - 1)] ; })    
				      .attr("transform", function(d) { return "translate(" + ( x(d.categoria) + x.step() / 2 )  + " ," + y(d.valor) + ")"; })
				      .attr("x", 10)
				      .attr("dy", "0.35em")
				      .attr("class", "line_legend")
				      .text(function(d) { return "'.$k[$i].'"; })
				      .style("fill", colores('.$i.'));  
			';
			}		
	
		}

		$res = null;
		$res['llamada'] = $call_function;
		$res['script'] = $function."}";
		$res['id'] = $this->id;
		$res['tipo'] = "line";

		return $res;


	}


	function draw_bar_chart($x_col_name, $y_col_name, $titulo = '' , $n = 0 ) {

		//Si tiene titulo nos esta indicando que es un multigráfico.
		if ($titulo == '' ) $this->informacion->ordenar_valores($y_col_name , false );
		$max_date=$min_date=$max_value=$min_value="";

		$valores = $this->datos_to_string();

		foreach($this->informacion->get_header() as $h ) {
				if ($h['nombre'] == $y_col_name ) {
					$max_value=$h['max'];
					$min_value=$h['min'];
				}
		}

		$clase_ejes = "";
		if ( $titulo != '' ) {
			$clase_ejes = "_multiple";
		}

		if ($max_value >= 1000000) {
			$textFormato = 'formatoMillones';
			$n_aux = 1000000;
		} else if ($max_value >= 1000) {
			$textFormato = 'formatoMiles';
			$n_aux = 1000;
		} else {
			$textFormato = 'formatoNormal';
			$n_aux = 1;
		}

		if (ceil($max_value/$n_aux) <= 4 && $max_value < 5 ) $decimales = 1;
		else $decimales = 0;
	

		$call_function = "
	draw_bar_chart_".$this->id."();";

		$function = "
	function draw_bar_chart_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones.'

		'.$this->colores_text.'

		var data = '.$valores.';
		//contruimos los ejes, los dominios y los rangos
		var x = d3.scaleBand().rangeRound([0, width_chart]).padding(0.1), 
		    y = d3.scaleLinear().range([height_chart, 0]);

		x.domain(data.map(function(d) { return d["'.$x_col_name.'"]; }));
		y.domain([ 0, '.$max_value.']).nice();

		//Formato para ejes 
		var formatInteger = d3.format(".'.$decimales.'f"),
		formatoNormal = function(d) { return formatInteger(d); },
		formatoMillones = function(d) { return formatInteger(d / 1e6) + "M"; },
		formatoBillines = function(d) { return formatInteger(d / 1e9) + "B"; },
		formatoMiles = function(d) { return formatInteger(d / 1e3) + "K"; };

		var xAxis = d3.axisBottom(x),
		    yAxis = d3.axisLeft(y).ticks(5).tickFormat('.$textFormato.');

		///////////////
		var svg_bar = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height );

		var g = svg_bar.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


		var div_tooltip = d3.select("body")
			.append("div")	
		    .attr("class", "tooltip")				
		    .style("opacity", 0); 

		// Add the X Axis
		g.append("g")
		    .attr("class", "x_axis_bar'.$clase_ejes.'")
		    .attr("transform", "translate(0," + height_chart + ")")
		    .call(xAxis);

		  // Add the Y Axis
		g.append("g")
			.attr("class", "y_axis_bar'.$clase_ejes.'")
		    .call(yAxis); 

		g.selectAll(".bar")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar")
		      .attr("fill", paleta_propia[0])
		      .attr("x", function(d) { return x(d["'.$x_col_name.'"]); })
		      .attr("y", function(d) { return y(d["'.$y_col_name.'"]); })
		      .attr("width", x.bandwidth())
		      .attr("height", function(d) { return height_chart - y(d["'.$y_col_name.'"]); })
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_col_name.' : "+d["'. $x_col_name.'"]+"<br> '.$y_col_name.' : "+d["'. $y_col_name.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    });

		g.append("text")
			.attr("x", -60)
			.attr("y", -10)
			.attr("class", "axisTittle")
			.attr("text-anchor", "start")
			.text("'.$y_col_name.'"); 

		g.append("text")
			.attr("x", width_chart + 10)
			.attr("y", height_chart + 10)
			.attr("class", "axisTittle")
			.attr("text-anchor", "start")
			.text("'.$x_col_name.'"); 

		if ("'.$titulo.'" != "" ) {

			g.append("text")
                .attr("class", "titulo_bar_multiple")
                .attr("y", -15 )
                .attr("x", width_chart / 2  )
                .attr("text-anchor", "middle")
                .text("'.$titulo.'");

		}   

	}';


		
		$res = null;
		$res['llamada'] = $call_function;
		$res['script'] = $function;
		$res['id'] = $this->id;
		$res['tipo'] = "bar";

		return $res;

	}

	function draw_doble_bar_chart($x_left_col_name, $x_right_col_name, $y_col_name) {

		$this->informacion->ordenar_valores($x_right_col_name , true );
		$max_date=$min_date=$max_value=$min_value="";

		$valores = $this->datos_to_string();

		foreach($this->informacion->get_header() as $h ) {
				if ($h['nombre'] == $x_left_col_name ) {
					$max_value_left=$h['max'];
					$min_value_left=$h['min'];
				}
		}

		foreach($this->informacion->get_header() as $h ) {
				if ($h['nombre'] == $x_right_col_name ) {
					$max_value_right=$h['max'];
					$min_value_right=$h['min'];
				}
		}

		if ($max_value_left >= 1000000) {
			$textFormato_left = 'formatoMillones';
		} else if ($max_value_left >= 1000) {
			$textFormato_left = 'formatoMiles';
		} else {
			$textFormato_left = 'formatoNormal';
		}

		if ($max_value_right >= 1000000) {
			$textFormato_right = 'formatoMillones';
		} else if ($max_value_right >= 1000) {
			$textFormato_right = 'formatoMiles';
		} else {
			$textFormato_right = 'formatoNormal';
		}
		

		$call_function = "
	draw_bar_chart_".$this->id."();";

		$function = "
	function draw_bar_chart_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones.'

		'.$this->colores_text.'
        var puntoCentral = width_chart / 2;
        var texto_cental = 100;

		var data = '.$valores.';
		//contruimos los ejes, los dominios y los rangos
		var l = d3.scaleLinear().range([0, (width_chart / 2 - texto_cental/2)]),
			r = d3.scaleLinear().range([0, (width_chart / 2 - texto_cental/2)]),
		    y = d3.scaleBand().rangeRound([height_chart, 0  ]).padding(0.1);

		l.domain(['.$max_value_left.' , 0]).nice();
		r.domain([0,'.$max_value_right.']).nice();
		y.domain(data.map(function(d) { return d["'.$y_col_name.'"]; }));


		var formatInteger = d3.format(".0f"),
		formatoNormal = function(d) { return formatInteger(d); },
		formatoMillones = function(d) { return formatInteger(d / 1e6) + "M"; },
		formatoBillines = function(d) { return formatInteger(d / 1e9) + "B"; },
		formatoMiles = function(d) { return formatInteger(d / 1e3) + "K"; };

		var xAxisRight = d3.axisBottom(r).ticks(5).tickFormat('.$textFormato_right.'),
		xAxisLeft = d3.axisBottom(l).ticks(5).tickFormat('.$textFormato_left.'),

		yAxis = d3.axisLeft(y) .tickFormat("");
		yAxis2 = d3.axisRight(y).tickFormat(""); 


		///////////////
		var svg_bar = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height );

		var g = svg_bar.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


		var div_tooltip = d3.select("body")
			.append("div")	
		    .attr("class", "tooltip")				
		    .style("opacity", 0); 

		// Add the X Axis //////////////////////////////////////////////////
		g.append("g")
		    .attr("class", "y_axis_bar")
		    .attr("transform", "translate("+(width_chart / 2 + texto_cental / 2 )+"," + height_chart + ")")
		    .call(xAxisRight);

		g.append("g")
		    .attr("class", "y_axis_bar")
		    .attr("transform", "translate(0," + height_chart + ")")
		    .call(xAxisLeft);

		// Add the Y Axis //////////////////////////////////////////////////
		var y_ini = parseFloat(width_chart / 2 + texto_cental / 2 );
		g.append("g")
			.attr("class", "x_axis_bar")
			.attr("text-anchor", "middle")
			.attr("transform", "translate( " + y_ini + ", 0   )")
		    .call(yAxis); 

		var y_ini = parseFloat(width_chart / 2 - texto_cental / 2 );
		g.append("g")
			.attr("class", "x_axis_bar")
			.attr("text-anchor", "middle")
			.attr("transform", "translate( " + y_ini + ", 0   )")
		    .call(yAxis2); 


///////////////////////////////////

            var textoCental = 200;
            var width_sub_chart_left = (width_chart / 2 - texto_cental / 2 );
            var chart_left_ini = (width_chart / 2 + texto_cental / 2 );

            var padding = height_chart / data.length * 0.05;
            var anchoBarra = height_chart / data.length - padding ;

            console.log(height_chart );
            console.log(data.length );
            console.log(height_chart / data.length );
            console.log(padding * data.length );


            

            // BARRAs izquierda /////////////
			g.selectAll(".bar")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar")
		      .attr("fill" , paleta_propia[0] )
		      .attr("x", function(d) { return l(d["'.$x_left_col_name.'"]); })
		      .attr("y", function(d) { return y(d["'.$y_col_name.'"]); })
		      .attr("width", function(d) { return ( width_sub_chart_left - l(d["'.$x_left_col_name.'"])); } )
		      .attr("height", anchoBarra )
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_left_col_name.' : "+d["'. $x_left_col_name.'"]+"<br> '.$y_col_name.' : "+d["'. $y_col_name.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    });

		    // BARRAs derecha /////////////
			g.selectAll(".bar2")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar2")
		      .attr("fill" , paleta_propia[2] )
		      .attr("x", chart_left_ini )
		      .attr("y", function(d) { return y(d["'.$y_col_name.'"]); })
		      .attr("width", function(d) { return  r(d["'.$x_right_col_name.'"]); } )
		      .attr("height", anchoBarra )
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_right_col_name.' : "+d["'. $x_right_col_name.'"]+"<br> '.$y_col_name.' : "+d["'. $y_col_name.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    }); 
		    
			//cabeceras de ejes
			var legend = g.append("g");

            legend.append("text")
                .attr("class", "titulo_bar_double")
                .attr("y", 0 )
                .attr("x", (width_chart / 4 - texto_cental / 2 ) )
                .attr("text-anchor", "middle")
                .text("'.$x_left_col_name.'");
		    
		    legend.append("text")
                .attr("class", "titulo_bar_double")
                .attr("y", 0 )
                .attr("x", ( 3 * (width_chart / 4 )+ texto_cental / 2 ) )
                .attr("text-anchor", "middle")
                .text("'.$x_right_col_name.'");


            legend.append("text")
                .attr("class", "titulo_bar_double")
                .attr("y", 0 )
                .attr("x", width_chart / 2 )
                .attr("text-anchor", "middle")
                .text("'.$y_col_name.'");
           
           	//Eje Y descriptores -> asi quedan centrados entre las dos graficos
           	g.selectAll(".descriptor_bar")
		    	.data(data)
		    	.enter()
			    	.append("text")
	                .attr("class", "descriptor_bar")
	                .attr("y", function(d) { return y(d["'.$y_col_name.'"]) + 20; } )
	                .attr("x", (width_chart / 2 ) )
	                .attr("text-anchor", "middle")
	                .text(function(d) { return (d["'.$y_col_name.'"]); } );
		   
	}';

		$res = null;
		$res['llamada'] = $call_function;
		$res['script'] = $function;
		$res['id'] = $this->id;
		$res['tipo'] = "bar";

		return $res;



	}

	function draw_group_bar_chart($x_col_name, $y_col_name_1 , $y_col_name_2, $y_col_name_3='', $y_col_name_4='') {


		$this->informacion->ordenar_valores_agrupados($x_col_name , false );
		$max_value=$min_value="";

		$valores = $this->datos_to_string();

		foreach($this->informacion->get_header() as $h ) {
			if ($h['nombre'] == $y_col_name_1 || $h['nombre'] == $y_col_name_2 || $h['nombre'] == $y_col_name_3 || $h['nombre'] == $y_col_name_4) {

				if ($max_value < $h['max'] ) {
					$max_value=$h['max'];
				}

				if ($min_value < $h['min'] ) {
					$min_value=$h['min'];
				}
				
			}		
		}

		if ($y_col_name_4=='') $n = 3;
		else if ($y_col_name_3=='') $n = 2;
		else $n = 3;

		if($y_col_name_3 == '' ) {
			$legenda_elementos = ' [ "'.$y_col_name_1.'" , "'.$y_col_name_2.'" ] ';
		}else if($y_col_name_4 == '' ) {
			$legenda_elementos = ' [ "'.$y_col_name_1.'" , "'.$y_col_name_2.'" , "'.$y_col_name_3.'" ] ';  
		} else {
			$legenda_elementos = ' [ "'.$y_col_name_1.'" , "'.$y_col_name_2.'", "'.$y_col_name_3.'", "'.$y_col_name_4.'"  ] '; 
		}
		
		if ($max_value >= 1000000) {
			$textFormato = 'formatoMillones';
			$n_aux = 1000000;
		} else if ($max_value >= 1000) {
			$textFormato = 'formatoMiles';
			$n_aux = 1000;
		} else {
			$textFormato = 'formatoNormal';
			$n_aux = 1;
		}

		if (ceil($max_value/$n_aux) <= 4 && $max_value < 5 ) $decimales = 1;
		else $decimales = 0;

		$call_function = "
	draw_group_bar_chart_".$this->id."();";

		$function = "
	function draw_group_bar_chart_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones.'

		'.$this->colores_text.'

		var data = '.$valores.';
		//contruimos los ejes, los dominios y los rangos
		var x = d3.scaleBand().rangeRound([0, width_chart]).padding(0.1), 
		    y = d3.scaleLinear().range([height_chart, 0]);
			colores = d3.scaleOrdinal(d3.schemeCategory10);

		x.domain(data.map(function(d) { return d["'.$x_col_name.'"]; }));
		y.domain([ 0, '.$max_value.']).nice();

		//Formato para ejes 
		var formatInteger = d3.format(".'.$decimales.'f"),
		formatoNormal = function(d) { return formatInteger(d); },
		formatoMillones = function(d) { return formatInteger(d / 1e6) + "M"; },
		formatoBillines = function(d) { return formatInteger(d / 1e9) + "B"; },
		formatoMiles = function(d) { return formatInteger(d / 1e3) + "K"; };


		var xAxis = d3.axisBottom(x).ticks(12),
		    yAxis = d3.axisLeft(y).ticks(5).tickFormat('.$textFormato.');;

		///////////////
		var svg_bar = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height );

		var g = svg_bar.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


		var div_tooltip = d3.select("body")
			.append("div")	
		    .attr("class", "tooltip")				
		    .style("opacity", 0); 


		
		// Add the X Axis
		g.append("g")
		    .attr("class", "x_axis_bar")
		    .attr("transform", "translate(0," + height_chart + ")")
		    .call(xAxis);

		  // Add the Y Axis
		g.append("g")
			.attr("class", "y_axis_bar")
		    .call(yAxis); 

		var padding = 2;
		var ancho_col =  (x.bandwidth() / '.$n.') - padding;   
		console.log(paleta_propia[0]);

		g.selectAll(".bar_1")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar_1")
		      .attr("x", function(d) { return x(d["'.$x_col_name.'"])  ; })
		      .attr("y", function(d) { return y(d["'.$y_col_name_1.'"]); })
		      .attr("width", ancho_col )
		      .attr("height", function(d) { return height_chart - y(d["'.$y_col_name_1.'"]); })
		      .attr("fill", paleta_propia[0] )
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_col_name.' : "+d["'. $x_col_name.'"]+"<br> '.$y_col_name_1.' : "+d["'. $y_col_name_1.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    }); 


		g.selectAll(".bar_2")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar_2")
		      .attr("x", function(d) { return x(d["'.$x_col_name.'"]) + ancho_col  ; })
		      .attr("y", function(d) { return y(d["'.$y_col_name_2.'"]); })
		      .attr("width", ancho_col )
		      .attr("height", function(d) { return height_chart - y(d["'.$y_col_name_2.'"]); })
		      .attr("fill", paleta_propia[1] )
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_col_name.' : "+d["'. $x_col_name.'"]+"<br> '.$y_col_name_2.' : "+d["'. $y_col_name_2.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    }); 
  ';

  if ( $y_col_name_3 != '') {
	$function.='
		g.selectAll(".bar_3")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar_3")
		      .attr("x", function(d) { return x(d["'.$x_col_name.'"]) + (2 * ancho_col)  ; })
		      .attr("y", function(d) { return y(d["'.$y_col_name_3.'"]); })
		      .attr("width", ancho_col )
		      .attr("height", function(d) { return height_chart - y(d["'.$y_col_name_3.'"]); })
		      .attr("fill", paleta_propia[2] )
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_col_name.' : "+d["'. $x_col_name.'"]+"<br> '.$y_col_name_3.' : "+d["'. $y_col_name_3.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    }); 

		    ';

  }

  if ( $y_col_name_4 != '') {
	$function.='
		g.selectAll(".bar_4")
		    .data(data)
		    .enter().append("rect")
		      .attr("class", "bar_4")
		      .attr("x", function(d) { return x(d["'.$x_col_name.'"]) + (2 * ancho_col)  ; })
		      .attr("y", function(d) { return y(d["'.$y_col_name_4.'"]); })
		      .attr("width", ancho_col )
		      .attr("height", function(d) { return height_chart - y(d["'.$y_col_name_4.'"]); })
		      .attr("fill", paleta_propia[3] )
		      .on("mouseover", function(d) {
		    	
		    	div_tooltip.transition()		
		        .duration(200)		
		        .style("opacity", .9);		
		        div_tooltip.html( "'.$x_col_name.' : "+d["'. $x_col_name.'"]+"<br> '.$y_col_name_4.' : "+d["'. $y_col_name_4.'"] )	
		            .style("left", (d3.event.pageX) + 30 + "px")		
		            .style("top", (d3.event.pageY - 65) + "px");	      
		    })
		    .on("mouseout", function(d) {
		    	//Tooltips		
		        div_tooltip.transition()		
		            .duration(500)		
		            .style("opacity", 0);	
		    }); 

		    ';

  }

	//LEYENDA 
	$function.='

			var legenda_elementos = '.$legenda_elementos.';

			var legend = g.selectAll(".legend")
                .data(legenda_elementos)
                .enter()
                .append("g");

            legend.append("rect")
                .attr("fill", function (d, i) {
                    return paleta_propia[i];
                })
                .attr("width", 20)
                .attr("height", 20)
                .attr("y", function (d, i) {
                    return i * 30 - 15 ;
                })
                .attr("x", width_chart + 10  );

            legend.append("text")
                .attr("class", "legenda_bar")
                .attr("y", function (d, i) {
                    return i * 30;
                })
                .attr("x", width_chart + 35 )
                .attr("text-anchor", "start")
                .text(function (d, i) {
                    return legenda_elementos[i];
                });

	';

	$function.='}';


		
	$res = null;
	$res['llamada'] = $call_function;
	$res['script'] = $function;
	$res['id'] = $this->id;
	$res['tipo'] = "bar";

	return $res;


	}


	function draw_stacked_bar_chart($x_col_name) {

		//Calculamos los totales por columna. PAra la ordenación
		$datos = $this->informacion->get_datos();
		 $max_value	=0;
		for($i = 0; $i < COUNT($datos); $i++ ) {

			$t = 0 ;
			$keys = array_keys($datos[$i]); 
			

			for($j = 0; $j < COUNT($keys); $j++ ) {
				if ($keys[$j] != $x_col_name  ) $t+=$datos[$i][$keys[$j]]; 
			} 

			$datos[$i]['_total'] = $t;
			if ( $max_value	< $t ) $max_value = $t;
		}

			$this->informacion->set_datos($datos);
			$valores = $this->datos_to_string();
		
		if ($max_value >= 1000000) {
			$textFormato = 'formatoMillones';
			$n_aux = 1000000;
		} else if ($max_value >= 1000) {
			$textFormato = 'formatoMiles';
			$n_aux = 1000;
		} else {
			$textFormato = 'formatoNormal';
			$n_aux = 1;
		}

		if (ceil($max_value/$n_aux) <= 3 ) $decimales = 1;
		else $decimales = 0;


		$columnas = "[ ";
		foreach ($this->informacion->get_header() as $h ) {

			if ($h['nombre'] != $x_col_name && $h['nombre'] != '_total' ) $columnas.= '"'.str_replace('"', "´" , $h['nombre'] ).'", ';
		}
		$columnas = substr($columnas, 0 , -2 ) . " ] ";
		

		$call_function = "
	draw_stacked_bar_chart_".$this->id."();";

		$function = "
	function draw_stacked_bar_chart_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones.'

		var data = '.$valores.';



		//Formato para ejes 
		var formatInteger = d3.format(".'.$decimales.'f"),
		formatoNormal = function(d) { return formatInteger(d); },
		formatoMillones = function(d) { return formatInteger(d / 1e6) + "M"; },
		formatoBillines = function(d) { return formatInteger(d / 1e9) + "B"; },
		formatoMiles = function(d) { return formatInteger(d / 1e3) + "K"; };


		// set x scale
		var x = d3.scaleBand()
    		.rangeRound([0, width_chart])
    		.paddingInner(0.05);

		// set y scale
		var y = d3.scaleLinear()
		    .rangeRound([height_chart, 0]).nice();

		// set the colors
		var z = d3.scaleOrdinal()
		    .range(["#51B4F7" , "#FFA448" , "#A7FF4F" , "#FF5959" , "#E673E6" ]);



		var keys = '.$columnas.';

		  data.sort(function(a, b) { return b._total - a._total; });
		  x.domain(data.map(function(d) { return d["'.$x_col_name.'"]; }));
		  y.domain([0, d3.max(data, function(d) { return +d._total; })]).nice();


		  z.domain(keys);


		var svg_bar = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height );

		var g = svg_bar.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
		

	 	g.append("g")
		    .selectAll("g")
		    .data(d3.stack().keys(keys)(data))
		    .enter().append("g")
		      .attr("fill", function(d) { return z(d.key); })
		    .selectAll("rect")
		    .data(function(d) { return d; })
		    .enter().append("rect")
		      .attr("x", function(d) { return x(d.data["'.$x_col_name.'"]); })
		      .attr("y", function(d) { return y(d[1]); })
		      .attr("height", function(d) { return y(d[0]) - y(d[1]); })
		      .attr("width", x.bandwidth())
		    .on("mouseover", function() { tooltip.style("display", null); })
		    .on("mouseout", function() { tooltip.style("display", "none"); })
		    .on("mousemove", function(d) {
		      var xPosition = d3.mouse(this)[0] - 5;
		      var yPosition = d3.mouse(this)[1] - 5;
		      tooltip.attr("transform", "translate(" + xPosition + "," + yPosition + ")");
		      tooltip.select("text").text( function (d2) {

					var var_name = ""; 
					for (var key in d.data) {
	    				if (key === "length" ) continue;
	    				if (d.data[key] == (d[1]-d[0]) ) { var_name = key;  }
					}
		      		
					return d.data["'.$x_col_name.'"] + " , "+ var_name + " = "  + (d[1]-d[0]) ;

		      }) ;
		    });

		g.append("g")
		  .attr("class", "axis")
		  .attr("transform", "translate(0," + height_chart + ")")
		  .call(d3.axisBottom(x));

		g.append("g")
		  .attr("class", "axis")
		  .call(d3.axisLeft(y).ticks(5).tickFormat('.$textFormato.'))
		.append("text")
		  .attr("x", 2)
		  .attr("y", y(y.ticks().pop()) + 0.5)
		  .attr("dy", "0.4em")
		  .attr("fill", "#000")
		  .attr("font-weight", "bold")
		  .attr("text-anchor", "start");



		 var tooltip = svg_bar.append("g")
		    .attr("class", "tooltip")
		    .style("display", "none");
		      
		  tooltip.append("rect")
		    .attr("width", 60)
		    .attr("height", 20)
		    .attr("fill", "white")
		    .style("opacity", 0.5);

		  tooltip.append("text")
		    .attr("x", 30)
		    .attr("dy", "1.2em")
		    .style("text-anchor", "middle")
		    .attr("font-size", "12px")
		    .attr("font-weight", "bold");



		var legend = g.append("g")
		  .attr("font-family", "sans-serif")
		  .attr("font-size", 10)
		  .attr("text-anchor", "end")
		.selectAll("g")
		.data(keys.slice().reverse())
		.enter().append("g")
		  .attr("transform", function(d, i) { return "translate(-70," + i * 20 + ")"; });

		legend.append("rect")
		  .attr("x", width - 19)
		  .attr("width", 19)
		  .attr("height", 19)
		  .attr("fill", z);

		legend.append("text")
		  .attr("x", width - 24)
		  .attr("y", 9.5)
		  .attr("dy", "0.32em")
		  .text(function(d) { return d; });

	}';


		
		$res = null;
		$res['llamada'] = $call_function;
		$res['script'] = $function;
		$res['id'] = $this->id;
		$res['tipo'] = "bar";

		return $res;


	}


	function draw_scatter_plot($x_col_name, $y_col_name , $z_col_name = "", $w_col_name = null ) { 


		$x_max = $x_min = 0;
		$y_max = $y_min = 0;
		$w_max = $w_min = 0;


		foreach ($this->informacion->get_header() as $c ) {

			if ($c['nombre'] == $x_col_name ) {
				$x_max = $c['max']; 
				$x_min = $c['min'];
			}

			if ($c['nombre'] == $y_col_name ) {
				$y_max = $c['max']; 
				$y_min = $c['min'];
			}

			if ($c['nombre'] == $w_col_name ) {
				$w_max = $c['max']; 
				$w_min = $c['min'];
			}
		}


		

		$call_function = "
	draw_scatter_plot_".$this->id."();";

		$function = "
	function draw_scatter_plot_".$this->id."(){
		".$this->margenes."
		".$this->dimensiones;


	$function .= '
		var svg = d3.select("#grafico_'.$this->id.'")
			.attr("width", width )
			.attr("height", height )
	.append("g")
		.attr("transform", "translate(" + margin.left + ", " + margin.top + ")" )';


$function .= "
	//EJES y ESCALAS///////////////////////////////
	var xScale = d3.scaleLinear().range([0, width_chart]).domain( [".$x_min.",".$x_max."]).nice();
	var xAxis = d3.axisBottom().scale(xScale).ticks(5);

	var yScale = d3.scaleLinear().range([height_chart, 0]).domain([".$y_min.",".$y_max."]).nice();
	var yAxis = d3.axisLeft().scale(yScale).ticks(5);

	var color = d3.scaleOrdinal(d3.schemeCategory10);

	//DIBUJAMOS LOS EJES//////////////////////////
	svg.append('g')
		.attr('transform', 'translate(0,' + height_chart + ')')
		.attr('class', 'x axis')
		.call(xAxis);
	//Titulo eje X
	svg.append('text')
		.attr('x', width_chart + 10)
		.attr('y', height_chart +15)
		.attr('class', 'axisTittle')
		.attr('text-anchor', 'start')
		.text('".$x_col_name."');

	//Titulo eje Y
	svg.append('g')
		.attr('transform', 'translate(0,0)')
		.attr('class', 'y axis')
		.call(yAxis);
	svg.append('text')
			.attr('x', -60)
			.attr('y', -10)
			.attr('class', 'axisTittle')
			.attr('text-anchor', 'start')
			.text('".$y_col_name."');
";


	$radio = ".attr('r', 5)";
	$w_text = "";

	if ($w_col_name != null ) {
		$function .= "
		//Radio de los circulos
		var radius = d3.scaleLinear()
			.range([5,15]).domain([".$w_min.",".$w_max."]).nice();
		";

		$radio = '.attr("r", function(d){ return radius(d["'.$w_col_name.'"]); })';

		$w_text .= " + ' $w_col_name : ' + d['".$w_col_name."']";
	}

	$aux = $this->datos_to_string_line($z_col_name, $x_col_name, $y_col_name, $w_col_name);

	//Iteramos por cada valor variable de texto que dispongamos
	$literales = array_keys($aux);
	for ($i = 0; $i < COUNT($aux)  ; $i++) {

			$z_text = '';
			$z = $literales[$i];

			$function .= '

			var datos_'.$i.' = '.$aux[$literales[$i]].'; 

			datos_'.$i.'.forEach(function(d){
					d["'.$x_col_name.'"] = +d["'.$x_col_name.'"];
					d["'.$y_col_name.'"] = +d["'.$y_col_name.'"]; ';

			//En caso de ser 3 numerales
			if ($w_col_name != null ) {
				$function .= '
				d["'.$w_col_name.'"] = +d["'.$w_col_name.'"] ; 
				';
			}

			//En caso de ser 1 literal y 2 numerales
			if ($z_col_name != null ) {
				$function .= "
					d['".$z_col_name."'] = +d['".$z_col_name."']; ";

				$color_aux = ' color("'.$z.'") ';

				$z_text = " + ' $z_col_name : $z ' ";

			} else {
				$color_aux = ' "#51B4F7" ';

			}



			$function .= "
			});";

			
		
			$function .= "

				var bubble_".$i." = svg.selectAll('.bubble_".$i."')
					.data(datos_".$i.")
					.enter().append('circle')
					.attr('class', 'bubble_".$i."')
					.attr('cx', function(d){return xScale(d['".$x_col_name."']);})
					.attr('cy', function(d){ return yScale(d['".$y_col_name."']); })
					".$radio."
					.style('fill', function(d){ return ".$color_aux." ; });

				bubble_".$i.".append('title')
					".$radio."
					.text(function(d){
						return '$x_col_name : '+ d['".$x_col_name."'] + ' $y_col_name : '+ d['".$y_col_name."'] ".$w_text." ". $z_text.";
					});
			";
		}

		if ($z_col_name != '' ) {

			$function .= "
				
				var legend = svg.selectAll('legend')
					.data(color.domain())
					.enter()
					.append('g')
					.attr('class', 'legend');

				legend.append('rect')
					.attr('width', 18)
					.attr('height', 18)
					.style('fill', color)
					.attr('y', function (d, i) {
                    		return i * 30 - 15 ;
                	})
                	.attr('x', width_chart + 30  )

				legend.append('text')
                .attr('class', 'legenda_bar')
                .attr('y', function (d, i) {
                    return i * 30;
                })
                .attr('x', width_chart + 55 )
                .attr('text-anchor', 'start')
                .text(function(d) { return d; }
                );

               


				legend.on('click', function(type){
					d3.selectAll('.bubble')
						.style('opacity', 0.15)
						.filter(function(d){
							return d['".$z_col_name."'] == type;
						})
						.style('opacity', 1);
				})";
				
		}

		if ($w_col_name != '' ) {

$function .= "

 				legend.append('text')
                .attr('class', 'legenda_bar')
                .attr('y', 300)
                .attr('x', width_chart + 25 )
                .attr('text-anchor', 'start')
                .text('Tamaño del punto');

                legend.append('text')
                .attr('class', 'legenda_bar')
                .attr('y', 320)
                .attr('x', width_chart + 25 )
                .attr('text-anchor', 'start')
                .attr('font-size', '12px')
                .text('".$w_col_name."');
                

                legend.append('circle')
					.attr('class', 'bubble_lmin')
					.attr('cx', width_chart + 48 )
					.attr('cy', 340 )
					.attr('r', 3 )
					.style('fill', '#808080' );

				legend.append('text')
                .attr('class', 'legenda_bar')
                .attr('y', 345)
                .attr('x', width_chart + 65 )
                .attr('text-anchor', 'start')
                .text('min: $w_min');

                legend.append('circle')
					.attr('class', 'bubble_lmin')
					.attr('cx', width_chart + 48 )
					.attr('cy', 365 )
					.attr('r', 15 )
					.style('fill', '#808080' );

				legend.append('text')
                .attr('class', 'legenda_bar')
                .attr('y', 368)
                .attr('x', width_chart + 65 )
                .attr('text-anchor', 'start')
                .text('max : $w_max');

					";
		}
		

	

		$res = null;
		$res['llamada'] = $call_function;
		$res['script'] = $function. "};";
		$res['id'] = $this->id;
		$res['tipo'] = "scatter";

		return $res;

	}



	private function datos_to_string(){

		$res = "[ ";
		foreach ($this->informacion->get_datos() as $registro){
				$res .= " { ";	
				foreach (array_keys($registro) as $columna) {
						$res .= '"'.$columna.'" : "'.$registro[$columna].'", ' ;
				}
				$res = substr($res , 0 , -2)." } , ";
		}

		$res = substr($res , 0 , -2)." ]";
		return $res;
	}

	private function datos_to_string_line($categorica_col, $x_col_name, $y_col_name, $w_col_name = '' , $filtro = '' ){

		$valores = null;

		foreach ($this->informacion->get_datos() as $registro){
				$valores[$registro[$categorica_col]] = 1;
		}

		foreach(array_keys($valores) as $k ) {
				$res = "[ ";
				foreach ($this->informacion->get_datos() as $registro){

						//seleccionamos solo los registros del valor determinado 
						if ($registro[$categorica_col] == $k  ) {

							$res .= " { ";	
							foreach (array_keys($registro) as $columna) {

									//filtramos las columnas que vamos a necesitar.	
									if (  ($x_col_name == $columna || $y_col_name == $columna || $w_col_name == $columna) ) {
										$res .= '"'.$columna.'" : "'.$registro[$columna].'", ' ;
									}
							}
							$res = substr($res , 0 , -2)." } , ";
						}
				}

				$valores[$k] = substr($res , 0 , -2)." ]";

		}

		return $valores;
	}



	private function datos_to_string_line_filtrado($categorica_col, $x_col_name, $y_col_name, $filtro  ){



		$valores = null;

		foreach ($this->informacion->get_datos() as $registro){

				if ($registro[$categorica_col] == $filtro ) 
						$valores[$registro[$categorica_col]] = 1;
		}



		foreach(array_keys($valores) as $k ) {

				$res = "[ ";
				foreach ($this->informacion->get_datos() as $registro){

						
						//seleccionamos solo los registros del valor determinado 
						if ($registro[$categorica_col] == $k) {

							$res .= " { ";	
							foreach (array_keys($registro) as $columna) {

									//filtramos las columnas que vamos a necesitar.	
									if (  ($x_col_name == $columna || $y_col_name == $columna || $w_col_name == $columna) ) {
										$res .= '"'.$columna.'" : "'.$registro[$columna].'", ' ;
									}
							}
							$res = substr($res , 0 , -2)." } , ";
						}
						
				}

				$valores[$k] = substr($res , 0 , -2)." ]";

		}
		
		return $valores;
	}


	function ordenar_array($col){

			$res = null;
	}

}

?>