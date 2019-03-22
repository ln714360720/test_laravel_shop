<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;

class create_doc_mapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:create-mapping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建Elasticseach里的索引为products类型为_doc的mapping映射关系';

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
        //定论参数
        $params=[
            "index"=>"products",
            "body"=>[
                'settings'=>[
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2,
                    'analysis'=>[
                        'filter'=>[
                            'synonym_filter'=>[
                                'type'=>'synonym',
                                'synonyms_path'=>'analysis/synonyms.txt',
                            ]
                        ],
                        'analyzer'=>[
                            'ik_smart_synonym'=>[
                                'type'=>'custom',
                                'tokenizer'=>'ik_smart',
                                'filter'=>['synonym_filter']
                            ]
                        ]
                    ]
                ],
                "mappings"=>[ //这是mapping关键字
                  '_doc'=>[//这里是 type名称
                      '_source'=>[
                        'enabled'=>true,
                      ],
                      "properties"=>[
                          //这是下面的字段类型定义
                        "type"=>[
                            "type"=>"keyword",

                        ],
                          "title"=>[
                              "type"=>"text",
                              "analyzer"=>"ik_smart",
                              "search_analyzer"=>'ik_smart_synonym',
                          ],
                        "long_title"=>[
                            "type"=>"text",
                            "analyzer"=>"ik_smart",
                            "search_analyzer"=>'ik_smart_synonym',
                        ],
                        "category_id"=>[
                            "type"=>"integer",
                        ],
                        "category"=>[
                            "type"=>"keyword",
                        ],
                        "category_path"=>[
                            "type"=>"keyword",
                        ],
                        "description"=>[
                            "type"=>"text",
                            "analyzer"=>"ik_smart",
                            "search_analyzer"=>'ik_smart_synonym',
                        ],
                        "price"=>[
                            "type"=>"scaled_float",//对于浮点类型，使用缩放因子将浮点数据存储到整数中通常更有效，这是该scaled_float 类型所做的。例如，一个price字段可以存储在 scaled_floata scaling_factor中100
                            "scaling_factor"=>100
                        ],
                        "on_sale"=>[
                            "type"=>"boolean",
                         ],
                        "rating"=>[
                            "type"=>"float",
                        ],
                        "sold_count"=>[
                            "type"=>"integer"
                        ],
                        "review_count"=>[
                            "type"=>"integer",
                        ],
                        "skus"=>[
                            "type"=>"nested",//是个复合类型
                            "properties"=>[
                                "title"=>[
                                    "type"=>"text",
                                    "analyzer"=>"ik_smart",
                                    "copy_to"=>"skus_title",
                                    "search_analyzer"=>'ik_smart_synonym',
                                ],
                                "description"=>[
                                    "type"=>"text",
                                    "analyzer"=>"ik_smart",
                                    "copy_to"=>"skus_description",
                                    "search_analyzer"=>'ik_smart_synonym',
                                ],
                                "price"=>[
                                    "type"=>"scaled_float",
                                    "scaling_factor"=>100
                                ]
                            ]

                        ],
                        "properties"=>[
                            "type"=>"nested",
                            "properties"=>[
                                "name"=>[
                                    "type"=>"keyword",
                                ],
                                "value"=>[
                                    "type"=>"keyword",
                                    "copy_to"=>"properties_value",
                                ],
                                "search_value"=>[
                                    "type"=>"keyword"
                                ]

                            ]
                        ]
    
                      ]
                  ]
                  
                ]
            ]
        ];
   
        
        //先判断是否有当前索引,
        $this->info('正在检查是否有当前索引');
        if(app('es')->indices()->exists(['index'=>'products'])){
            //有,则删除
            $this->info('当前索引存在,正在删除');
            app('es')->indices()->delete(['index'=>'products']);
            $this->info('删除成功');
            $this->info('开始创建新的索引');
            app('es')->indices()->create($params);
            $this->info('创建完成');
        }else{
            //没有,则创建
            $this->info('开始创建索引');
            app('es')->indices()->create($params);
            $this->info('创建完成');
        }
       
//        dd(app('es')->indices()->exists(['index'=>'products']));
//        dd(app('es')->indices()->delete(['index'=>'products']));
//        app('es')->indices()->putMapping($params);
//        dd(app('es')->indices()->getMapping(['index'=>'test_index']));
//        $this
//       app('es')->indices()->create($params);
        $this->info('完成');
        
    }
}
