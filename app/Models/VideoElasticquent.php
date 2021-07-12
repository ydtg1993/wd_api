<?php


namespace App\Models;



use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Model;

class VideoElasticquent  extends Model
{

    use ElasticquentTrait;

    protected $table = 'movie';

    /**
     * The elasticsearch settings.
     *
     * @var array
     */
    protected $indexSettings = [
        'analysis' => [
            'char_filter' => [

            ],
            'filter' => [

            ],
            "tokenizer"=> [
                "tokenizer_number"=> [
                    "type"=> "classic",
                ],
            ],
            'analyzer' => [
                'analyzer_number' => [
                    'type' => 'custom',

                    'tokenizer' => 'tokenizer_number',
                    'filter' => [
                        'classic',
                        'lowercase',
                    ],
                ],
                "comma"=> [
                    "type"=> "pattern",
                    "pattern"=>",",
                ],
            ],
        ],
    ];

    //对应es的mapping
    protected $mappingProperties = [
        'id' => [
            'type' => 'integer',
        ],
        'name' => [
            'type' => 'text',
            "search_analyzer"=> "ik_smart",
            'analyzer' => 'ik_smart'
        ],
        'number' => [
            'type' => 'text',
            'analyzer' => 'standard',
            'search_analyzer' => 'standard',
        ],
        'actors.name'=>[
            'type' => 'text',
            "search_analyzer"=> "ik_smart",
            'analyzer' => 'ik_smart'
        ],
        'status' => [
            'type' => 'byte',
        ],
        //不搜索字段
        'horizontal_cover'=>[
            'type' => 'text',
            'index'=> false
        ],
        'movie_length'=>[
            'type' => 'text',
            'index'=> false
        ],
        'views'=>[
            'type' => 'integer',
            'index'=> false
        ],
        'created_at'=>[
            'type' => 'text',
            'index'=> false
        ],
        'updated_at'=>[
            'type' => 'text',
            'index'=> false
        ],
        'publish_time'=>[
            'type' => 'text',
            'index'=> false
        ],
    ];

    function getIndexDocumentData(){
        $array = [];
        foreach (self::$fields as $f){
            if(isset($this->$f)){
                $array[$f] = $this->$f;
            }
        }

        return $array;
    }

    function getIndexName(){
        return 'video';
    }

    public static function getQueryArray($keyword = null,$page = 1,$pageSize = 10){
        if(empty($keyword)){
            return null;
        }

        $query = [
            'index' => 'video',
            'type' => 'movie',
            'body' => [
                'size'=> $pageSize,
                'from'=> ($page - 1) * $pageSize,
                'min_score' => 0.01,
                'query' => [
                    'bool'=>[
                        'should'=>[
                            [
                                'match' => [
                                    'number' => [
                                        'query' => $keyword,
                                        'boost' => 10,
                                    ],
                                ],
                            ],
                            [
                                'match_phrase' => [
                                    'name' => [
                                        'query' => $keyword,
                                        'boost' => 10,
                                    ],
                                ]
                            ],
                            [
                                'match' => [
                                    'actors.name' => [
                                        'query' => $keyword,
                                        'boost' => 10,
                                    ],
                                ]
                            ],
                        ],
                        "filter"=> [
                            'bool'=>[
                                'must'=>[
                                    "term" => [
                                        "status"=> 1,
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
                "highlight" => [
                    "fields" => [
                        "title" =>  (object)[],
                        "number" =>  (object)[],
                        "actors.name" =>  (object)[],
                    ],
                ],
            ],
        ];

        return $query;
    }


}
