<?php
return [
    'hosts' => [
        env('ELASTIC_HOST').':'.env('ELASTIC_PORT')
    ],
];