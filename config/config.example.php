<?php
/*
 * Template konfigurasi hosting Pustakata.
 *
 * Aplikasi membaca credential database dari environment variable:
 * PUSTAKATA_DB_HOST, PUSTAKATA_DB_USER, PUSTAKATA_DB_PASS, PUSTAKATA_DB_NAME.
 *
 * Jika hosting tidak mendukung environment variable, ubah fallback di
 * config/conn.php sesuai data MySQL dari cPanel/phpMyAdmin.
 */

putenv('PUSTAKATA_DB_HOST=localhost');
putenv('PUSTAKATA_DB_USER=u169077025_ridho');
putenv('PUSTAKATA_DB_PASS=Wnzah2124');
putenv('PUSTAKATA_DB_NAME=u169077025_pustakata_db');
