<?php

return [
    'provider' => env('SITE_IMAGE_PROVIDER', 'LocalImage'),

    'local'           => [
        'folder' => env('SITE_IMAGE_LOCAL_FOLDER', 'img'),
    ],

    'cloudinary' => [
        'cloudName'  => env('CLOUDINARY_CLOUD_NAME'),
        'baseUrl'    => env('CLOUDINARY_BASE_URL', 'http://res.cloudinary.com/'.env('CLOUDINARY_CLOUD_NAME')),
        'secureUrl'  => env('CLOUDINARY_SECURE_URL', 'https://res.cloudinary.com/'.env('CLOUDINARY_CLOUD_NAME')),
        'apiBaseUrl' => env('CLOUDINARY_API_BASE_URL', 'https://api.cloudinary.com/v1_1/'.env('CLOUDINARY_CLOUD_NAME')),
        'apiKey'     => env('CLOUDINARY_API_KEY'),
        'apiSecret'  => env('CLOUDINARY_API_SECRET'),

        'scaling'    => [
            'format' => 'png',
            'width'  => 150,
            'height' => 150,
            'crop'   => 'fit',
            'effect' => null
        ]
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
