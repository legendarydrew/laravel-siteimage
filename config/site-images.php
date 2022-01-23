<?php

return [
    'provider' => env('SITE_IMAGE_PROVIDER', 'LocalImage'),

    'local'           => [
        'folder' => env('SITE_IMAGE_LOCAL_FOLDER', 'img'),
    ],

    // http://cloudinary.com/documentation/admin_api#manage_transformations
    'transformations' => [
        'thumbnail' => [
            'width'         => 100,
            'height'        => 100,
            'crop'          => 'thumb',
            'gravity'       => 'face:center',
            'default_image' => 'placeholder.png',
        ],
    ],
];
