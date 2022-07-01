<?php

namespace App\Services\DataLogic;

use Elasticsearch\ClientBuilder;

class DL extends DLStruct
{
    public $ES;
    protected static $Instance;
    public $struct;

    private function __construct()
    {
        $this->ES = ClientBuilder::create()->setHosts([env('ELASTIC_HOST') . ':' . env('ELASTIC_PORT')])->build();
    }

    public static function getInstance($struct): DL
    {
        if (!isset(self::$Instance)) {
            self::$Instance = new self();
        }
        self::$Instance->struct = new $struct;
        self::$Instance->struct->ES = self::$Instance->ES;
        return self::$Instance;
    }

    public function first($id)
    {
        return $this->struct->first($id);
    }

    public function get($ids)
    {
        return $this->struct->get($ids);
    }

    public function store($params)
    {
        return $this->struct->store($params);
    }

    public function update($id, $params)
    {
        return $this->struct->update($id, $params);
    }

    public function delete($id)
    {
        return $this->struct->delete($id);
    }
}
