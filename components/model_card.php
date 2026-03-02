<?php
/**
 * Backward compatible wrapper for legacy includes.
 */
defined('ABSPATH') || exit;

$card_path = __DIR__ . '/model_card_archive.php';
if (file_exists($card_path)) {
    include $card_path;
}
