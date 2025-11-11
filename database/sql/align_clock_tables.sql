-- Align legacy clock tables with current local schema
-- Run against the production database AFTER taking a full backup.

-- clockGroup adjustments
ALTER TABLE `clockGroup`
  MODIFY `groupName` VARCHAR(50) NOT NULL,
  MODIFY `weekStartDOW` VARCHAR(20) NOT NULL,
  MODIFY `weekStartTime` VARCHAR(20) NOT NULL;

ALTER TABLE `clockGroup`
  DROP COLUMN IF EXISTS `created_at`,
  DROP COLUMN IF EXISTS `updated_at`;

-- clockUser adjustments
ALTER TABLE `clockUser`
  MODIFY `userCode` VARCHAR(10) NOT NULL,
  MODIFY `groupId` INT(11) NOT NULL,
  MODIFY `name` VARCHAR(30) NOT NULL;

ALTER TABLE `clockUser`
  DROP COLUMN IF EXISTS `created_at`,
  DROP COLUMN IF EXISTS `updated_at`;

ALTER TABLE `clockUser`
  DROP INDEX IF EXISTS `clockUser_userCode_unique`,
  DROP INDEX IF EXISTS `clockuser_usercode_unique`;

-- clockEvent adjustments
ALTER TABLE `clockEvent`
  MODIFY `userId` INT(11) NOT NULL,
  MODIFY `eventTime` VARCHAR(20) NOT NULL,
  MODIFY `inOrOut` VARCHAR(5) NOT NULL;

ALTER TABLE `clockEvent`
  DROP COLUMN IF EXISTS `created_at`,
  DROP COLUMN IF EXISTS `updated_at`;

ALTER TABLE `clockEvent`
  DROP INDEX IF EXISTS `clockEvent_userId_index`,
  DROP INDEX IF EXISTS `clockEvent_eventTime_index`,
  DROP INDEX IF EXISTS `clockevent_userid_index`,
  DROP INDEX IF EXISTS `clockevent_eventtime_index`;

