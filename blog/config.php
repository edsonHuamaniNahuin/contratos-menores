<?php

return [
    'production' => false,
    'baseUrl' => 'https://vigilanteseace.pe/blog',
    'title' => 'Vigilante SEACE — Blog',
    'description' => 'Noticias, guías y análisis del SEACE. Contratos menores y mayores, análisis de TDR, direccionamiento y más.',
    'collections' => [
        'posts' => [
            'path' => '{date|Y}/{date|m}/{filename}',
            'sort' => '-date',
            'author' => 'Vigilante SEACE',
        ],
    ],
    'build' => [
        'source' => 'source',
        'destination' => '../public/blog',
    ],
];
