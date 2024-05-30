<?php

require_once("init.php");

@session_start();

// connexion obligatoire par défaut
if (!defined('NO_LOGIN')) {
	require_once("restrict.php");
}