<?php

// -------------------------------------------------------------------------------------------------------------
// initialisation
// -------------------------------------------------------------------------------------------------------------

define("NO_LOGIN",true);
require_once("init_session.php");

// -------------------------------------------------------------------------------------------------------------
// affichage
// -------------------------------------------------------------------------------------------------------------

?>

<!doctype html>
<html lang="fr">
  <head>
    <title>Connexion - Bilan Logiciel company</title>
    <meta charset="utf-8">
    <style>
      body {
        font-family: 'Times New Roman';
      }   
      table {
        border-collapse: collapse;
        border-spacing: 0px;
      }
      tr > :first-child, th, td {
        border: none;
        background-color: transparent;
        padding : 0px;
      }
      .center {
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
  </head>
  <body>

<?php

print '<table class="center">';
print '<tr><td><h2> Bilan Logiciel company </h2></td></tr>';
print '<tr><td> Veuillez vous authentifier pour accéder à cette page. </td></tr>';
print '<tr><td><img class="center" src="images/company_logo.PNG"></img></td></tr>';
print '<tr><td><img class="center" src="images/company_entites_logos.PNG"></img></td></tr>';

if (isset($message)){
  print '<tr><td style="color:red;">'.$message.'</td></tr>';
}else{
  print '<tr><td></td></tr>';
}

print '<tr><td><form action="restrict.php" method="post"> <table class="center"> <thead> <tr> <th>Login</th> <th>Mot de passe</th> </tr> </thead> <tbody> <tr>';
print '<td><input type="text" name="login"></td>';
print '<td><input type="password" name="mdp">';

if (isset($_GET["origin"])){
  if ($_GET["origin"] == "index"){
    $origin = "index";
  }elseif ($_GET["origin"] == "mini"){
    $origin = "mini";
  }else{
    $origin = "index";
  }
}else{
  if (!(isset($origin))){
    $origin = "index";
  }
}

print '<input type="hidden" name="origin" value="'.$origin.'"></td>';


print '</tr> <tr> <td colspan="2"> <button class="center" type="submit" name="demandeConnexion">Connexion</button> </td> </tr> </tbody> </table> </form></td></tr>';

?>

   </body>
</html>