<?php

/**
 * Plugin Name: Fabrikage - QR Codes
 * Plugin URI:  https://fabrikage.nl
 * Description: QR Code generation and redirection
 * Version:     dev-main
 * Author:      Fabrikage (Bart Klein Reesink)
 * Author URI:  https://fabrikage.nl
 * Text Domain: fabrikage
 *
 * Copyright 2024 Fabrikage (email : bart@fabrikage.nl)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

namespace Fabrikage\QR;

const FABRIKAGE_QR_CODES_PLUGIN_FILE = __FILE__;
const FABRIKAGE_QR_CODES_VERSION = 'dev-main';

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

register_activation_hook(__FILE__, [Bootstrap::class, 'activate']);
register_deactivation_hook(__FILE__, [Bootstrap::class, 'deactivate']);

Bootstrap::init();
