<?php


	include_once dirname(__FILE__)."/MeteoLV.php";
	
	$meteo = new MeteoLV();
	
	$meteo->loadData();
	
	$riga = $meteo->getData("Rīga LU");
	$zoseni = $meteo->getData("Zosēni");
	
	$diff = $riga['params']['temperature'] - $zoseni['params']['temperature'];
	
	echo "Zosēnos ir par ".abs($diff)." grādiem ".(($diff > 0)?" aukstāks ":" siltāks ")."kā Rīgā";
	
	
	//visi dati par visām stacijām
	$alldata = $meteo->getData();