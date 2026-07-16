<?php

return [
    'enabled' => env('AGT_ENABLED', env('AGT_EINVOICE_ENABLED', false)),
    'environment' => env('AGT_ENV', env('AGT_EINVOICE_ENV', 'hml')),
    'timeout' => (int) env('AGT_TIMEOUT', env('AGT_EINVOICE_TIMEOUT', 30)),
    'connect_timeout' => (int) env('AGT_CONNECT_TIMEOUT', 10),
    'log_channel' => env('AGT_LOG_CHANNEL', 'stack'),
    'debug_jws' => (bool) env('AGT_DEBUG_JWS', false),

    'nif' => env('AGT_NIF'),
    'username' => env('AGT_USERNAME'),
    'password' => env('AGT_PASSWORD'),
    'private_key' => env('AGT_PRIVATE_KEY'),
    'private_key_path' => env('AGT_PRIVATE_KEY_PATH', storage_path('chave_posterior/privateKeyFE.pem')),

    'software' => [
        'product_id' => env('AGT_PRODUCT_ID', 'XHotel'),
        'version' => env('AGT_SOFTWARE_VERSION', '1.2'),
        'validation_number' => env('AGT_' . strtoupper(env('AGT_ENV', env('AGT_EINVOICE_ENV', 'hml'))) . '_SOFTWARE_VALIDATION_NUMBER', 
        env('AGT_SOFTWARE_VALIDATION_NUMBER')),
    ],

    'establishment_number' => env('AGT_ESTABLISHMENT_NUMBER', 'SEDE'),
    'series_contingency_indicator' => env('AGT_SERIES_CONTINGENCY_INDICATOR', 'N'),

    'endpoints' => [
        'hml' => [
            'registar_factura' => env('AGT_HML_REGISTAR_FACTURA', 'https://sifphml.minfin.gov.ao/sigt/fe/v1/registarFactura'),
            'consultar_factura' => env('AGT_HML_CONSULTAR_FACTURA', 'https://sifphml.minfin.gov.ao/sigt/fe/v1/consultarFactura'),
            'obter_estado' => env('AGT_HML_OBTER_ESTADO', 'https://sifphml.minfin.gov.ao/sigt/fe/v1/obterEstado'),
            'solicitar_serie' => env('AGT_HML_SOLICITAR_SERIE', 'https://sifphml.minfin.gov.ao/sigt/fe/v1/solicitarSerie'),
            'listar_series' => env('AGT_HML_LISTAR_SERIES', 'https://sifphml.minfin.gov.ao/sigt/fe/v1/listarSeries'),
            'listar_facturas' => env('AGT_HML_LISTAR_FACTURAS', 'https://sifphml.minfin.gov.ao/sigt/fe/v1/listarFacturas'),
        ],
        'prd' => [
            'registar_factura' => env('AGT_PRD_REGISTAR_FACTURA', 'https://sifp.minfin.gov.ao/sigt/fe/v1/registarFactura'),
            'consultar_factura' => env('AGT_PRD_CONSULTAR_FACTURA', 'https://sifp.minfin.gov.ao/sigt/fe/v1/consultarFactura'),
            'obter_estado' => env('AGT_PRD_OBTER_ESTADO', 'https://sifp.minfin.gov.ao/sigt/fe/v1/obterEstado'),
            'solicitar_serie' => env('AGT_PRD_SOLICITAR_SERIE', 'https://sifp.minfin.gov.ao/sigt/fe/v1/solicitarSerie'),
            'listar_series' => env('AGT_PRD_LISTAR_SERIES', 'https://sifp.minfin.gov.ao/sigt/fe/v1/listarSeries'),
            'listar_facturas' => env('AGT_PRD_LISTAR_FACTURAS', 'https://sifp.minfin.gov.ao/sigt/fe/v1/listarFacturas'),
        ],
    ],
];