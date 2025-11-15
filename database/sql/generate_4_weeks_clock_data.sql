-- Generate 4 weeks of clock in/out data for users with 8 or fewer records
-- Enhanced version with:
-- 1. All clock in/out times are different (variation each day)
-- 2. Days off differ between users and weeks
-- 3. At least 1 transition event per user (early out, late in, missed out, or extra day)
-- Compatible with MySQL 5.7+ and MySQL 8.0+

-- Calculate date range: 4 weeks starting from 4 weeks ago, ending yesterday
SET @start_date = DATE_SUB(CURDATE(), INTERVAL 4 WEEK);
SET @end_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);

-- Create temporary table with users who need data (8 or fewer records)
DROP TEMPORARY TABLE IF EXISTS users_needing_data;
CREATE TEMPORARY TABLE users_needing_data AS
SELECT 
    u.id AS userId,
    FLOOR(RAND() * 21) + 30 AS target_hours_per_week,  -- Random 30-50 hours
    FLOOR(RAND() * 5) + 7 AS base_clock_in_hour,  -- Base hour 7-11 AM
    FLOOR(RAND() * 60) AS base_clock_in_minute,  -- Base minute 0-59
    FLOOR(RAND() * 2) AS base_lunch_duration  -- Base lunch 0 or 1 hour
FROM clockUser u
WHERE (
    SELECT COUNT(*) 
    FROM clockEvent e 
    WHERE e.userId = u.id
) <= 8;

-- Create temporary table for work schedule (days off per user per week)
DROP TEMPORARY TABLE IF EXISTS user_work_schedule;
CREATE TEMPORARY TABLE user_work_schedule AS
SELECT 
    u.userId,
    w.week_num,
    FLOOR(RAND() * 7) AS day_off_1,
    FLOOR(RAND() * 6) AS day_off_2_temp
FROM users_needing_data u
CROSS JOIN (
    SELECT 0 AS week_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
) w;

-- Fix day_off_2 to ensure it's different from day_off_1 for each user/week
ALTER TABLE user_work_schedule ADD COLUMN day_off_2 INT;
UPDATE user_work_schedule 
SET day_off_2 = CASE 
    WHEN day_off_2_temp >= day_off_1 THEN MOD(day_off_2_temp + 1, 7)
    ELSE day_off_2_temp
END;
UPDATE user_work_schedule 
SET day_off_2 = MOD(day_off_1 + 1, 7)
WHERE day_off_2 = day_off_1;
ALTER TABLE user_work_schedule DROP COLUMN day_off_2_temp;

-- Create temporary table for transition events (at least 1 per user)
-- We'll pick a random work day for each user (will be set after work_days table is created)
DROP TEMPORARY TABLE IF EXISTS user_transitions;
CREATE TEMPORARY TABLE user_transitions (
    userId INT,
    transition_date DATE,
    transition_type INT
);

-- After work_days is created, we'll populate this with a random work day per user

-- Create a work days table that maps each user/week/day combination
DROP TEMPORARY TABLE IF EXISTS user_work_days;
CREATE TEMPORARY TABLE user_work_days AS
SELECT 
    u.userId,
    w.week_num,
    d.day_num,
    DATE_ADD(@start_date, INTERVAL (w.week_num * 7 + d.day_num) DAY) AS work_date,
    DAYOFWEEK(DATE_ADD(@start_date, INTERVAL (w.week_num * 7 + d.day_num) DAY)) - 1 AS day_of_week
FROM users_needing_data u
CROSS JOIN (
    SELECT 0 AS week_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
) w
CROSS JOIN (
    SELECT 0 AS day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION 
    SELECT 4 UNION SELECT 5 UNION SELECT 6
) d
INNER JOIN user_work_schedule ws ON ws.userId = u.userId AND ws.week_num = w.week_num
WHERE DAYOFWEEK(DATE_ADD(@start_date, INTERVAL (w.week_num * 7 + d.day_num) DAY)) - 1 
    NOT IN (ws.day_off_1, ws.day_off_2)
AND DATE_ADD(@start_date, INTERVAL (w.week_num * 7 + d.day_num) DAY) <= @end_date;

-- Populate transition events with random work days (types 0, 1, 2) or any day (type 3)
INSERT INTO user_transitions (userId, transition_date, transition_type)
SELECT 
    uwt.userId,
    CASE 
        WHEN uwt.transition_type = 3 THEN 
            -- Type 3 (extra_day): any day including off days
            DATE_ADD(@start_date, INTERVAL FLOOR(RAND() * 28) DAY)
        ELSE 
            -- Types 0, 1, 2: must be a work day
            (SELECT uwd2.work_date
             FROM user_work_days uwd2
             WHERE uwd2.userId = uwt.userId
             ORDER BY RAND()
             LIMIT 1)
    END AS transition_date,
    uwt.transition_type
FROM (
    SELECT 
        u.userId,
        FLOOR(RAND() * 4) AS transition_type  -- 0=early_out, 1=late_in, 2=missed_out, 3=extra_day
    FROM users_needing_data u
) AS uwt;

-- Generate clock IN events for all work days with variation
INSERT INTO clockEvent (userId, eventTime, inOrOut)
SELECT 
    uwd.userId,
    ADDTIME(
        CONCAT(
            uwd.work_date,
            ' ',
            LPAD(u.base_clock_in_hour, 2, '0'), ':',
            LPAD(u.base_clock_in_minute, 2, '0'), ':00'
        ),
        SEC_TO_TIME(
            -- Add random variation: -30 to +60 minutes for each clock in
            (FLOOR(RAND() * 91) - 30) * 60 +
            -- Add extra late variation for late_in transition events
            CASE 
                WHEN t.transition_type = 1 AND DATE(t.transition_date) = uwd.work_date
                THEN 60 * (FLOOR(RAND() * 121) + 30)  -- Late in: +30 to +150 minutes
                ELSE 0
            END
        )
    ) AS eventTime,
    'IN' AS inOrOut
FROM user_work_days uwd
INNER JOIN users_needing_data u ON u.userId = uwd.userId
LEFT JOIN user_transitions t ON t.userId = uwd.userId;

-- Generate clock OUT events with variation (excluding missed out transitions)
INSERT INTO clockEvent (userId, eventTime, inOrOut)
SELECT 
    uwd.userId,
    ADDTIME(
        (SELECT MAX(eventTime) 
         FROM clockEvent ce2 
         WHERE ce2.userId = uwd.userId 
         AND ce2.inOrOut = 'IN'
         AND DATE(ce2.eventTime) = uwd.work_date
         LIMIT 1),
        SEC_TO_TIME(
            ROUND((u.target_hours_per_week / 5) * 3600) +  -- Base daily hours
            (u.base_lunch_duration * 3600) +  -- Lunch break
            -- Add random variation: -15 to +45 minutes for each clock out
            (FLOOR(RAND() * 61) - 15) * 60 +
            -- Add early variation for early_out transition events
            CASE 
                WHEN t.transition_type = 0 AND DATE(t.transition_date) = uwd.work_date
                THEN -60 * (FLOOR(RAND() * 121) + 60)  -- Early out: -60 to -180 minutes
                ELSE 0
            END
        )
    ) AS eventTime,
    'OUT' AS inOrOut
FROM user_work_days uwd
INNER JOIN users_needing_data u ON u.userId = uwd.userId
LEFT JOIN user_transitions t ON t.userId = uwd.userId
-- Skip clock out for missed_out transition events
WHERE NOT (
    t.transition_type = 2 AND DATE(t.transition_date) = uwd.work_date
);

-- Handle extra day transition events (type 3) - add an extra work day
-- Find an off day near the transition_date and use it as an extra work day
INSERT INTO clockEvent (userId, eventTime, inOrOut)
SELECT 
    u.userId,
    ADDTIME(
        CONCAT(
            -- Find an off day in the week containing transition_date
            (SELECT DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)
             FROM (SELECT 0 AS day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d2
             INNER JOIN user_work_schedule ws2 ON ws2.userId = u.userId 
                 AND ws2.week_num = FLOOR(DATEDIFF(t.transition_date, @start_date) / 7)
             WHERE DAYOFWEEK(DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)) - 1 
                 IN (ws2.day_off_1, ws2.day_off_2)
             ORDER BY d2.day_num
             LIMIT 1),
            ' ',
            LPAD(u.base_clock_in_hour + 1, 2, '0'), ':',
            LPAD(u.base_clock_in_minute, 2, '0'), ':00'
        ),
        SEC_TO_TIME(FLOOR(RAND() * 61) * 60)  -- Variation 0-60 minutes
    ) AS eventTime,
    'IN' AS inOrOut
FROM users_needing_data u
INNER JOIN user_transitions t ON t.userId = u.userId
WHERE t.transition_type = 3
AND (SELECT DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)
     FROM (SELECT 0 AS day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d2
     INNER JOIN user_work_schedule ws2 ON ws2.userId = u.userId 
         AND ws2.week_num = FLOOR(DATEDIFF(t.transition_date, @start_date) / 7)
     WHERE DAYOFWEEK(DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)) - 1 
         IN (ws2.day_off_1, ws2.day_off_2)
     ORDER BY d2.day_num
     LIMIT 1) <= @end_date;

-- Generate clock out for extra day events
INSERT INTO clockEvent (userId, eventTime, inOrOut)
SELECT 
    u.userId,
    ADDTIME(
        (SELECT MAX(eventTime) 
         FROM clockEvent ce2 
         WHERE ce2.userId = u.userId 
         AND ce2.inOrOut = 'IN'
         AND DATE(ce2.eventTime) = (
             SELECT DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)
             FROM (SELECT 0 AS day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d2
             INNER JOIN user_work_schedule ws2 ON ws2.userId = u.userId 
                 AND ws2.week_num = FLOOR(DATEDIFF(t.transition_date, @start_date) / 7)
             WHERE DAYOFWEEK(DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)) - 1 
                 IN (ws2.day_off_1, ws2.day_off_2)
             ORDER BY d2.day_num
             LIMIT 1
         )
         LIMIT 1),
        SEC_TO_TIME(
            ROUND((u.target_hours_per_week / 5) * 3600) +  -- Base hours
            (u.base_lunch_duration * 3600) +  -- Lunch
            (FLOOR(RAND() * 61) - 15) * 60  -- Variation
        )
    ) AS eventTime,
    'OUT' AS inOrOut
FROM users_needing_data u
INNER JOIN user_transitions t ON t.userId = u.userId
WHERE t.transition_type = 3
AND (SELECT DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)
     FROM (SELECT 0 AS day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d2
     INNER JOIN user_work_schedule ws2 ON ws2.userId = u.userId 
         AND ws2.week_num = FLOOR(DATEDIFF(t.transition_date, @start_date) / 7)
     WHERE DAYOFWEEK(DATE_ADD(@start_date, INTERVAL (FLOOR(DATEDIFF(t.transition_date, @start_date) / 7) * 7 + d2.day_num) DAY)) - 1 
         IN (ws2.day_off_1, ws2.day_off_2)
     ORDER BY d2.day_num
     LIMIT 1) <= @end_date;

-- Clean up temporary tables
DROP TEMPORARY TABLE IF EXISTS users_needing_data;
DROP TEMPORARY TABLE IF EXISTS user_work_schedule;
DROP TEMPORARY TABLE IF EXISTS user_transitions;
DROP TEMPORARY TABLE IF EXISTS user_work_days;

-- Display summary of generated data
SELECT 
    'Clock data generation complete!' AS status,
    COUNT(DISTINCT userId) AS users_updated,
    COUNT(*) AS total_events_inserted,
    COUNT(DISTINCT DATE(eventTime)) AS unique_days,
    MIN(eventTime) AS earliest_event,
    MAX(eventTime) AS latest_event
FROM clockEvent
WHERE eventTime >= @start_date
AND eventTime <= DATE_ADD(@end_date, INTERVAL 1 DAY);
