<?php

namespace App\Console\Commands\apidocToSwagger;

use Illuminate\Console\Command;
use App\Services\Swagger\SwaggerService;
use App\Repositories\ProjectRepository;
use Log;
use Throwable;

/**
 * SyncDoc Task
 */
class SyncDocTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidocToSwagger:syncDoc';

    /**
     * The console command description.
     */
    protected $description = 'apidoc json convert to swagger json';

    private $swaggerService;
    private $projectRepo;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        SwaggerService    $swaggerService,
        ProjectRepository $projectRepo
    ) {
        parent::__construct();

        $this->swaggerService = $swaggerService;
        $this->projectRepo = $projectRepo;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        echo "|--- Start Sync Doc Task --|\n";

        $filter = [ 'is_enable' => true ];
        $projects = $this->projectRepo->getListByFilter($filter, true);

        var_export($projects);
    }
}
