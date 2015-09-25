<?php
/**************************
 * Control de Horario
 * https://github.com/MdeMoUcH/horario.git
 * Desarrollado por MdeMoUcH
 * mdemouch@gmail.com
 * http://www.lagranm.com/
 **************************/
 
/*** Para la base de datos: ***/
/* CREATE TABLE `registro` (`id` int(11) NOT NULL AUTO_INCREMENT, `dia` varchar(15) NOT NULL, `ip` varchar(15) NOT NULL, `entrada` varchar(8) NOT NULL, `comida` varchar(8) NOT NULL, `vuelta` varchar(8) NOT NULL, `salida` varchar(8) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8; */

/*** Configuración: ***/
$horas_dia = 8;
$minutos_dia = 30;
$minutos_comida = 30;
$horas_viernes = 6;
$minutos_comida_viernes = 0;

$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'sinpass';
$db_name = 'test_time';
/**********************/


$ip = $_SERVER['REMOTE_ADDR'];
$dia = date('Y-m-d');
$ahora = date('H:i:s');

mysql_connect($db_host,$db_user,$db_pass);
mysql_select_db($db_name);
$s_botones = '';

if($resultado = mysql_query("SELECT * FROM registro WHERE dia = '".$dia."' AND ip = '".$ip."';")){
	if($fila = mysql_fetch_assoc($resultado)){
		if($fila["comida"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?comida\"' value='Comida'/>";
		}elseif($fila["vuelta"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?vuelta\"' value='Vuelta'/>";
		}else{
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?salida\"' value='Salida'/>";
		}
		
		if(isset($_GET["comida"]) && $fila["comida"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?vuelta\"' value='Vuelta'/>";
			mysql_query("UPDATE registro SET comida = '".$ahora."' WHERE dia = '".$dia."' AND ip = '".$ip."';");
		}
		if(isset($_GET["vuelta"]) && $fila["vuelta"] == ""){
			$s_botones = "<input type='button' onclick='javascript:window.location=\"?salida\"' value='Salida'/>";
			mysql_query("UPDATE registro SET vuelta = '".$ahora."' WHERE dia = '".$dia."' AND ip = '".$ip."';");
		}
		if(isset($_GET["salida"])){
			mysql_query("UPDATE registro SET salida = '".$ahora."' WHERE dia = '".$dia."' AND ip = '".$ip."';");
		}
	}else{
		mysql_query("INSERT INTO registro (dia,ip,entrada) VALUES ('".$dia."','".$ip."','".$ahora."');");
	}
}else{
	die('Consulta no v&aacute;lida: ' . mysql_error());
}

$s_tabla = "<center>No hay datos</center>";

if($resultado = mysql_query("SELECT * FROM registro ORDER BY dia DESC;")){
	$s_tabla = "<table border='1' width='100%'><tr><td>D&iacute;a</td><td>IP</td><td>Entrada</td><td>Comida</td><td>Salida</td><td>Horas</td></tr>";
	$i = 0;
	$lastday = 8;
	$b_show_separador = false;
	$tiempo_semana = array('horas'=>0,'minutos'=>0);
	while($fila = mysql_fetch_assoc($resultado)){
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
				$horaestimada = strtotime('+'.$minutos_comida.' minute', $horaestimada);
			}else{
				$horaestimada = strtotime('+'.$comida_m.' minute', $horaestimada);
			}
			$s_estimated = "<small>(&#126;".date("H:i:s",$horaestimada).")</small>";
		}
		
		if(isset($comida_s)){
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
			$s_tabla .= "<tr><td colspan='6' style='background-color:#585858;text-align:right'>&#8593;".$tiempo_semana['horas']."h ".$tiempo_semana['minutos']."min&#8593;</td></tr>";
			$tiempo_semana = array('horas'=>0,'minutos'=>0);
		}
		
		$tiempo_semana['horas'] += $a_total[0];
		$tiempo_semana['minutos'] += $min;
		if($tiempo_semana['minutos'] >= 60){
			$tiempo_semana['minutos'] = $tiempo_semana['minutos'] - 60;
			$tiempo_semana['horas'] = $tiempo_semana['horas'] + 1;
		}
		
		if($fila["dia"] == $dia){
			$fila["dia"] = "<b>".$fila["dia"]."</b>";
		}
		
		if(($i%2) == 0){
			$background = "";
		}else{
			$background = " style='background-color:#F2F2F2;'";
		}
		$i++;
		
		
		$s_tabla .= "<tr".$background."><td>".$fila["dia"]." (".$s_dia.")</td><td>".$fila["ip"]."</td><td>".$fila["entrada"]."</td><td>".$fila["comida"]." - ".$fila["vuelta"]." ".$s_comida."</td><td>".$fila["salida"]."</td><td>".$s_total." ".$s_estimated."</td></tr>";
		
		if($s_dia == "V"){
			if($fila["salida"] == ""){
				$horaestimada = strtotime('+'.$horas_viernes.' hour', strtotime($fila["entrada"]));
				if($fila["vuelta"] == ""){
					$horaestimada = strtotime('+'.$minutos_comida_viernes.' minute', $horaestimada);
				}else{
					$horaestimada = strtotime('+'.$comida_m.' minute', $horaestimada);
				}
				$s_estimated = "<small>(&#126;".date("H:i:s",$horaestimada).")</small>";
			}
		}
		$lastday = $i_dia;
	}
	$s_tabla .= "</table>";
}

$s_botones = "<p style='text-align:right;'><input type='button' onclick='javascript:document.location.href = document.location.href;' value='Recargar' />".$s_botones."</p>";

echo $s_botones.$s_tabla."<br/><small>Total: ".$i." d&iacute;as</small>";


