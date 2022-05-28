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
        $this->upRank();
    }

    private function upDB()
    {
        $sql = <<<EOF
ALTER TABLE `movie_actor` ADD INDEX sex ( `sex` )
EOF;
        \Illuminate\Support\Facades\DB::unprepared($sql);

        $sql = <<<EOF
ALTER TABLE movie MODIFY  `new_comment_time` datetime DEFAULT '2020-01-01' COMMENT '最新评论时间 -冗余'
EOF;
        \Illuminate\Support\Facades\DB::unprepared($sql);

        $sql = <<<EOF
ALTER TABLE `actor_popularity_chart` add column `new_movie_count` int(11) NOT NULL DEFAULT '0' COMMENT '上月新增影片数';
ALTER TABLE `actor_popularity_chart` add column `new_movie_pv` int(11) NOT NULL DEFAULT '0' COMMENT '上月新片的PV量';
ALTER TABLE `actor_popularity_chart` add column `new_movie_want` int(11) NOT NULL DEFAULT '0' COMMENT '上月新片想看量';
ALTER TABLE `actor_popularity_chart` add column `new_movie_seen` int(11) NOT NULL DEFAULT '0' COMMENT '上月新片看过量';
ALTER TABLE `actor_popularity_chart` add column `new_movie_score` int(11) NOT NULL DEFAULT '0' COMMENT '上月所有片子得分平均值';
ALTER TABLE `actor_popularity_chart` add column `new_movie_score_people` int(11) NOT NULL DEFAULT '0' COMMENT '评分人数';
EOF;
        \Illuminate\Support\Facades\DB::unprepared($sql);
    }

    private function upRank()
    {
        DB::table('actor_popularity_chart')->where('mtime','>','2020-12-01')->delete();
        $ts = [
            date('2021-01-01'),
            date('2021-02-01'),
            date('2021-03-01'),
            date('2021-04-01'),
            date('2021-05-01'),
            date('2021-06-01'),
            date('2021-07-01'),
            date('2021-08-01'),
            date('2021-09-01'),
            date('2021-10-01'),
            date('2021-11-01'),
            date('2021-12-01'),
            date('2022-01-01'),
            date('2022-02-01'),
            date('2022-03-01'),
            date('2022-04-01'),
            date('2022-05-01')
        ];
        foreach ($ts as $t){
            (new RankList())->actorHotProcess(strtotime($t));
        }
    }
}
