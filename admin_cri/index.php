<!doctype html>
<html lang="fr">
  <head>
    <title>Admin CRI</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../tableau-style.css">
    <style>
      tbody tr > :first-child {
        background-color: white;
      }
    </style>
  </head>
  <body>

  <h1> Administration du bilan logiciel </h1>
  
  <hr />

<?php

// -------------------------------------------------------------------------------------------------------------
// initialisation
// -------------------------------------------------------------------------------------------------------------

require_once("../init.php");

// -------------------------------------------------------------------------------------------------------------
// lecture de la BDD company en l'etat
// -------------------------------------------------------------------------------------------------------------

$requete = "SELECT id, nom, editeur, mecanique, version, cache FROM logiciels ORDER BY nom";
$reponse = $company_db->query($requete);
$logiciels = $reponse->fetchAll();

$requete = "SELECT id, search_text, editeur_text, groupe, version_text FROM logiciels_searchtexts";
$reponse = $company_db->query($requete);
$searchtexts = $reponse->fetchAll();

$requete = "SELECT id, logiciel FROM logiciels_searchgroups";
$reponse = $company_db->query($requete);
$searchgroups = $reponse->fetchAll();

$requete = "SELECT id, nom, search_text FROM salles ORDER BY search_text";
$reponse = $company_db->query($requete);
$salles = $reponse->fetchAll();

$requete = "SELECT id, nom FROM postes_exclus ORDER BY nom";
$reponse = $company_db->query($requete);
$exclusions = $reponse->fetchAll();

// -------------------------------------------------------------------------------------------------------------
// affichage de la BDD avec édition des élements + ajout des groupes & searchtexts + suppression des searchtexts & versions
// -------------------------------------------------------------------------------------------------------------

print '<h2> BDD company </h2>';

// message d'erreur si existe
if (isset($_GET["message"])){
  print '<span style="color:red;">'.$_GET["message"].'</span> <br />';
}

print '<a style="float:right;" href="index.php">Rafraîchir</a>';

print " Contenu actuel (logiciels) :  <br /> ";

print '<form action="submit.php" method="post"> <table>';
// entetes
print "<thead><tr><th> Nom présentable du logiciel </th> <th> Nom présentable de l'éditeur </th> <th> Nom présentable de version </th>";
print "<th> Texte(s) à chercher <br> OU : au moins un doit être présent <br> ET : tous doivent être présents </th> <th> Editeur texte à chercher </th>";
print "<th> Texte(s) de version(s) à chercher </th> <th> Ligne cachée au public </th> <th> Listé sur formulaire de réservation <br /> ('espaces mécaniques') </th> <th> Supprimer le logiciel </th></tr></thead>";

print "<tbody>";

// ligne par logiciel
foreach ($logiciels as $logiciel){
  // identifiants de table + champ + ligne pour le formulaire
  $logiciel_nom_id = "logiciels/nom/".$logiciel[0];
  $logiciel_editeur_id = "logiciels/editeur/".$logiciel[0];
  $logiciel_version_id = "logiciels/version/".$logiciel[0];
  $logiciel_cache_id = "logiciels/cache/".$logiciel[0];
  $logiciel_mecanique_id = "logiciels/mecanique/".$logiciel[0];
  $edit_logiciel_nom_id = "edit/".$logiciel_nom_id;
  $edit_logiciel_editeur_id = "edit/".$logiciel_editeur_id;
  $edit_logiciel_version_id = "edit/".$logiciel_version_id;
  $edit_logiciel_mecanique_id = "edit/".$logiciel_mecanique_id;
  $edit_logiciel_cache_id = "edit/".$logiciel_cache_id;
  $del_logiciel_id = "del/logiciels/".$logiciel[0];
  // colonnes nom / editeur / version
  print '<tr> <td><input type="text" name="'.$logiciel_nom_id.'" value="'.$logiciel[1].'"><button type="submit" name="'.$edit_logiciel_nom_id.'"> Modifier </button></td>';
  print '<td><input type="text" name = "'.$logiciel_editeur_id.'" value="'.$logiciel[2].'"><button type="submit" name="'.$edit_logiciel_editeur_id.'"> Modifier </button></td>';
  print '<td><input type="text" name= "'.$logiciel_version_id.'" value="'.$logiciel[4].'"><button type="submit" name="'.$edit_logiciel_version_id.'"> Modifier </button></td>';
  // colonnes de searchtext (par groupes)
  print "<td><table>";
  $add_searchgroup_id = "add/logiciels_searchgroups/".$logiciel[0];
  $cnt = 0;
  foreach ($searchgroups as $searchgroup){
    $del_searchgroup_id = "del/logiciels_searchgroups/".$searchgroup[0];
    // id de logiciel
    if ($searchgroup[1] == $logiciel[0]){
        print '<tr><td>';
        if ($cnt > 0){
            print '<br>ET<br>';
        }
        // sous-tableau pour chaque searchtext du groupe
        print "<table>";
        $add_searchtext_id = "add/logiciels_searchtexts/".$searchgroup[0];
        $cnt2 = 0;
        foreach ($searchtexts as $searchtext){
            $searchtext_id = "logiciels_searchtexts/search_text/".$searchtext[0];
            $edit_searchtext_id = "edit/".$searchtext_id;
            $del_searchtext_id = "del/logiciels_searchtexts/".$searchtext[0];
            if ($searchtext[3] == $searchgroup[0]){
                print '<tr><td>';
                if ($cnt2 > 0){
                  print "OU";
                }
                print '<input type="text" name= "'.$searchtext_id.'" value="'.$searchtext[1].'"><button type="submit" name="'.$edit_searchtext_id.'"> Modifier </button>';
                if ($cnt2 > 0){
                  print '<button type="submit" name="'.$del_searchtext_id.'"> Supprimer </button>';
                }
                print '</td></tr>';
                $cnt2 += 1;
            }
        }
        print "</table>";
        if ($cnt2 > 0){
            print "OU";
        }
        print '<input type="text" name="addschtxt/logiciels_searchtexts/search_text/'.$searchgroup[0].'" value=""><button type="submit" name="'.$add_searchtext_id.'"> Ajouter </button>';
        if ($cnt > 0){
            print '<button type="submit" name="'.$del_searchgroup_id.'"> Supprimer groupe </button>';
        }
        print '</td></tr>';
        $cnt += 1;
    }
  }
  print "</table>";
  print '<button type="submit" name="'.$add_searchgroup_id.'"> Ajouter groupe (ET) </button>';
  print "</td>";
  // idem pour le searchtext de l'éditeur
  print "<td><table>";
  $cnt = 0;
  foreach ($searchgroups as $searchgroup){
    if ($searchgroup[1] == $logiciel[0]){
        print '<tr><td>';
        if ($cnt > 0){
            print '<br><br>';
        }
        print "<table>";
        foreach ($searchtexts as $searchtext){
            $searchtext_editeur_id = "logiciels_searchtexts/editeur_text/".$searchtext[0];
            $edit_searchtext_editeur_id = "edit/".$searchtext_editeur_id;
            if ($searchtext[3] == $searchgroup[0]){
                print '<tr><td>';
                print '<input type="text" name= "'.$searchtext_editeur_id.'" value="'.$searchtext[2].'"><button type="submit" name="'.$edit_searchtext_editeur_id.'"> Modifier </button>';
                print '</td></tr>';
            }
        }
        print "</table>";
        print '</td></tr>';
        $cnt += 1;
    }
  }
    // idem pour le searchtext de version
    print "</table></td>";
    print "<td><table>";
    $cnt = 0;
    foreach ($searchgroups as $searchgroup){
        if ($searchgroup[1] == $logiciel[0]){
            print '<tr><td>';
            if ($cnt > 0){
                print '<br><br>';
            }
            print "<table>";
            foreach ($searchtexts as $searchtext){
                $searchtext_version_id = "logiciels_searchtexts/version_text/".$searchtext[0];
                $edit_searchtext_version_id = "edit/".$searchtext_version_id;
                if ($searchtext[3] == $searchgroup[0]){
                    print '<tr><td>';
                    print '<input type="text" name= "'.$searchtext_version_id.'" value="'.$searchtext[4].'"><button type="submit" name="'.$edit_searchtext_version_id.'"> Modifier </button>';
                    print '</td></tr>';
                }
            }
            print "</table>";
            print '</td></tr>';
            $cnt += 1;
        }
    }

print "</table></td>";
// ligne cachée au public ou non
  if ($logiciel[5] == 0){
    print '<td><input type="checkbox" name="'.$logiciel_cache_id.'" value="cache"><button type="submit" name="'.$edit_logiciel_cache_id.'"> Modifier </button>';
  }else{
    print '<td><input type="checkbox" name="'.$logiciel_cache_id.'" value="cache" checked><button type="submit" name="'.$edit_logiciel_cache_id.'"> Modifier </button>';
  }

  // listé sur formulaire réservation ou non
  if ($logiciel[3] == 0){
    print '<td><input type="checkbox" name="'.$logiciel_mecanique_id.'" value="mecanique"><button type="submit" name="'.$edit_logiciel_mecanique_id.'"> Modifier </button>';
  }else{
    print '<td><input type="checkbox" name="'.$logiciel_mecanique_id.'" value="mecanique" checked><button type="submit" name="'.$edit_logiciel_mecanique_id.'"> Modifier </button>';
  }

  // suppression
  print '<td><button type="submit" name="'.$del_logiciel_id.'"> Supprimer </button></td></tr>';
}

print "</tbody>";
print "</table></form>";

// -------------------------------------------------------------------------------------------------------------
// pour ajout de logiciel
// -------------------------------------------------------------------------------------------------------------

print "<hr/>  Ajouter un logiciel :  <br /> <br />";

print '<form action="submit.php" method="post"> <table>';
// entetes
print "<thead><tr><th> Nom présentable </th> <th> Nom présentable de l'éditeur </th> <th> Nom présentable de version </th> <th> Texte à chercher </th> <th> Editeur texte à chercher </th>";
print "<th> Texte de version à chercher </th> <th> Ligne cachée au public </th> <th> Listé sur formulaire de réservation <br /> ('espaces mécaniques') </th> <th> </th></tr></thead>";

// ligne d'edition
print "<tbody><tr>";
print '<td><input type="text" name="logiciels/nom"></td>';
print '<td><input type="text" name="logiciels/editeur"></td>';
print '<td><input type="text" name="logiciels/version"></td>';
print '<td><input type="text" name="addsoft/logiciels_searchtexts/search_text"></td>';
print '<td><input type="text" name="addsoft/logiciels_searchtexts/editeur_text"></td>';
print '<td><input type="text" name="addsoft/logiciels_searchtexts/version_text"></td>';
print '<td><input type="checkbox" name="addsoft/logiciels/cache"></td>';
print '<td><input type="checkbox" name="addsoft/logiciels/mecanique"</td>';
print '<td><button type="submit" name="add/logiciels"> Ajouter </button></td>';

print "</tr></tbody>";
print "</table></form>";

// -------------------------------------------------------------------------------------------------------------
// affichage des salles
// -------------------------------------------------------------------------------------------------------------

print "<hr/>  Salles actuelles :  <br /> ";

print '<form action="submit.php" method="post"> <table>';
// entetes
print "<thead><tr><th> Nom présentable de la salle </th> <th> Texte à chercher (X dans M-company-X-...) </th> <th> Supprimer la salle </th></tr></thead>";

print "<tbody>";

// ligne par salle
foreach ($salles as $salle){
  // identifiants de table + champ + ligne pour le formulaire
  $salle_nom_id = "salles/nom/".$salle[0];
  $edit_salle_nom_id = "edit/".$salle_nom_id;
  $salle_searchtext_id = "salles/search_text/".$salle[0];
  $edit_salle_searchtext_id = "edit/".$salle_searchtext_id;
  $del_salle_id = "del/salles/".$salle[0];
  // colonne nom
  print '<tr> <td><input type="text" name="'.$salle_nom_id.'" value="'.$salle[1].'"><button type="submit" name="'.$edit_salle_nom_id.'"> Modifier </button></td>';
  // colonne searchtext
  print '<td><input type="text" name="'.$salle_searchtext_id.'" value="'.$salle[2].'"><button type="submit" name="'.$edit_salle_searchtext_id.'"> Modifier </button></td>';
  // colonne suppression
  print '<td><button type="submit" name="'.$del_salle_id.'"> Supprimer </button></td></tr>';
}

print "</tbody>";
print "</table></form>";

// -------------------------------------------------------------------------------------------------------------
// pour ajout de salle
// -------------------------------------------------------------------------------------------------------------

print "<hr/>  Ajouter une salle :  <br /> <br />";

print '<form action="submit.php" method="post"> <table>';
// entetes
print "<thead><tr><th> Nom présentable </th> <th> Texte à chercher (X dans M-company-X-...) </th> <th> </th></tr></thead>";

// ligne d'edition
print "<tbody><tr>";

print '<td><input type="text" name="addsalle/salles/nom"></td>';
print '<td><input type="text" name="addsalle/salles/search_text"></td>';
print '<td><button type="submit" name="add/salles"> Ajouter </button></td>';

print "</tr></tbody>";
print "</table></form>";

// -------------------------------------------------------------------------------------------------------------
// postes exclus
// -------------------------------------------------------------------------------------------------------------

print "<hr/>  Postes actuellement exclus :  <br /> ";
print "<p> Il s'agit de machines dont le nom corresponds à une salle du tableau et qu'on veut ignorer </p><br />";

print '<form action="submit.php" method="post"> <table>';
// entetes
print "<thead><tr><th> Nom d'ordinateur </th> <th> Supprimer l'exclusion </th></tr></thead>";

print "<tbody>";

// ligne par salle
foreach ($exclusions as $exclu){
  // identifiants de table + champ + ligne pour le formulaire
  $exclu_nom_id = "postes_exclus/nom/".$exclu[0];
  $edit_exclu_nom_id = "edit/".$exclu_nom_id;
  $del_exclu_id = "del/postes_exclus/".$exclu[0];
  // colonne nom
  print '<tr> <td><input type="text" name="'.$exclu_nom_id.'" value="'.$exclu[1].'"><button type="submit" name="'.$edit_exclu_nom_id.'"> Modifier </button></td>';
  // colonne suppression
  print '<td><button type="submit" name="'.$del_exclu_id.'"> Supprimer </button></td></tr>';
}

print "</tbody>";
print "</table></form>";

// -------------------------------------------------------------------------------------------------------------
// pour ajout d'exclusion
// -------------------------------------------------------------------------------------------------------------

print "<hr/>  Ajouter une exclusion :  <br /> <br />";

print '<form action="submit.php" method="post"> <table>';
// entetes
print "<thead><tr><th> Nom d'ordinateur </th> <th> </th></tr></thead>";

// ligne d'edition
print "<tbody><tr>";

print '<td><input type="text" name="addexclu/postes_exclus/nom"></td>';
print '<td><button type="submit" name="add/postes_exclus"> Ajouter </button></td>';

print "</tr></tbody>";
print "</table></form>";

// -------------------------------------------------------------------------------------------------------------
// affichage restant
// -------------------------------------------------------------------------------------------------------------

print "<hr/> <h2> Voir le bilan logiciel : </h2> ";
print '<a href="../bilan_logiciel_company/index.php" target="_blank" rel="noopener noreferrer"> Page publique (essentiel) </a> <br /> <br />';
print '<a href="../bilan_cri.php" target="_blank" rel="noopener noreferrer"> Page pour le CRI (plus d'."'infos) </a> ";

?>
   </body>
</html>
