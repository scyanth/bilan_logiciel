<?php

// gestion des erreurs
//error_reporting(E_ALL);
//ini_set("display_errors",1);

// chargement des librairies
require "vendor/autoload.php";
use vlucas\phpdotenv;

// chargement des variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// connexion a la BDD company
try {
  $company_db = new PDO('mysql:host='.$_ENV['company_DB_HOST'].';dbname='.$_ENV['company_DB_NAME'],$_ENV['company_DB_LOGIN'],$_ENV['company_DB_PASSWORD']);
}catch (PDOException $e){
  print "Erreur de connexion a la BDD company : <br/> ". $e->getMessage(). "<br/>";
}
$company_db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);

// connexion a la BDD de GLPI
try {
  $glpi_db = new PDO('mysql:host='.$_ENV['GLPI_DB_HOST'].';dbname='.$_ENV['GLPI_DB_NAME'],$_ENV['GLPI_DB_LOGIN'],$_ENV['GLPI_DB_PASSWORD']);
}catch (PDOException $e){
  print "Erreur de connexion a la BDD GLPI : <br/> ". $e->getMessage(). "<br/>";
}

// URL de base de l'API de GLPI
$api_url_base = "http://".$_ENV['GLPI_API_HOST']."/glpi/apirest.php/";

// connexion a l'API (initialisation de session)
$initsession_url = $api_url_base."initSession";
$curl_initsession = curl_init($initsession_url);
$base64_creds = base64_encode($_ENV['GLPI_API_LOGIN'].":".$_ENV['GLPI_API_PASSWORD']);
curl_setopt($curl_initsession, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_initsession, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic '.$base64_creds,
]);
$reponse = curl_exec($curl_initsession);
curl_close($curl_initsession);
$obj_rep = json_decode($reponse);
$session_token = $obj_rep->session_token;