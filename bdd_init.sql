CREATE DATABASE CYJE;

USE CYJE;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'USER') DEFAULT 'USER'
);

CREATE TABLE prospect (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entreprise VARCHAR(100) NOT NULL,
    secteur VARCHAR(100),
    adresse_entreprise VARCHAR(255),
    site_web_entreprise VARCHAR(255),
    status_prospect ENUM('A contacter', 'Contacté', 'A rappeler', 'Relancé', 'RDV', 'PC', 'Signé', 'PC refusée', 'Perdu'),
    relance_le DATE,
    type_acquisition ENUM('DE', "Appel d'offre", 'Web crawling', 'Porte à porte', 'IRL', 'Fidélisation', 'BaNCO', 'Partenariat'),
    date_premier_contact DATE,
    type_premier_contact ENUM('Porte à porte', 'Formulaire de contact', 'Event CY Entreprise', 'LinkedIn', 'Mail', "Appel d'offre", 'DE', 'Cold call', 'Salon'),
    chaleur ENUM('Froid', 'Tiède', 'Chaud'),
    offre_prestation ENUM('Informatique', 'Chimie', 'Biotechnologies', 'Génie civil'),
    commentaire TEXT,
    chef_de_projet_id INT,    
    CONSTRAINT fk_prospect_user
        FOREIGN KEY (chef_de_projet_id) REFERENCES users(id)    
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prospect_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    poste VARCHAR(100),
    email VARCHAR(100),
    tel VARCHAR(20),
    linkedin VARCHAR(255),
    CONSTRAINT fk_contact_prospect
        FOREIGN KEY (prospect_id) REFERENCES prospect(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT unique_contact UNIQUE (nom, prenom, prospect_id)
);