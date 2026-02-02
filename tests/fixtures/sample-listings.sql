-- Sample Listings Fixture
--
-- This file contains sample listing data for integration and E2E tests.
-- Load with: mysql -u user -p database < sample-listings.sql
-- Or use the load-fixtures.sh script.
--
-- Contains 25 listings with varied content:
-- - Mix of statuses: publish, pending, draft, expired
-- - Various categories and tags assigned
-- - Different authors (user IDs 1-3)
-- - Custom field values populated

-- Note: This fixture depends on sample-categories.sql being loaded first.
-- Note: Run this AFTER WordPress is installed and apd_listing post type is registered.

-- ============================================
-- CLEANUP
-- ============================================

DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'apd_listing');
DELETE FROM wp_term_relationships WHERE object_id IN (SELECT ID FROM wp_posts WHERE post_type = 'apd_listing');
DELETE FROM wp_posts WHERE post_type = 'apd_listing';

-- ============================================
-- LISTINGS - RESTAURANTS
-- ============================================

-- 1. Published - Fine Dining - Great reviews
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3001, 1, '2025-01-15 10:00:00', '2025-01-15 10:00:00', 'Experience culinary excellence at The Golden Fork, where our award-winning chef creates unforgettable dining experiences. Our menu features locally-sourced ingredients, paired with an extensive wine list from around the world. Perfect for special occasions and romantic dinners.', 'The Golden Fork Restaurant', 'Award-winning fine dining in the heart of downtown', 'publish', 'the-golden-fork-restaurant', 'apd_listing', '2025-01-15 10:00:00', '2025-01-15 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3001, '_apd_phone', '(555) 123-4567'),
(3001, '_apd_email', 'reservations@goldenfork.example.com'),
(3001, '_apd_website', 'https://goldenfork.example.com'),
(3001, '_apd_address', '123 Main Street, Suite 100'),
(3001, '_apd_city', 'Downtown'),
(3001, '_apd_state', 'CA'),
(3001, '_apd_zip', '90210'),
(3001, '_apd_price_range', '$$$$'),
(3001, '_apd_hours', 'Tue-Sun: 5pm-10pm'),
(3001, '_apd_rating', '4.8'),
(3001, '_apd_review_count', '47'),
(3001, '_apd_views', '1523');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3001, 1001), (3001, 1012), (3001, 2009), (3001, 2010);

-- 2. Published - Cafe
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3002, 1, '2025-01-10 09:00:00', '2025-01-10 09:00:00', 'Start your day right at Morning Brew Cafe. We serve artisan coffee, fresh pastries, and healthy breakfast options. Our cozy atmosphere is perfect for remote work or catching up with friends. Free WiFi and plenty of outlets available.', 'Morning Brew Cafe', 'Artisan coffee and fresh pastries daily', 'publish', 'morning-brew-cafe', 'apd_listing', '2025-01-10 09:00:00', '2025-01-10 09:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3002, '_apd_phone', '(555) 234-5678'),
(3002, '_apd_email', 'hello@morningbrew.example.com'),
(3002, '_apd_website', 'https://morningbrew.example.com'),
(3002, '_apd_address', '456 Oak Avenue'),
(3002, '_apd_city', 'Midtown'),
(3002, '_apd_state', 'CA'),
(3002, '_apd_zip', '90211'),
(3002, '_apd_price_range', '$$'),
(3002, '_apd_hours', 'Mon-Fri: 6am-6pm, Sat-Sun: 7am-5pm'),
(3002, '_apd_rating', '4.5'),
(3002, '_apd_review_count', '89'),
(3002, '_apd_views', '2341');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3002, 1001), (3002, 1011), (3002, 2003), (3002, 2007);

-- 3. Published - Fast Food
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3003, 2, '2025-01-08 12:00:00', '2025-01-08 12:00:00', 'Quick Bites serves delicious burgers, fries, and shakes. Family-owned since 1985, we pride ourselves on fresh ingredients and fast service. Try our famous Triple Stack Burger!', 'Quick Bites Burgers', 'Classic American burgers since 1985', 'publish', 'quick-bites-burgers', 'apd_listing', '2025-01-08 12:00:00', '2025-01-08 12:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3003, '_apd_phone', '(555) 345-6789'),
(3003, '_apd_email', 'info@quickbites.example.com'),
(3003, '_apd_address', '789 Burger Lane'),
(3003, '_apd_city', 'Westside'),
(3003, '_apd_state', 'CA'),
(3003, '_apd_zip', '90212'),
(3003, '_apd_price_range', '$'),
(3003, '_apd_hours', 'Daily: 10am-11pm'),
(3003, '_apd_rating', '4.2'),
(3003, '_apd_review_count', '156'),
(3003, '_apd_views', '3892');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3003, 1001), (3003, 1013), (3003, 2004), (3003, 2006), (3003, 2008);

-- 4. Pending - Restaurant awaiting approval
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3004, 2, '2025-01-20 14:00:00', '2025-01-20 14:00:00', 'New Italian restaurant opening soon! Family recipes passed down for generations. Authentic pasta, pizza, and traditional desserts.', 'Mama Lucia Italian Kitchen', 'Authentic Italian cuisine with family recipes', 'pending', 'mama-lucia-italian-kitchen', 'apd_listing', '2025-01-20 14:00:00', '2025-01-20 14:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3004, '_apd_phone', '(555) 456-7890'),
(3004, '_apd_email', 'contact@mamalucia.example.com'),
(3004, '_apd_address', '321 Pasta Street'),
(3004, '_apd_city', 'Little Italy'),
(3004, '_apd_state', 'CA'),
(3004, '_apd_zip', '90213'),
(3004, '_apd_price_range', '$$$'),
(3004, '_apd_views', '45');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3004, 1001), (3004, 2006), (3004, 2010);

-- ============================================
-- LISTINGS - HOTELS
-- ============================================

-- 5. Published - Hotel
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3005, 1, '2024-12-01 10:00:00', '2024-12-01 10:00:00', 'Grand Plaza Hotel offers luxury accommodations in the heart of the city. Features include rooftop pool, spa, fine dining restaurant, and concierge service. Perfect for business travelers and tourists alike.', 'Grand Plaza Hotel', 'Luxury hotel with rooftop pool and spa', 'publish', 'grand-plaza-hotel', 'apd_listing', '2024-12-01 10:00:00', '2024-12-01 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3005, '_apd_phone', '(555) 567-8901'),
(3005, '_apd_email', 'reservations@grandplaza.example.com'),
(3005, '_apd_website', 'https://grandplazahotel.example.com'),
(3005, '_apd_address', '500 Plaza Avenue'),
(3005, '_apd_city', 'Downtown'),
(3005, '_apd_state', 'CA'),
(3005, '_apd_zip', '90210'),
(3005, '_apd_price_range', '$$$$'),
(3005, '_apd_rating', '4.7'),
(3005, '_apd_review_count', '234'),
(3005, '_apd_views', '4521');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3005, 1002), (3005, 2002), (3005, 2003), (3005, 2004);

-- 6. Published - B&B
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3006, 3, '2024-11-15 09:00:00', '2024-11-15 09:00:00', 'Escape to Rosewood B&B, a charming Victorian home offering comfortable rooms and homemade breakfast. Beautiful gardens and peaceful atmosphere. Just 10 minutes from downtown attractions.', 'Rosewood Bed & Breakfast', 'Charming Victorian B&B with gardens', 'publish', 'rosewood-bed-breakfast', 'apd_listing', '2024-11-15 09:00:00', '2024-11-15 09:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3006, '_apd_phone', '(555) 678-9012'),
(3006, '_apd_email', 'stay@rosewoodbnb.example.com'),
(3006, '_apd_website', 'https://rosewoodbnb.example.com'),
(3006, '_apd_address', '42 Rose Garden Lane'),
(3006, '_apd_city', 'Hillside'),
(3006, '_apd_state', 'CA'),
(3006, '_apd_zip', '90214'),
(3006, '_apd_price_range', '$$$'),
(3006, '_apd_rating', '4.9'),
(3006, '_apd_review_count', '67'),
(3006, '_apd_views', '1876');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3006, 1002), (3006, 1021), (3006, 2003), (3006, 2004);

-- 7. Draft - Vacation Rental
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3007, 3, '2025-01-18 16:00:00', '2025-01-18 16:00:00', 'Beautiful beachfront condo with stunning ocean views. 2 bedrooms, full kitchen, private balcony. Walking distance to shops and restaurants.', 'Oceanview Beach Condo', 'Beachfront rental with stunning views', 'draft', 'oceanview-beach-condo', 'apd_listing', '2025-01-18 16:00:00', '2025-01-18 16:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3007, '_apd_phone', '(555) 789-0123'),
(3007, '_apd_email', 'rentals@oceanview.example.com'),
(3007, '_apd_address', '100 Beach Boulevard, Unit 5'),
(3007, '_apd_city', 'Seaside'),
(3007, '_apd_state', 'CA'),
(3007, '_apd_zip', '90215'),
(3007, '_apd_price_range', '$$$'),
(3007, '_apd_views', '12');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3007, 1002), (3007, 1022), (3007, 2001), (3007, 2003);

-- ============================================
-- LISTINGS - SHOPPING
-- ============================================

-- 8. Published - Clothing Store
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3008, 1, '2025-01-05 11:00:00', '2025-01-05 11:00:00', 'Urban Thread Boutique offers curated fashion for the modern professional. Designer brands, unique accessories, and personalized styling services. New arrivals weekly.', 'Urban Thread Boutique', 'Curated fashion for modern professionals', 'publish', 'urban-thread-boutique', 'apd_listing', '2025-01-05 11:00:00', '2025-01-05 11:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3008, '_apd_phone', '(555) 890-1234'),
(3008, '_apd_email', 'shop@urbanthread.example.com'),
(3008, '_apd_website', 'https://urbanthread.example.com'),
(3008, '_apd_address', '888 Fashion Avenue'),
(3008, '_apd_city', 'Uptown'),
(3008, '_apd_state', 'CA'),
(3008, '_apd_zip', '90216'),
(3008, '_apd_price_range', '$$$'),
(3008, '_apd_hours', 'Mon-Sat: 10am-8pm, Sun: 12pm-6pm'),
(3008, '_apd_rating', '4.6'),
(3008, '_apd_review_count', '52'),
(3008, '_apd_views', '2134');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3008, 1003), (3008, 1031), (3008, 2002), (3008, 2009);

-- 9. Published - Electronics Store
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3009, 2, '2024-10-20 10:00:00', '2024-10-20 10:00:00', 'TechZone is your destination for the latest gadgets and electronics. Expert staff, competitive prices, and repair services available. We carry all major brands.', 'TechZone Electronics', 'Latest gadgets and expert repair services', 'publish', 'techzone-electronics', 'apd_listing', '2024-10-20 10:00:00', '2024-10-20 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3009, '_apd_phone', '(555) 901-2345'),
(3009, '_apd_email', 'sales@techzone.example.com'),
(3009, '_apd_website', 'https://techzone.example.com'),
(3009, '_apd_address', '999 Tech Drive'),
(3009, '_apd_city', 'Tech Park'),
(3009, '_apd_state', 'CA'),
(3009, '_apd_zip', '90217'),
(3009, '_apd_price_range', '$$'),
(3009, '_apd_hours', 'Daily: 9am-9pm'),
(3009, '_apd_rating', '4.3'),
(3009, '_apd_review_count', '128'),
(3009, '_apd_views', '5672');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3009, 1003), (3009, 1032), (3009, 2002), (3009, 2004), (3009, 2009);

-- 10. Published - Grocery Store
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3010, 1, '2024-09-01 08:00:00', '2024-09-01 08:00:00', 'Fresh Market brings you organic produce, local meats, and artisan foods. Supporting local farmers and sustainable practices since 2010. Bulk foods and zero-waste options available.', 'Fresh Market Organic Grocery', 'Organic produce and local artisan foods', 'publish', 'fresh-market-organic-grocery', 'apd_listing', '2024-09-01 08:00:00', '2024-09-01 08:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3010, '_apd_phone', '(555) 012-3456'),
(3010, '_apd_email', 'info@freshmarket.example.com'),
(3010, '_apd_website', 'https://freshmarket.example.com'),
(3010, '_apd_address', '200 Green Street'),
(3010, '_apd_city', 'Greenville'),
(3010, '_apd_state', 'CA'),
(3010, '_apd_zip', '90218'),
(3010, '_apd_price_range', '$$'),
(3010, '_apd_hours', 'Daily: 7am-9pm'),
(3010, '_apd_rating', '4.7'),
(3010, '_apd_review_count', '203'),
(3010, '_apd_views', '8934');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3010, 1003), (3010, 1033), (3010, 2002), (3010, 2004), (3010, 2009);

-- ============================================
-- LISTINGS - SERVICES
-- ============================================

-- 11. Published - Auto Repair
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3011, 2, '2024-08-15 09:00:00', '2024-08-15 09:00:00', 'Precision Auto Care provides honest, reliable auto repair and maintenance. ASE certified technicians, fair prices, and fast turnaround. We service all makes and models.', 'Precision Auto Care', 'Honest, reliable auto repair since 1998', 'publish', 'precision-auto-care', 'apd_listing', '2024-08-15 09:00:00', '2024-08-15 09:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3011, '_apd_phone', '(555) 111-2222'),
(3011, '_apd_email', 'service@precisionauto.example.com'),
(3011, '_apd_website', 'https://precisionauto.example.com'),
(3011, '_apd_address', '555 Mechanic Road'),
(3011, '_apd_city', 'Industrial District'),
(3011, '_apd_state', 'CA'),
(3011, '_apd_zip', '90219'),
(3011, '_apd_price_range', '$$'),
(3011, '_apd_hours', 'Mon-Fri: 7am-6pm, Sat: 8am-4pm'),
(3011, '_apd_rating', '4.8'),
(3011, '_apd_review_count', '312'),
(3011, '_apd_views', '6234');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3011, 1004), (3011, 1041), (3011, 2002), (3011, 2009);

-- 12. Published - Plumber
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3012, 3, '2024-07-01 10:00:00', '2024-07-01 10:00:00', 'Quick Flow Plumbing offers 24/7 emergency plumbing services. Licensed and insured, with over 20 years of experience. No job too big or too small.', 'Quick Flow Plumbing', '24/7 emergency plumbing services', 'publish', 'quick-flow-plumbing', 'apd_listing', '2024-07-01 10:00:00', '2024-07-01 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3012, '_apd_phone', '(555) 222-3333'),
(3012, '_apd_email', 'emergency@quickflow.example.com'),
(3012, '_apd_website', 'https://quickflow.example.com'),
(3012, '_apd_address', 'Mobile Service - Citywide'),
(3012, '_apd_city', 'Citywide'),
(3012, '_apd_state', 'CA'),
(3012, '_apd_zip', '90220'),
(3012, '_apd_price_range', '$$'),
(3012, '_apd_hours', '24/7 Emergency Service'),
(3012, '_apd_rating', '4.6'),
(3012, '_apd_review_count', '178'),
(3012, '_apd_views', '4521');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3012, 1004), (3012, 1042), (3012, 2009);

-- 13. Published - Lawyer
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3013, 1, '2024-06-15 09:00:00', '2024-06-15 09:00:00', 'Smith & Associates provides expert legal services in family law, real estate, and business matters. Free initial consultation. Serving the community for over 30 years.', 'Smith & Associates Law Firm', 'Expert legal services for families and businesses', 'publish', 'smith-associates-law-firm', 'apd_listing', '2024-06-15 09:00:00', '2024-06-15 09:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3013, '_apd_phone', '(555) 333-4444'),
(3013, '_apd_email', 'info@smithlaw.example.com'),
(3013, '_apd_website', 'https://smithlaw.example.com'),
(3013, '_apd_address', '1000 Legal Plaza, Suite 500'),
(3013, '_apd_city', 'Financial District'),
(3013, '_apd_state', 'CA'),
(3013, '_apd_zip', '90221'),
(3013, '_apd_price_range', '$$$$'),
(3013, '_apd_hours', 'Mon-Fri: 9am-5pm'),
(3013, '_apd_rating', '4.9'),
(3013, '_apd_review_count', '89'),
(3013, '_apd_views', '3456');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3013, 1004), (3013, 1043), (3013, 2002), (3013, 2004);

-- 14. Expired - Old service listing
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3014, 2, '2024-01-01 10:00:00', '2024-01-01 10:00:00', 'Budget movers for local and long distance moves. Student discounts available.', 'Budget Movers LLC', 'Affordable moving services', 'expired', 'budget-movers-llc', 'apd_listing', '2024-06-01 10:00:00', '2024-06-01 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3014, '_apd_phone', '(555) 444-5555'),
(3014, '_apd_email', 'info@budgetmovers.example.com'),
(3014, '_apd_address', '777 Moving Lane'),
(3014, '_apd_city', 'Warehouse District'),
(3014, '_apd_state', 'CA'),
(3014, '_apd_zip', '90222'),
(3014, '_apd_price_range', '$'),
(3014, '_apd_views', '234');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3014, 1004), (3014, 1042);

-- ============================================
-- LISTINGS - ENTERTAINMENT
-- ============================================

-- 15. Published - Bar/Nightclub
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3015, 3, '2024-11-01 18:00:00', '2024-11-01 18:00:00', 'Neon Nights is the citys hottest nightclub. World-class DJs, premium bottle service, and unforgettable parties every weekend. VIP tables available.', 'Neon Nights Club', 'Premier nightclub with world-class DJs', 'publish', 'neon-nights-club', 'apd_listing', '2024-11-01 18:00:00', '2024-11-01 18:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3015, '_apd_phone', '(555) 555-6666'),
(3015, '_apd_email', 'vip@neonnights.example.com'),
(3015, '_apd_website', 'https://neonnights.example.com'),
(3015, '_apd_address', '222 Party Street'),
(3015, '_apd_city', 'Entertainment District'),
(3015, '_apd_state', 'CA'),
(3015, '_apd_zip', '90223'),
(3015, '_apd_price_range', '$$$'),
(3015, '_apd_hours', 'Thu-Sat: 10pm-4am'),
(3015, '_apd_rating', '4.2'),
(3015, '_apd_review_count', '234'),
(3015, '_apd_views', '8765');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3015, 1005), (3015, 1051), (3015, 2005), (3015, 2009), (3015, 2010);

-- 16. Published - Movie Theater
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3016, 1, '2024-05-01 10:00:00', '2024-05-01 10:00:00', 'Starlight Cinema offers the ultimate movie experience. IMAX screens, luxury recliners, and full-service dining. Catch the latest blockbusters in style.', 'Starlight Cinema', 'Premium movie theater with IMAX and dining', 'publish', 'starlight-cinema', 'apd_listing', '2024-05-01 10:00:00', '2024-05-01 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3016, '_apd_phone', '(555) 666-7777'),
(3016, '_apd_email', 'info@starlightcinema.example.com'),
(3016, '_apd_website', 'https://starlightcinema.example.com'),
(3016, '_apd_address', '333 Movie Lane'),
(3016, '_apd_city', 'Entertainment District'),
(3016, '_apd_state', 'CA'),
(3016, '_apd_zip', '90223'),
(3016, '_apd_price_range', '$$'),
(3016, '_apd_hours', 'Daily: 11am-12am'),
(3016, '_apd_rating', '4.5'),
(3016, '_apd_review_count', '567'),
(3016, '_apd_views', '12345');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3016, 1005), (3016, 1052), (3016, 2002), (3016, 2004), (3016, 2006), (3016, 2009);

-- 17. Published - Gym
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3017, 2, '2024-04-15 06:00:00', '2024-04-15 06:00:00', 'FitLife Gym offers state-of-the-art equipment, group classes, and personal training. Open 24 hours for your convenience. First week free for new members!', 'FitLife Gym', '24-hour gym with classes and personal training', 'publish', 'fitlife-gym', 'apd_listing', '2024-04-15 06:00:00', '2024-04-15 06:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3017, '_apd_phone', '(555) 777-8888'),
(3017, '_apd_email', 'join@fitlifegym.example.com'),
(3017, '_apd_website', 'https://fitlifegym.example.com'),
(3017, '_apd_address', '444 Fitness Boulevard'),
(3017, '_apd_city', 'Sports Complex'),
(3017, '_apd_state', 'CA'),
(3017, '_apd_zip', '90224'),
(3017, '_apd_price_range', '$$'),
(3017, '_apd_hours', 'Open 24 Hours'),
(3017, '_apd_rating', '4.4'),
(3017, '_apd_review_count', '423'),
(3017, '_apd_views', '9876');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3017, 1005), (3017, 1053), (3017, 2002), (3017, 2004), (3017, 2005), (3017, 2009);

-- ============================================
-- LISTINGS - HEALTHCARE
-- ============================================

-- 18. Published - Medical Clinic
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3018, 1, '2024-03-01 08:00:00', '2024-03-01 08:00:00', 'Citywide Medical Clinic provides comprehensive healthcare for the whole family. Walk-ins welcome, same-day appointments available. Board-certified physicians and friendly staff.', 'Citywide Medical Clinic', 'Family healthcare with same-day appointments', 'publish', 'citywide-medical-clinic', 'apd_listing', '2024-03-01 08:00:00', '2024-03-01 08:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3018, '_apd_phone', '(555) 888-9999'),
(3018, '_apd_email', 'appointments@citywidemed.example.com'),
(3018, '_apd_website', 'https://citywidemed.example.com'),
(3018, '_apd_address', '600 Health Way'),
(3018, '_apd_city', 'Medical Center'),
(3018, '_apd_state', 'CA'),
(3018, '_apd_zip', '90225'),
(3018, '_apd_price_range', '$$'),
(3018, '_apd_hours', 'Mon-Fri: 7am-7pm, Sat: 8am-2pm'),
(3018, '_apd_rating', '4.6'),
(3018, '_apd_review_count', '287'),
(3018, '_apd_views', '7654');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3018, 1006), (3018, 1061), (3018, 2002), (3018, 2004), (3018, 2009);

-- 19. Published - Dentist
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3019, 3, '2024-02-15 09:00:00', '2024-02-15 09:00:00', 'Bright Smile Dental offers gentle, comprehensive dental care. Cosmetic dentistry, orthodontics, and family dental services. Sedation options available for anxious patients.', 'Bright Smile Dental', 'Gentle dental care for the whole family', 'publish', 'bright-smile-dental', 'apd_listing', '2024-02-15 09:00:00', '2024-02-15 09:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3019, '_apd_phone', '(555) 999-0000'),
(3019, '_apd_email', 'smile@brightsmile.example.com'),
(3019, '_apd_website', 'https://brightsmile.example.com'),
(3019, '_apd_address', '700 Tooth Lane'),
(3019, '_apd_city', 'Medical Center'),
(3019, '_apd_state', 'CA'),
(3019, '_apd_zip', '90225'),
(3019, '_apd_price_range', '$$$'),
(3019, '_apd_hours', 'Mon-Fri: 8am-5pm'),
(3019, '_apd_rating', '4.8'),
(3019, '_apd_review_count', '156'),
(3019, '_apd_views', '4321');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3019, 1006), (3019, 1062), (3019, 2002), (3019, 2006), (3019, 2009);

-- 20. Published - Pharmacy
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3020, 2, '2024-01-15 07:00:00', '2024-01-15 07:00:00', 'Community Pharmacy offers personalized pharmaceutical care. Prescription delivery, compounding services, and immunizations. Your neighborhood pharmacy since 1975.', 'Community Pharmacy', 'Personalized pharmaceutical care since 1975', 'publish', 'community-pharmacy', 'apd_listing', '2024-01-15 07:00:00', '2024-01-15 07:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3020, '_apd_phone', '(555) 000-1111'),
(3020, '_apd_email', 'pharmacy@communityrx.example.com'),
(3020, '_apd_website', 'https://communityrx.example.com'),
(3020, '_apd_address', '800 Medicine Street'),
(3020, '_apd_city', 'Medical Center'),
(3020, '_apd_state', 'CA'),
(3020, '_apd_zip', '90225'),
(3020, '_apd_price_range', '$$'),
(3020, '_apd_hours', 'Mon-Sat: 8am-9pm, Sun: 10am-6pm'),
(3020, '_apd_rating', '4.7'),
(3020, '_apd_review_count', '198'),
(3020, '_apd_views', '5432');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3020, 1006), (3020, 1063), (3020, 2002), (3020, 2008), (3020, 2009);

-- ============================================
-- ADDITIONAL LISTINGS FOR VARIETY
-- ============================================

-- 21. Pending - New restaurant
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3021, 3, '2025-01-22 11:00:00', '2025-01-22 11:00:00', 'Authentic Thai cuisine made with love. Fresh ingredients, bold flavors, and friendly service. Dine-in, takeout, and delivery available.', 'Thai Orchid Kitchen', 'Authentic Thai cuisine with fresh ingredients', 'pending', 'thai-orchid-kitchen', 'apd_listing', '2025-01-22 11:00:00', '2025-01-22 11:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3021, '_apd_phone', '(555) 111-3333'),
(3021, '_apd_email', 'order@thaiorchid.example.com'),
(3021, '_apd_address', '150 Spice Road'),
(3021, '_apd_city', 'Asian District'),
(3021, '_apd_state', 'CA'),
(3021, '_apd_zip', '90226'),
(3021, '_apd_price_range', '$$'),
(3021, '_apd_views', '23');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3021, 1001), (3021, 2008);

-- 22. Draft - Incomplete listing
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3022, 1, '2025-01-19 15:00:00', '2025-01-19 15:00:00', 'Coming soon - new fitness studio opening in March!', 'Zen Yoga Studio', '', 'draft', 'zen-yoga-studio', 'apd_listing', '2025-01-19 15:00:00', '2025-01-19 15:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3022, '_apd_phone', '(555) 222-4444'),
(3022, '_apd_views', '5');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3022, 1005), (3022, 1053);

-- 23. Published - High review count listing
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3023, 1, '2023-06-01 10:00:00', '2023-06-01 10:00:00', 'Downtown Deli has been serving the best sandwiches in town for over 40 years. Fresh-baked bread, quality meats, and homemade sides. A local favorite.', 'Downtown Deli', 'Best sandwiches in town since 1983', 'publish', 'downtown-deli', 'apd_listing', '2023-06-01 10:00:00', '2023-06-01 10:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3023, '_apd_phone', '(555) 333-5555'),
(3023, '_apd_email', 'orders@downtowndeli.example.com'),
(3023, '_apd_website', 'https://downtowndeli.example.com'),
(3023, '_apd_address', '50 Main Street'),
(3023, '_apd_city', 'Downtown'),
(3023, '_apd_state', 'CA'),
(3023, '_apd_zip', '90210'),
(3023, '_apd_price_range', '$'),
(3023, '_apd_hours', 'Mon-Sat: 7am-4pm'),
(3023, '_apd_rating', '4.9'),
(3023, '_apd_review_count', '1247'),
(3023, '_apd_views', '45678');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3023, 1001), (3023, 2004), (3023, 2006), (3023, 2008), (3023, 2009);

-- 24. Published - Low rating listing
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3024, 2, '2024-08-01 12:00:00', '2024-08-01 12:00:00', 'Basic laundromat with wash and dry services. Coin operated machines, some with card readers. Open late for your convenience.', 'Quick Wash Laundromat', 'Self-service laundry open late', 'publish', 'quick-wash-laundromat', 'apd_listing', '2024-08-01 12:00:00', '2024-08-01 12:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3024, '_apd_phone', '(555) 444-6666'),
(3024, '_apd_address', '999 Wash Way'),
(3024, '_apd_city', 'Eastside'),
(3024, '_apd_state', 'CA'),
(3024, '_apd_zip', '90227'),
(3024, '_apd_price_range', '$'),
(3024, '_apd_hours', 'Daily: 6am-12am'),
(3024, '_apd_rating', '2.8'),
(3024, '_apd_review_count', '45'),
(3024, '_apd_views', '1234');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3024, 1004), (3024, 2005);

-- 25. Published - No reviews yet
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_type, post_modified, post_modified_gmt) VALUES
(3025, 3, '2025-01-25 14:00:00', '2025-01-25 14:00:00', 'Brand new pet grooming salon! Professional grooming services for dogs and cats. Organic shampoos, gentle handling, and stylish cuts.', 'Pampered Paws Pet Spa', 'Professional pet grooming with organic products', 'publish', 'pampered-paws-pet-spa', 'apd_listing', '2025-01-25 14:00:00', '2025-01-25 14:00:00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(3025, '_apd_phone', '(555) 555-7777'),
(3025, '_apd_email', 'book@pamperedpaws.example.com'),
(3025, '_apd_website', 'https://pamperedpaws.example.com'),
(3025, '_apd_address', '888 Pet Lane'),
(3025, '_apd_city', 'Suburbia'),
(3025, '_apd_state', 'CA'),
(3025, '_apd_zip', '90228'),
(3025, '_apd_price_range', '$$'),
(3025, '_apd_hours', 'Tue-Sat: 9am-6pm'),
(3025, '_apd_rating', '0'),
(3025, '_apd_review_count', '0'),
(3025, '_apd_views', '89');

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (3025, 1004), (3025, 2001), (3025, 2004), (3025, 2009);
