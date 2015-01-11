ChemLab - gestione laboratorio
==============================

Concept di applicazione web per la gestione dell'inventario di un laboratorio (es.: di chimica), con gestione degli ordini.

Basato su Symfony 2.6.1.

## Requisiti

* PHP 5.5+
* MySQL 5.6 (potebbe bastare 5.0)

Per l'installazione dei componenti, si è usato Composer 1.0-dev.

Per i test, lo sviluppo è stato effettuato con PHPUnit 4.4.1.

## Installazione

Per cominciare, basta clonare il repository:

    git clone https://github.com/MaxArt2501/chemlab.git path/

In seguito, basta procedere all'installazione dei componenti tramite Composer:

    composer install

Verranno richiesti i parametri per la configurazione di Doctrine, che andranno a definire il file [parameters.yml](app/config/parameters.yml).

L'applicazione ha preconfigurato un profilo di test in [config_test.yml](app/config/config_test.yml), dove viene indicato l'utilizzo del database `chem_lab_test` e dell'utente `chemlab_test` con password `chemlab_test_pwd`. Cambiare questi parametri secondo le proprie necessità, ed eventualmente definire parametri simili per l'ambiente `dev` (che di default utilizza gli stessi parametri di `prod`).

In base a ciò, sarà necessario creare i database su MySQL, concordemente a quanto definito nei file di configurazione. Per le operazioni di configurazione, si richiede che l'utente indicato nella configurazione abbia sufficienti privilegi sul database (selezione, inserimento, cancellazione, creazione e alterazione tabelle e così via).

In seguito, sarà necessario che l'applicazione esegua l'update degli schemi delle entità:

    php app/console doctrine:schema:update --force
    php app/console doctrine:schema:update --force --env=test
    php app/console doctrine:schema:update --force --env=dev (se necessario)

## Inizializzazione

ChemLab ha dipendenza da [doctrine/doctrine-fixture-bundle](https://github.com/doctrine/DoctrineFixturesBundle) per il caricamento delle fixtures dell'applicazione. Vengono forniti alcuni dati per lo startup dell'applicazione negli ambienti `prod`, `dev` e `test`, per i quali basta eseguire

    php app/console doctrine:fixture:load --env=prod
    php app/console doctrine:fixture:load --env=dev
    php app/console doctrine:fixture:load --env=test

Si rammenta che, di default, le fixtures per gli ambienti `prod` e `dev` andranno sullo stesso database.

Vengono forniti alcuni utenti con cui iniziare, tra cui `admin:theAdmin` e `testuser:commonuser` come utenti rispettivamente amministratore e comune. L'elenco completo degli utenti con i relativi dati è presente nei file [user_prod.json](src/ChemLab/AccountBundle/DataFixtures/user_prod.json), [user_dev.json](src/ChemLab/AccountBundle/DataFixtures/user_dev.json) e [user_test.json](src/ChemLab/AccountBundle/DataFixtures/user_test.json). Specialmente per l'ambiente di produzione, si raccomanda di cambiare immediatamente le password.

ChemLab è ora pronta per l'esecuzione:

    php app/console server:run

## Test

Sono definiti dei test dell'applicazione, operanti con le fixtures definite per l'ambiente di test. Per l'esecuzione basta fare

    phpunti -c app src/ChemLab/
