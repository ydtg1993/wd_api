<?php

namespace App\Services\Logic;


class HandleLogic extends BaseLogic
{

    protected $namePath = 'App\\Services\\Logic\\';
    protected $baseClassName = 'BaseLogic';
    //类型执行
    protected $typeClass = array();

    protected function getClassName($type)
    {
        return isset($this->typeClass[$type]) ? $this->namePath . $this->typeClass[$type] : $this->namePath . $this->baseClassName;
    }
}
