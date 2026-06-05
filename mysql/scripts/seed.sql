USE sql_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer toutes les lignes des tables :
TRUNCATE TABLE wishlist;
TRUNCATE TABLE business_review;
TRUNCATE TABLE application;
TRUNCATE TABLE offer_requirement;
TRUNCATE TABLE student_enrollment;
TRUNCATE TABLE promotion_assignment;
TRUNCATE TABLE internship_offer;
TRUNCATE TABLE skill;
TRUNCATE TABLE company_site;
TRUNCATE TABLE company;
TRUNCATE TABLE business_sector;
TRUNCATE TABLE promotion;
TRUNCATE TABLE campus;
TRUNCATE TABLE administrator;
TRUNCATE TABLE student;
TRUNCATE TABLE pilot;
TRUNCATE TABLE `user`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- USER MANAGEMENT
-- ============================================================

-- Comptes de démo importants :
-- admin@example.com      -> Admin1234!
-- pilot@example.com      -> Pilote1234!
-- pilot2@example.com     -> Pilote1234!
-- student1@example.com   -> Etudiant1234!
-- student2@example.com   -> EtudiantBis1234!
-- test@example.com       -> Test1234!
--
-- Autres comptes :
-- admins supplémentaires -> Admin1234!
-- pilotes supplémentaires -> Pilote1234!
-- étudiants supplémentaires -> Etudiant1234!

INSERT INTO `user` (
    id_user,
    email_user,
    password,
    first_name_user,
    last_name_user,
    is_active_user,
    created_at_user
) VALUES
      (1,  'admin@example.com',      '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Alice',     'Admin',     1, '2026-04-01 09:00:00'),
      (2,  'admin2@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Marc',      'Perrin',    1, '2026-04-01 09:01:00'),
      (3,  'admin3@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Julie',     'Renard',    1, '2026-04-01 09:02:00'),
      (4,  'admin4@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Thomas',    'Gillet',    1, '2026-04-01 09:03:00'),
      (5,  'admin5@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Camille',   'Noel',      1, '2026-04-01 09:04:00'),
      (6,  'admin6@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Hugo',      'Leroy',     1, '2026-04-01 09:05:00'),
      (7,  'admin7@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Nina',      'Boucher',   1, '2026-04-01 09:06:00'),
      (8,  'admin8@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Louis',     'Schmitt',   1, '2026-04-01 09:07:00'),
      (9,  'admin9@example.com',     '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Sarah',     'Dubois',    1, '2026-04-01 09:08:00'),
      (10, 'admin10@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Nicolas',   'Adam',      1, '2026-04-01 09:09:00'),
      (11, 'admin11@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Emma',      'Chevalier', 1, '2026-04-01 09:10:00'),
      (12, 'admin12@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Yanis',     'Roux',      1, '2026-04-01 09:11:00'),
      (13, 'admin13@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Lea',       'Garcia',    1, '2026-04-01 09:12:00'),
      (14, 'admin14@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Baptiste',  'Meyer',     1, '2026-04-01 09:13:00'),
      (15, 'admin15@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Chloe',     'Prevost',   1, '2026-04-01 09:14:00'),

      (16, 'pilot@example.com',      '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Paul',      'Pilote',    1, '2026-04-01 09:20:00'),
      (17, 'pilot2@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Claire',    'Durand',    1, '2026-04-01 09:21:00'),
      (18, 'pilot3@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Julien',    'Petit',     1, '2026-04-01 09:22:00'),
      (19, 'pilot4@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Sophie',    'Lambert',   1, '2026-04-01 09:23:00'),
      (20, 'pilot5@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Romain',    'Moreau',    1, '2026-04-01 09:24:00'),
      (21, 'pilot6@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Laura',     'Fischer',   1, '2026-04-01 09:25:00'),
      (22, 'pilot7@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Damien',    'Klein',     1, '2026-04-01 09:26:00'),
      (23, 'pilot8@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Marine',    'Simon',     1, '2026-04-01 09:27:00'),
      (24, 'pilot9@example.com',     '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Cedric',    'Marchand',  1, '2026-04-01 09:28:00'),
      (25, 'pilot10@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Aurelie',   'Muller',    1, '2026-04-01 09:29:00'),
      (26, 'pilot11@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Victor',    'Renaud',    1, '2026-04-01 09:30:00'),
      (27, 'pilot12@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Elise',     'Colin',     1, '2026-04-01 09:31:00'),
      (28, 'pilot13@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Mathieu',   'Mercier',   1, '2026-04-01 09:32:00'),
      (29, 'pilot14@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Lucie',     'Weber',     1, '2026-04-01 09:33:00'),
      (30, 'pilot15@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Antoine',   'Robin',     1, '2026-04-01 09:34:00'),

      (31, 'student1@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Sonia',     'Martin',    1, '2026-04-01 09:40:00'),
      (32, 'student2@example.com',   '$2y$12$cCi0YPr56ohxVUwoc3zmCOgxzdkEltmIlpuLpvPgWdK1NnCn0aiqi', 'Karim',     'Benali',    1, '2026-04-01 09:41:00'),
      (33, 'test@example.com',       '$2y$12$3jbLcI1FHArYwkjCTBFwvuIwstxRulE62PhLwCZuxWpKpw40l95UC', 'Test',      'User',      1, '2026-04-01 09:42:00'),
      (34, 'student4@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Manon',     'Lopez',     1, '2026-04-01 09:43:00'),
      (35, 'student5@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Youssef',   'Amrani',    1, '2026-04-01 09:44:00'),
      (36, 'student6@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Julie',     'Nguyen',    1, '2026-04-01 09:45:00'),
      (37, 'student7@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Leo',       'Masson',    1, '2026-04-01 09:46:00'),
      (38, 'student8@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Ines',      'Da Silva',  1, '2026-04-01 09:47:00'),
      (39, 'student9@example.com',   '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Noah',      'Caron',     1, '2026-04-01 09:48:00'),
      (40, 'student10@example.com',  '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Lea',       'Benoit',    1, '2026-04-01 09:49:00'),
      (41, 'student11@example.com',  '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Bilal',     'Saidi',     1, '2026-04-01 09:50:00'),
      (42, 'student12@example.com',  '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Elsa',      'Moulin',    1, '2026-04-01 09:51:00'),
      (43, 'student13@example.com',  '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Tom',       'Gilbert',   1, '2026-04-01 09:52:00'),
      (44, 'student14@example.com',  '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Sarah',     'Jacquet',   1, '2026-04-01 09:53:00'),
      (45, 'student15@example.com',  '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Adam',      'Rivière',   1, '2026-04-01 09:54:00');

INSERT INTO administrator (id_administrator) VALUES
                                                 (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15);

INSERT INTO pilot (id_pilot) VALUES
                                 (16),(17),(18),(19),(20),(21),(22),(23),(24),(25),(26),(27),(28),(29),(30);

INSERT INTO student (id_student, status_student) VALUES
                                                     (31, 'searching'),
                                                     (32, 'hired'),
                                                     (33, 'searching'),
                                                     (34, 'searching'),
                                                     (35, 'inactive'),
                                                     (36, 'searching'),
                                                     (37, 'hired'),
                                                     (38, 'searching'),
                                                     (39, 'searching'),
                                                     (40, 'inactive'),
                                                     (41, 'searching'),
                                                     (42, 'searching'),
                                                     (43, 'hired'),
                                                     (44, 'searching'),
                                                     (45, 'searching');

-- ============================================================
-- ORGANIZATION
-- ============================================================

INSERT INTO campus (id_campus, name_campus, address_campus) VALUES
                                                                (1,  'CESI Nancy',          '8 rue de la Grande Oye, 54500 Vandœuvre-lès-Nancy'),
                                                                (2,  'CESI Strasbourg',     '2 allée des Foulons, 67380 Lingolsheim'),
                                                                (3,  'CESI Reims',          '7 bis avenue Robert Schuman, 51100 Reims'),
                                                                (4,  'CESI Lyon',           '19 rue d''Athènes, 69100 Villeurbanne'),
                                                                (5,  'CESI Paris Nanterre', '93 boulevard de la Seine, 92000 Nanterre'),
                                                                (6,  'CESI Dijon',          '22 boulevard Winston Churchill, 21000 Dijon'),
                                                                (7,  'CESI Lille',          '7 rue des Châteaux, 59200 Tourcoing'),
                                                                (8,  'CESI Rouen',          '1 rue Marconi, 76130 Mont-Saint-Aignan'),
                                                                (9,  'CESI Brest',          '465 rue de Kerlaurent, 29200 Brest'),
                                                                (10, 'CESI Bordeaux',       '8 rue des Frères Caudron, 33000 Bordeaux'),
                                                                (11, 'CESI Toulouse',       '16 rue Magellan, 31670 Labège'),
                                                                (12, 'CESI Montpellier',    '169 rue Georges Auric, 34000 Montpellier'),
                                                                (13, 'CESI Nice',           '124 avenue Maurice Donat, 06250 Mougins'),
                                                                (14, 'CESI Aix-en-Provence','220 rue Denis Papin, 13100 Aix-en-Provence'),
                                                                (15, 'CESI Arras',          '5 avenue des Peupliers, 62000 Arras');

INSERT INTO promotion (id_promotion, label_promotion, academic_year_promotion, campus_id_promotion) VALUES
                                                                                                        (1,  'CPI A2 info',          '2025-2026', 1),
                                                                                                        (2,  'CPI A2 info',          '2024-2025', 1),
                                                                                                        (3,  'CPI A2 généraliste',   '2025-2026', 1),
                                                                                                        (4,  'CPI A2 généraliste',   '2024-2025', 1),
                                                                                                        (5,  'FISA info',            '2024-2027', 2),
                                                                                                        (6,  'B3 informatique',      '2025-2026', 2),
                                                                                                        (7,  'Mastère MSI',          '2025-2026', 3),
                                                                                                        (8,  'Bachelor IT',          '2025-2026', 4),
                                                                                                        (9,  'CPI A1 info',          '2025-2026', 5),
                                                                                                        (10, 'CPI A1 généraliste',   '2025-2026', 6),
                                                                                                        (11, 'B2 informatique',      '2025-2026', 7),
                                                                                                        (12, 'B3 data',              '2025-2026', 8),
                                                                                                        (13, 'Mastère cybersécurité','2025-2026', 9),
                                                                                                        (14, 'Bachelor cloud',       '2025-2026', 10),
                                                                                                        (15, 'FISA généraliste',     '2024-2027', 11);

-- ============================================================
-- BUSINESS & OFFERS
-- ============================================================

INSERT INTO business_sector (id_business_sector, name_business_sector, description_business_sector) VALUES
                                                                                                        (1,  'Développement web',        'Applications web, interfaces, plateformes métiers'),
                                                                                                        (2,  'Industrie',                'Production, maintenance, SI industriels'),
                                                                                                        (3,  'Réseaux & cybersécurité',  'Infrastructure, sécurité, administration'),
                                                                                                        (4,  'Cloud & DevOps',           'Services cloud, CI/CD, automatisation'),
                                                                                                        (5,  'Data & BI',                'Data engineering, reporting, visualisation'),
                                                                                                        (6,  'Logiciels embarqués',      'Développement bas niveau et systèmes embarqués'),
                                                                                                        (7,  'Télécommunications',       'Télécoms, services connectés, réseau'),
                                                                                                        (8,  'Santé numérique',          'Applications et systèmes pour la santé'),
                                                                                                        (9,  'Banque & assurance',       'Outils métiers, conformité, SI bancaires'),
                                                                                                        (10, 'E-commerce',               'Plateformes de vente et logistique digitale'),
                                                                                                        (11, 'Éducation numérique',      'Outils pédagogiques et plateformes de formation'),
                                                                                                        (12, 'Énergie',                  'Systèmes d’information pour l’énergie'),
                                                                                                        (13, 'Transport & mobilité',     'Applications mobilité et optimisation'),
                                                                                                        (14, 'Conseil IT',               'Prestations, audit et transformation digitale'),
                                                                                                        (15, 'SaaS B2B',                 'Solutions logicielles en ligne pour entreprises');

INSERT INTO company (
    id_company,
    name_company,
    description_company,
    email_company,
    phone_company,
    tax_id_company,
    is_active_company,
    sector_id_company,
    created_at_company
) VALUES
      (1,  'TechNova',         'Entreprise spécialisée en développement web et outils métiers.',                        'contact@technova.fr',       '0383000001', '54200000000001', 1, 1,  '2026-04-01 10:00:00'),
      (2,  'Rhein Systems',    'Entreprise industrielle avec une équipe IT interne.',                                   'hr@rheinsystems.fr',       '0383000002', '54200000000002', 1, 2,  '2026-04-01 10:05:00'),
      (3,  'CloudPulse',       'Société orientée SaaS, DevOps et cloud.',                                               'jobs@cloudpulse.fr',       '0383000003', '54200000000003', 1, 15, '2026-04-01 10:10:00'),
      (4,  'NetSecure',        'Prestataire réseau et cybersécurité pour PME et collectivités.',                        'contact@netsecure.fr',     '0383000004', '54200000000004', 1, 3,  '2026-04-01 10:15:00'),
      (5,  'DataForge',        'Cabinet orienté data engineering, reporting et automatisation.',                        'careers@dataforge.fr',     '0383000005', '54200000000005', 1, 5,  '2026-04-01 10:20:00'),
      (6,  'EduSoft',          'Éditeur de solutions numériques pour l’enseignement supérieur.',                        'rh@edusoft.fr',            '0383000006', '54200000000006', 1, 11, '2026-04-01 10:25:00'),
      (7,  'Mobility Labs',    'Entreprise innovante dans les applications de transport et mobilité.',                  'jobs@mobilitylabs.fr',     '0383000007', '54200000000007', 1, 13, '2026-04-01 10:30:00'),
      (8,  'FinAxis',          'Acteur du logiciel bancaire et conformité réglementaire.',                              'recrutement@finaxis.fr',   '0383000008', '54200000000008', 1, 9,  '2026-04-01 10:35:00'),
      (9,  'MediConnect',      'Solutions numériques pour établissements de santé.',                                    'contact@mediconnect.fr',   '0383000009', '54200000000009', 1, 8,  '2026-04-01 10:40:00'),
      (10, 'Telecomia',        'Entreprise de services réseaux et télécommunications.',                                 'jobs@telecomia.fr',        '0383000010', '54200000000010', 1, 7,  '2026-04-01 10:45:00'),
      (11, 'GreenGrid',        'Solutions numériques pour la gestion énergétique.',                                     'careers@greengrid.fr',     '0383000011', '54200000000011', 1, 12, '2026-04-01 10:50:00'),
      (12, 'Embedded Works',   'Développement de logiciels embarqués pour l’industrie.',                                'hr@embeddedworks.fr',      '0383000012', '54200000000012', 1, 6,  '2026-04-01 10:55:00'),
      (13, 'RetailFlow',       'Entreprise e-commerce et optimisation logistique.',                                     'contact@retailflow.fr',    '0383000013', '54200000000013', 1, 10, '2026-04-01 11:00:00'),
      (14, 'Infra Conseil',    'Cabinet de conseil IT, audit, architecture et modernisation SI.',                      'jobs@infraconseil.fr',     '0383000014', '54200000000014', 1, 14, '2026-04-01 11:05:00'),
      (15, 'AeroMind',         'Entreprise tech intervenant sur des outils d’analyse et supervision pour l’aéronautique.', 'rh@aeromind.fr',        '0383000015', '54200000000015', 1, 4,  '2026-04-01 11:10:00');

INSERT INTO company_site (
    id_company_site,
    address_company_site,
    city_company_site,
    company_id_company_site
) VALUES
      (1,  '10 rue Saint-Georges',            'Nancy',       1),
      (2,  '22 route de Bischwiller',         'Haguenau',    2),
      (3,  '7 quai Kléber',                   'Strasbourg',  3),
      (4,  '14 boulevard Lobau',              'Nancy',       4),
      (5,  '3 rue Gambetta',                  'Reims',       5),
      (6,  '15 rue de la République',         'Lyon',        6),
      (7,  '6 avenue du Rhin',                'Mulhouse',    7),
      (8,  '18 rue de la Bourse',             'Paris',       8),
      (9,  '4 place Wilson',                  'Dijon',       9),
      (10, '12 boulevard Carnot',             'Lille',       10),
      (11, '25 rue de la Gare',               'Metz',        11),
      (12, '9 avenue Albert 1er',             'Toulouse',    12),
      (13, '11 rue des Tisserands',           'Rouen',       13),
      (14, '2 allée des Entrepreneurs',       'Bordeaux',    14),
      (15, '48 avenue Jean Monnet',           'Montpellier', 15);

INSERT INTO skill (id_skill, label_skill) VALUES
                                              (1,  'PHP'),
                                              (2,  'MySQL'),
                                              (3,  'Docker'),
                                              (4,  'Twig'),
                                              (5,  'JavaScript'),
                                              (6,  'Linux'),
                                              (7,  'Réseau'),
                                              (8,  'Cybersécurité'),
                                              (9,  'Git'),
                                              (10, 'API REST'),
                                              (11, 'Python'),
                                              (12, 'Java'),
                                              (13, 'CI/CD'),
                                              (14, 'HTML/CSS'),
                                              (15, 'Data visualisation');

INSERT INTO internship_offer (
    id_internship_offer,
    title_internship_offer,
    description_internship_offer,
    hourly_rate_internship_offer,
    start_date_internship_offer,
    duration_weeks_internship_offer,
    is_active_internship_offer,
    published_at_internship_offer,
    company_site_id_internship_offer
) VALUES
      (1,  'Stage Développeur PHP/Twig',            'Participation au développement d’une plateforme interne MVC.',                             5.50, '2026-05-02', 10, 1, '2026-04-01 11:30:00', 1),
      (2,  'Stage Support IT Industriel',           'Support utilisateurs et maintenance du parc informatique.',                                  5.20, '2026-05-10',  8, 1, '2026-04-01 11:35:00', 2),
      (3,  'Stage Développeur Full Stack',          'Développement front/back sur une application SaaS.',                                         6.00, '2026-06-01', 16, 1, '2026-04-01 11:40:00', 3),
      (4,  'Stage Réseau & Cybersécurité',          'Audit réseau, durcissement de postes et supervision sécurité.',                              5.90, '2026-05-25', 10, 1, '2026-04-01 11:45:00', 4),
      (5,  'Stage Data / Reporting',                'Création de tableaux de bord, automatisation de flux et reporting.',                         5.70, '2026-05-20', 14, 1, '2026-04-01 11:50:00', 5),
      (6,  'Stage Développeur EdTech',              'Évolution d’une plateforme d’apprentissage et outils de suivi pédagogique.',                5.60, '2026-05-18', 12, 1, '2026-04-01 11:55:00', 6),
      (7,  'Stage Application Mobilité',            'Développement d’un module de suivi de trajets et optimisation d’itinéraires.',             5.65, '2026-05-22', 12, 1, '2026-04-01 12:00:00', 7),
      (8,  'Stage QA Banque & API',                 'Tests fonctionnels et API sur une application bancaire.',                                   5.75, '2026-05-19', 10, 1, '2026-04-01 12:05:00', 8),
      (9,  'Stage Web Santé',                       'Développement de modules pour portail de suivi patient.',                                   5.80, '2026-05-26', 14, 1, '2026-04-01 12:10:00', 9),
      (10, 'Stage Télécom & Supervision',           'Supervision réseau, métrologie et support d’exploitation.',                                5.85, '2026-05-28', 10, 1, '2026-04-01 12:15:00', 10),
      (11, 'Stage Outils Énergie',                  'Développement d’un tableau de bord de consommation et maintenance.',                        5.60, '2026-06-02', 13, 1, '2026-04-01 12:20:00', 11),
      (12, 'Stage Développement Embarqué',          'Développement et validation de modules logiciels pour systèmes embarqués.',                5.95, '2026-05-30', 15, 1, '2026-04-01 12:25:00', 12),
      (13, 'Stage E-commerce & Logistique',         'Amélioration d’un outil de suivi de commandes et de stocks.',                              5.55, '2026-05-21', 11, 1, '2026-04-01 12:30:00', 13),
      (14, 'Stage Audit Infrastructure',            'Participation à des audits techniques et documentation d’architecture.',                    5.90, '2026-06-03', 12, 1, '2026-04-01 12:35:00', 14),
      (15, 'Stage DevOps & Observabilité',          'Mise en place de pipelines CI/CD et supervision applicative.',                              6.10, '2026-06-05', 16, 1, '2026-04-01 12:40:00', 15);

-- ============================================================
-- ASSOCIATIVE TABLES
-- ============================================================

INSERT INTO promotion_assignment (
    promotion_assignment_id,
    pilot_assignment_id,
    assigned_at
) VALUES
      (1, 16, '2026-03-01 09:00:00'),
      (2, 17, '2026-03-01 09:10:00'),
      (3, 18, '2026-03-01 09:20:00'),
      (4, 19, '2026-03-01 09:30:00'),
      (5, 20, '2026-03-01 09:40:00'),
      (6, 21, '2026-03-01 09:50:00'),
      (7, 22, '2026-03-01 10:00:00'),
      (8, 23, '2026-03-01 10:10:00'),
      (9, 24, '2026-03-01 10:20:00'),
      (10, 25, '2026-03-01 10:30:00'),
      (11, 26, '2026-03-01 10:40:00'),
      (12, 27, '2026-03-01 10:50:00'),
      (13, 28, '2026-03-01 11:00:00'),
      (14, 29, '2026-03-01 11:10:00'),
      (15, 30, '2026-03-01 11:20:00');

INSERT INTO student_enrollment (
    promotion_id_student_enrollment,
    student_id_student_enrollment,
    enrolled_at
) VALUES
      (1, 31, '2025-09-01'),
      (1, 32, '2025-09-01'),
      (2, 33, '2024-09-01'),
      (3, 34, '2025-09-01'),
      (4, 35, '2024-09-01'),
      (5, 36, '2024-09-01'),
      (6, 37, '2025-09-01'),
      (7, 38, '2025-09-01'),
      (8, 39, '2025-09-01'),
      (9, 40, '2025-09-01'),
      (10, 41, '2025-09-01'),
      (11, 42, '2025-09-01'),
      (12, 43, '2025-09-01'),
      (13, 44, '2025-09-01'),
      (14, 45, '2025-09-01');

INSERT INTO offer_requirement (
    offer_requirement_id,
    skill_requirement_id
) VALUES
      (1,1),(1,2),(1,4),
      (2,6),(2,9),(2,7),
      (3,1),(3,5),(3,10),
      (4,7),(4,8),(4,6),
      (5,2),(5,15),(5,10),
      (6,1),(6,4),(6,14),
      (7,5),(7,10),(7,9),
      (8,10),(8,5),(8,9),
      (9,1),(9,14),(9,10),
      (10,7),(10,6),(10,13),
      (11,2),(11,15),(11,5),
      (12,6),(12,11),(12,12),
      (13,5),(13,2),(13,14),
      (14,6),(14,9),(14,7),
      (15,3),(15,13),(15,10);

INSERT INTO application (
    id_application,
    student_id_application,
    offer_id_application,
    cv_path_application,
    cover_letter_path_application,
    status_application,
    applied_at_application
) VALUES
      (1,  31, 1,  '/uploads/cv/student1_cv.pdf',  '/uploads/letters/student1_lm_1.pdf',  'pending',  '2026-04-01 14:00:00'),
      (2,  31, 4,  '/uploads/cv/student1_cv.pdf',  '/uploads/letters/student1_lm_2.pdf',  'accepted', '2026-04-02 09:30:00'),
      (3,  32, 3,  '/uploads/cv/student2_cv.pdf',  '/uploads/letters/student2_lm_1.pdf',  'rejected', '2026-04-02 10:15:00'),
      (4,  33, 2,  '/uploads/cv/test_cv.pdf',      '/uploads/letters/test_lm_1.pdf',      'pending',  '2026-04-03 11:45:00'),
      (5,  33, 5,  '/uploads/cv/test_cv.pdf',      '/uploads/letters/test_lm_2.pdf',      'pending',  '2026-04-04 16:20:00'),
      (6,  34, 6,  '/uploads/cv/student4_cv.pdf',  '/uploads/letters/student4_lm_1.pdf',  'pending',  '2026-04-05 09:10:00'),
      (7,  35, 7,  '/uploads/cv/student5_cv.pdf',  '/uploads/letters/student5_lm_1.pdf',  'accepted', '2026-04-05 10:00:00'),
      (8,  36, 8,  '/uploads/cv/student6_cv.pdf',  '/uploads/letters/student6_lm_1.pdf',  'pending',  '2026-04-05 11:15:00'),
      (9,  37, 9,  '/uploads/cv/student7_cv.pdf',  '/uploads/letters/student7_lm_1.pdf',  'rejected', '2026-04-05 12:30:00'),
      (10, 38, 10, '/uploads/cv/student8_cv.pdf',  '/uploads/letters/student8_lm_1.pdf',  'pending',  '2026-04-06 09:40:00'),
      (11, 39, 11, '/uploads/cv/student9_cv.pdf',  '/uploads/letters/student9_lm_1.pdf',  'accepted', '2026-04-06 10:25:00'),
      (12, 40, 12, '/uploads/cv/student10_cv.pdf', '/uploads/letters/student10_lm_1.pdf', 'pending',  '2026-04-06 11:05:00'),
      (13, 41, 13, '/uploads/cv/student11_cv.pdf', '/uploads/letters/student11_lm_1.pdf', 'rejected', '2026-04-06 13:20:00'),
      (14, 42, 14, '/uploads/cv/student12_cv.pdf', '/uploads/letters/student12_lm_1.pdf', 'pending',  '2026-04-06 14:10:00'),
      (15, 43, 15, '/uploads/cv/student13_cv.pdf', '/uploads/letters/student13_lm_1.pdf', 'accepted', '2026-04-06 15:35:00');

INSERT INTO business_review (
    pilot_id_business_review,
    company_id_business_review,
    rating_business_review,
    comment_business_review,
    reviewed_at_business_review
) VALUES
      (16, 1, 5, 'Très bon partenaire, encadrement sérieux.',                          '2026-03-20 10:00:00'),
      (17, 2, 4, 'Bonne entreprise, missions intéressantes.',                           '2026-03-20 11:00:00'),
      (18, 3, 5, 'Excellent environnement technique et outils modernes.',               '2026-03-20 12:00:00'),
      (19, 4, 4, 'Très adaptée aux profils systèmes et réseau.',                        '2026-03-20 13:00:00'),
      (20, 5, 4, 'Bonne opportunité pour les étudiants orientés data.',                 '2026-03-20 14:00:00'),
      (21, 6, 5, 'Très bonne qualité de tutorat et projets concrets.',                  '2026-03-21 09:00:00'),
      (22, 7, 4, 'Missions motivantes, bonne ambiance de travail.',                     '2026-03-21 10:00:00'),
      (23, 8, 3, 'Environnement exigeant mais formateur.',                              '2026-03-21 11:00:00'),
      (24, 9, 4, 'Bonne structure d’accueil pour des profils web.',                     '2026-03-21 12:00:00'),
      (25,10, 4, 'Sujet intéressant pour les étudiants réseau.',                        '2026-03-21 13:00:00'),
      (26,11, 5, 'Très bon accompagnement sur les outils métiers.',                     '2026-03-21 14:00:00'),
      (27,12, 4, 'Missions techniques bien cadrées.',                                   '2026-03-22 09:00:00'),
      (28,13, 4, 'Entreprise réactive et dynamique.',                                   '2026-03-22 10:00:00'),
      (29,14, 5, 'Audit très formateur, bonne qualité documentaire.',                   '2026-03-22 11:00:00'),
      (30,15, 5, 'Très bon contexte DevOps et observabilité.',                          '2026-03-22 12:00:00'),
      -- Avis posté par des ADMINISTRATEURS (id 1-15) : démontre que les admins peuvent aussi noter
      (1, 1, 4, 'Vu en tant qu''administrateur : entreprise fiable et conventions à jour.', '2026-03-23 09:00:00'),
      (3, 2, 5, 'Validation administrative : excellent suivi des stagiaires.',              '2026-03-23 10:00:00');

INSERT INTO wishlist (
    student_id_wishlist,
    offer_id_wishlist,
    saved_at_wishlist
) VALUES
      (31, 3,  '2026-04-01 18:00:00'),
      (31, 5,  '2026-04-02 18:05:00'),
      (32, 1,  '2026-04-02 18:10:00'),
      (33, 2,  '2026-04-03 18:15:00'),
      (33, 6,  '2026-04-03 18:20:00'),
      (34, 7,  '2026-04-03 18:25:00'),
      (35, 4,  '2026-04-03 18:30:00'),
      (36, 8,  '2026-04-03 18:35:00'),
      (37, 9,  '2026-04-03 18:40:00'),
      (38, 10, '2026-04-03 18:45:00'),
      (39, 11, '2026-04-03 18:50:00'),
      (40, 12, '2026-04-03 18:55:00'),
      (41, 13, '2026-04-03 19:00:00'),
      (42, 14, '2026-04-03 19:05:00'),
      (43, 15, '2026-04-03 19:10:00');
