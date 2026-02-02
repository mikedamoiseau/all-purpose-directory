-- Sample Categories Fixture
--
-- This file contains sample category data for integration and E2E tests.
-- Load with: mysql -u user -p database < sample-categories.sql
-- Or use the load-fixtures.sh script.
--
-- Categories for a local business directory with hierarchical structure.

-- Clear existing test data
DELETE FROM wp_termmeta WHERE term_id IN (SELECT term_id FROM wp_terms WHERE slug LIKE 'apd-test-%' OR slug IN ('restaurants', 'cafes-coffee', 'fine-dining', 'fast-food', 'hotels', 'bed-breakfast', 'vacation-rentals', 'shopping', 'clothing', 'electronics', 'grocery', 'services', 'auto-repair', 'home-services', 'professional', 'entertainment', 'nightlife', 'movies-theater', 'sports-recreation', 'healthcare', 'doctors', 'dentists', 'pharmacies'));
DELETE FROM wp_term_relationships WHERE term_taxonomy_id IN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE taxonomy = 'apd_category');
DELETE FROM wp_term_taxonomy WHERE taxonomy = 'apd_category';
DELETE FROM wp_terms WHERE slug LIKE 'apd-test-%' OR slug IN ('restaurants', 'cafes-coffee', 'fine-dining', 'fast-food', 'hotels', 'bed-breakfast', 'vacation-rentals', 'shopping', 'clothing', 'electronics', 'grocery', 'services', 'auto-repair', 'home-services', 'professional', 'entertainment', 'nightlife', 'movies-theater', 'sports-recreation', 'healthcare', 'doctors', 'dentists', 'pharmacies');

-- ============================================
-- PARENT CATEGORIES
-- ============================================

-- Restaurants (ID: 1)
INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1001, 'Restaurants', 'restaurants', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1001, 1001, 'apd_category', 'Places to eat and drink', 0, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1001, '_apd_icon', 'dashicons-food');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1001, '_apd_color', '#FF5722');

-- Hotels & Lodging (ID: 2)
INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1002, 'Hotels & Lodging', 'hotels', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1002, 1002, 'apd_category', 'Places to stay', 0, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1002, '_apd_icon', 'dashicons-building');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1002, '_apd_color', '#3F51B5');

-- Shopping (ID: 3)
INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1003, 'Shopping', 'shopping', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1003, 1003, 'apd_category', 'Retail stores and malls', 0, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1003, '_apd_icon', 'dashicons-cart');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1003, '_apd_color', '#4CAF50');

-- Services (ID: 4)
INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1004, 'Services', 'services', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1004, 1004, 'apd_category', 'Local services and businesses', 0, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1004, '_apd_icon', 'dashicons-hammer');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1004, '_apd_color', '#795548');

-- Entertainment (ID: 5)
INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1005, 'Entertainment', 'entertainment', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1005, 1005, 'apd_category', 'Fun and leisure activities', 0, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1005, '_apd_icon', 'dashicons-tickets-alt');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1005, '_apd_color', '#9C27B0');

-- Healthcare (ID: 6)
INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1006, 'Healthcare', 'healthcare', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1006, 1006, 'apd_category', 'Medical and health services', 0, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1006, '_apd_icon', 'dashicons-heart');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1006, '_apd_color', '#F44336');

-- ============================================
-- CHILD CATEGORIES (Restaurants)
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1011, 'Cafes & Coffee', 'cafes-coffee', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1011, 1011, 'apd_category', 'Coffee shops and cafes', 1001, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1011, '_apd_icon', 'dashicons-coffee');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1011, '_apd_color', '#8D6E63');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1012, 'Fine Dining', 'fine-dining', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1012, 1012, 'apd_category', 'Upscale dining experiences', 1001, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1012, '_apd_icon', 'dashicons-star-filled');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1012, '_apd_color', '#D4AF37');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1013, 'Fast Food', 'fast-food', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1013, 1013, 'apd_category', 'Quick service restaurants', 1001, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1013, '_apd_icon', 'dashicons-food');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1013, '_apd_color', '#FF9800');

-- ============================================
-- CHILD CATEGORIES (Hotels)
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1021, 'Bed & Breakfast', 'bed-breakfast', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1021, 1021, 'apd_category', 'Cozy B&B accommodations', 1002, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1021, '_apd_icon', 'dashicons-admin-home');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1021, '_apd_color', '#E91E63');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1022, 'Vacation Rentals', 'vacation-rentals', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1022, 1022, 'apd_category', 'Short-term rental properties', 1002, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1022, '_apd_icon', 'dashicons-palmtree');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1022, '_apd_color', '#00BCD4');

-- ============================================
-- CHILD CATEGORIES (Shopping)
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1031, 'Clothing & Apparel', 'clothing', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1031, 1031, 'apd_category', 'Fashion and clothing stores', 1003, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1031, '_apd_icon', 'dashicons-tag');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1031, '_apd_color', '#673AB7');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1032, 'Electronics', 'electronics', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1032, 1032, 'apd_category', 'Tech and electronics stores', 1003, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1032, '_apd_icon', 'dashicons-laptop');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1032, '_apd_color', '#2196F3');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1033, 'Grocery & Markets', 'grocery', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1033, 1033, 'apd_category', 'Food and grocery stores', 1003, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1033, '_apd_icon', 'dashicons-carrot');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1033, '_apd_color', '#8BC34A');

-- ============================================
-- CHILD CATEGORIES (Services)
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1041, 'Auto Repair', 'auto-repair', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1041, 1041, 'apd_category', 'Auto mechanics and repair shops', 1004, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1041, '_apd_icon', 'dashicons-car');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1041, '_apd_color', '#607D8B');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1042, 'Home Services', 'home-services', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1042, 1042, 'apd_category', 'Plumbers, electricians, contractors', 1004, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1042, '_apd_icon', 'dashicons-admin-tools');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1042, '_apd_color', '#CDDC39');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1043, 'Professional Services', 'professional', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1043, 1043, 'apd_category', 'Legal, accounting, consulting', 1004, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1043, '_apd_icon', 'dashicons-businessperson');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1043, '_apd_color', '#455A64');

-- ============================================
-- CHILD CATEGORIES (Entertainment)
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1051, 'Nightlife', 'nightlife', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1051, 1051, 'apd_category', 'Bars, clubs, nightlife venues', 1005, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1051, '_apd_icon', 'dashicons-drumstick');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1051, '_apd_color', '#311B92');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1052, 'Movies & Theater', 'movies-theater', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1052, 1052, 'apd_category', 'Cinemas and performing arts', 1005, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1052, '_apd_icon', 'dashicons-video-alt3');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1052, '_apd_color', '#B71C1C');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1053, 'Sports & Recreation', 'sports-recreation', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1053, 1053, 'apd_category', 'Gyms, sports facilities, parks', 1005, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1053, '_apd_icon', 'dashicons-universal-access');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1053, '_apd_color', '#1B5E20');

-- ============================================
-- CHILD CATEGORIES (Healthcare)
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1061, 'Doctors & Clinics', 'doctors', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1061, 1061, 'apd_category', 'Medical doctors and clinics', 1006, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1061, '_apd_icon', 'dashicons-heart');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1061, '_apd_color', '#C62828');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1062, 'Dentists', 'dentists', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1062, 1062, 'apd_category', 'Dental care providers', 1006, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1062, '_apd_icon', 'dashicons-smiley');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1062, '_apd_color', '#00ACC1');

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (1063, 'Pharmacies', 'pharmacies', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1063, 1063, 'apd_category', 'Pharmacies and drug stores', 1006, 0);
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1063, '_apd_icon', 'dashicons-plus-alt');
INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES (1063, '_apd_color', '#43A047');

-- ============================================
-- TAGS
-- ============================================

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2001, 'Pet Friendly', 'pet-friendly', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2001, 2001, 'apd_tag', 'Welcomes pets', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2002, 'Wheelchair Accessible', 'wheelchair-accessible', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2002, 2002, 'apd_tag', 'Fully accessible', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2003, 'Free WiFi', 'free-wifi', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2003, 2003, 'apd_tag', 'Complimentary WiFi', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2004, 'Parking Available', 'parking-available', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2004, 2004, 'apd_tag', 'On-site parking', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2005, 'Open Late', 'open-late', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2005, 2005, 'apd_tag', 'Late night hours', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2006, 'Family Friendly', 'family-friendly', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2006, 2006, 'apd_tag', 'Great for families', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2007, 'Outdoor Seating', 'outdoor-seating', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2007, 2007, 'apd_tag', 'Patio or outdoor area', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2008, 'Delivery Available', 'delivery-available', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2008, 2008, 'apd_tag', 'Offers delivery service', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2009, 'Accepts Credit Cards', 'accepts-credit-cards', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2009, 2009, 'apd_tag', 'Credit card payments accepted', 0, 0);

INSERT INTO wp_terms (term_id, name, slug, term_group) VALUES (2010, 'Reservations', 'reservations', 0);
INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (2010, 2010, 'apd_tag', 'Takes reservations', 0, 0);
