<?php

// -------------------------------------------------------------------------------------------------------------
// initialisation
// -------------------------------------------------------------------------------------------------------------

require_once("init_session.php");

// -------------------------------------------------------------------------------------------------------------
// controle du formulaire de connexion
// -------------------------------------------------------------------------------------------------------------

if (isset($_POST['demandeConnexion'])){
    if (($_POST["login"] != "") && ($_POST["mdp"]) != ""){
        // escapade
        $login = htmlspecialchars($_POST["login"]);
        $mdp = htmlspecialchars($_POST["mdp"]);    
        // tente l'authentification a l'AD LDS
        $auth = bind_ad_lds($login,$mdp);
        // valide la connexion
        if ($auth){
            $_SESSION["user_connecte"] = 1;
            if ($_POST["origin"] != ""){
                if ($_POST["origin"] == "index"){
                    header("Location:index.php");
                }elseif ($_POST["origin"] == "mini"){
                    header("Location:mini.php");
                }else{
                    header("Location:index.php");
                }
            }else{
                header("Location:index.php");
            }
            exit();
        }else{
            $message = "Erreur : Accès refusé ou identifiants incorrects. Veuillez réessayer.";
        }

    }else{
        $message = "Erreur : veuillez remplir tous les champs !";
    }

    unset($_SESSION["user_connecte"]);

    if ($_POST["origin"] != ""){
        if ($_POST["origin"] == "index"){
            $origin = "index";
        }elseif ($_POST["origin"] == "mini"){
            $origin = "mini";
        }else{
            $origin = "index";
        }
    }else{
        $origin = "index";
    }

    include("connexion.php");
    exit();
}

// -------------------------------------------------------------------------------------------------------------
// demande de deconnexion
// -------------------------------------------------------------------------------------------------------------

if (isset($_GET["logout"])){
    unset($_SESSION["user_connecte"]);
    $message = "Déconnexion réussie.";
    if (isset($_GET["origin"])){
        if ($_GET["origin"] == "index"){
            header("Location:connexion.php?origin=index");
        }elseif ($_GET["origin"] == "mini"){
            header("Location:connexion.php?origin=mini");
        }else{
            header("Location:connexion.php?origin=index");
        }
      }else{
        header("Location:connexion.php?origin=index");
      }
    exit();
}

// Identification
if (!isset($_SESSION["user_connecte"]) ) {
	// l'utilisateur n'est pas identifié
    if (isset($page)){
        if ($page == "index"){
            header("Location:connexion.php?origin=index");
            exit();
        }else{
            header("Location:connexion.php?origin=mini");
            exit();
        }
    }else{
        header("Location:connexion.php?origin=index");
        exit();
    }
}


// -------------------------------------------------------------------------------------------------------------
// fonction de bind de l'AD LDS
// -------------------------------------------------------------------------------------------------------------

function bind_ad_lds($login,$mdp){
    // authentification rejetee par defaut
    $auth = false;
    try {
        // connexion au serveur AD LDS
        $ADHost="ldap://".$_ENV["AD_LDS_HOST"];
        $adconn = ldap_connect($ADHost, 636) or die("Impossible de se connecter au serveur LDAP.");
        // parametres LDAP
        ldap_set_option($adconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($adconn, LDAP_OPT_REFERRALS, 0);
        // premier bind avec le compte de service
        $login_svc = $_ENV["AD_LDS_CN"];
        $mdp_svc = $_ENV["AD_LDS_PASSWORD"];
        $admin_bind = ldap_bind($adconn, $login_svc, $mdp_svc);
        if ($admin_bind) {
            // recherche du login
            $index_login = ldap_search($adconn,"DC=company,DC=LOCAL","(cn=$login)");
            $entrees_login = ldap_get_entries($adconn,$index_login);
            $dn_login = $entrees_login[0]['dn'];
            $personnel = false;
            // si entité : IU
            if (strpos($dn_login,"OU=A,OU=S,OU=U,OU=iu,OU=C") !== false){
                if ($entrees_login[0]['catusager'][0] != "i_etu"){
                    $personnel = true;
                }
            // si entité : IN
            }elseif (strpos($dn_login,"OU=S,OU=U,OU=in,OU=C") !== false){
                if ($entrees_login[0]['employeetype'][0] != "student"){
                    $personnel = true;
                }
            // si entité : IS
            }elseif (strpos($dn_login,"OU=S,OU=U,OU=is,OU=C") !== false){
                // contrôle du domaine mail
                $mail_parts = explode("@",$entrees_login[0]['mail'][0]);
                if ($mail_parts[1] == "is.fr"){
                    $personnel = true;
                }
            // si entité : UP
            }elseif (strpos($dn_login,"OU=S,OU=U,OU=f,OU=C") !== false){
            // il peut y avoir plusieurs 'affiliations' par individu
                if (!(in_array("Student",$entrees_login[0]["edupersonaffiliation"]))){
                    $personnel = true;
                }
            }

            if ($personnel == true){
                // bind avec les credentials
                $user_bind = ldap_bind($adconn, $dn_login, $mdp);
                // si bind reussi => authentification acceptee
                if ($user_bind){
                    $auth = true;
                }
            }
        }
    } catch (exception $e){
        $auth = false;
    }
    // fermeture de la connexion
    ldap_close($adconn);
    return $auth;
  }