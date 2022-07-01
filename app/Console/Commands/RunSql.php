<?php

namespace App\Console\Commands;

use App\Models\MovieActor;
use App\Models\MovieActorName;
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
        $this->admin();
       $this->upDB();
       $this->upRank();
        $this->resolveDB();
    }

    private function admin()
    {
        $sql = <<<EOF
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (8, 19, 8, '用户列表', 'fa-align-justify', '/account', '*', '2022-05-25 13:10:58', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (9, 0, 12, '广告管理', 'fa-buysellads', NULL, '*', '2022-05-25 13:54:15', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (10, 9, 13, '广告列表', 'fa-align-justify', '/ads_list', '*', '2022-05-25 13:54:59', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (11, 9, 14, '广告位', 'fa-clone', '/ads_pos', '*', '2022-05-25 15:51:21', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (12, 0, 10, '举报管理', 'fa-warning', '/report', '*', '2022-05-25 16:04:12', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (13, 0, 15, '公告管理', 'fa-bullhorn', '/announce', '*', '2022-05-25 16:48:18', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (14, 0, 5, '内容管理', 'fa-folder-open', NULL, '*', '2022-05-30 17:42:28', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (15, 14, 6, '影片管理', 'fa-film', '/manage_movie', '*', '2022-05-30 17:43:07', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (16, 0, 11, '轮播图', 'fa-photo', '/carousel', '*', '2022-06-14 10:22:55', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (17, 19, 9, '黑名单', 'fa-ban', '/locklistdata', '*', '2022-06-17 10:02:47', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (19, 0, 7, '用户管理', 'fa-group', NULL, NULL, '2022-06-17 10:03:52', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (20, 0, 1, '评论管理', 'fa-wechat', NULL, NULL, '2022-06-17 10:07:12', '2022-06-17 10:07:58');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (21, 20, 2, '评论列表', 'fa-commenting-o', '/comment', '*', '2022-06-17 10:07:44', '2022-06-17 10:08:48');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (22, 20, 4, '过滤词列表', 'fa-microphone-slash', '/filter', '*', '2022-06-17 10:08:25', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (23, 20, 3, '回复管理', 'fa-comments', '/recomment', '*', '2022-06-17 10:10:39', '2022-06-17 10:10:47');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (24, 0, 0, '网站管理', 'fa-desktop', NULL, NULL, '2022-06-17 10:11:40', '2022-06-17 10:11:40');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (25, 24, 0, '热搜词管理', 'fa-search-plus', '/hotwords', '*', '2022-06-17 10:12:25', '2022-06-17 10:12:25');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (26, 24, 0, '短评须知', 'fa-bookmark-o', '/notes', '*', '2022-06-17 10:13:42', '2022-06-17 10:13:42');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (27, 24, 0, 'APP分享', 'fa-share-alt', '/share', '*', '2022-06-17 10:14:08', '2022-06-17 10:14:08');

INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (28, 14, 14, '片商管理', 'fa-university', '/manage_company', '*', '2022-06-28 13:54:56', '2022-07-01 13:40:38');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (29, 14, 15, '系列管理', 'fa-list-ol', '/manage_series', '*', '2022-06-28 15:09:42', '2022-07-01 13:40:38');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (32, 14, 16, '导演管理', 'fa-tripadvisor', '/manage_director', '*', '2022-06-28 15:21:50', '2022-07-01 13:40:38');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (33, 14, 13, '分类管理', 'fa-cubes', '/manage_category', '*', '2022-06-28 15:47:44', '2022-07-01 13:40:38');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (34, 14, 12, '演员管理', 'fa-female', '/manage_actor', '*', '2022-06-29 15:48:12', '2022-07-01 13:40:38');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (35, 14, 11, '番号管理', 'fa-bold', '/manage_numbers', '*', '2022-07-01 13:40:24', '2022-07-01 13:40:38');
INSERT INTO `admin_menu`(`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES (36, 14, 12, '片单', 'fa-bookmark-o', '/manage_pieces', '*', '2022-07-01 15:48:53', '2022-07-01 15:49:13');
EOF;
        \Illuminate\Support\Facades\DB::unprepared($sql);
    }

    private function upDB()
    {
        $sql = <<<EOF
DROP TABLE `article_comment`;
DROP TABLE `article_tag`;
DROP TABLE `articles`;
DROP TABLE `batch_comment_script`;
ALTER TABLE `actor_popularity_chart` add column `rank` int(11) NOT NULL DEFAULT '0' COMMENT '当月排名';
ALTER TABLE `actor_popularity_chart` add column `rank_float` int(11) NOT NULL DEFAULT '0' COMMENT '当月排名较上月浮动';
ALTER TABLE `hot_keyword` add column `times` int(11) NOT NULL DEFAULT '0' COMMENT '搜索次数';
ALTER TABLE `movie_actor` add column `names` json DEFAULT NULL COMMENT '演员别名';
EOF;
        \Illuminate\Support\Facades\DB::unprepared($sql);

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
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `category` (`category`),
  KEY `ctime` (`ctime`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COMMENT='热门推荐影片';
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
            date('2022-05-01'),
            date('2022-06-01'),
            date('2022-07-01'),
        ];
        foreach ($ts as $t){
            (new RankList())->actorHotProcess(strtotime($t));
        }
    }

    private function resolveDB()
    {
        $page = 0;
        $limit = 500;
        while(true){
            $ids = MovieActor::offset($page*$limit)->limit($limit)->pluck('id')->all();
            if(empty($ids)){
                break;
            }
            foreach ($ids as $id){
               $names = MovieActorName::where('aid',$id)->pluck('name')->all();
               if(empty($names)){
                   continue;
               }
               MovieActor::where('id',$id)->update(['names'=>json_encode($names)]);
            }
        }
    }
}
