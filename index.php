<?php

/*
 * Front controller for hosts whose document root is this project folder
 * rather than public/ (e.g. XAMPP at http://localhost/jayyid-autos-dealership, or
 * shared hosting). The root .htaccess serves static files from public/ and
 * routes everything else here, so Laravel sees the correct base URL.
 *
 * Where you control the web server, point the document root at public/
 * instead and this file is never used.
 */

require __DIR__.'/public/index.php';
