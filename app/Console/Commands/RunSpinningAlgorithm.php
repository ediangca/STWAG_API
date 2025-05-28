<?php

namespace App\Console\Commands;

use App\Services\SpinningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunSpinningAlgorithm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    // protected $signature = 'app:run-spinning-algorithm';
    // protected $description = 'Command description';


    // protected $signature = 'spin:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $signature = 'spin:run';
    protected $description = 'Run spinning algorithm based on time';

    /**
     * Execute the console command.
     */

     
    public function __construct(protected SpinningService $spinningService)
    {
        parent::__construct();
    }

    public function handle()
    {
        //
        $result = $this->spinningService->generateResult();

        Log::info("Spin result: " . $result);
        
        // Optional: broadcast event or store to DB
        return Command::SUCCESS;
    }
}
