<!doctype html>
<html lang="fr">
  <head>
    <title>Bilan Logiciel company (version CRI)</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="tableau-style.css">
    <style>
        .infobulle {
            position: relative;
        }
        .infobulle div {
            display: none;
        }
        .infobulle:hover {
            z-index: 500;
        }
        .infobulle:hover div {
            display:block;
            position: absolute;
            border: 1px solid black;
            background: white;
        }
    </style>
  </head>
  <body>

  <h1> Bilan logiciel pour le CRI </h1>
  <p> Le tableau contient le nombre de postes par salle ou le logiciel est installé. </p>

<?php

// -------------------------------------------------------------------------------------------------------------
// initialisation
// -------------------------------------------------------------------------------------------------------------

require_once("init.php");

// -------------------------------------------------------------------------------------------------------------
// préparation des données
// -------------------------------------------------------------------------------------------------------------

require_once("tableau-prepare.php");

// -------------------------------------------------------------------------------------------------------------
// affichage
// -------------------------------------------------------------------------------------------------------------

print "<table>";

// ligne des entetes
print "<thead>";
print '<tr style="background-color:#D3D3D3;">';
print '<th> Logiciel </th> <th colspan="2"> Salles <br /><hr /> Total de postes </th> ';
foreach ($toutes_salles as $salle){
  $ordis_doublons = ordis_doublons($touspostes_par_salle[$salle[1]]);
  if (sizeof($ordis_doublons) < 1){
    print "<th>".$salle[0]."<br /><hr />".$nbpostes_par_salle[$salle[1]]."</th>";
  }else{
    print '<th style="color:red;" class="infobulle">'.$salle[0]."<br /><hr />".$nbpostes_par_salle[$salle[1]]."*<div>";
    print "Doublons à supprimer : <br/>";
    foreach ($ordis_doublons as $ordi){
      print $ordi."<br/>";
    }
    print "</div></th>";
  }
}
print "</tr>";

print "</thead>";
print "<tbody>";

// seconde ligne d'entetes
print '<tr style="background-color:#D3D3D3;">';
print '<td>  </td> <td class="important"> Editeur </td> <td class="important"> Version </td>';
foreach ($toutes_salles as $salle){
  print "<td></td>";
}
print "</tr>";

// autres lignes
foreach ($infos_par_logiciel as $id => $infos){
  // extraction des infos pour entetes
  $infos_ar = explode(";",$infos);
  $logiciel = $infos_ar[0];
  $editeur = $infos_ar[1];
  $version = $infos_ar[2];

  print "<tr>";

  // cellule d'entete du logiciel
  print '<td class="important">'.$logiciel."</td>";

  // cellule d'entete de l'editeur
  print "<td>".$editeur."</td>";

  // cellule d'entete de version
  print "<td>".$version."</td>";

  // liste et nb ordis par salle
  $liste_ordis_par_salle = $tableau_listes_ordis[$id];
  $nb_ordis_par_salle = $tableau_nb_ordis[$id];

  foreach ($toutes_salles as $salle){
    if (isset($nb_ordis_par_salle[$salle[1]])){
        $nb_ordis = $nb_ordis_par_salle[$salle[1]];
        $liste_ordis = $liste_ordis_par_salle[$salle[1]];
        asort($liste_ordis);
        // si nb ordis insuffisant
        if ($nb_ordis < $nbpostes_par_salle[$salle[1]]){
            print '<td style="background-color:orange;" class="infobulle">'.$nb_ordis.'<div>';
            $ordis_manquants = ordis_manquants($liste_ordis,$touspostes_par_salle[$salle[1]]);
            if (sizeof($ordis_manquants) < 1){
              print "Postes manquants introuvables, vérifier doublons. <br />";
            }else{
              print "Postes manquants : <br/>";
              foreach ($ordis_manquants as $ordi){
                print $ordi."<br/>";
              }
            }
            print '</div></td>';
        }else{
            print "<td>".$nb_ordis."</td>";
        }
    }else{
        // si aucun ordi
        print "<td> </td>";
    }
  }

  print "</tr>";
}

print "</tbody>";
print "</table>";

// -------------------------------------------------------------------------------------------------------------
// fonctions
// -------------------------------------------------------------------------------------------------------------

// fonction pour obtenir les ordis manquants d'une salle
function ordis_manquants($liste_ordis,$touspostes){
  $ordis_manquants = array();
  foreach ($touspostes as $poste){
    if (!(in_array($poste[0],$liste_ordis))){
      array_push($ordis_manquants,$poste[0]);
    }
  }
  return $ordis_manquants;
}

// fonction pour obtenir les ordis en doublons
function ordis_doublons($touspostes){
  $ordis_doublons = array();
  $touspostes_raw = array();
  foreach ($touspostes as $poste){
    array_push($touspostes_raw,$poste[0]);
  }
  $ordis_comptes = array_count_values($touspostes_raw);
  foreach ($ordis_comptes as $ordi=>$nb){
    if ($nb > 1){
      array_push($ordis_doublons,$ordi);
    }
  }
  return $ordis_doublons;
}

?>

   </body>
</html>
