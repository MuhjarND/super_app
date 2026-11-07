<?php

// Export only the bundled reference data. No database connection or credentials are used.
$root = dirname(__DIR__);
$source = json_decode(file_get_contents($root . '/resources/data/progress-zi/evidence-guide.json'), true, 512, JSON_THROW_ON_ERROR);
$destination = $argv[1] ?? $root . '/database/sql/panduan_eviden_zi.sql';
$sql = [];
function ziSql($value)
{
    if ($value === null) { return 'NULL'; }
    if (is_int($value)) { return (string) $value; }
    if (is_array($value)) { $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); }
    return $value === '' ? "''" : 'CONVERT(0x' . bin2hex((string) $value) . ' USING utf8mb4) COLLATE utf8mb4_unicode_ci';
}
function ziNormalize($value)
{
    return trim(preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower($value, 'UTF-8')));
}
function ziNormalizedSql($field)
{
    $value = 'LOWER(CONVERT(' . $field . ' USING utf8mb4) COLLATE utf8mb4_unicode_ci)';
    $characters = array_merge(str_split('!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~'), ["\t", "\r", "\n", "\xc2\xa0", '“', '”', '‘', '’', '–', '—', '•', '≥', '≤']);
    foreach ($characters as $character) { $value = 'REPLACE(' . $value . ', ' . ziSql($character) . ", ' ')"; }
    for ($i = 0; $i < 6; $i++) { $value = 'REPLACE(' . $value . ", '  ', ' ')"; }
    return 'TRIM(' . $value . ')';
}
function ziStatement($text) { global $sql; $sql[] = $text . ";\n"; }
function ziCandidate($variable, $fromAndWhere, $distinctId)
{
    ziStatement('SET ' . $variable . ' = COALESCE(' . $variable . ', (SELECT IF(COUNT(DISTINCT ' . $distinctId . ') = 1, MIN(' . $distinctId . '), NULL) ' . $fromAndWhere . '))');
}
function ziAvailableCode($table, $parent, $parentVariable, $preferred, $row, $variable)
{
    $base = $preferred && $preferred !== '-' ? $preferred : 'panduan-' . $row;
    ziStatement('SET ' . $variable . ' = ' . ziSql($base));
    ziStatement('SET ' . $variable . ' = IF(EXISTS(SELECT 1 FROM `' . $table . '` WHERE `' . $parent . '` = ' . $parentVariable . ' AND `code` = ' . $variable . '), ' . ziSql('panduan-' . $row) . ', ' . $variable . ')');
    ziStatement('SET ' . $variable . ' = IF(EXISTS(SELECT 1 FROM `' . $table . '` WHERE `' . $parent . '` = ' . $parentVariable . ' AND `code` = ' . $variable . "), CONCAT('p" . $row . "-', LEFT(REPLACE(UUID(), '-', ''), 8)), " . $variable . ')');
}
function ziEnsureColumn($table, $column, $definition)
{
    $query = 'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition;
    ziStatement('SET @zi_guide_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ' . ziSql($table) . ' AND COLUMN_NAME = ' . ziSql($column) . ') = 0, ' . ziSql($query) . ", 'SELECT 1')");
    ziStatement('PREPARE zi_guide_stmt FROM @zi_guide_ddl');
    ziStatement('EXECUTE zi_guide_stmt');
    ziStatement('DEALLOCATE PREPARE zi_guide_stmt');
}

$sql[] = "-- PANDUAN PENYUSUNAN EVIDEN ZI\n-- Pilih database aplikasi sebelum mengimpor melalui phpMyAdmin atau mysql client\n-- Memerlukan tabel dasar modul Progress ZI yang sudah tersedia\n-- MySQL 5.7+ / MariaDB 10.4+, tanpa stored routine dan tanpa ID tetap\n-- Sumber: " . $source['source_name'] . ' / ' . $source['sheet'] . "\n-- SHA-256 sumber: " . $source['source_sha256'] . "\n-- Kolom O disimpan di zi_evidence_guides.notes (Catatan/Keterangan/Penjelasan)\n-- Kolom I disimpan di explanation, catatan pengumpul eviden tidak diubah\n-- Tidak mengubah data kegiatan, persetujuan, PIC, file, tautan atau catatan pengumpul\n-- Contoh jawaban dan nilai Excel tidak diimpor sebagai penilaian\n-- Gambar contoh tersedia pada folder public/images/progress-zi/guide aplikasi terbaru\n";
ziStatement('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
ziEnsureColumn('zi_guideline_sub_points', 'evidence_guide_key', 'VARCHAR(50) NULL');
ziStatement("SET @zi_guide_ddl = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'zi_guideline_sub_points' AND COLUMN_NAME = 'evidence_guide_key' AND NON_UNIQUE = 0) = 0, 'ALTER TABLE `zi_guideline_sub_points` ADD UNIQUE INDEX `zi_guideline_sub_points_evidence_guide_key_unique` (`evidence_guide_key`)', 'SELECT 1')");
ziStatement('PREPARE zi_guide_stmt FROM @zi_guide_ddl');
ziStatement('EXECUTE zi_guide_stmt');
ziStatement('DEALLOCATE PREPARE zi_guide_stmt');
ziEnsureColumn('zi_evidences', 'drive_url', 'TEXT NULL');
ziStatement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `zi_evidence_guides` (
  `guide_key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `source_name` VARCHAR(255) NOT NULL,
  `source_sha256` VARCHAR(64) NOT NULL,
  `source_sheet` VARCHAR(100) NOT NULL,
  `source_row` INT UNSIGNED NOT NULL,
  `group_type` VARCHAR(30) NOT NULL,
  `area_code` VARCHAR(30) NOT NULL,
  `area_title` VARCHAR(255) NOT NULL,
  `area_guidance` LONGTEXT NULL,
  `point_key` VARCHAR(50) NOT NULL,
  `point_code` VARCHAR(30) NOT NULL,
  `point_title` VARCHAR(255) NOT NULL,
  `point_guidance` LONGTEXT NULL,
  `display_code` VARCHAR(20) NOT NULL,
  `title` TEXT NOT NULL,
  `explanation` LONGTEXT NULL,
  `notes` LONGTEXT NULL,
  `answer_format` VARCHAR(50) NULL,
  `supporting_data` LONGTEXT NOT NULL,
  `images` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);
$sql[] = "-- Seluruh DDL di atas selesai sebelum transaksi data dimulai\n";
ziStatement('START TRANSACTION');
foreach (['areas', 'points', 'subs'] as $name) { ziStatement('DROP TEMPORARY TABLE IF EXISTS `zi_guide_import_' . $name . '`'); }
ziStatement('CREATE TEMPORARY TABLE `zi_guide_import_areas` AS SELECT `id`, `group_type`, ' . ziNormalizedSql('`name`') . ' AS `normalized_title` FROM `zi_areas`');
ziStatement("UPDATE `zi_guide_import_areas` SET `normalized_title` = LEFT(`normalized_title`, CHAR_LENGTH(`normalized_title`) - 9) WHERE RIGHT(`normalized_title`, 9) = ' aparatur'");
ziStatement('CREATE TEMPORARY TABLE `zi_guide_import_points` AS SELECT `id`, `zi_area_id`, ' . ziNormalizedSql('`title`') . ' AS `normalized_title` FROM `zi_guideline_points`');
ziStatement('CREATE TEMPORARY TABLE `zi_guide_import_subs` AS SELECT `id`, `zi_guideline_point_id`, ' . ziNormalizedSql('`title`') . ' AS `normalized_title` FROM `zi_guideline_sub_points`');
$allKeys = [];
foreach ($source['areas'] as $area) {
    $areaKeys = [];
    foreach ($area['points'] as $point) { $areaKeys = array_merge($areaKeys, array_column($point['entries'], 'key')); }
    $keysSql = implode(', ', array_map('ziSql', $areaKeys));
    $group = ziSql($area['group']);
    $areaCode = 'PANDUAN-' . strtoupper(substr($area['group'], 0, 1)) . '-' . $area['code'];
    $sql[] = '-- Area ' . $area['key'] . ': ' . str_replace(["\r", "\n"], ' ', $area['title']) . "\n";
    ziStatement('SET @zi_guide_area = NULL');
    ziCandidate('@zi_guide_area', 'FROM `zi_areas` a JOIN `zi_guideline_points` p ON p.zi_area_id = a.id JOIN `zi_guideline_sub_points` s ON s.zi_guideline_point_id = p.id WHERE a.group_type = ' . $group . ' AND s.evidence_guide_key IN (' . $keysSql . ')', 'a.id');
    ziCandidate('@zi_guide_area', 'FROM `zi_guide_import_areas` a WHERE a.group_type = ' . $group . ' AND a.normalized_title = ' . ziSql(preg_replace('/\s+aparatur$/', '', ziNormalize($area['title']))), 'a.id');
    ziCandidate('@zi_guide_area', 'FROM `zi_areas` a WHERE a.group_type = ' . $group . ' AND a.code = ' . ziSql($areaCode), 'a.id');
    ziStatement('INSERT INTO `zi_areas` (`code`, `name`, `description`, `group_type`, `is_active`, `created_at`, `updated_at`) SELECT ' . implode(', ', [ziSql($areaCode), ziSql($area['title']), ziSql($area['guidance'] ?: null), $group, '1', 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP']) . ' WHERE @zi_guide_area IS NULL');
    ziStatement('SET @zi_guide_area = COALESCE(@zi_guide_area, LAST_INSERT_ID())');
    foreach ($area['points'] as $point) {
        $pointKeys = implode(', ', array_map('ziSql', array_column($point['entries'], 'key')));
        $titles = implode(', ', array_map(function ($entry) { return ziSql(ziNormalize($entry['title'])); }, $point['entries']));
        $pointTitleMatches = [ziSql(ziNormalize($point['title']))];
        if ($point['source_row'] === 8) { $pointTitleMatches = array_merge($pointTitleMatches, [ziSql('tim kerja'), ziSql('tim reformasi birokrasi')]); }
        ziStatement('SET @zi_guide_point = NULL');
        ziCandidate('@zi_guide_point', 'FROM `zi_guideline_points` p JOIN `zi_guideline_sub_points` s ON s.zi_guideline_point_id = p.id WHERE p.zi_area_id = @zi_guide_area AND s.evidence_guide_key IN (' . $pointKeys . ')', 'p.id');
        ziCandidate('@zi_guide_point', 'FROM `zi_guide_import_points` p WHERE p.zi_area_id = @zi_guide_area AND p.normalized_title IN (' . implode(', ', $pointTitleMatches) . ')', 'p.id');
        ziCandidate('@zi_guide_point', 'FROM `zi_guideline_points` p JOIN `zi_guide_import_subs` s ON s.zi_guideline_point_id = p.id WHERE p.zi_area_id = @zi_guide_area AND s.normalized_title IN (' . $titles . ')', 'p.id');
        ziAvailableCode('zi_guideline_points', 'zi_area_id', '@zi_guide_area', $point['code'], $point['source_row'], '@zi_guide_point_code');
        ziStatement('INSERT INTO `zi_guideline_points` (`zi_area_id`, `code`, `title`, `description`, `sort_order`, `created_at`, `updated_at`) SELECT @zi_guide_area, @zi_guide_point_code, ' . implode(', ', [ziSql($point['title']), ziSql($point['guidance'] ?: null), $point['source_row'], 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP']) . ' WHERE @zi_guide_point IS NULL');
        ziStatement('SET @zi_guide_point = COALESCE(@zi_guide_point, LAST_INSERT_ID())');
        ziStatement('UPDATE `zi_guideline_points` SET `sort_order` = ' . $point['source_row'] . ', `updated_at` = CURRENT_TIMESTAMP WHERE `id` = @zi_guide_point');
        foreach ($point['entries'] as $entry) {
            $allKeys[] = $entry['key'];
            $key = ziSql($entry['key']);
            ziStatement('SET @zi_guide_sub = (SELECT `id` FROM `zi_guideline_sub_points` WHERE `evidence_guide_key` = ' . $key . ' LIMIT 1)');
            ziCandidate('@zi_guide_sub', 'FROM `zi_guide_import_subs` c JOIN `zi_guideline_sub_points` s ON s.id = c.id WHERE c.zi_guideline_point_id = @zi_guide_point AND s.evidence_guide_key IS NULL AND c.normalized_title = ' . ziSql(ziNormalize($entry['title'])), 's.id');
            ziAvailableCode('zi_guideline_sub_points', 'zi_guideline_point_id', '@zi_guide_point', $entry['code'], $entry['source_row'], '@zi_guide_sub_code');
            ziStatement('INSERT INTO `zi_guideline_sub_points` (`zi_guideline_point_id`, `code`, `title`, `description`, `sort_order`, `evidence_guide_key`, `created_at`, `updated_at`) SELECT @zi_guide_point, @zi_guide_sub_code, ' . implode(', ', [ziSql($entry['title']), ziSql($entry['guidance'] ?: null), $entry['source_row'], $key, 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP']) . ' WHERE @zi_guide_sub IS NULL');
            ziStatement('SET @zi_guide_sub = COALESCE(@zi_guide_sub, LAST_INSERT_ID())');
            ziStatement('UPDATE `zi_guideline_sub_points` SET `evidence_guide_key` = ' . $key . ', `sort_order` = ' . $entry['source_row'] . ', `description` = CASE WHEN `description` IS NULL OR `description` = \'\' THEN ' . ziSql($entry['guidance'] ?: null) . ' ELSE `description` END, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = @zi_guide_sub');
            $payload = [
                'guide_key' => $entry['key'], 'source_name' => $source['source_name'], 'source_sha256' => $source['source_sha256'],
                'source_sheet' => $source['sheet'], 'source_row' => $entry['source_row'], 'group_type' => $area['group'],
                'area_code' => $area['code'], 'area_title' => $area['title'], 'area_guidance' => $area['guidance'],
                'point_key' => $point['key'], 'point_code' => $point['code'], 'point_title' => $point['title'], 'point_guidance' => $point['guidance'],
                'display_code' => $entry['code'], 'title' => $entry['title'], 'explanation' => $entry['explanation'], 'notes' => $entry['guidance'],
                'answer_format' => $entry['answer_format'], 'supporting_data' => $entry['supporting_data'], 'images' => $entry['images'],
            ];
            $columns = '`' . implode('`, `', array_keys($payload)) . '`';
            $updates = [];
            foreach (array_keys($payload) as $column) { if ($column !== 'guide_key') { $updates[] = '`' . $column . '` = VALUES(`' . $column . '`)'; } }
            ziStatement('INSERT INTO `zi_evidence_guides` (' . $columns . ', `created_at`, `updated_at`) VALUES (' . implode(', ', array_map('ziSql', array_values($payload))) . ', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE ' . implode(', ', $updates) . ', `updated_at` = CURRENT_TIMESTAMP');
        }
    }
}
ziStatement('COMMIT');
foreach (['areas', 'points', 'subs'] as $name) { ziStatement('DROP TEMPORARY TABLE IF EXISTS `zi_guide_import_' . $name . '`'); }
ziStatement("SELECT (SELECT COUNT(*) FROM `zi_evidence_guides` WHERE `source_sha256` = " . ziSql($source['source_sha256']) . ") AS `poin_panduan_target_113`, (SELECT COUNT(*) FROM `zi_guideline_sub_points` WHERE `evidence_guide_key` IN (" . implode(', ', array_map('ziSql', $allKeys)) . ")) AS `poin_terpasang_target_113`, (SELECT COUNT(*) FROM `zi_evidence_guides` WHERE NULLIF(TRIM(`notes`), '') IS NOT NULL) AS `poin_dengan_catatan_keterangan_penjelasan`");
if (!is_dir(dirname($destination))) { mkdir(dirname($destination), 0755, true); }
file_put_contents($destination, implode("\n", $sql));
echo json_encode(['path' => $destination, 'bytes' => filesize($destination), 'evidence_points' => count($allKeys), 'notes_column' => 'zi_evidence_guides.notes'], JSON_UNESCAPED_SLASHES) . PHP_EOL;
