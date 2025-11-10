-- SQL script to insert 10 random Fortune 500 companies
-- with random start of week (Saturday or Sunday) and whole hour start times

INSERT INTO clockGroup (groupName, weekStartDOW, weekStartTime) VALUES
('Walmart', 'Saturday', '08:00:00'),
('Amazon', 'Sunday', '00:00:00'),
('Apple', 'Saturday', '12:00:00'),
('Microsoft', 'Sunday', '06:00:00'),
('CVS Health', 'Saturday', '18:00:00'),
('UnitedHealth Group', 'Sunday', '14:00:00'),
('Berkshire Hathaway', 'Saturday', '09:00:00'),
('Alphabet', 'Sunday', '03:00:00'),
('Exxon Mobil', 'Saturday', '21:00:00'),
('McKesson', 'Sunday', '11:00:00');

