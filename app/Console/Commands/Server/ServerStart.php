<?php 

namespace App\Console\Commands\Server;

use App\Exceptions\CommandFailedException;
use App\Exceptions\InvalidServerException;
use App\Exceptions\ProcessFailedException;
use Symfony\Component\Process\Process;

class ServerStart extends ServerCommand {
    
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'server:start 
                            {serverName=default : The id of the server to start}
                            {configName? : Start server with specifiec configuration}
                            {--s|live : Start the server manually without a tmux session}
                        ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Start a game server";

    protected $gameCommand = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->serverFromArgument();
        $this->buildCommand();
        
        if ($this->option('live')) {
            return $this->startLiveServer();
        }

        return $this->startTmuxServer();
    }

    protected function buildCommand()
    {
        $params = $this->server->params;
        if ($this->argument('configName')) {
            $config = $this->server->configs()->where('name', $this->argument('configName'))->first();

            if (! $config) {
                throw new InvalidServerException(trans('servers.config.not_found_for', ['name' => $this->argument('configName'), 'server' => $this->server->name]));
            }

            $params = $config->params;
        }

        $this->gameCommand = 'cd ' . $this->server->path . ' && ./' . $this->server->binary . ' ' . $params;
    }

    protected function startTmuxServer()
    {
        $command = 'tmux new-session -d -s "' . prefixedServerName($this->server->name) . '" "' . $this->gameCommand . '" 2> ' . logPath($this->server->name, 'error');

        $process = new Process($command);
        $process->setTimeout(10);
        $process->run();

        if (!$process->isSuccessful()) {
            if ($this->serverOnline($this->server->name)) {
                return $this->info('Server "' . $this->server->name . '" already running');
            }

            throw new CommandFailedException('Server "' . $this->server->name . '" failed to start');
        }

        if ($this->serverOnline($this->server->name)) {
            return $this->info('Server "' . $this->server->name . '" started');
        }

        throw new CommandFailedException('Server "' . $this->server->name . '" failed to start');
    }

    protected function startLiveServer()
    {
        (new Process($this->gameCommand))->setTimeout(null)->run(function($type, $line)
        {
            $this->info($line);
        });
    }

}
