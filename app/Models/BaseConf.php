<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\MovieLabel;
use App\Models\MovieLabelCategoryAssociate;
use Illuminate\Support\Facades\Redis;

class BaseConf extends Model
{
    protected $table = 'configuration';
}