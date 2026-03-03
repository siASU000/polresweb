<?php

if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

if (!isset($conn)) {
  require_once __DIR__ . '/../admin/db_connection.php';
}
