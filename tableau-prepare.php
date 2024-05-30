<?php

// -------------------------------------------------------------------------------------------------------------
// recuperation des infos de base
// -------------------------------------------------------------------------------------------------------------

// toutes les salles
$requete = "SELECT nom, search_text FROM salles ORDER BY search_text";
$reponse = $company_db->query($requete);
$toutes_salles = $reponse->fetchAll();

// arrays utilitaires
$salles_noms = array();
$salles_searchtexts = array();
$i = 0;
foreach ($toutes_salles as $salle){
  $toutes_salles[$i][1] = strtolower($salle[1]);
  $toutes_salles[$i]["search_text"] = strtolower($salle[1]);
  array_push($salles_noms,$salle[0]);
  // array associatif nom => search_text
  $salles_searchtexts[$salle[0]] = strtolower($salle[1]);
  $i++;
}

// exclusions pour le listage/comptage de postes
$requete = "SELECT nom FROM postes_exclus";
$reponse = $company_db->query($requete);
$exclusions = $reponse->fetchAll();

// listage et comptage des postes par salle
$nbpostes_par_salle = array();
$touspostes_par_salle = array();
foreach ($toutes_salles as $salle){
  $requete = "SELECT name FROM glpi_computers WHERE name LIKE 'M-company-$salle[1]%' AND is_deleted = 0";
  $reponse = $glpi_db->query($requete);
  $touspostes = $reponse->fetchAll();
  // suppression des postes exclus
  $i = 0;
  foreach ($touspostes as $poste){
    $touspostes[$i][0] = strtolower($poste[0]);
    $touspostes[$i]["name"] = strtolower($poste[0]);
    foreach ($exclusions as $exclu){
      if ($poste[0] == $exclu[0]){
        unset($touspostes[array_search($poste[0],$touspostes)]);
      }
    }
    $i++;
  }

  // comptage
  $nbpostes = sizeof($touspostes);
  // array associatif nom salle => liste de machines
  $touspostes_par_salle[$salle[1]] = $touspostes;
  // array associatif nom salle => nombre de machines
  $nbpostes_par_salle[$salle[1]] = $nbpostes;
}

// tous les logiciels
$requete = "SELECT id, nom, editeur, mecanique, version, cache FROM logiciels ORDER BY nom";
$reponse = $company_db->query($requete);
$tous_logiciels = $reponse->fetchAll();

// arrays utilitaires
$logiciels_noms = array();
$logiciels_editeurs = array();
foreach ($tous_logiciels as $logiciel){
  array_push($logiciels_noms,$logiciel[1]);
  // array associatif nom => editeur
  $logiciels_editeurs[$logiciel[1]] = $logiciel[2];
}

// array "template" pour chaque ligne du tableau (prerempli avec les noms des salles)
$ligne_base = array_fill_keys($salles_searchtexts,0);

// -------------------------------------------------------------------------------------------------------------
// construction du tableau logiciels / salles
// -------------------------------------------------------------------------------------------------------------

$tableau_nb_ordis = array();
$tableau_listes_ordis = array();

// boucle par logiciel
foreach ($tous_logiciels as $logiciel){

    // groupes de searchtexts par logiciel
    $requete = "SELECT id FROM logiciels_searchgroups WHERE logiciel = $logiciel[0]";
    $reponse = $company_db->query($requete);
    $searchgroups = $reponse->fetchAll();

    $ordis_logiciel = array();
    $ordis_par_groupe = array();
    foreach ($searchgroups as $groupe){

        // chaines a rechercher par groupe (=> entrées dans la base de GLPI)
        $requete = "SELECT search_text, editeur_text, version_text FROM logiciels_searchtexts WHERE groupe = $groupe[0]";
        $reponse = $company_db->query($requete);
        $search_texts = $reponse->fetchAll();

        // si le groupe est vide (pas de search_text associé) on ignore
        if (sizeof($search_texts) > 0){ 

            // pour le groupe, on additionne les ordis trouvés pour l'ensemble des search_texts (OU) et des versions trouvées
            $ordis_groupe = array();
            foreach ($search_texts as $search_text){

                // si l'editeur est vide on l'ignore pour la requete du soft
                if ($search_text[1] == ""){
                    $requete_soft = "SELECT id FROM glpi_softwares WHERE name LIKE '$search_text[0]'";
                }else{
                    // sinon on cherche l'id de l'editeur puis on l'inclut dans la requete du soft
                    $requete_editeur = "SELECT id FROM glpi_manufacturers WHERE name LIKE '$search_text[1]'";
                    $reponse_editeur = $glpi_db->query($requete_editeur);
                    $editeur_id = $reponse_editeur->fetch();
                    $requete_soft = "SELECT id FROM glpi_softwares WHERE (name LIKE '$search_text[0]' AND manufacturers_id = '$editeur_id[0]')";
                }
                // recherche de l'id du soft
                $reponse = $glpi_db->query($requete_soft);
                $softid = $reponse->fetch();

                // versions trouvées pour le soft
                $requete = "SELECT id,name FROM glpi_softwareversions WHERE (softwares_id = '$softid[0]' AND name LIKE '$search_text[2]')";
                $reponse = $glpi_db->query($requete);
                $versions_matchees = $reponse->fetchAll();

                foreach ($versions_matchees as $version_id){

                    // id de machines ou elle est installée
                    $requete = "SELECT items_id FROM glpi_items_softwareversions WHERE (softwareversions_id = '$version_id[0][0]' AND itemtype = 'Computer')";
                    $reponse = $glpi_db->query($requete);
                    $ordis_id = $reponse->fetchAll();

                    // hostnames des machines
                    foreach ($ordis_id as $ordi){
                        $requete = "SELECT name, is_deleted FROM glpi_computers WHERE id = '$ordi[0]'";
                        $reponse = $glpi_db->query($requete);
                        $ordi = $reponse->fetch();
                        $ordi_nom = strtolower($ordi["name"]);
                        $ordi_exclu = false;
                        foreach ($exclusions as $exclu){
                            if ($ordi_nom == $exclu[0]){
                                $ordi_exclu = true;
                            }
                        }
                        // on ne prends que les ordis non supprimés et non exclus
                        if (($ordi["is_deleted"] == 0) && ($ordi_exclu == false)){
                            array_push($ordis_groupe,$ordi_nom);
                        }
                    }
                }
            }
            array_push($ordis_par_groupe,$ordis_groupe);
        }
    }

    // on ne garde que les ordis qui sont dans l'ensemble des groupes (ET)
    $ordis_logiciel = call_user_func_array('array_intersect',$ordis_par_groupe);

    $tableau_listes_ordis[$logiciel[0]] = array();
    foreach ($ordis_logiciel as $ordi){
        // découpage de l'hostname pour avoir le numéro de salle
        $ordi_nom_ar = explode("-",$ordi);
        $salle = $ordi_nom_ar[2];
        // on ne compte que les salles de la bdd company
        if (in_array($salle,$salles_searchtexts)){
            // ajout au tableau global par logiciel
            if (!(array_key_exists($salle,$tableau_listes_ordis[$logiciel[0]]))){
                $tableau_listes_ordis[$logiciel[0]][$salle] = array();
                array_push($tableau_listes_ordis[$logiciel[0]][$salle],$ordi);
            }else{
                array_push($tableau_listes_ordis[$logiciel[0]][$salle],$ordi);
            }
        }
    }

    $tableau_nb_ordis[$logiciel[0]] = array();
    // comptage des ordis par salle
    foreach ($tableau_listes_ordis[$logiciel[0]] as $salle => $liste){
        $tableau_nb_ordis[$logiciel[0]][$salle] = sizeof($liste);
    }

    // array associatif id => (nom;editeur;version)
    $infos_par_logiciel[$logiciel[0]] = $logiciel[1].";".$logiciel[2].";".$logiciel["version"].";".$logiciel["cache"];

}