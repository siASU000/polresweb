<?php
// partials/helpers.php

if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

/**
 * Ambil koneksi DB sekali saja.
 * Pastikan file admin/db_connection.php menghasilkan $conn (mysqli).
 */
if (!isset($conn)) {
  require_once __DIR__ . '/../admin/db_connection.php';
}
