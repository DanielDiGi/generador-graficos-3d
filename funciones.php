<?php 

/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/

function cabecera($titulo){

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>T4uG - '.$titulo.' </title>
<script src="./d3.v4.js"></script>
<script src="https://d3js.org/d3-geo.v1.min.js"></script>
<script src="http://d3js.org/topojson.v1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3-composite-projections/1.0.2/d3-composite-projections.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<link rel="stylesheet" type="text/css" href="./style.css"/> 
</head>
<body>
<div class="cabecera"> 
TFM : Sistema experto para la generación semiautomática de gráficos
</div>
';
}

?>