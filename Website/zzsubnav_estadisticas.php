<h1><?php echo _('Statistik wählen'); ?></h1>
<form action="/redireccion_estadisticas.php" method="post" accept-charset="utf-8">
<p><select name="stat" size="1" style="width:200px">
<?php
$statistiken = array();
$statistiken[] = array('clasificacion5anos', _('5-Jahres-Wertung'));
$statistiken[] = array('campeonesCopaInt', _('Cup-Sieger'));
$statistiken[] = array('masPartidos', _('Dauerbrenner'));
$statistiken[] = array('historialResultados', _('Ergebnisverlauf'));
$statistiken[] = array('historia', _('Geschichte'));
$statistiken[] = array('cambioLiga', _('Getauschte Ligen'));
$statistiken[] = array('tablaGlobal', _('Globale Tabelle'));
$statistiken[] = array('plantillasMasJovenes', _('Jüngste Kader'));
$statistiken[] = array('masTitulos', _('Meiste Titel'));
$statistiken[] = array('masEspectadores', _('Meiste Zuschauer'));
$statistiken[] = array('campeones', _('Meister'));
$statistiken[] = array('campeonesCopa', _('Pokal-Sieger'));
$statistiken[] = array('ligasMasRicas', _('Reichste Ligen'));
$statistiken[] = array('evolucionTemporada', _('Saisonverlauf'));
$statistiken[] = array('ligasMasFuertesA', _('Stärkste Ligen - Aufstellung'));
$statistiken[] = array('ligasMasFuertesK', _('Stärkste Ligen - Kader'));
$statistiken[] = array('ligasMasFuertesR', _('Stärkste Ligen - RKP'));
$statistiken[] = array('resumenTransferencias', _('Teuerste Transfers'));
$statistiken[] = array('goleadores', _('Torjägerliste'));
$statistiken[] = array('jugadoresMasFieles', _('Treueste Spieler'));
$statistiken[] = array('valorEconomico', _('Wert des Geldes'));
$statistiken[] = array('jugadoresMasValiosos', _('Wertvollste Spieler'));
$statistiken[] = array('equiposMasValiosos', _('Wertvollste Teams'));
$statistiken[] = array('evolucionEspectadores', _('Zuschauerverlauf'));
foreach ($statistiken as $statistik) {
	echo '<option value="'.$statistik[0].'"';
	if ($_SERVER['SCRIPT_NAME'] == '/est_'.$statistik[0].'.php') { echo ' selected="selected"'; }
	echo '>'.$statistik[1].'</option>';
}
?>
</select> <input type="submit" value="<?php echo _('Auswählen'); ?>" /></p>
</form>
