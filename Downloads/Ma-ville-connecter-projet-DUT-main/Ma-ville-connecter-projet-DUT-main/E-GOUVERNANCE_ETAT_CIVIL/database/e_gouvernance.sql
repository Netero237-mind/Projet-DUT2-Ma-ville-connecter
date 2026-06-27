-- ============================================================
-- BASE DE DONNÉES : e_gouvernance_etat_civil
-- Plateforme de E-Gouvernance - État Civil Municipal
-- Version : 1.0.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Création et sélection de la base
DROP DATABASE IF EXISTS `e_gouvernance_etat_civil`;
CREATE DATABASE `e_gouvernance_etat_civil`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `e_gouvernance_etat_civil`;

-- ============================================================
-- TABLE : roles
-- ============================================================
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `permissions` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`nom`, `description`, `permissions`) VALUES
('admin', 'Administrateur système avec tous les droits', '{"gestion_users":true,"gestion_agents":true,"statistiques":true,"parametres":true,"tous_actes":true}'),
('agent', 'Agent municipal de traitement des dossiers', '{"traitement_demandes":true,"generation_actes":true,"consultation":true}'),
('citoyen', 'Citoyen déposant des demandes d\'actes', '{"depot_demande":true,"suivi_demande":true,"telechargement":true}');

-- ============================================================
-- TABLE : users
-- ============================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `telephone` VARCHAR(20),
    `adresse` TEXT,
    `ville` VARCHAR(100),
    `photo_profil` VARCHAR(255) DEFAULT NULL,
    `statut` ENUM('actif','inactif','suspendu') DEFAULT 'actif',
    `email_verifie` TINYINT(1) DEFAULT 0,
    `token_reset` VARCHAR(100) DEFAULT NULL,
    `derniere_connexion` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role_id`),
    INDEX `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des utilisateurs de démo
-- Admin : admin@mairie.cm / Admin@2024
-- Agent : agent@mairie.cm / Agent@2024
-- Citoyen : citoyen@example.cm / Citoyen@2024
INSERT INTO `users` (`role_id`, `nom`, `prenom`, `email`, `password`, `telephone`, `adresse`, `ville`, `statut`, `email_verifie`) VALUES
(1, 'ADMINISTRATEUR', 'Système', 'admin@mairie.cm', '$2y$12$AgDK6nOIXyGLDG7EHHN0wO1wfvUsIkfjhFeKrBH.XLnvu4A5MNnqO', '+237 222 000 001', 'Hôtel de Ville, Rue de la Mairie', 'Douala', 'actif', 1),
(2, 'MBALLA', 'Jean-Pierre', 'agent@mairie.cm', '$2y$12$nKmual4amMbkYBbS.uJZL..R5i8EM.RQMWoGMly4l0kzCUZbelxMK', '+237 699 123 456', 'Quartier Akwa, Rue des Agents', 'Douala', 'actif', 1),
(2, 'EKOTTO', 'Marie-Claire', 'agent2@mairie.cm', '$2y$12$nKmual4amMbkYBbS.uJZL..R5i8EM.RQMWoGMly4l0kzCUZbelxMK', '+237 677 234 567', 'Quartier Bali, Avenue de la Liberté', 'Douala', 'actif', 1),
(3, 'NKONO', 'Paul', 'citoyen@example.cm', '$2y$12$FGL/h5a2REDzM3pQw/mThuxsCTRoxVPjyFHAXeLdgBvHWIKZDRgxm', '+237 655 345 678', '12 Rue de la Paix, Bonapriso', 'Douala', 'actif', 1),
(3, 'BIYONG', 'Amina', 'amina.biyong@gmail.com', '$2y$12$FGL/h5a2REDzM3pQw/mThuxsCTRoxVPjyFHAXeLdgBvHWIKZDRgxm', '+237 693 456 789', '45 Avenue Kennedy, Akwa', 'Douala', 'actif', 1);

-- ============================================================
-- TABLE : citoyens (profil étendu)
-- ============================================================
CREATE TABLE `citoyens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `numero_cni` VARCHAR(50) UNIQUE,
    `lieu_naissance` VARCHAR(150),
    `date_naissance` DATE,
    `nationalite` VARCHAR(100) DEFAULT 'Camerounaise',
    `situation_matrimoniale` ENUM('celibataire','marie','divorce','veuf') DEFAULT 'celibataire',
    `profession` VARCHAR(100),
    `numero_contribuable` VARCHAR(50),
    `documents_identite` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_cni` (`numero_cni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `citoyens` (`user_id`, `numero_cni`, `lieu_naissance`, `date_naissance`, `nationalite`, `profession`) VALUES
(4, 'CNI-DLA-1985-001234', 'Douala', '1985-03-15', 'Camerounaise', 'Ingénieur informatique'),
(5, 'CNI-DLA-1992-005678', 'Yaoundé', '1992-07-22', 'Camerounaise', 'Enseignante');

-- ============================================================
-- TABLE : agents
-- ============================================================
CREATE TABLE `agents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `matricule` VARCHAR(30) NOT NULL UNIQUE,
    `departement` VARCHAR(100) DEFAULT 'État Civil',
    `poste` VARCHAR(100),
    `date_prise_service` DATE,
    `superviseur_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`superviseur_id`) REFERENCES `agents`(`id`) ON DELETE SET NULL,
    INDEX `idx_matricule` (`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agents` (`user_id`, `matricule`, `departement`, `poste`, `date_prise_service`) VALUES
(2, 'AGT-DLA-2018-001', 'État Civil', 'Chef de service État Civil', '2018-01-15'),
(3, 'AGT-DLA-2020-002', 'État Civil', 'Agent de traitement', '2020-03-01');

-- ============================================================
-- TABLE : demandes
-- ============================================================
CREATE TABLE `demandes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_reference` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `agent_id` INT UNSIGNED DEFAULT NULL,
    `type_acte` ENUM('naissance','deces','mariage','casier','nationalite','autre') NOT NULL,
    `statut` ENUM('soumis','en_cours','valide','rejete','archive') DEFAULT 'soumis',
    `priorite` ENUM('normale','urgente') DEFAULT 'normale',
    `motif_demande` TEXT,
    `commentaire_agent` TEXT,
    `motif_rejet` TEXT,
    `date_traitement` DATETIME DEFAULT NULL,
    `date_validation` DATETIME DEFAULT NULL,
    `acte_pdf_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_reference` (`numero_reference`),
    INDEX `idx_statut` (`statut`),
    INDEX `idx_type` (`type_acte`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : naissances
-- ============================================================
CREATE TABLE `naissances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `demande_id` INT UNSIGNED NOT NULL UNIQUE,
    `nom_enfant` VARCHAR(100) NOT NULL,
    `prenom_enfant` VARCHAR(150) NOT NULL,
    `sexe` ENUM('M','F') NOT NULL,
    `date_naissance` DATE NOT NULL,
    `heure_naissance` TIME,
    `lieu_naissance` VARCHAR(200) NOT NULL,
    `centre_sante` VARCHAR(200),
    `nom_pere` VARCHAR(200),
    `prenom_pere` VARCHAR(200),
    `nationalite_pere` VARCHAR(100) DEFAULT 'Camerounaise',
    `profession_pere` VARCHAR(100),
    `nom_mere` VARCHAR(200),
    `prenom_mere` VARCHAR(200),
    `nationalite_mere` VARCHAR(100) DEFAULT 'Camerounaise',
    `profession_mere` VARCHAR(100),
    `nom_declarant` VARCHAR(200),
    `lien_declarant` VARCHAR(100),
    `numero_acte` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`demande_id`) REFERENCES `demandes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : deces
-- ============================================================
CREATE TABLE `deces` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `demande_id` INT UNSIGNED NOT NULL UNIQUE,
    `nom_defunt` VARCHAR(100) NOT NULL,
    `prenom_defunt` VARCHAR(150) NOT NULL,
    `sexe` ENUM('M','F') NOT NULL,
    `date_naissance_defunt` DATE,
    `lieu_naissance_defunt` VARCHAR(200),
    `nationalite_defunt` VARCHAR(100) DEFAULT 'Camerounaise',
    `profession_defunt` VARCHAR(100),
    `date_deces` DATE NOT NULL,
    `heure_deces` TIME,
    `lieu_deces` VARCHAR(200) NOT NULL,
    `cause_deces` VARCHAR(200),
    `nom_conjoint` VARCHAR(200),
    `nom_declarant` VARCHAR(200) NOT NULL,
    `lien_declarant` VARCHAR(100) NOT NULL,
    `numero_acte` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`demande_id`) REFERENCES `demandes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : mariages
-- ============================================================
CREATE TABLE `mariages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `demande_id` INT UNSIGNED NOT NULL UNIQUE,
    `nom_epoux` VARCHAR(100) NOT NULL,
    `prenom_epoux` VARCHAR(150) NOT NULL,
    `date_naissance_epoux` DATE,
    `lieu_naissance_epoux` VARCHAR(200),
    `nationalite_epoux` VARCHAR(100) DEFAULT 'Camerounaise',
    `profession_epoux` VARCHAR(100),
    `nom_epouse` VARCHAR(100) NOT NULL,
    `prenom_epouse` VARCHAR(150) NOT NULL,
    `date_naissance_epouse` DATE,
    `lieu_naissance_epouse` VARCHAR(200),
    `nationalite_epouse` VARCHAR(100) DEFAULT 'Camerounaise',
    `profession_epouse` VARCHAR(100),
    `date_mariage` DATE NOT NULL,
    `lieu_mariage` VARCHAR(200) NOT NULL,
    `regime_matrimonial` ENUM('communaute_biens','separation_biens','autre') DEFAULT 'communaute_biens',
    `temoin1_nom` VARCHAR(200),
    `temoin2_nom` VARCHAR(200),
    `numero_acte` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`demande_id`) REFERENCES `demandes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : documents (pièces jointes)
-- ============================================================
CREATE TABLE `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `demande_id` INT UNSIGNED NOT NULL,
    `nom_fichier` VARCHAR(255) NOT NULL,
    `nom_original` VARCHAR(255) NOT NULL,
    `type_document` VARCHAR(100),
    `mime_type` VARCHAR(100),
    `taille` INT UNSIGNED,
    `chemin` VARCHAR(500) NOT NULL,
    `uploaded_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`demande_id`) REFERENCES `demandes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_demande` (`demande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : notifications
-- ============================================================
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `titre` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info','succes','avertissement','erreur') DEFAULT 'info',
    `lien` VARCHAR(255) DEFAULT NULL,
    `lu` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_lu` (`user_id`, `lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : historique_actions
-- ============================================================
CREATE TABLE `historique_actions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `entite` VARCHAR(50),
    `entite_id` INT UNSIGNED,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_action` (`action`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : parametres_systeme
-- ============================================================
CREATE TABLE `parametres_systeme` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cle` VARCHAR(100) NOT NULL UNIQUE,
    `valeur` TEXT,
    `description` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `parametres_systeme` (`cle`, `valeur`, `description`) VALUES
('nom_mairie', 'Mairie de Douala 1er', 'Nom officiel de la mairie'),
('ville', 'Douala', 'Ville de la mairie'),
('region', 'Littoral', 'Région administrative'),
('pays', 'Cameroun', 'Pays'),
('adresse_mairie', 'Place de la Mairie, Bonanjo, Douala', 'Adresse postale'),
('telephone_mairie', '+237 233 42 12 12', 'Téléphone principal'),
('email_mairie', 'contact@mairie-douala1.cm', 'Email officiel'),
('site_web', 'www.mairie-douala1.cm', 'Site web officiel'),
('delai_traitement_naissance', '5', 'Délai en jours ouvrés pour acte de naissance'),
('delai_traitement_deces', '3', 'Délai en jours ouvrés pour acte de décès'),
('delai_traitement_mariage', '7', 'Délai en jours ouvrés pour acte de mariage'),
('taille_max_fichier', '5242880', 'Taille maximale des fichiers en octets (5 Mo)'),
('types_fichiers_autorises', 'pdf,jpg,jpeg,png', 'Types de fichiers autorisés pour upload'),
('maintenance_mode', '0', 'Mode maintenance (0=off, 1=on)'),
('logo_mairie', 'assets/images/logo-mairie.png', 'Chemin du logo');

-- ============================================================
-- DONNÉES DE DÉMONSTRATION : Demandes et actes
-- ============================================================

-- Demandes de démonstration
INSERT INTO `demandes` (`numero_reference`, `user_id`, `agent_id`, `type_acte`, `statut`, `motif_demande`, `created_at`) VALUES
('REF-2024-NAI-00001', 4, 2, 'naissance', 'valide', 'Déclaration de naissance de mon enfant', '2024-01-10 09:30:00'),
('REF-2024-DEC-00001', 4, 2, 'deces', 'valide', 'Déclaration de décès de mon père', '2024-01-15 11:00:00'),
('REF-2024-MAR-00001', 5, 3, 'mariage', 'en_cours', 'Demande d\'acte de mariage', '2024-02-01 14:00:00'),
('REF-2024-NAI-00002', 5, NULL, 'naissance', 'soumis', 'Déclaration de naissance de ma fille', '2024-02-10 10:00:00'),
('REF-2024-DEC-00002', 4, NULL, 'deces', 'soumis', 'Déclaration de décès', '2024-02-15 16:00:00');

-- Actes de naissance
INSERT INTO `naissances` (`demande_id`, `nom_enfant`, `prenom_enfant`, `sexe`, `date_naissance`, `heure_naissance`, `lieu_naissance`, `centre_sante`, `nom_pere`, `prenom_pere`, `nationalite_pere`, `profession_pere`, `nom_mere`, `prenom_mere`, `nationalite_mere`, `profession_mere`, `nom_declarant`, `lien_declarant`, `numero_acte`) VALUES
(1, 'NKONO', 'Pierre-Emmanuel', 'M', '2024-01-05', '08:45:00', 'Douala', 'Hôpital Général de Douala', 'NKONO', 'Paul', 'Camerounaise', 'Ingénieur', 'MBOUA', 'Céleste', 'Camerounaise', 'Institutrice', 'NKONO Paul', 'Père', 'NAI-DLA-2024-001'),
(4, 'BIYONG', 'Fatou-Aimée', 'F', '2024-01-28', '14:20:00', 'Douala', 'Clinique de la Cathédrale', 'BIYONG', 'André', 'Camerounaise', 'Commerçant', 'BIYONG', 'Amina', 'Camerounaise', 'Enseignante', 'BIYONG Amina', 'Mère', NULL);

-- Actes de décès
INSERT INTO `deces` (`demande_id`, `nom_defunt`, `prenom_defunt`, `sexe`, `date_naissance_defunt`, `lieu_naissance_defunt`, `nationalite_defunt`, `profession_defunt`, `date_deces`, `lieu_deces`, `cause_deces`, `nom_declarant`, `lien_declarant`, `numero_acte`) VALUES
(2, 'NKONO', 'Martin', 'M', '1945-06-10', 'Bafia', 'Camerounaise', 'Retraité', '2024-01-13', 'Douala, Akwa', 'Cause naturelle', 'NKONO Paul', 'Fils', 'DEC-DLA-2024-001'),
(5, 'ESSAM', 'Bernadette', 'F', '1955-03-22', 'Ebolowa', 'Camerounaise', 'Ménagère', '2024-02-14', 'Douala, Bali', 'Maladie', 'NKONO Paul', 'Ami de la famille', NULL);

-- Actes de mariage
INSERT INTO `mariages` (`demande_id`, `nom_epoux`, `prenom_epoux`, `date_naissance_epoux`, `lieu_naissance_epoux`, `nationalite_epoux`, `profession_epoux`, `nom_epouse`, `prenom_epouse`, `date_naissance_epouse`, `lieu_naissance_epouse`, `nationalite_epouse`, `profession_epouse`, `date_mariage`, `lieu_mariage`, `regime_matrimonial`, `temoin1_nom`, `temoin2_nom`) VALUES
(3, 'KAMGA', 'Rodrigue', '1988-11-05', 'Bafoussam', 'Camerounaise', 'Médecin', 'BIYONG', 'Amina', '1992-07-22', 'Yaoundé', 'Camerounaise', 'Enseignante', '2024-01-27', 'Mairie de Douala 1er', 'communaute_biens', 'MBALLA Jean-Pierre', 'FOTSO Suzanne');

-- Notifications de démonstration
INSERT INTO `notifications` (`user_id`, `titre`, `message`, `type`, `lien`, `lu`) VALUES
(4, 'Demande validée', 'Votre demande d\'acte de naissance REF-2024-NAI-00001 a été validée. Vous pouvez télécharger votre acte.', 'succes', '/citoyen/mes-demandes.php', 0),
(4, 'Déclaration de décès confirmée', 'Votre déclaration de décès REF-2024-DEC-00001 a été enregistrée et validée.', 'succes', '/citoyen/mes-demandes.php', 1),
(5, 'Demande en cours de traitement', 'Votre demande de mariage REF-2024-MAR-00001 est en cours de traitement par nos agents.', 'info', '/citoyen/mes-demandes.php', 0),
(2, 'Nouvelle demande assignée', 'Une nouvelle demande (REF-2024-MAR-00001) vous a été assignée pour traitement.', 'info', '/agent/demandes.php', 0);

-- Historique des actions
INSERT INTO `historique_actions` (`user_id`, `action`, `description`, `entite`, `entite_id`, `ip_address`) VALUES
(1, 'connexion', 'Connexion administrateur au système', 'users', 1, '127.0.0.1'),
(2, 'validation_demande', 'Validation de la demande de naissance REF-2024-NAI-00001', 'demandes', 1, '127.0.0.1'),
(2, 'validation_demande', 'Validation de la déclaration de décès REF-2024-DEC-00001', 'demandes', 2, '127.0.0.1'),
(4, 'depot_demande', 'Dépôt d\'une nouvelle demande d\'acte de naissance', 'demandes', 4, '127.0.0.1'),
(5, 'depot_demande', 'Dépôt d\'une nouvelle demande de mariage', 'demandes', 3, '127.0.0.1');

-- ============================================================
-- NOTE IMPORTANTE : Mise à jour des mots de passe de démo
-- ============================================================
-- Après import, exécuter ce script PHP ou utiliser la commande SQL
-- Les hashes générés avec PHP password_hash():
-- admin@mairie.cm      => Admin@2024
-- agent@mairie.cm      => Agent@2024
-- agent2@mairie.cm     => Agent@2024
-- citoyen@example.cm   => Citoyen@2024
-- amina.biyong@gmail.com => Citoyen@2024
--
-- Pour recalculer les vrais hashes, lancer : php database/hash_passwords.php
-- ============================================================
