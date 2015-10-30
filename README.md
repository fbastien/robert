
# ROBERT fork, version 1.0.0


## 1) Description rapide du Robert

### Robert, c'est :
* Une WEB-APP (logiciel en ligne) open source, écrite en php, js, html et xml, utilisant jQuery, ajax et mysql.

**Hum, mais encore ?**

* Un gestionnaire de parc de matériel destiné à la location
* Un outil bien pratique pour assurer le bon fonctionnement d'un parc de matériel et la liaison avec les clients / techniciens, accessible n'importe où depuis internet
* Un moyen simple et efficace de s'y retrouver dans les sorties-retours de matériel
* Une interface claire et fonctionnelle
* Une solution pour stocker les données relative au parc, de manière sécurisée et centralisée sur votre propre serveur
* Un projet communautaire auquel tout le monde (quidam, association, entreprise) peut participer


### Robert, ce n'est pas :

* Un logiciel de compta
* Un logiciel exécutable sur un ordinateur local (c'est une web-app, accessible seulement via un navigateur web)
* Un substitut à votre cerveau
* Une machine à café



## 2) Liste des fonctionnalités du Robert

### Gestionnaire d’événements (module calendrier)
* Ajout d’événement
* Gestion de la disponibilité des techniciens
* Gestion des quantités de matériel disponible à la (aux) date(s) donnée(s)
* Création de devis et factures au format PDF
* Système de calcul de remises, en % ou en €
* Création de listes récapitulatives du matériel à préparer/louer, des infos sur l'événement et du fichier de déclaration des techniciens.
* Météo du lieu de l’événement (si pas trop éloigné dans le temps)
* Petit système de post-it pour communiquer avec toute l'équipe

### Gestionnaire de parc matériel
* Gestion du matériel (ajout, suppression, modification)
* Création de "Pack" de matériels (liste prédéfinie)
* Création de sous catégories pour le tri du matériel

### Gestion des techniciens
* Ajout de techniciens et de leur infos (n°SÉCU, n°GUSO, coordonnées, etc.)
* Création de comptes (compte utilisateur associé aux infos technicien)

### Gestion des "bénéficiaires" (clients)
* Gestion de structures (associations, collectivités, entreprises, particuliers...)
* Gestion des interlocuteurs associés à ces structures

### Gestion des informations
* Coordonnées de la structure du parc de matériel

### Module de sauvegarde
* Sauvegarde de la base de données dans un fichier téléchargeable
* Restauration d'un fichier de sauvegarde dans la base en cas d'erreur

### Plusieurs thèmes disponibles pour l'interface



## 3) Documentation du Robert : INSTALLATION

### 1. Avant de commencer

Vous devez disposer d'un serveur LAMP (ou WAMP ou MAMP) comprenant :
* PHP5.3.3 ou ultérieur, avec la librairie "curl"
* MySQL5
* PhpMyAdmin
Assurez-vous d'avoir un accès au serveur MySQL ainsi qu'un compte phpMyAdmin pour la base de données, et un accès FTP pour le transfert de fichiers vers le serveur.

### 2. Installation du Robert pas à pas

* **Téléchargez** l'archive de la dernière version du Robert, et décompactez-la (c'est comme un fichier ZIP) dans un dossier facile à retrouver.
* Ouvrez le fichier " **config.ini** " se trouvant dans le dossier "/config".
* **modifiez les lignes 7 à 10** en mettant les bons codes d'accès au serveur MySQL, et le nom de la base de données, en vous inspirant de l'exemple, puis sauvegardez.
* Rendez-vous sur votre **phpMyAdmin**, créez une base de données si besoin, et entrez dedans.
* Cliquez sur l'onglet " **Importer** ", puis sur le bouton "parcourir" allez chercher le fichier " **install_DB.sql** " se trouvant dans le dossier "/scripts/install". Vous pouvez aussi copier-coller le contenu de ce fichier dans la zone de requête de l'onglet "SQL".
* **Uploadez le code source** du Robert sur le serveur, dans le dossier racine du nom de domaine (ou sous-domaine) que vous avez choisi.
* **Connectez-vous au Robert** grâce au log/pass suivant : `log : root@robertmanager.org, pass: admin`

### 3. Problèmes connus



## 4) Documentation du Robert : UTILISATION

1. **Pour commencer**
2. **Pour aller + loin**
3. **Pour contribuer**
4. **Pour développer**



## 5) Documentation du Robert : DÉVELOPPEMENT

### 1. Versionning, dépôt GIT et GitHub

Le code source de Robert utilise le [logiciel de versionning "Git"](http://fr.wikipedia.org/wiki/Git). Vous pouvez créer un clone du dépôt grâce à la commande :

	git clone git://github.com/fbastien/robert

Ou bien, si vous avez un compte gitHub, grâce au [bouton "Fork"](https://github.com/fbastien/robert/fork_select) (en haut de page), vous pourrez cloner le dépôt gitHub sur votre propre compte gitHub.
Lorsque vous avez modifié le code source, pour ajouter une fonctionnalité ou corriger un bug, vous pouvez faire un ["Pull Request"](https://github.com/fbastien/robert/pull/new/master). Ceci nous préviendra qu'une nouvelle version du Robert est disponible, afin que nous puissions fusionner votre version à la version officielle (faire un "merge").

### 2. Avant de commencer

En plus des éléments nécessaires listés pour l'installation du Robert, vous devez aussi disposer des logiciels suivants :
* Composer, qui permet d'importer facilement des librairies externes
* Apache Ant, qui permet de gérer la construction du projet

Ouvrir le fichier "/scripts/build/**build.xml**" et renseigner la propriété `<property name="composer.bin" location="..." />` en renseignant dans l'attribut "location" le chemin absolu du script de lancement de composer (nommé "composer.bat" pour Windows et "composer" pour Unix).
Inspirez-vous des exemples en commentaires.

Les tests unitaires se trouvent dans le dossier "/test" et peuvent être exécutés avec PHPUnit.
Celui-ci est importé par Composer et l'exécutable est installé dans le dossier "/lib/bin".
Le fichier de configuration "/test/**phpunit.xml**" doit être passé en paramètre (`--configuration`) de PHPUnit.
Les tests doivent être effectués sur une base de données différente de celle sur laquelle est déployée le Robert (mais ayant la même structure).
Par défaut, ils sont effectués sur une base "robert_test" en localhost ; modifier les informations de connexion dans ce fichier de configuration si besoin.

### 3. Règles de base de présentation du code

Ceci peut paraître trivial, mais il est important pour la lisibilité du code que l'on soit tous sur la même longueur d'onde en ce qui concerne la présentation...

* **indentation** : merci d'indenter correctement votre code, avec des tabulations.
* **nom des variables** : la première lettre doit être en minuscule. Merci d'utiliser des noms de variables compréhensibles, qui ont un rapport avec leurs valeurs.
Ex : `$nomDeLaVar`, ou `$nom_de_la_var`
* **accolades** : laisser celle d'ouverture sur la ligne de déclaration, et celle de fermeture sur une ligne seule.

Par exemple :

	if ($var == 'string') {
		do something
	}
	else {
		do something else
	}

* Inspirez-vous du code existant pour la présentation.

### 4. La structure du Robert expliquée

#### Présentation de la structure des dossiers :
* `/classes` : Les définitions de classes PHP
* `/config` : Les paramètres pour accéder aux BDD et les infos de votre structure
* `/css` : hum hum ;)
* `/data` : Les fichiers générés par le site (factures, devis, sauvegardes)
* `/debug` : Interfaces pour tester certaines classes
* `/fct` : Les fichiers de fonctions
* `/font` : Polices perso
* `/gfx` : Les graphismes
* `/inc` : Classes d'initialisation ( chemins par défaut / connections / header HTML)
* `/js` : Bibliothèques JS ( upload de fichiers, JQUERY ... )
* `/lib` : Les définitions de classes PHP de bibliothèques externes
* `/modals` : Fenêtres modales de pages
* `/pages` : Pages principales du site
* `/scripts` : Scripts d'installation et de maintenance du site
* `/tmp/BFLogs` : Log des connections au site

#### Le système des "pages" Ajax

Dans le dossier **/fct/** vous trouverez les fonctions.
Chaque module comprend au moins un fichier **\*_actions.php** et **\*_Ajax.js**.
Ainsi les fichiers en rapport avec la section 'matos' du site seront :
* `matos_Ajax.js`  => Traitement des formulaires, Gestions des éléments de l'interface, Appels Ajax au fichier php
* `matos_actions.php`  =>  Récupère les données et appelle les traitements, retourne le status en JSON à la page appelante
* `matos_tri_sousCat.php` =>  Des fonctions supplémentaires  uniquement pour gérer les sous catégories de matériel. Ces fonctions sont donc rassemblées dans un fichier extérieur à matos_actions.php

![structure php ajax bdd](http://www.robert.polosson.com/gfx/dev-tuto/structure_robert.jpg)

#### Structure de donnée & Base de Donnée

#### Détails concernant...
Les fichiers `initInclude.php` : *TODO*

### 5. Liste des classes et leur utilisation

* `Calendar.class.php`
* `Connecting.class.php`
* `Devis.class.php`
* `Infos.class.php`
* `Interlocuteur.class.php`
* `Liste.class.php`
* `Matos.class.php`
* `Pack.class.php`
* `PDF_Devisfacture.class.php`
* `Plan.class.php`
* `SortiePDF.class.php`
* `Structure.class.php`
* `Tekos.class.php`
* `Users.class.php`



## Licence du Robert

Le Robert est un logiciel libre; vous pouvez le redistribuer et/ou
le modifier sous les termes de la Licence Publique Générale GNU Affero
comme publiée par la Free Software Foundation;
version 3.0.

Cette WebApp est distribuée dans l'espoir qu'elle soit utile,
mais SANS AUCUNE GARANTIE; sans même la garantie implicite de
COMMERCIALISATION ou D'ADAPTATION A UN USAGE PARTICULIER.
Voir la Licence Publique Générale GNU Affero pour plus de détails.

Vous devriez avoir reçu une copie de la Licence Publique Générale
GNU Affero avec les sources du logiciel (LICENCE.txt); si ce n'est pas
le cas, rendez-vous à http://www.gnu.org/licenses/agpl.txt (en Anglais)
