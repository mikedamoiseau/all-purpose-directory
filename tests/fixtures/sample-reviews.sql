-- Sample Reviews Fixture
--
-- This file contains sample review data for integration and E2E tests.
-- Load with: mysql -u user -p database < sample-reviews.sql
-- Or use the load-fixtures.sh script.
--
-- Contains reviews for various listings:
-- - Mix of ratings (1-5 stars)
-- - Approved and pending reviews
-- - Different authors

-- Note: This fixture depends on sample-listings.sql being loaded first.

-- ============================================
-- CLEANUP
-- ============================================

DELETE FROM wp_commentmeta WHERE comment_id IN (SELECT comment_ID FROM wp_comments WHERE comment_type = 'apd_review');
DELETE FROM wp_comments WHERE comment_type = 'apd_review';

-- ============================================
-- REVIEWS FOR THE GOLDEN FORK (3001) - Fine Dining
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5001, 3001, 'Sarah Mitchell', 'sarah.m@example.com', '2025-01-16 19:30:00', '2025-01-16 19:30:00', 'Absolutely stunning experience! The tasting menu was incredible, and the wine pairings were perfect. Our server was knowledgeable and attentive. Worth every penny for a special occasion.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5001, '_apd_rating', '5'), (5001, '_apd_title', 'Perfect anniversary dinner');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5002, 3001, 'Michael Chen', 'mchen@example.com', '2025-01-18 20:15:00', '2025-01-18 20:15:00', 'Great food and ambiance. The steak was cooked perfectly. Only giving 4 stars because parking was a bit difficult to find, but the valet service helped.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5002, '_apd_rating', '4'), (5002, '_apd_title', 'Excellent food, tricky parking');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5003, 3001, 'Jennifer Adams', 'jadams@example.com', '2025-01-20 21:00:00', '2025-01-20 21:00:00', 'Came here for a business dinner. Impressive wine list and the service was impeccable. Would definitely bring clients here again.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5003, '_apd_rating', '5'), (5003, '_apd_title', 'Perfect for business dinners');

-- ============================================
-- REVIEWS FOR MORNING BREW CAFE (3002)
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5004, 3002, 'David Thompson', 'dthompson@example.com', '2025-01-11 08:45:00', '2025-01-11 08:45:00', 'Best coffee in town! I come here every morning before work. The baristas remember my order and the atmosphere is warm and welcoming.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5004, '_apd_rating', '5'), (5004, '_apd_title', 'My daily coffee spot');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5005, 3002, 'Emily Watson', 'ewatson@example.com', '2025-01-12 10:30:00', '2025-01-12 10:30:00', 'Great place to work remotely. Fast WiFi, plenty of outlets, and the pastries are delicious. Gets a bit crowded around noon though.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5005, '_apd_rating', '4'), (5005, '_apd_title', 'Perfect work-from-cafe spot');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5006, 3002, 'Alex Rivera', 'arivera@example.com', '2025-01-14 09:15:00', '2025-01-14 09:15:00', 'Love their pour-over coffee and the avocado toast is amazing. Staff is always friendly and the music playlist is great.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5006, '_apd_rating', '5'), (5006, '_apd_title', 'Excellent coffee and food');

-- Pending review
INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5007, 3002, 'New Reviewer', 'newbie@example.com', '2025-01-25 11:00:00', '2025-01-25 11:00:00', 'Just discovered this place! Really enjoyed my latte.', 0, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5007, '_apd_rating', '4'), (5007, '_apd_title', 'Nice find!');

-- ============================================
-- REVIEWS FOR GRAND PLAZA HOTEL (3005)
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5008, 3005, 'Robert Johnson', 'rjohnson@example.com', '2024-12-15 14:00:00', '2024-12-15 14:00:00', 'Stayed for a weekend getaway. The rooftop pool has amazing views. Room was spacious and clean. Concierge helped us get dinner reservations at a sold-out restaurant!', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5008, '_apd_rating', '5'), (5008, '_apd_title', 'Wonderful weekend stay');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5009, 3005, 'Linda Martinez', 'lmartinez@example.com', '2024-12-20 11:30:00', '2024-12-20 11:30:00', 'Business trip stay. Great location, excellent WiFi, and the business center had everything I needed. Breakfast buffet was impressive.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5009, '_apd_rating', '5'), (5009, '_apd_title', 'Ideal for business travel');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5010, 3005, 'James Wilson', 'jwilson@example.com', '2025-01-05 10:00:00', '2025-01-05 10:00:00', 'Beautiful hotel but the room was smaller than expected for the price. Staff was very professional though, and the spa was excellent.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5010, '_apd_rating', '4'), (5010, '_apd_title', 'Great service, small room');

-- ============================================
-- REVIEWS FOR PRECISION AUTO CARE (3011)
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5011, 3011, 'Tom Baker', 'tbaker@example.com', '2024-09-10 16:00:00', '2024-09-10 16:00:00', 'Honest mechanics are hard to find, and these guys are the real deal. They explained everything clearly and didnt try to upsell unnecessary services. Will definitely be back.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5011, '_apd_rating', '5'), (5011, '_apd_title', 'Finally found honest mechanics');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5012, 3011, 'Nancy Davis', 'ndavis@example.com', '2024-10-22 09:30:00', '2024-10-22 09:30:00', 'Brought my car in for a brake job. Fair price, quick turnaround, and they even washed my car before returning it. Great service!', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5012, '_apd_rating', '5'), (5012, '_apd_title', 'Excellent brake service');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5013, 3011, 'Steve Miller', 'smiller@example.com', '2024-12-05 14:45:00', '2024-12-05 14:45:00', 'Very professional. They diagnosed an issue that another shop missed. A bit more expensive than some competitors but worth it for the quality and honesty.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5013, '_apd_rating', '4'), (5013, '_apd_title', 'Professional and thorough');

-- ============================================
-- REVIEWS FOR DOWNTOWN DELI (3023) - High volume
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5014, 3023, 'Chris Anderson', 'canderson@example.com', '2024-06-15 12:30:00', '2024-06-15 12:30:00', 'The pastrami sandwich here is legendary. Been coming for years and it never disappoints. Quick service even during the lunch rush.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5014, '_apd_rating', '5'), (5014, '_apd_title', 'Best pastrami in town');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5015, 3023, 'Maria Garcia', 'mgarcia@example.com', '2024-07-20 13:00:00', '2024-07-20 13:00:00', 'A true classic! The bread is always fresh and the portions are generous. Love their homemade coleslaw too.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5015, '_apd_rating', '5'), (5015, '_apd_title', 'Classic deli experience');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5016, 3023, 'Kevin Brown', 'kbrown@example.com', '2024-08-12 11:45:00', '2024-08-12 11:45:00', 'Great sandwiches at reasonable prices. Gets crowded at lunch but the line moves fast. Try the Reuben!', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5016, '_apd_rating', '5'), (5016, '_apd_title', 'Worth the wait');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5017, 3023, 'Amy White', 'awhite@example.com', '2024-09-28 12:15:00', '2024-09-28 12:15:00', 'Solid sandwiches. Nothing fancy but done right. Wish they were open on Sundays though.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5017, '_apd_rating', '4'), (5017, '_apd_title', 'Reliable lunch spot');

-- ============================================
-- REVIEWS FOR QUICK WASH LAUNDROMAT (3024) - Mixed reviews
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5018, 3024, 'Paul Turner', 'pturner@example.com', '2024-08-15 20:00:00', '2024-08-15 20:00:00', 'Machines are old and some dont work properly. At least its open late which is convenient.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5018, '_apd_rating', '2'), (5018, '_apd_title', 'Needs better maintenance');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5019, 3024, 'Lisa Chang', 'lchang@example.com', '2024-09-20 18:30:00', '2024-09-20 18:30:00', 'Its okay for basic laundry needs. Prices are reasonable and its usually not too crowded. Could use some cleaning.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5019, '_apd_rating', '3'), (5019, '_apd_title', 'Gets the job done');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5020, 3024, 'Mark Johnson', 'mjohnson@example.com', '2024-11-10 21:15:00', '2024-11-10 21:15:00', 'One dryer ate my quarters without working. Tried to get a refund but no attendant was on duty. Very frustrating.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5020, '_apd_rating', '1'), (5020, '_apd_title', 'Lost money to broken machine');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5021, 3024, 'Rachel Kim', 'rkim@example.com', '2025-01-05 19:45:00', '2025-01-05 19:45:00', 'They finally added card readers to some machines. Still not the cleanest place but its improving.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5021, '_apd_rating', '3'), (5021, '_apd_title', 'Slowly getting better');

-- ============================================
-- REVIEWS FOR FITLIFE GYM (3017)
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5022, 3017, 'Jason Lee', 'jlee@example.com', '2024-05-20 06:30:00', '2024-05-20 06:30:00', 'Great 24-hour gym. Equipment is well-maintained and theres usually a machine available even during peak hours. Staff is friendly.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5022, '_apd_rating', '5'), (5022, '_apd_title', 'Best 24-hour gym around');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5023, 3017, 'Samantha Green', 'sgreen@example.com', '2024-07-15 18:00:00', '2024-07-15 18:00:00', 'Love the group fitness classes! The instructors are motivating and the schedule is convenient. Locker rooms could be cleaner though.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5023, '_apd_rating', '4'), (5023, '_apd_title', 'Great classes!');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5024, 3017, 'Derek Taylor', 'dtaylor@example.com', '2024-10-01 20:30:00', '2024-10-01 20:30:00', 'Good variety of equipment and the personal trainers are knowledgeable. The free first week helped me decide to join.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5024, '_apd_rating', '4'), (5024, '_apd_title', 'Solid gym membership');

-- ============================================
-- REVIEWS FOR BRIGHT SMILE DENTAL (3019)
-- ============================================

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5025, 3019, 'Amanda Foster', 'afoster@example.com', '2024-03-10 10:00:00', '2024-03-10 10:00:00', 'Dr. Johnson is amazing with kids! My daughter used to be terrified of the dentist but now she actually looks forward to her visits.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5025, '_apd_rating', '5'), (5025, '_apd_title', 'Fantastic with children');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5026, 3019, 'William Brown', 'wbrown@example.com', '2024-06-22 14:30:00', '2024-06-22 14:30:00', 'Had a root canal done here. Was dreading it but the sedation option made it painless. Very professional and caring staff.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5026, '_apd_rating', '5'), (5026, '_apd_title', 'Painless root canal');

INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5027, 3019, 'Catherine Moore', 'cmoore@example.com', '2024-09-15 11:00:00', '2024-09-15 11:00:00', 'Clean office, modern equipment, and they actually run on time! Rare for a dental office. Highly recommend.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5027, '_apd_rating', '5'), (5027, '_apd_title', 'Punctual and professional');

-- ============================================
-- ADDITIONAL REVIEWS FOR VARIETY
-- ============================================

-- Review for Quick Bites Burgers (3003)
INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5028, 3003, 'Brian Scott', 'bscott@example.com', '2025-01-10 13:30:00', '2025-01-10 13:30:00', 'Best burgers without breaking the bank! The Triple Stack is massive. Great value for families.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5028, '_apd_rating', '4'), (5028, '_apd_title', 'Great value for families');

-- Review for TechZone (3009)
INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5029, 3009, 'Michelle Young', 'myoung@example.com', '2024-11-15 15:00:00', '2024-11-15 15:00:00', 'Staff really knows their stuff. Helped me find the right laptop for my needs without pushing the most expensive option.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5029, '_apd_rating', '5'), (5029, '_apd_title', 'Knowledgeable and honest');

-- Review for Neon Nights Club (3015)
INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5030, 3015, 'Tyler Adams', 'tadams@example.com', '2024-12-31 02:30:00', '2024-12-31 02:30:00', 'NYE party was incredible! Great music, nice crowd, and the VIP service was top-notch. A bit pricey but worth it for special occasions.', 1, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5030, '_apd_rating', '4'), (5030, '_apd_title', 'Amazing NYE party');

-- Pending review for Starlight Cinema (3016)
INSERT INTO wp_comments (comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, comment_approved, comment_type) VALUES
(5031, 3016, 'New User', 'newuser@example.com', '2025-01-28 22:00:00', '2025-01-28 22:00:00', 'Watched the new Marvel movie here. IMAX was awesome but the food prices are outrageous!', 0, 'apd_review');
INSERT INTO wp_commentmeta (comment_id, meta_key, meta_value) VALUES (5031, '_apd_rating', '3'), (5031, '_apd_title', 'Great IMAX, expensive food');
