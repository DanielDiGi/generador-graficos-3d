<?php
/***
* Autor:		Daniel Díaz Gil
* Universidad: 	UNIR Universidad Internacional de la Rioja
* Titulación: 	Máster Universitario en Visual Analytics y Big Data
* TFM : 		Sistema experto para la generación semiautomática de gráficos
*/

class BD{
	

	/*
	Constructor de la clase.
	Realiza la conexion con la base de datos. Si no recibe parametros toma los valores por defecto
	*/
	public function __construct($usuario="think_4u_graph", $password="Ixo3NdbnI5x9rCK8", $bd="think_4u_graph", $host = "localhost")
	{
		$this->idConexion = 0;
		$bdTest = false;
		$this->idConexion = new mysqli($host, $usuario, $password, $bd);
		if ($this->idConexion->connect_error )
		{
			echo "Error: No se pudo conectar a MySQL." . PHP_EOL."<br>";
      die("errno de depuración (".$this->idConexion->connect_errno.") : " . $this->idConexion->connect_error) ;
		}
	}

	/*
	Funci�n para simplificar las consultas.
	Devuelve un array con el resultado de la consulta.
	*/
	public function consultar($stringConsulta)
	{
    //$stringConsulta = $this->idConexion->real_escape_string($stringConsulta);
		$result=$this->idConexion->query($stringConsulta);
		if (!$result)
		{
	
			echo "<br>Error en la consulta: $stringConsulta<br>";
      echo $this->idConexion->error ;
			die();
		}	
		$rows = null;
		while($row = $result->fetch_array())
		{
				$rows[] = $row;
		}

    $result->free_result();
		return $rows;
	}
	
	
	/*
	Funci�n para simplificar las inserciones, modificaciones y borrado.
	Devuelve un entero con el numero de filas afectadas.
	*/
	public function modificar($stringModificacion)
	{
    //$stringModificacion = $this->idConexion->real_escape_string($stringModificacion);
		$result=$this->idConexion->query($stringModificacion);
    if (!$result)
    {
      //throw new OperacionesBDException(mysql_error());
      echo "<br>Error en la consulta: $stringModificacion<br>";
      die($this->idConexion->error);
    }
    
    return $this->idConexion->affected_rows;;
	}

}
?>
