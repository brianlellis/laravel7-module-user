<?php

// Copy this file to db_service.php
// Do not remove this file
return [
  // STATUSES
  'db_label'  =>  'service_users',
  'is_active' =>  true,
  'details'   => [
    'driver'          => 'mysql',
    'url'             => env('DATABASE_URL'),
    // EDIT THESE VALUES
    'host'            => '127.0.0.1',
    'port'            => 3306,
    'database'        => 'service_users',
    'username'        => 'root',
    'password'        => 'root',
    // END EDITING OF VALUES
    'unix_socket'     => env('DB_SOCKET', ''),
    'charset'         => 'utf8mb4',
    'collation'       => 'utf8mb4_unicode_ci',
    'prefix'          => '',
    'prefix_indexes'  => true,
    'strict'          => false,
    'engine'          => null,
    'options'         => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
  ]
];
