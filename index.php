<?php

/**
 * Root index.php for shared hosting where document root cannot be set to /public.
 * Forwards all requests to Laravel's public front controller.
 */

require __DIR__.'/public/index.php';
