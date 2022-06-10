<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\MovieCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RunSql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       $this->upDB();
    }

    private function upDB()
    {
        $sql = <<<EOF
CREATE TABLE `recommend_movie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mid` int(11) NOT NULL COMMENT '影片id',
  `category` int(11) NOT NULL COMMENT '分类',
  `photo` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
  `pv` int(11) NOT NULL DEFAULT '0' COMMENT '昨日pv',
  `comment_num` int(11) NOT NULL DEFAULT '0' COMMENT '评论量',
  `want_see` int(11) NOT NULL DEFAULT '0' COMMENT '想看量',
  `seen` int(11) NOT NULL DEFAULT '0' COMMENT '看过量',
  `hot` int(11) NOT NULL DEFAULT '0' COMMENT '合计热度',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0开启 1关闭',
  `ctime` datetime DEFAULT NULL COMMENT '结算时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COMMENT='热门推荐影片';
EOF;
        \Illuminate\Support\Facades\DB::unprepared($sql);
    }
}
