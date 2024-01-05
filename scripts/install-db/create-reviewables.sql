CREATE TABLE IF NOT EXISTS `ReviewableDef` (
  `id` INTEGER PRIMARY KEY,
  `name` TEXT UNIQUE NOT NULL CHECK (`name` <> '')
);

CREATE TABLE IF NOT EXISTS `ReviewableFieldDef` (
  `id` INTEGER PRIMARY KEY,
  `def_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL CHECK (`name` <> ''),
  `type` TEXT NOT NULL CHECK(`type` = 'text' OR `type` = 'integer'),
  FOREIGN KEY (`def_id`) REFERENCES `ReviewableDef` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `Reviewable` (
  `id` INTEGER PRIMARY KEY,
  `def_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL CHECK (`name` <> ''),
  FOREIGN KEY (`def_id`) REFERENCES `ReviewableDef` (`id`)
);

CREATE TABLE IF NOT EXISTS `TextFieldValues` (
  `id` INTEGER PRIMARY KEY,
  `field_id` INTEGER NOT NULL,
  `reviewable_id` INTEGER NOT NULL,
  `value` TEXT NOT NULL CHECK (`value` <> ''),
  FOREIGN KEY (`field_id`) REFERENCES `ReviewableFieldDef` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewable_id`) REFERENCES `Reviewable` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `IntegerFieldValues` (
  `id` INTEGER PRIMARY KEY,
  `field_id` INTEGER NOT NULL,
  `reviewable_id` INTEGER NOT NULL,
  `value` INTEGER NOT NULL,
  FOREIGN KEY (`field_id`) REFERENCES `ReviewableFieldDef` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewable_id`) REFERENCES `Reviewable` (`id`) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_reviewable_field_def_unique
  ON ReviewableFieldDef (def_id, name);

CREATE INDEX IF NOT EXISTS idx_reviewable_field_def_datatype
  ON ReviewableFieldDef(type);
