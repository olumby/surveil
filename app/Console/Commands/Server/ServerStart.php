<?php 

namespace App\Console\Commands\Server;

use App\Exceptions\InvalidServerException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class ServerStart extends ServerCommand {
    
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'server:start 
                            {serverId? : The id of the server to start}
                            {--s|unsupervised : Start the server manually, not through supervisor}
                        ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Start a game server";

    protected $server = [];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        try {
            $this->server = $this->getServer();
        } catch(\Exception $e) {
            $this->error($e->getMessage());
            return;
        }
        
        if ($this->option('unsupervised')) {
            return $this->startUnsupervisedServer();
        }

        return $this->startSupervisedServer();
    }

    protected function startSupervisedServer()
    {
        $command = 'supervisorctl start ' . $this->supervisor->supervisorProgramForServer($this->argument('serverId'));
        (new Process($command))->setTimeout(null)->run(function($type, $line)
        {
            $this->info($line);
        });
    }

    protected function startUnsupervisedServer()
    {
        $command = 'cd ' . $this->server['path'] . ' && ./' . $this->server['binary'] . ' ' . $this->server['startup_params'];
        (new Process($command))->setTimeout(null)->run(function($type, $line)
        {
            $this->info($line);
        });
    }

}