# CYJE_MP_Charge-DSI - Espace Gestion de prospects

## Documentation technique

Le présent document est une documentation technique de la Mission Piou de NISAR Sofiane, pour le poste de Chargé DSI à la CY Junior Engineering.

-------
### Installation

- Pour installer l'environnement nécessaire au bon fonctionnement du site web, veuillez installer [XAMPP](https://www.apachefriends.org/fr/index.html) (avec au minimum Apache et MySQL).
    - **Lisez l'avertissement** ci-dessous ! Notez donc bien le **chemin d'installation choisi** car il sera utilisé dans la suite de cette documentation.
> [!NOTE]
> XAMPP est l'environnement utilisé pour le développement de cette MP.
> Cependant, n'importe quel serveur PHP et instance MySQL (*MariaDB* de préférence) devraient fonctionner.

> [!WARNING]
> Si vous êtes sous Windows, veillez toujours à **lancer XAMPP en tant qu'administrateur**, pour éviter toute corruptions de la base de données et/ou de l'environnement de développement.
> Veillez également à **ne pas installer XAMPP dans** ```C:/Program Files``` ou ```C:/Program Files (x86)``` pour éviter les problèmes de lecture/écriture.

> [!CAUTION]
> XAMPP ne doit **en aucun cas être utilisé pour mettre en ligne le site publiquement**, car XAMPP est un outil de développement **local** dépourvu de sécurité.

A titre d'information :

    Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12
    Version du client de base de données : libmysql - mysqlnd 8.2.12
    Extension PHP : mysqli, curl, mbstring 
    Version de PHP : 8.2.12
    Version de phpMyAdmin 5.2.1
    Type de serveur : MariaDB 

- Rendez vous dans le **dossier d'installation** de XAMPP, puis dans ```htdocs```
    - Si vous êtes un utilisateur avancé, et que vous avez git déjà installé :
        -     git clone https://github.com/sofiansr/CYJE_MP_Charge-DSI.git
    - Sinon, **téléchargez le code** via le bouton vert "Code" du répertoire GitHub, et **décompressez* l'archive ZIP dans ```XAMPP/htdocs``` via 7-Zip ou WinRAR.
        - ![](https://i.imgur.com/kFcynsn.png)
    - Dans tous les cas, vous devrirez avoir une architecture du style : ```[...]/XAMPP/htdocs/CYJE_MP_Charge-DSI/[fichiers du site]```.
- **Lancez** XAMPP.
- **Démarrez** le service Apache puis MySQL.
- Cliquez sur le bouton ```Admin```, sur la ligne du service MySQL.
    - ![](https://i.imgur.com/ZaRmOAm.png)   
- Sur la page d'accueil de phpMyAdmin, cliquez en haut sur l'onglet ```SQL```.
    - ![](https://i.imgur.com/f9FY1Fn.png)
- Dans le champ de texte, copier-coller **l'entièreté** de [bdd_init.sql](https://github.com/sofiansr/CYJE_MP_Charge-DSI/blob/main/bdd_init.sql), puis cliquez sur le bouton ```Exécuter```.
- Vous pouvez ensuite cliquer sur l'onglet ```cyje``` de la liste des base de données, à gauche de l'écran.
    - ![](https://i.imgur.com/t4VpDpi.png)
- Repérez la ligne de la table ```users```, puis cliquez sur ```Insérer```.
    - ![](https://i.imgur.com/irIEKst.png)
- Insérons le premier ```ADMIN``` du site :
    - Ne rentrez pas d'ID. Vous pouvez le faire, mais la base de données le fait déjà à votre place.
    - Rentrez nom, prénom, et email.
    - Générez un mot de passe complexe, convertissez-le en hash via [ce site](https://onlinephp.io/password-hash) (php version = 8.2.12, cost=10) puis copier-collez le dans le champ ```password```.
    - Choississez ```ADMIN```.
    - Cliquez enfin sur ```Exécuter```.
    - ![](https://i.imgur.com/xYKnMJm.png)
- Rendez-vous sur ```http://localhost/CYJE_MP_Charge-DSI/connexion.html``` pour vérifier vos identifiants et vous connecter au site.
    - ![](https://i.imgur.com/WoxrUSe.png)

-------
### Fonctionnement

Ce projet utilise les technologies suivantes pour fonctionner :
- HTML
- CSS
- PHP (pages dynamiques et back-end)
- JavaScript (front-end)
- MySQL (base de données)
- API Fetch en JSON
- [Chart.js](https://www.chartjs.org/) (bibliothèque JavaScript pour générer des graphiques)
- PHP Data Objects (ou PDO) (permet la communication PHP-SQL)

```
CYJE_MP_Charge-DSI/
├── assets/
│   └── (Logo et images nécessaires pour le site)
├── scripts/
│   ├── admin_api.php (back-end de admin.php)
│   ├── admin.js (front-end de admin.php)
│   ├── auth.php (back-end de connexion.html)
│   ├── dashboard_api.php (back-end de dashboard.php)
│   ├── home.js (front-end de dashboard.php)
│   ├── logout.php (script de déconnexion)
│   ├── prospects_api.php (back-end de prospects.php)
│   └── prospects.js (front-end de prospects.php)
├── style/
│   ├── connexion.css
│   ├── dashboard.css
│   └── prospects.css (également utilisé par admin.php)
├── admin.php (page de consultation, d'ajout, de modification et de suppression d'utilisateurs)
├── dashboard.php (page d'accueil statistiques)
├── prospects.php (page des prospects, avec ajout, modification, suppression, filtrage et tri) 
├── README.md
├── Documentation-technique.md
├── Manuel-Utilisateur.md
└── bdd_init.sql (Commandes SQL à exécuter lors de la création de la base de données)
```

La police d'écriture *Barlow Semi Condensed* est récupérée auprès de [Google Fonts](https://fonts.google.com/specimen/Barlow+Semi+Condensed).

Un chef de projet peut gérer plusieurs prospects à la fois, cependant un prospect est géré par un seul chef de projet.

Généralement, pour les requêtes API/back-end, on a le schéma suivant :</br>
Action utilisateur -> JavaScript -> PHP (-> SQL -> PHP) -> JavaScript -> Affichage 

-------
### Passation

Il est crucial de **désigner un responsable** du site web, qui aura le rôle ```ADMIN```. Ainsi, si ce responsable venait à devoir **transmettre** ses responsabilités, il peut créer un autre utilisateur ADMIN pour le nouveau responsable.

Ce dernier **supprimera** si nécessaire le compte du précédent responsable, ou le **rétrogradera** en tant qu'```USER```.

Dans le cas où cette procédure n'est pas respectée, et que vous n'arrivez pas à créer un nouvel utilisateur faute d'accès à un compte ```ADMIN```, vous pouvez accéder à phpMyAdmin (ou la console SQL de l'environnement que vous avez choisi) afin d'insérer un nouvel ```ADMIN```.

> [!IMPORTANT]
> Il est en général très recommandé de faire des sauvegardes de ses bases de données. Par exemple, sur phpMyAdmin, vous pouvez aller dans ```cyje```, puis dans l'onglet ```Exporter```.


-------
Made with 💙 in Cergy-Pontoise, France

<a href="https://github.com/sofiansr/CYJE_MP_Charge-DSI/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=sofiansr/CYJE_MP_Charge-DSI" />
</a>
