CREATE TABLE IF NOT EXISTS `Definition` (
  `id` INTEGER PRIMARY KEY,
  `name` TEXT UNIQUE NOT NULL CHECK (`name` <> ''),
  `scale_id` INTEGER NOT NULL,
  FOREIGN KEY (`scale_id`) REFERENCES `GradingScale` (`id`) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `DefinitionField` (
  `id` INTEGER PRIMARY KEY,
  `def_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL CHECK (`name` <> ''),
  `type` TEXT NOT NULL CHECK(`type` = 'text' OR `type` = 'integer'),
  FOREIGN KEY (`def_id`) REFERENCES `Definition` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `Reviewable` (
  `id` INTEGER PRIMARY KEY,
  `def_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL CHECK (`name` <> ''),
  FOREIGN KEY (`def_id`) REFERENCES `Definition` (`id`)
);

CREATE TABLE IF NOT EXISTS `TextFieldValues` (
  `id` INTEGER PRIMARY KEY,
  `field_id` INTEGER NOT NULL,
  `reviewable_id` INTEGER NOT NULL,
  `value` TEXT NOT NULL CHECK (`value` <> ''),
  FOREIGN KEY (`field_id`) REFERENCES `DefinitionField` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewable_id`) REFERENCES `Reviewable` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `IntegerFieldValues` (
  `id` INTEGER PRIMARY KEY,
  `field_id` INTEGER NOT NULL,
  `reviewable_id` INTEGER NOT NULL,
  `value` INTEGER NOT NULL,
  FOREIGN KEY (`field_id`) REFERENCES `DefinitionField` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewable_id`) REFERENCES `Reviewable` (`id`) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_reviewable_field_def_unique
  ON DefinitionField (def_id, name);

CREATE INDEX IF NOT EXISTS idx_reviewable_field_def_datatype
  ON DefinitionField(type);
