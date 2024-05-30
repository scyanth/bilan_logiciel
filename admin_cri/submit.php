<?php

// -------------------------------------------------------------------------------------------------------------
// initialisation
// -------------------------------------------------------------------------------------------------------------

require_once("../init.php");

// -------------------------------------------------------------------------------------------------------------
// contrôle du formulaire
// -------------------------------------------------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST as $nom => $valeur) {
        // escapade
        $nom = htmlspecialchars($nom);
        // decoupage du nom avec le separateur /
        $nom_parts = explode("/",$nom);
        // edition d'elements
        if ($nom_parts[0] == "edit"){
            $table = $nom_parts[1];
            $champ = $nom_parts[2];
            $ligne = $nom_parts[3];
            $input_nom = $table."/".$champ."/".$ligne;
            if ($champ == "cache" || $champ == "mecanique"){
                // pour checkboxes : valeur de formulaire présente uniquement si cochée
                if (isset($_POST[$input_nom])){
                    $input_valeur = 1;
                }else{
                    $input_valeur = 0;
                }
            }else{
                $input_valeur = htmlspecialchars($_POST[$input_nom]);
                # exception pour editeur_text : peut etre vide
                if ($champ != "editeur_text"){
                    if ($input_valeur == ""){
                        $message = "Erreur : le champ ne peut pas être vide !";
                        affiche_message($message);
                        exit();
                    }
                }
            }
            edit_element($table,$champ,$ligne,$input_valeur,$company_db);
            header("Location:index.php");
            exit();
        }
        // suppression d'elements
        if ($nom_parts[0] == "del"){
            $table = $nom_parts[1];
            $ligne = $nom_parts[2];
            del_element($table,$ligne,$company_db);
        }
        // ajout d'elements
        if ($nom_parts[0] == "add"){
            $table = $nom_parts[1];
            if ($table == "logiciels"){
                $nom =  htmlspecialchars($_POST["logiciels/nom"]);
                $editeur =  htmlspecialchars($_POST["logiciels/editeur"]);
                $version_nom =  htmlspecialchars($_POST["logiciels/version"]);
                $searchtext =  htmlspecialchars($_POST["addsoft/logiciels_searchtexts/search_text"]);
                $editeur_text = htmlspecialchars($_POST["addsoft/logiciels_searchtexts/editeur_text"]);
                $version_searchtext =  htmlspecialchars($_POST["addsoft/logiciels_searchtexts/version_text"]);
                if (isset($_POST["addsoft/versions/cache"])){
                    $version_cache = 1;
                }else{
                    $version_cache = 0;
                }
                if (isset($_POST["addsoft/logiciels/mecanique"])){
                    $mecanique = 1;
                }else{
                    $mecanique = 0;
                }
                # exception pour editeur_text : peut etre vide
                if (($nom == "") || ($editeur == "") || ($searchtext == "") || ($version_nom == "") || ($version_searchtext == "")){
                    $message = "Erreur : veuillez remplir tous les champs !";
                    affiche_message($message);
                    exit();
                }else{
                    add_logiciel($nom,$editeur,$searchtext,$editeur_text,$version_nom,$version_searchtext,$version_cache,$mecanique,$company_db);
                }
            }
            if ($table == "logiciels_searchgroups"){
                $logiciel = $nom_parts[2];
                add_searchgroup($logiciel,$company_db);
            }
            if ($table == "logiciels_searchtexts"){
                $groupe = $nom_parts[2];
                $searchtext = htmlspecialchars($_POST["addschtxt/logiciels_searchtexts/search_text/".$groupe]);
                if ($searchtext == ""){
                    $message = "Erreur : veuillez remplir le champ !";
                    affiche_message($message);
                    exit();
                }else{
                    add_searchtext($groupe,$searchtext,$company_db);
                }
            }
            if ($table == "salles"){
                $nom = htmlspecialchars($_POST["addsalle/salles/nom"]);
                $searchtext = htmlspecialchars($_POST["addsalle/salles/search_text"]);
                if (($nom == "") || ($searchtext == "")){
                    $message = "Erreur : veuillez remplir tous les champs !";
                    affiche_message($message);
                    exit();
                }else{
                    add_salle($nom,$searchtext,$company_db);
                }
            }
            if ($table == "postes_exclus"){
                $nom = htmlspecialchars(($_POST["addexclu/postes_exclus/nom"]));
                if ($nom == ""){
                    $message = "Erreur : veuillez remplir tous les champs !";
                    affiche_message($message);
                    exit();
                }else{
                    add_exclu($nom,$company_db);
                }
            }
        }
    }
}else{
    header("Location:index.php");
    exit();
}

// -------------------------------------------------------------------------------------------------------------
// fonctions pour alimenter la BDD
// -------------------------------------------------------------------------------------------------------------

function edit_element($table,$champ,$ligne,$valeur,$company_db){
    $requete_preparee = $company_db->prepare("UPDATE $table SET $champ = :valeur WHERE id = $ligne");
    $requete_preparee->bindValue(":valeur",$valeur);
    try {
        $reponse = $requete_preparee->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    if (($reponse < 1) || ($reponse > 1)){
        $message = "Problème SQL : nombre de lignes modifiées incohérent (".$reponse.")";
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

function del_element($table,$ligne,$company_db){
    // si c'est un logiciel => il faut supprimer d'abord tous ses groupes et search_texts (contraintes de clés étrangères)
    if ($table == "logiciels"){
        $requete1 = "DELETE FROM logiciels_searchtexts WHERE logiciels_searchtexts.groupe IN (SELECT logiciels_searchgroups.id FROM logiciels_searchgroups WHERE logiciels_searchgroups.logiciel = $ligne)";
        $requete2 = "DELETE FROM logiciels_searchgroups WHERE logiciel = $ligne";
        try {
            $reponse1 = $company_db->exec($requete1);
            $reponse2 = $company_db->exec($requete2);
        }catch (PDOException $e){
            $message = "Erreur SQL : ".$e;
            affiche_message($message);
            exit();
        }
    }
    // si c'est un groupe => il faut supprimer d'abord tous ses search_texts
    if ($table == "logiciels_searchgroups"){
        $requete1 = "DELETE FROM logiciels_searchtexts WHERE groupe = $ligne";
        try {
            $reponse1 = $company_db->exec($requete1);
        }catch (PDOException $e){
            $message = "Erreur SQL : ".$e;
            affiche_message($message);
            exit();
        }
    }
    $requete = "DELETE FROM $table WHERE id = $ligne";
    try {
        $reponse = $company_db->exec($requete);
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

function add_logiciel($nom,$editeur,$searchtext,$editeur_text,$version_nom,$version_searchtext,$version_cache,$mecanique,$company_db){
    // insertion dans la table logiciels
    $requete_preparee = $company_db->prepare("INSERT INTO logiciels (nom, editeur, mecanique, version, cache) VALUES (:nom, :editeur, :mecanique, :version, :cache)");
    $requete_preparee->bindValue(":nom",$nom);
    $requete_preparee->bindValue(":editeur",$editeur);
    $requete_preparee->bindValue(":mecanique",$mecanique);
    $requete_preparee->bindValue(":version",$version_nom);
    $requete_preparee->bindValue(":cache",$version_cache);
    try {
        $reponse = $requete_preparee->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    // récuperation de l'id de la ligne insérée (logiciel)
    $requete_id = "SELECT LAST_INSERT_ID()";
    $reponse_id = $company_db->query($requete_id);
    $softid = $reponse_id->fetch();
    // insertion dans la table logiciels_searchgroups
    $requete = "INSERT INTO logiciels_searchgroups (logiciel) VALUES ($softid[0])";
    $reponse = $company_db->exec($requete);
    // récuperation de l'id de la ligne insérée (groupe)
    $requete_g = "SELECT LAST_INSERT_ID()";
    $reponse_g = $company_db->query($requete_g);
    $groupe_id = $reponse_g->fetch();
    // insertions dans la tables logiciels_searchtexts
    $requete_preparee2 = $company_db->prepare("INSERT INTO logiciels_searchtexts (search_text, editeur_text, groupe, version_text) VALUES (:searchtext,:editeur_text,$groupe_id[0],:version_text)");
    $requete_preparee2->bindValue(":searchtext",$searchtext);
    $requete_preparee2->bindValue(":editeur_text",$editeur_text);
    $requete_preparee2->bindValue(":version_text",$version_searchtext);
    try {
        $reponse2 = $requete_preparee2->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

function add_searchgroup($logiciel,$company_db){
    $requete_preparee = $company_db->prepare("INSERT INTO logiciels_searchgroups (logiciel) VALUES (:logiciel)");
    $requete_preparee->bindValue(":logiciel",$logiciel);
    try {
        $reponse = $requete_preparee->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

function add_searchtext($groupe,$searchtext,$company_db){
    // par defaut on prends l'editeur et la version d'un searchtext preexistant pour l'ensemble des groupes du logiciel
    $requete = "SELECT logiciels_searchtexts.editeur_text, logiciels_searchtexts.version_text FROM logiciels_searchtexts WHERE logiciels_searchtexts.groupe IN (SELECT logiciels_searchgroups.id FROM logiciels_searchgroups WHERE logiciels_searchgroups.logiciel IN (SELECT logiciels_searchgroups.logiciel FROM logiciels_searchgroups WHERE logiciels_searchgroups.id = $groupe))";
    $reponse = $company_db->query($requete);
    $infos= $reponse->fetch();
    // ajout de la ligne
    $requete_preparee = $company_db->prepare("INSERT INTO logiciels_searchtexts (search_text, editeur_text, groupe, version_text) VALUES (:searchtext,'$infos[0]',:groupe,'$infos[1]')");
    $requete_preparee->bindValue(":searchtext",$searchtext);
    $requete_preparee->bindValue(":groupe",$groupe);
    try {
        $reponse = $requete_preparee->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

function add_salle($nom,$searchtext,$company_db){
    $requete_preparee = $company_db->prepare("INSERT INTO salles (nom, search_text) VALUES (:nom, :searchtext)");
    $requete_preparee->bindValue(":nom",$nom);
    $requete_preparee->bindValue(":searchtext",$searchtext);
    try {
        $reponse = $requete_preparee->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

function add_exclu($nom,$company_db){
    $requete_preparee = $company_db->prepare("INSERT INTO postes_exclus (nom) VALUES (:nom)");
    $requete_preparee->bindValue(":nom",$nom);
    try {
        $reponse = $requete_preparee->execute();
    }catch (PDOException $e){
        $message = "Erreur SQL : ".$e;
        affiche_message($message);
        exit();
    }
    header("Location:index.php");
    exit();
}

// -------------------------------------------------------------------------------------------------------------
// fonction pour afficher un message d'erreur
// -------------------------------------------------------------------------------------------------------------

function affiche_message($message){
    print '<!doctype html>
    <html lang="fr">
      <head>
        <title>Admin CRI - Erreur</title>
        <meta charset="utf-8">
        <style>
          #cont {
            font-family: Arial, Tahoma, Verdana;
            color: red;
          }   
        </style>
      </head>
      <body> <div id="cont" <h3>'.$message.'</h3><br />
      <a href="index.php">Retour</a>
    </div></body></html>';
}