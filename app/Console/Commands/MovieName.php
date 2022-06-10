<?php

namespace App\Console\Commands;

use App\Models\Movie;
use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MovieName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changeMovieName';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $page = 1;
        $pageSize = 500;//一次处理500条
        $ES = ClientBuilder::create()->setHosts([env('ELASTIC_HOST').':'.env('ELASTIC_PORT')])->build();
        while (true) {
            try {
                DB::beginTransaction();
                $movies = Movie::offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->select('id', 'number', 'name')->get()->toArray();
                if (empty($movies)) {
                    break;
                }
                $page++;
                foreach ($movies as $movie) {
                    if($movie['number'] == '' || $movie['name'] == ''){
                        continue;
                    }
                    if (preg_match("/{$movie['number']}/", $movie['name'])) {
                        $name = trim(str_replace($movie['number'], '', $movie['name']));
                        Movie::where('id', $movie['id'])->update(['name' => $name]);
                        try {
                            $ES->update([
                                'index' => 'movie',
                                'type' => '_doc',
                                'id' => $movie['id'],
                                'body' => [
                                    'doc' => [
                                        'name' => $name
                                    ]
                                ]
                            ]);
                        }catch (\Exception $e){

                        }
                    }
                }

                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                echo $e->getMessage();
                return;
            }
            echo $page . PHP_EOL;
        }
    }

}
