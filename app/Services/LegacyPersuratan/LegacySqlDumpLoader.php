<?php

namespace App\Services\LegacyPersuratan;

use RuntimeException;

class LegacySqlDumpLoader
{
    public function loadIntoDatabase($sqlDumpPath, array $connectionConfig, $databaseName, $resetDatabase = true)
    {
        if (!extension_loaded('mysqli')) {
            throw new RuntimeException('Ekstensi mysqli tidak tersedia. Dump SQL legacy tidak dapat dimuat.');
        }

        if (!is_file($sqlDumpPath)) {
            throw new RuntimeException('File dump SQL tidak ditemukan: ' . $sqlDumpPath);
        }

        $host = $connectionConfig['host'] ?? '127.0.0.1';
        $port = (int) ($connectionConfig['port'] ?? 3306);
        $username = $connectionConfig['username'] ?? '';
        $password = $connectionConfig['password'] ?? '';

        $mysqli = mysqli_init();

        if (!$mysqli->real_connect($host, $username, $password, null, $port)) {
            throw new RuntimeException('Gagal terhubung ke MySQL untuk memuat dump legacy: ' . mysqli_connect_error());
        }

        $mysqli->set_charset('utf8mb4');

        if ($resetDatabase) {
            $this->runStatement($mysqli, 'DROP DATABASE IF EXISTS `' . str_replace('`', '``', $databaseName) . '`');
        }

        $this->runStatement($mysqli, 'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $databaseName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->runStatement($mysqli, 'USE `' . str_replace('`', '``', $databaseName) . '`');
        $this->runStatement($mysqli, 'SET FOREIGN_KEY_CHECKS=0');

        $sql = file_get_contents($sqlDumpPath);

        if ($sql === false) {
            throw new RuntimeException('Gagal membaca file dump SQL legacy.');
        }

        if (!$mysqli->multi_query($sql)) {
            throw new RuntimeException('Gagal menjalankan dump SQL legacy: ' . $mysqli->error);
        }

        while (true) {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }

            if (!$mysqli->more_results()) {
                break;
            }

            if (!$mysqli->next_result()) {
                throw new RuntimeException('Gagal memproses statement dump SQL legacy: ' . $mysqli->error);
            }
        }

        $this->runStatement($mysqli, 'SET FOREIGN_KEY_CHECKS=1');
        $mysqli->close();
    }

    protected function runStatement(\mysqli $mysqli, $sql)
    {
        if (!$mysqli->query($sql)) {
            throw new RuntimeException('Gagal menjalankan SQL: ' . $mysqli->error);
        }
    }
}
