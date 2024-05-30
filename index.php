<?php

// -------------------------------------------------------------------------------------------------------------
// initialisation
// -------------------------------------------------------------------------------------------------------------

$page = "index";

require_once("init_session.php");

?>

<!doctype html>
<html lang="fr">
  <head>
    <title>Bilan Logiciel company</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="tableau-style.css">
  </head>
  <body>

<?php

// -------------------------------------------------------------------------------------------------------------
// préparation des données
// -------------------------------------------------------------------------------------------------------------

require_once("tableau-prepare.php");

// -------------------------------------------------------------------------------------------------------------
// affichage
// -------------------------------------------------------------------------------------------------------------

print '<h1> Bilan logiciel company </h1>';

// lien de déconnexion
print '<a style="float:right;font-size:20px;" href="restrict.php?logout=1&origin=index">Déconnexion</a>';

print "<table>";

// ligne des entetes
print "<thead>";
print '<tr style="background-color:#D3D3D3;">';
print '<th> Logiciel </th> <th colspan="2"> Salles <br /><hr /> Total de postes </th> ';
foreach ($toutes_salles as $salle){
    print "<th>".$salle[0]."<br /><hr />".$nbpostes_par_salle[$salle[1]]."</th>";
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
  $cache = $infos_ar[3];

    // on affiche uniquement si 'caché' = 0
    if ($cache == 0){

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
                    print '<td></td>';
                }else{
                    print '<td style="background-color:green;"></td>';
                }
            }else{
                // si aucun ordi
                print "<td></td>";
            }
        }
        print "</tr>";
    }
}

print "</tbody>";
print "</table>";

?>

<script>

if (window.top !== window.self){
  stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.type = "text/css";
  stylesheet.href = "iframe_style.css";
  document.head.appendChild(stylesheet);
}
</script>

   </body>

</html>
