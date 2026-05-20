<?php
/**
 * CRM QUANTUN Digital - Logout
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logout();
header('Location: index.php');
exit;
