# Paris Caretaker Services

- Installer composer
    1) Vérifier si php est installé : `php -v` (version 8.1.10)
    2) Sinon installer php
    3) Vérifier s'il est déjà installé : `composer --version`
    4) S'il n'est pas installé, soit installé le ici : [Composer](https://getcomposer.org/Composer-Setup.exe) ou alors faites ces commandes :  
            - `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"`  
            - `php -r "if (hash_file('sha384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"`  
            - `php composer-setup.php`  
            - `php -r "unlink('composer-setup.php');"`  
            - Inshallah ça marche correctement

- Installer symfony CLI (Windows) (Pour Linux aller voir ici : [Lien pour Linux](https://symfony.com/download))
    1) Aller sur un PowerShell est faire ces commandes :  
            - `Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser`  
            - `Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression`
    2) Taper cette commande : `scoop install symfony-cli`

- Comment démarrer l'API en local la première fois ?
    1) Démarrer Apache et MySQL depuis XAMPP
    2) Ouvrir votre php.ini (sur XAMPP dans le bouton Config d'apache) et décommenter la ligne ;extension=sodium
    3) Se rendre dans le dossier [CaretakerServicesApi](./CaretakerServicesApi) dans votre terminal.
    4) Taper la commande `composer install`
    5) Taper la commande `symfony server:start`
    6) Se rendre sur [https://127.0.0.1:8000/api](https://127.0.0.1:8000/api)
    7) Créer la base de données en faisant :  
            - `php bin/console doctrine:database:create`  
            - `php bin/console make:migration`  
            - `php bin/console doctrine:migrations:migrate`  --> Si erreur = supprimer tous les fichiers (à part le .gitignore) dans le dossier migrations et recommencer à la make:migration  
            - `php bin/console doctrine:fixtures:load`

- Comment démarrer l'API en local les autres fois ?
    1) Démarrer Apache et MySQL depuis XAMPP
    3) Se rendre dans le dossier [CaretakerServicesApi](./CaretakerServicesApi) dans votre terminal.
    4) Taper la commande `composer install` (uniquement si de nouveau composer require ont été fait)
    5) Taper la commande `symfony server:start`
    6) Se rendre sur [https://127.0.0.1:8000/api](https://127.0.0.1:8000/api)
    7) Créer la base de données en faisant (uniquement si de nouvelles tables/colonnes ont été ajoutés) :  
            - `php bin/console make:migration`  
            - `php bin/console doctrine:migrations:migrate`  --> Si erreur = supprimer tous les fichiers (à part le .gitignore) dans le dossier migrations et recommencer à la make:migration  
            - `php bin/console doctrine:fixtures:load`

- Comment démarrer l'appli Web en local ?
    1) Démarrer Apache et MySQL depuis XAMPP
    2) Se rendre dans le dossier [CaretakerServicesWeb](./CaretakerServicesWeb) dans un autre terminal.
    4) Taper la commande `composer install` (uniquement la première fois ou si de nouveau composer require ont été fait)
    3) Taper la commande `symfony server:start --port=8001`
    4) Se rendre sur [https://127.0.0.1:8001](https://127.0.0.1:8001)

- Comment réinitialiser la base de données ?
    1) Pour la supprimer faire `php bin/console doctrine:database:drop --force`  
    2) Ensuite il faut la recréer `php bin/console doctrine:database:create`  
    3) Puis y pusher les nouveaux schemas : `php bin/console doctrine:schema:update --force`  
    4) Créer les nouvelles migrations : `php bin/console make:migration`  
    5) Ajouter les fixtures : `php bin/console doctrine:fixtures:load`

- Fonctionnement du Github :
    1) Ne pas push sur le main !
    2) A chaque nouvelle feature, correction de bug, ajout de doc, ... : créer une nouvelle branche avec cette archi de nom :  
            - **feature** -> to add, rework or remove a feature  
            - **bugfix** -> to fix a bug  
            - **hotfix** -> to change code with a temporary solution and/or without following the usual process  
            - **test** -> to experiment outside of a problem  
            - **docs** -> to add docs  
            - **misc** -> to add things other than code  
            - Puis une très courte description de l'action effectuée / ou le nom de la nouvelle fonctionnalité  
            - *Ex : feature/add-login-page*
    3) Une fois que vous avez push votre branche + qu'elle est fini (que vous n'avez plus rien à y ajouter) vous pouvez faire une pull request vers la branche de dev  
    4) Si vous voulez implémenter votre fonctionnalité en prod, faire une pull request de la branche de dev vers celle de prod (main) et me mettre comme reviewers et ne pas la merge tant que je ne l'ai pas validé aussi.