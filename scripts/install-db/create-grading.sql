CREATE TABLE IF NOT EXISTS `GradingScale` (
  `id` INTEGER PRIMARY KEY,
  `name` TEXT UNIQUE NOT NULL CHECK (`name` <> '')
);

-- We can declare multiple criteria for a single scale
-- This allows us to have multiple criteria for a single reviewable (e.g. "Soundtrack", "Scenario", ...)
CREATE TABLE IF NOT EXISTS `GradingCriteria` (
  `id` INTEGER PRIMARY KEY,
  `scale_id` INTEGER NOT NULL,
  `name` TEXT, -- NULL value will fallback to the grading scale name
  `min_value` INTEGER,
  `max_value` INTEGER,
  FOREIGN KEY (`scale_id`) REFERENCES `GradingScale` (`id`) ON DELETE CASCADE,
  UNIQUE(scale_id, name)
);

CREATE TABLE IF NOT EXISTS `Grade` (
  `id` INTEGER PRIMARY KEY,
  `reviewable_id` INTEGER NOT NULL,
  `criteria_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `value` INTEGER,
  FOREIGN KEY (`reviewable_id`) REFERENCES `Reviewable` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`criteria_id`) REFERENCES `GradingCriteria` (`id`) ON DELETE CASCADE,
  UNIQUE(reviewable_id, criteria_id, user_id)
);

CREATE TABLE IF NOT EXISTS `GradeModifier` (
  `id` INTEGER PRIMARY KEY,
  `grade_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL,
  `value` INTEGER,
  FOREIGN KEY (`grade_id`) REFERENCES `Grade` (`id`) ON DELETE CASCADE,
  UNIQUE(grade_id, name)
);
