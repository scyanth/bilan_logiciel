Application PHP pour afficher quels logiciels sont installés dans les salles informatiques de l'entreprise, en se basant sur les données collectées par l'outil GLPI (Gestionnaire Libre de Parc Informatique).<br>
Utilise une BDD interne et celle de GLPI, tous deux sous MariaDB.<br>
L'authentification est gérée avec l'AD LDS.<br>
L'outil "admin_cri" a également été développé pour faciliter la mise à jour de la BDD interne notamment avec les informations des logiciels à afficher et à rechercher.<br>
La librairie dotEnv est utilisée pour protéger les informations sensibles.<br>
Les informations confidentielles de l'entreprise ont été anonymisées.<br>
