<?php
/**************************
 * Control de Horario
 * https://github.com/MdeMoUcH/horario.git
 * Desarrollado por MdeMoUcH
 * mdemouch@gmail.com
 * http://www.lagranm.com/
 **************************/
 
/*** Para la base de datos: ***/
/* CREATE TABLE `registro` (   `id` int(11) NOT NULL AUTO_INCREMENT,   `dia` varchar(15) NOT NULL default '',   `ip` varchar(15) NOT NULL default '',   `entrada` varchar(8) NOT NULL default '',   `comida` varchar(8) NOT NULL default '',   `vuelta` varchar(8) NOT NULL default '',   `salida` varchar(8) NOT NULL default '',   PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8; */

/*** Configuración: ***/
$horas_dia = 8;
$minutos_dia = 30;
$minutos_comida = 30;
$horas_viernes = 6;
$minutos_comida_viernes = 30;
$b_recuperar_comida = false;

$db_host = 'localhost';
-$db_user = 'root';
-$db_pass = 'sinpass';
-$db_name = 'test_time';
/**********************/


$ip = $_SERVER['REMOTE_ADDR'];
$dia = date('Y-m-d');
$ahora = date('H:i:s');

$mysql = mysqli_connect($db_host,$db_user,$db_pass,$db_name);
$s_botones = '';

if($resultado = mysqli_query($mysql,"SELECT * FROM registro WHERE dia = '".$dia."' AND ip = '".$ip."';")){
	if($fila = mysqli_fetch_assoc($resultado)){
		if($fila["comida"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?comida\"' value='Comida'/>";
		}elseif($fila["vuelta"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?vuelta\"' value='Vuelta'/>";
		}else{
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?salida\"' value='Salida'/>";
		}
		
		if(isset($_GET["comida"]) && $fila["comida"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?vuelta\"' value='Vuelta'/>";
			mysqli_query($mysql,"UPDATE registro SET comida = '".$ahora."' WHERE dia = '".$dia."' AND ip = '".$ip."';");
		}
		if(isset($_GET["vuelta"]) && $fila["vuelta"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?salida\"' value='Salida'/>";
			mysqli_query($mysql,"UPDATE registro SET vuelta = '".$ahora."' WHERE dia = '".$dia."' AND ip = '".$ip."';");
		}
		if(isset($_GET["salida"])){
			mysqli_query($mysql,"UPDATE registro SET salida = '".$ahora."' WHERE dia = '".$dia."' AND ip = '".$ip."';");
		}
	}else{
		mysqli_query($mysql,"INSERT INTO registro (dia,ip,entrada) VALUES ('".$dia."','".$ip."','".$ahora."');");
	}
}else{
	die('Consulta no v&aacute;lida: ' . mysql_error());
}

$s_tabla = "<center>No hay datos</center>";

if($resultado = mysqli_query($mysql,"SELECT * FROM registro ORDER BY dia DESC;")){
	$s_tabla = "\t<table border='1' width='100%'>".PHP_EOL."\t\t<tr><th>D&iacute;a</th><th>IP</th><th>Entrada</th><th>Comida</th><th>Salida</th><th>Horas</th></tr>".PHP_EOL;
	$i = 0;
	$lastday = 8;
	$b_show_separador = false;
	$tiempo_semana = array('horas'=>0,'minutos'=>0);
	while($fila = mysqli_fetch_assoc($resultado)){
		$s_comida = "";
		$s_total = "";
		$comida_m = 0;
		if($fila["comida"] != "" && $fila["vuelta"] != ""){
			$comida_s = strtotime($fila["vuelta"])-strtotime($fila["comida"]);
			if($comida_s >= 60){
				$comida_m = round($comida_s/60,0);
				$s_comida = $comida_m." min";
			}else{
				
				$s_comida = $comida_s." seg";
			}
			$s_comida = "(".$s_comida.")";
		}
		
		$s_estimated = "";
		if($fila["salida"] != ""){
			$total = strtotime($fila["salida"])-strtotime($fila["entrada"]);
		}else{
			$total = strtotime($ahora)-strtotime($dia." ".$fila["entrada"]);
			
			$horaestimada = strtotime('+'.$horas_dia.' hour', strtotime($fila["entrada"]));
			$horaestimada = strtotime('+'.$minutos_dia.' minute', $horaestimada);
			if($fila["vuelta"] == ""){
				if($b_recuperar_comida){
					$horaestimada = strtotime('+'.$minutos_comida.' minute', $horaestimada);
				}
			}else{
				$horaestimada = strtotime('+'.$comida_m.' minute', $horaestimada);
				if(!$b_recuperar_comida){
					$horaestimada = strtotime('-'.$minutos_comida.' minute', $horaestimada);
				}
			}
			$s_estimated = "<small>(&#126;".date("H:i:s",$horaestimada).")</small>";
		}
		
		if(isset($comida_s) && $b_recuperar_comida){
			$total -= $comida_s;
		}
		
		$total = $total/3600;
		$a_total = explode(".",$total);
		
		$min = ($total-$a_total[0])*60;
		$min = round($min,0);
		if($min < 10){
			$min = "0".$min;
		}elseif($min >= 60){
			$min = "00";
			$a_total[0] = $a_total[0] + 1;
		}
		$s_total = $a_total[0]."h ".$min."min";
		
		$s_date = $fila["dia"]." ".$fila["entrada"];
		
		$s_dia = date("l",strtotime($s_date));
		
		
		
		
		
		switch($s_dia){
			case "Monday":		$s_dia = "L";$i_dia = 1; 	break;
			case "Tuesday":		$s_dia = "M";$i_dia = 2; 	break;
			case "Wednesday": 	$s_dia = "X";$i_dia = 3; 	break;
			case "Thursday": 	$s_dia = "J";$i_dia = 4; 	break;
			case "Friday": 		$s_dia = "V";$i_dia = 5; 	break;
		}
		
		
		
		
		if(($lastday <= $i_dia)){// || $s_dia == 'V')){// && $lastday != 8){
			$s_tabla .= "\t\t<tr><td colspan='6' class='semana'>&#8593;".$tiempo_semana['horas']."h ".$tiempo_semana['minutos']."min&#8593;</td></tr>".PHP_EOL;
			$tiempo_semana = array('horas'=>0,'minutos'=>0);
		}
		
		$tiempo_semana['horas'] += $a_total[0];
		$tiempo_semana['minutos'] += $min;
		if($tiempo_semana['minutos'] >= 60){
			$tiempo_semana['minutos'] = $tiempo_semana['minutos'] - 60;
			$tiempo_semana['horas'] = $tiempo_semana['horas'] + 1;
		}
		
		if(($i%2) == 0){
			$background = "";
		}else{
			$background = " class='impar'";
		}
		$i++;
		
		
		if($i == 1){
			$ultimo_dia = $fila['dia'];
		}else{
			$primer_dia = $fila['dia'];
		}
		
		if($fila["dia"] == $dia){
			$fila["dia"] = "<b>".$fila["dia"]."</b>";
		}
		
		if($s_dia == "V"){
			if($fila["salida"] == ""){
				$horaestimada = strtotime('+'.$horas_viernes.' hour', strtotime($fila["entrada"]));
				if($fila["vuelta"] == ""){
					if($b_recuperar_comida){
						$horaestimada = strtotime('+'.$minutos_comida_viernes.' minute', $horaestimada);
					}
				}else{
					$horaestimada = strtotime('+'.$comida_m.' minute', $horaestimada);
					if(!$b_recuperar_comida){
						$horaestimada = strtotime('-'.$minutos_comida_viernes.' minute', $horaestimada);
					}
				}
				$s_estimated = "<small>(&#126;".date("H:i:s",$horaestimada).")</small>";
			}
		}
		$s_tabla .= "\t\t<tr".$background."><td>".$fila["dia"]." (".$s_dia.")</td><td>".$fila["ip"]."</td><td>".$fila["entrada"]."</td><td>".$fila["comida"]." - ".$fila["vuelta"]." ".$s_comida."</td><td>".$fila["salida"]."</td><td>".$s_total." ".$s_estimated."</td></tr>".PHP_EOL;
		
		$lastday = $i_dia;
	}
	$s_tabla .= "\t\t<tr><td colspan='6' class='semana'>&#8593;".$tiempo_semana['horas']."h ".$tiempo_semana['minutos']."min&#8593;</td></tr>".PHP_EOL."\t</table>".PHP_EOL;
}


$s_botones = "<p style='text-align:right;'>".$s_botones."<input type='button' onclick='javascript:document.location.href = location.protocol+`//`+location.host+location.pathname' value='Recargar' /></p>".PHP_EOL;


$a_interval = date_diff(date_create(@$primer_dia),date_create(@$ultimo_dia));
$diff_time = $a_interval->days;


?><!DOCTYPE HTML>
<html lang="es">
<head>
	<meta charset="utf-8">
	<title>Horario</title>
	<style>
		body{
			font-family: "Trebuchet MS", "Helvetica", "Arial",  "Verdana", "sans-serif";
			font-size: 82.5%;
			color: #555555;
		}
		input{
			font-family: "Trebuchet MS", "Helvetica", "Arial",  "Verdana", "sans-serif";
			font-size: 82.5%;
			cursor:pointer;
		}
		table, th, td {
			padding-left:2px;
			padding-right:2px;
			border-collapse: collapse;
			border:solid 1px black;
			
		}
		.semana{
			background-color:#585858;
			text-align:right;
			color:white;
			font-size: 75%;
		}
		.impar{
			background-color:#F2F2F2;
		}
		th{
			background-color:#585858;
			color:white;
		}
	</style>
</head>
<body>
	
	<?=$s_botones.$s_tabla.PHP_EOL."\t<p>Total: ".$i." d&iacute;as trabajados (de un total de ".$diff_time.")</p>"?>
	
</body>
</html>
