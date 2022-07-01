<?php

namespace App\Services\DataLogic;

abstract class DLStruct
{
    public $ES;
    public $struct = [];

    abstract function first($id);

    abstract function get($ids);

    abstract function store($params);

    abstract function update($id, $params);

    abstract function delete($id);

    public function checkStruct($params)
    {
        foreach ($this->struct as $key => $s) {
            if (!isset($params[$key])) {
                return false;
            }
        }
        return true;
    }

    public function fillStruct($params)
    {
        $data = [];
        foreach ($this->struct as $key => $s) {
            $data[$key] = '';
            if (isset($params[$key])) {
                $data[$key] = $params[$key];
            }
        }
        return $data;
    }
}
