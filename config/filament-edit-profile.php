<?php

return [
    'avatar_column' => 'profile_photo',
    'disk' => env('FILESYSTEM_DISK', 'public'),
    'visibility' => 'public', // or replace by filesystem disk visibility with fallback value
];
