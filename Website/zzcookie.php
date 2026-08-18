<?php
@session_set_cookie_params(3600 * 24 * 14, '/', str_replace('www.', '.', CONFIG_SITE_DOMAIN), false, true);
@session_start();
if (isset($_SESSION['loggedin']) AND $_SESSION['loggedin'] == 1) {
	$loggedin = $_SESSION['loggedin'];
	$cookie_id = $_SESSION['userid'];
	$cookie_username = $_SESSION['username'];
	$cookie_liga = $_SESSION['liga'];
	$cookie_team = $_SESSION['team'];
	$cookie_teamname = $_SESSION['teamname'];
	$cookie_scout = $_SESSION['scout'];
	if (mt_rand(1, 3) == 2) { // nur bei jedem dritten Seitenaufruf
		$last_login1 = "UPDATE ".$prefix."users SET verwarnt = 0, last_login = ".time();
		$last_login1 .= ", last_ip = '".getUserIP()."'";
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$last_login1 .= ", last_uagent = '".mysql_real_escape_string(trim(strip_tags($_SERVER['HTTP_USER_AGENT'])))."'";
		}
		if (isset($_COOKIE['uniqueHash'])) {
			$last_login1 .= ", last_uniqueHash = '".mysql_real_escape_string(trim(strip_tags($_COOKIE['uniqueHash'])))."'";
		}
		$last_login1 .= " WHERE ids = '".$cookie_id."'";
		$last_login2 = mysql_query($last_login1);
	}
}
else {
	$loggedin = 0;
	$cookie_id = '';
	$cookie_username = '';
	$cookie_liga = '';
	$cookie_team = '';
	$cookie_teamname = '';
	$cookie_scout = 0;
}
if ($loggedin == 1) {
	$ohneRegeln = array('/index.php', 
						'/aviso_legal.php',
						'/recuperar_contrasena.php',
						'/reglas.php',
						'/tour.php', 
						'/registrarse.php',
						'/registro.php',
						'/notas.php',
						'/configuracion.php',
						'/foro.php',
						'/foro_tema.php',
						'/correo.php',
						'/bandeja_entrada.php',
						'/bandeja_salida.php',
						'/consejos_del_dia.php',
						'/amigos.php',
						'/support.php',
						'/supportRequest.php', 
						'/login.php', 
						'/logout.php');
	if (!isset($_SESSION['acceptedRules'])) { $_SESSION['acceptedRules'] = 0; }
	if (!in_array($_SERVER['SCRIPT_NAME'], $ohneRegeln) && $_SESSION['acceptedRules'] == 0) {
		header('Location: /reglas.php');
		exit;
	}
	if ($cookie_team == '__'.$cookie_id) {
		$publicPages = array('/index.php', 
							'/aviso_legal.php',
							'/recuperar_contrasena.php',
							'/reglas.php',
							'/tour.php', 
							'/registrarse.php',
							'/registro.php',
							'/quien_esta_en_linea.php',
							'/manager.php', 
							'/notas.php',
							'/configuracion.php',
							'/lig_tabla.php',
							'/mercado_fichajes.php',
							'/cesion_mercado.php',
							'/lig_transferencias.php',
							'/copa.php',
							'/copa_int.php',
							'/foro.php',
							'/foro_tema.php',
							'/chat.php', 
							'/motor_chat.php',
							'/equipo.php',
							'/subasta_mercado.php',
							'/jugador.php',
							'/informe_partido.php',
							'/historial_jugador.php',
							'/mejores_managers.php',
							'/accion_amigos.php',
							'/escribir_correo.php',
							'/post_geschrieben.php', 
							'/correo.php',
							'/forum_eintrag_hinzufuegen.php', 
							'/forum_eintrag_hinzugefuegt.php', 
							'/bandeja_entrada.php',
							'/bandeja_salida.php',
							'/consejos_del_dia.php',
							'/amigos.php',
							'/support.php',
							'/supportRequest.php', 
							'/supportAdd.php', 
							'/login.php', 
							'/logout.php');
		if (!in_array($_SERVER['SCRIPT_NAME'], $publicPages) && substr($_SERVER['SCRIPT_NAME'], 0, 3) != '/aa') {
			header('Location: /index.php');
			exit;
		}
	}
	if ($_SESSION['multiSperre'] == 1 && $_SERVER['SCRIPT_NAME'] != '/bloqueoMulti.php' && $_SERVER['SCRIPT_NAME'] != '/aviso_legal.php' && $_SERVER['SCRIPT_NAME'] != '/reglas.php' && $_SERVER['SCRIPT_NAME'] != '/logout.php' && $_SERVER['SCRIPT_NAME'] != '/configuracion.php') {
		header('Location: /bloqueoMulti.php');
		exit;
	}
}
?>