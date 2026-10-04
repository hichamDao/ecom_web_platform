-- =====================================================================
--  EcomWeb Platform - Jeu de données de démonstration
--  À importer APRÈS schema.sql
--
--  Comptes de démonstration :
--    admin@ecomweb.ma   / admin123     (administrateur)
--    client@ecomweb.ma  / password123  (client)
-- =====================================================================

USE ecom_platform;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE notifications;
TRUNCATE TABLE order_status_history;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE cart_items;
TRUNCATE TABLE carts;
TRUNCATE TABLE coupons;
TRUNCATE TABLE wishlists;
TRUNCATE TABLE addresses;
TRUNCATE TABLE reviews;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
TRUNCATE TABLE settings;
TRUNCATE TABLE promo_banners;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Utilisateurs (mot de passe hashé avec password_hash / BCRYPT)
-- ---------------------------------------------------------------------
INSERT INTO users (id, username, email, password, first_name, last_name, phone, role, created_at) VALUES
(1, 'admin',    'admin@ecomweb.ma',   '$2y$10$6CwWjRMAG3/bK2Oqaqisk.9uSTcbpoW697dUnflFJ/f9wV4JzMmwW', 'Amine',  'Berrada',  '+212 661 00 11 22', 'ADMIN',    '2026-01-05 09:00:00'),
(2, 'client',   'client@ecomweb.ma',  '$2y$10$uYUDSws4H.M5vRz6c7J1wuf6lNiPOjh2KoAlx7xo2C.gd8C8o3uM2', 'Yasmine', 'El Amrani', '+212 662 33 44 55', 'CUSTOMER', '2026-02-11 10:15:00'),
(3, 'sarah',    'sarah@ecomweb.ma',   '$2y$10$uYUDSws4H.M5vRz6c7J1wuf6lNiPOjh2KoAlx7xo2C.gd8C8o3uM2', 'Sarah',  'Benali',   '+212 663 55 66 77', 'CUSTOMER', '2026-03-02 14:40:00'),
(4, 'youssef',  'youssef@ecomweb.ma', '$2y$10$uYUDSws4H.M5vRz6c7J1wuf6lNiPOjh2KoAlx7xo2C.gd8C8o3uM2', 'Youssef','Idrissi',  '+212 664 77 88 99', 'CUSTOMER', '2026-04-19 08:30:00');

-- ---------------------------------------------------------------------
-- Catégories
-- ---------------------------------------------------------------------
INSERT INTO categories (id, name, description, image, parent_id) VALUES
(1, 'Homme',      'Vêtements et accessoires pour homme',     'https://picsum.photos/seed/cat-homme/400/400',     NULL),
(2, 'Femme',      'Tendances et essentiels pour femme',      'https://picsum.photos/seed/cat-femme/400/400',     NULL),
(3, 'High-tech',  'Smartphones, ordinateurs et accessoires',  'https://picsum.photos/seed/cat-tech/400/400',      NULL),
(4, 'Beauté',     'Soins, parfums et cosmétiques',          'https://picsum.photos/seed/cat-beaute/400/400',   NULL),
(5, 'Maison',     'Décoration et art de vivre',              'https://picsum.photos/seed/cat-maison/400/400',    NULL),
(6, 'Sport',      'Vêtements et matériel de sport',          'https://picsum.photos/seed/cat-sport/400/400',     NULL),
(7, 'Chemises',   'Chemises et polos homme',       'https://picsum.photos/seed/cat-chemise/300/300', 1),
(8, 'Robes',      'Robes et tuniques femme',       'https://picsum.photos/seed/cat-robe/300/300',    2),
(9, 'Audio',      'Casques et enceintes',         'https://picsum.photos/seed/cat-audio/300/300',    3);

-- ---------------------------------------------------------------------
-- Produits
-- ---------------------------------------------------------------------
INSERT INTO products
(id, name, description, price, old_price, discount_percentage, stock, thumbnail, images, attributes, brand, is_featured, is_new, overlay_image, category_id, sold_count, views) VALUES
(1, 'Chemise lin Premium', 'Chemise en lin lavé, coupe régulière, idéale pour les journées chaudes. Boutons nacrés et lavage en machine.', 249.00, 349.00, 28.65, 34, 'https://picsum.photos/seed/p1/600/600', '["https://picsum.photos/seed/p1b/600/600","https://picsum.photos/seed/p1c/600/600"]', '{"couleur":"Blanc cassé","taille":["M","L","XL"],"matiere":"Lin 100%"}', 'Linora', 1, 1, NULL, 7, 42, 610),
(2, 'Polo piqué coton', 'Polo en coton piqué 220g, coupe slim, bandes rayées aux manches. Très résistant au lavage.', 189.00, NULL, NULL, 58, 'https://picsum.photos/seed/p2/600/600', '["https://picsum.photos/seed/p2b/600/600"]', '{"couleur":"Navy","taille":["S","M","L"],"matiere":"Coton piqué"}', 'UrbanFit', 0, 0, NULL, 7, 96, 388),
(3, 'Veste blazer structurée', 'Blazer deux boutons en tissu Mélange, un basique élégant qui se porte au bureau comme en soirée.', 899.00, 1299.00, 30.75, 12, 'https://picsum.photos/seed/p3/600/600', '["https://picsum.photos/seed/p3b/600/600","https://picsum.photos/seed/p3c/600/600"]', '{"couleur":"Gris chiné","taille":["48","50","52"],"matiere":"Polyester laine"}', 'UrbanFit', 1, 0, NULL, 1, 31, 254),
(4, 'Jean slim délavé', 'Jean slim en denim italien, délavage léger sur les cuisses, coupe moderne.', 399.00, NULL, NULL, 40, 'https://picsum.photos/seed/p4/600/600', '["https://picsum.photos/seed/p4b/600/600"]', '{"couleur":"Bleu délavé","taille":["30","32","34","36"],"matiere":"Denim 12oz"}', 'DenimCo', 0, 1, NULL, 1, 78, 441),
(5, 'Sneakers running Air', 'Sneakers légères avec semelle amortissante et tige respirante. Idéales pour le running quotidien.', 749.00, 999.00, 25.03, 26, 'https://picsum.photos/seed/p5/600/600', '["https://picsum.photos/seed/p5b/600/600","https://picsum.photos/seed/p5c/600/600"]', '{"couleur":"Blanc / Noir","pointure":["41","42","43","44"]}', 'RunFast', 1, 1, 'https://picsum.photos/seed/p5-overlay/600/600', 6, 134, 812),
(6, 'Robe midi fleurie', 'Robe midi en viscose, imprimé floral, ceinture à nouer inclus.', 349.00, NULL, NULL, 22, 'https://picsum.photos/seed/p6/600/600', '["https://picsum.photos/seed/p6b/600/600"]', '{"couleur":"Rose poudré","taille":["S","M","L"],"matiere":"Viscose"}', 'Lunelle', 0, 0, NULL, 8, 64, 377),
(7, 'Sac à main cuir', 'Sac en cuir pleine fleur avec bandoulière amovible et poche zippée intérieure.', 1299.00, 1699.00, 23.54, 9, 'https://picsum.photos/seed/p7/600/600', '["https://picsum.photos/seed/p7b/600/600","https://picsum.photos/seed/p7c/600/600"]', '{"couleur":"Cognac","materiau":"Cuir pleine fleur"}', 'Artisan', 1, 0, NULL, 2, 18, 290),
(8, 'Escarpins classiques', 'Escarpins à bout pointu, talon 8cm, semelle cushioned.', 549.00, NULL, NULL, 15, 'https://picsum.photos/seed/p8/600/600', '["https://picsum.photos/seed/p8b/600/600"]', '{"couleur":"Noir","pointure":["37","38","39"],"talon":"8cm"}', 'Lunelle', 0, 0, NULL, 2, 27, 203),
(9, 'Smartphone Nova X Pro', 'Smartphone 6.7" AMOLED, 256 Go, triple capteur 50 MP et charge rapide 67 W.', 6499.00, 7499.00, 13.35, 15, 'https://picsum.photos/seed/p9/600/600', '["https://picsum.photos/seed/p9b/600/600","https://picsum.photos/seed/p9c/600/600"]', '{"couleur":"Titane","stockage":"256 Go","ecran":"6.7 AMOLED"}', 'NovaTech', 1, 1, NULL, 3, 88, 1240),
(10, 'Ordinateur portable Air 14', 'PC portable 14" Full HD, 16 Go RAM, 512 Go SSD, Intel Core i5, 7h d autonomie.', 7299.00, NULL, NULL, 8, 'https://picsum.photos/seed/p10/600/600', '["https://picsum.photos/seed/p10b/600/600"]', '{"couleur":"Argent","ram":"16 Go","ssd":"512 Go"}', 'NovaTech', 0, 1, NULL, 3, 31, 720),
(11, 'Casque sans fil ANC', 'Casque Bluetooth avec réduction de bruit active, 40h d autonomie et boîtier de charge.', 1299.00, 1699.00, 23.54, 33, 'https://picsum.photos/seed/p11/600/600', '["https://picsum.photos/seed/p11b/600/600","https://picsum.photos/seed/p11c/600/600"]', '{"couleur":"Noir mat","autonomie":"40 h"}', 'SoundMax', 1, 0, NULL, 9, 112, 655),
(12, 'Enceinte Bluetooth Etanche', 'Enceinte portable certifiée IPX7, basses renforcées et appairage stéréo.', 499.00, NULL, NULL, 47, 'https://picsum.photos/seed/p12/600/600', '["https://picsum.photos/seed/p12b/600/600"]', '{"couleur":"Bleu","puissance":"20 W"}', 'SoundMax', 0, 0, NULL, 9, 69, 305),
(13, 'Montre connectée Fit Pro', 'Monture sport avec GPS, suivi cardiaque et 10 jours d autonomie.', 899.00, 1199.00, 25.02, 29, 'https://picsum.photos/seed/p13/600/600', '["https://picsum.photos/seed/p13b/600/600"]', '{"couleur":"Graphite","bracelet":"Silicone"}', 'FitLife', 0, 1, NULL, 3, 52, 410),
(14, 'Sérum visage vitamine C', 'Sérum concentré à 15% de vitamine C, éclaircit les taches et unifie le teint.', 289.00, 379.00, 23.72, 64, 'https://picsum.photos/seed/p14/600/600', '["https://picsum.photos/seed/p14b/600/600"]', '{"volume":"30 ml","peau":"Tous types"}', 'DermaPure', 1, 0, NULL, 4, 156, 902),
(15, 'Parfum Nuit Étoilée', 'Eau de parfum boisée ambrée, tenue 8h,notes de tête bergamote et fond cuir.', 749.00, NULL, NULL, 21, 'https://picsum.photos/seed/p15/600/600', '["https://picsum.photos/seed/p15b/600/600"]', '{"volume":"100 ml","famille":"Boise ambrée"}', 'Essence', 0, 1, NULL, 4, 44, 356),
(16, 'Crème hydratante intense', 'Crème visage 24h à l acide hyaluronique et céramides, texture fondante non grasse.', 199.00, 259.00, 23.17, 80, 'https://picsum.photos/seed/p16/600/600', '["https://picsum.photos/seed/p16b/600/600"]', '{"volume":"50 ml","peau":"Sèche à normale"}', 'DermaPure', 0, 0, NULL, 4, 203, 588),
(17, 'Lampe designer Minimal', 'Lampe d appoint en métal brossé avec variateur de luminosité intégré.', 449.00, 599.00, 25.04, 18, 'https://picsum.photos/seed/p17/600/600', '["https://picsum.photos/seed/p17b/600/600","https://picsum.photos/seed/p17c/600/600"]', '{"couleur":"Laiton","ampoule":"E27 incluse"}', 'MaisonNine', 1, 0, NULL, 5, 24, 241),
(18, 'Set 4 tasses en céramique', 'Tasses faites main en céramique émaillée, tossed avec 4 mugs assortis.', 329.00, NULL, NULL, 27, 'https://picsum.photos/seed/p18/600/600', '["https://picsum.photos/seed/p18b/600/600"]', '{"matiere":"Céramique émaillée","contenance":"4 x 320 ml"}', 'MaisonNine', 0, 1, NULL, 5, 37, 189),
(19, 'Tableau abstrait 90x120', 'Canvas imprimé sur toile, cadre bois naturel.', 799.00, NULL, NULL, 10, 'https://picsum.photos/seed/p19/600/600', '["https://picsum.photos/seed/p19b/600/600"]', '{"dimensions":"90 x 120 cm","cadre":"Chêne naturel"}', 'AtelierHome', 0, 0, NULL, 5, 11, 156),
(20, 'Tapis berbère tissé', 'Tapis 100% laine avec motifs berbères, 160x230cm.', 1899.00, 2499.00, 24.01, 6, 'https://picsum.photos/seed/p20/600/600', '["https://picsum.photos/seed/p20b/600/600","https://picsum.photos/seed/p20c/600/600"]', '{"dimensions":"160 x 230 cm","matiere":"Laine"}', 'AtlasDecor', 1, 0, NULL, 5, 7, 214),
(21, 'Veste technique-vent', 'Veste 3 couches imperméable, coutures étanches et capuche amovible.', 1199.00, 1599.00, 25.02, 19, 'https://picsum.photos/seed/p21/600/600', '["https://picsum.photos/seed/p21b/600/600"]', '{"couleur":"Vert kaki","taille":["M","L","XL"],"imperméable":"Oui"}', 'RunFast', 0, 1, NULL, 6, 41, 337),
(22, 'T-shirt sport technique', 'T-shirt seamless à séchage rapide, coupe ajustée et très léger.', 149.00, NULL, NULL, 95, 'https://picsum.photos/seed/p22/600/600', '["https://picsum.photos/seed/p22b/600/600"]', '{"couleur":"Rouge","taille":["S","M","L","XL"]}', 'RunFast', 0, 0, NULL, 6, 187, 512),
(23, 'Ballon de basket cuir', 'Ballon officielle en cuir synthétique, tenue de pression accrue.', 349.00, 449.00, 22.27, 31, 'https://picsum.photos/seed/p23/600/600', '["https://picsum.photos/seed/p23b/600/600"]', '{"taille":"Taille 7","matiere":"Cuir synthétique"}', 'SportPro', 0, 0, NULL, 6, 58, 264),
(24, 'Gourde isotherme 750ml', 'Gourde inox double paroi, garde la boisson froide 24h, bouchon sport.', 129.00, NULL, NULL, 120, 'https://picsum.photos/seed/p24/600/600', '["https://picsum.photos/seed/p24b/600/600"]', '{"contenance":"750 ml","materiau":"Inox 18/8"}', 'FitLife', 0, 0, NULL, 6, 210, 401);

-- ---------------------------------------------------------------------
-- Adresses
-- ---------------------------------------------------------------------
INSERT INTO addresses (id, user_id, full_name, phone, address, city, state, zip_code, country, is_default) VALUES
(1, 2, 'Yasmine El Amrani', '+212 662 33 44 55', 'Rue 12, Hay Riad', 'Casablanca', 'Casablanca-Settat', '20250', 'Maroc', 1),
(2, 2, 'Yasmine El Amrani', '+212 662 33 44 55', 'Bd Zerktouni, Appt 5', 'Casablanca', 'Casablanca-Settat', '20000', 'Maroc', 0),
(3, 3, 'Sarah Benali', '+212 663 55 66 77', 'Lotissement Al Wifaq, Imm B', 'Rabat', 'Rabat-Salé-Kénitra', '10100', 'Maroc', 1),
(4, 4, 'Youssef Idrissi', '+212 664 77 88 99', 'Avenue Mohammed VI', 'Marrakech', 'Marrakech-Safi', '40000', 'Maroc', 1);

-- ---------------------------------------------------------------------
-- Avis clients
-- ---------------------------------------------------------------------
INSERT INTO reviews (id, product_id, user_id, rating, comment, status, is_verified_purchase, created_at) VALUES
(1, 5, 2, 5.0, 'Très légères, la semelle fait vraiment le confort sur les longues distances.', 'APPROVED', 1, '2026-06-12 10:20:00'),
(2, 5, 3, 4.0, 'Bonne qualité, taille un peu petite, je advise de prendre une pointure au-dessus.', 'APPROVED', 1, '2026-06-18 15:45:00'),
(3, 9, 4, 5.0, 'Autonomie incroyable et la photo est au top niveau de la nuit.', 'APPROVED', 1, '2026-07-02 09:10:00'),
(4, 9, 2, 4.5, 'Très rapide, écran superbe. Un peu cher mais justifié.', 'APPROVED', 1, '2026-07-15 17:30:00'),
(5, 14, 3, 5.0, 'Résultats visibles en 3 semaines, texture parfaite.', 'APPROVED', 0, '2026-08-01 12:00:00'),
(6, 14, 4, 4.0, 'Bonne qualité, packaging un peu simple.', 'APPROVED', 1, '2026-08-09 11:15:00'),
(7, 11, 2, 5.0, 'La réduction de bruit est efficace, on isole complètement dans le métro.', 'APPROVED', 1, '2026-08-20 20:05:00'),
(8, 7, 4, 5.0, 'Cuir magnifique et finitions irreprochables.', 'PENDING', 0, '2026-09-05 08:40:00'),
(9, 3, 3, 4.0, 'Belle coupe, il faut prendre une taille au-dessus.', 'PENDING', 0, '2026-09-22 14:25:00'),
(10, 20, 2, 5.0, 'Tapis magnifique, livraison soignée.', 'APPROVED', 0, '2026-09-28 19:50:00');

-- Recalcule la note moyenne et le nombre d'avis de chaque produit
UPDATE products p
LEFT JOIN (
  SELECT product_id, ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS cnt
  FROM reviews WHERE status = 'APPROVED'
  GROUP BY product_id
) r ON r.product_id = p.id
SET p.rating_cache = COALESCE(r.avg_rating, 0),
    p.review_count_cache = COALESCE(r.cnt, 0);

-- ---------------------------------------------------------------------
-- Listes de wishes
-- ---------------------------------------------------------------------
INSERT INTO wishlists (user_id, product_id) VALUES
(2, 9), (2, 11), (2, 20), (3, 14), (3, 17), (4, 5), (4, 7), (4, 24);

-- ---------------------------------------------------------------------
-- Commandes (réparties sur plusieurs mois pour les statistiques)
-- ---------------------------------------------------------------------
INSERT INTO orders
(id, order_number, user_id, customer_email, subtotal, shipping_fee, discount_amount, total, status, is_paid, payment_method, tracking_number, ship_full_name, ship_phone, ship_address, ship_city, ship_state, ship_zip_code, ship_country, created_at) VALUES
(1, 'ORD-2026-0001', 2, 'client@ecomweb.ma',  1285.00, 40.00,  128.50, 1196.50, 'DELIVERED',  1, 'CARD',      'TRK-884512', 'Yasmine El Amrani', '+212 662 33 44 55', 'Rue 12, Hay Riad', 'Casablanca', 'Casablanca-Settat', '20250', 'Maroc', '2026-05-14 10:12:00'),
(2, 'ORD-2026-0002', 3, 'sarah@ecomweb.ma',    998.00, 40.00,    0.00, 1038.00, 'DELIVERED',  1, 'CASH',      'TRK-884530', 'Sarah Benali',      '+212 663 55 66 77', 'Lotissement Al Wifaq, Imm B', 'Rabat', 'Rabat-Salé-Kénitra', '10100', 'Maroc', '2026-06-02 16:40:00'),
(3, 'ORD-2026-0003', 4, 'youssef@ecomweb.ma',  749.00,  0.00,   74.90,  674.10, 'DELIVERED',  1, 'CARD',      'TRK-884577', 'Youssef Idrissi',   '+212 664 77 88 99', 'Avenue Mohammed VI', 'Marrakech', 'Marrakech-Safi', '40000', 'Maroc', '2026-06-21 09:05:00'),
(4, 'ORD-2026-0004', 2, 'client@ecomweb.ma',  7299.00,  0.00,  729.90, 6569.10, 'SHIPPED',    1, 'CARD',      'TRK-884612', 'Yasmine El Amrani', '+212 662 33 44 55', 'Rue 12, Hay Riad', 'Casablanca', 'Casablanca-Settat', '20250', 'Maroc', '2026-07-11 11:30:00'),
(5, 'ORD-2026-0005', 3, 'sarah@ecomweb.ma',    578.00, 40.00,   57.80,  560.20, 'PROCESSING', 1, 'CASH',      NULL,            'Sarah Benali',      '+212 663 55 66 77', 'Lotissement Al Wifaq, Imm B', 'Rabat', 'Rabat-Salé-Kénitra', '10100', 'Maroc', '2026-08-08 14:22:00'),
(6, 'ORD-2026-0006', 4, 'youssef@ecomweb.ma', 1899.00,  0.00,  189.90, 1709.10, 'PENDING',    0, 'CARD',      NULL,            'Youssef Idrissi',   '+212 664 77 88 99', 'Avenue Mohammed VI', 'Marrakech', 'Marrakech-Safi', '40000', 'Maroc', '2026-09-17 18:55:00'),
(7, 'ORD-2026-0007', 2, 'client@ecomweb.ma',  1548.00,  0.00,  154.80, 1393.20, 'CONFIRMED',  1, 'CARD',      NULL,            'Yasmine El Amrani', '+212 662 33 44 55', 'Bd Zerktouni, Appt 5', 'Casablanca', 'Casablanca-Settat', '20000', 'Maroc', '2026-10-02 10:05:00');

INSERT INTO order_items (order_id, product_id, product_name, product_thumbnail, quantity, unit_price, selected_size, selected_color) VALUES
(1, 9,  'Smartphone Nova X Pro',      'https://picsum.photos/seed/p9/600/600',  1, 6499.00, NULL, 'Titane'),
(1, 14, 'Sérum visage vitamine C',    'https://picsum.photos/seed/p14/600/600', 1,  289.00, NULL, NULL),
(1, 16, 'Crème hydratante intense',   'https://picsum.photos/seed/p16/600/600', 1,  199.00, NULL, NULL),
(2, 11, 'Casque sans fil ANC',        'https://picsum.photos/seed/p11/600/600', 1, 1299.00, NULL, 'Noir mat'),
(2, 17, 'Lampe designer Minimal',     'https://picsum.photos/seed/p17/600/600', 1,  449.00, NULL, NULL),
(3, 5,  'Sneakers running Air',       'https://picsum.photos/seed/p5/600/600',  1,  749.00, '42',  'Blanc / Noir'),
(4, 10, 'Ordinateur portable Air 14', 'https://picsum.photos/seed/p10/600/600', 1, 7299.00, NULL, NULL),
(5, 15, 'Parfum Nuit Étoilée',        'https://picsum.photos/seed/p15/600/600', 1,  749.00, NULL, NULL),
(5, 12, 'Enceinte Bluetooth',         'https://picsum.photos/seed/p12/600/600', 1,  499.00, NULL, 'Bleu'),
(6, 20, 'Tapis berbère tissé',        'https://picsum.photos/seed/p20/600/600', 1, 1899.00, NULL, NULL),
(7, 7,  'Sac à main cuir',            'https://picsum.photos/seed/p7/600/600',  1, 1299.00, NULL, 'Cognac'),
(7, 3,  'Veste blazer structurée',    'https://picsum.photos/seed/p3/600/600',  1,  899.00, '50',  'Gris chiné'),
(7, 13, 'Montre connectée Fit Pro',   'https://picsum.photos/seed/p13/600/600', 1,  899.00, NULL, NULL);

INSERT INTO order_status_history (order_id, status, note, created_at) VALUES
(1, 'PENDING',   'Commande reçue', '2026-05-14 10:12:00'),
(1, 'CONFIRMED', 'Paiement validé', '2026-05-14 10:15:00'),
(1, 'SHIPPED',   'Colis remis au transporteur', '2026-05-16 09:00:00'),
(1, 'DELIVERED', 'Commande livrée', '2026-05-18 14:30:00'),
(2, 'PENDING',   'Commande reçue', '2026-06-02 16:40:00'),
(2, 'DELIVERED', 'Commande livrée', '2026-06-05 11:20:00'),
(3, 'DELIVERED', 'Commande livrée', '2026-06-24 17:10:00'),
(4, 'SHIPPED',   'Colis remis au transporteur', '2026-07-12 08:45:00'),
(5, 'PROCESSING','Commande en préparation', '2026-08-09 09:10:00'),
(6, 'PENDING',   'Commande reçue', '2026-09-17 18:55:00'),
(7, 'CONFIRMED', 'Paiement validé', '2026-10-02 10:06:00');

-- ---------------------------------------------------------------------
-- Codes promo
-- ---------------------------------------------------------------------
INSERT INTO coupons (code, percentage, min_order_amount, max_discount_amount, usage_limit, used_count, user_id, expires_at, is_active) VALUES
('BIENVENUE10', 10.00,  200.00, 150.00, 500, 12,  NULL, '2026-12-31 23:59:59', 1),
('ETE25',       25.00,  800.00, 400.00, 200,  4,  NULL, '2026-08-31 23:59:59', 1),
('FREESHIP',     5.00,  NULL,  50.00, 1000, 87,  NULL, '2026-11-30 23:59:59', 1),
('SOLDES50',    50.00, 1500.00, 900.00, 100,  0,  NULL, '2026-07-31 23:59:59', 0),
('YASMINE15',   15.00,  300.00, 200.00,   1,  0,  2, '2026-10-31 23:59:59', 1);

-- ---------------------------------------------------------------------
-- Bannières promotionnelles
-- ---------------------------------------------------------------------
INSERT INTO promo_banners (id, title, subtitle, photo, link, button_text, color_hex, position, is_active) VALUES
(1, 'Soldes jusqu\u2019à -50%', 'Sur une sélection de vêtements et chaussures', 'https://picsum.photos/seed/banner1/1200/500', '/boutique?promo=1', 'Voir les offres', '#4F46E5', 1, 1),
(2, 'High-tech', 'Le meilleur de la technologie au meilleur prix', 'https://picsum.photos/seed/banner2/1200/500', '/categorie/3', 'Découvrir', '#0EA5E9', 2, 1),
(3, 'Livraison offerte', 'Dès 1 000 MAD d\u2019achat, partout au Maroc', 'https://picsum.photos/seed/banner3/1200/500', '/boutique', 'Profiter', '#10B981', 3, 1);

-- ---------------------------------------------------------------------
-- Notifications
-- ---------------------------------------------------------------------
INSERT INTO notifications (user_id, type, title, message, priority, is_read, data, created_at) VALUES
(NULL, 'PROMOTION', 'Offre spéciale -20%', 'Profitez de -20% sur toute la sélection Mode jusqu\u2019à dimanche.', 'MEDIUM', 0, '{"code":"BIENVENUE10"}', '2026-10-01 09:00:00'),
(NULL, 'MAINTENANCE', 'Maintenance planifiée', 'La plateforme sera brièvement indisponible dimanche de 02h à 04h.', 'LOW', 0, NULL, '2026-09-25 18:00:00'),
(2, 'ORDER_CONFIRMED', 'Commande confirmée #ORD-2026-0007', 'Votre commande a été confirmée et est en cours de préparation.', 'HIGH', 0, '{"orderId":7}', '2026-10-02 10:06:00'),
(2, 'SHIPPING_UPDATE', 'Commande expédiée #ORD-2026-0004', 'Votre colis a été expédié. Suivi : TRK-884612', 'HIGH', 1, '{"orderId":4}', '2026-07-12 08:45:00'),
(3, 'PRICE_DROP', 'Baisse de prix - Sérum vitamine C', 'Un produit de votre liste de favoris a baissé de prix.', 'MEDIUM', 0, '{"productId":14}', '2026-09-28 12:00:00'),
(4, 'BACK_IN_STOCK', 'De retour en stock - Sneakers running Air', 'Le produit que vous surveilliez est de nouveau disponible.', 'MEDIUM', 0, '{"productId":5}', '2026-09-20 15:30:00');

-- ---------------------------------------------------------------------
-- Paramètres
-- ---------------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('shipping_fee', '40'),
('free_shipping_from', '1000'),
('store_email', 'contact@ecomweb.ma'),
('store_phone', '+212 5 22 00 00 00'),
('store_address', '45 Boulevard d Anfa, Casablanca, Maroc');
