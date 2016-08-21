<?php

namespace App\Surveil\Rcon;

use App\Exceptions\RconImplementationNotFoundException;
use App\Surveil\Rcon\Specific\Cod4;

class RconClient {

    public $connection;

    protected $rconImplementations = [
        'cod4' => Cod4::class
    ];

    public function setupRcon($server)
    {
        if (!isset($server['server_game'])) {
            throw new RconImplementationNotFoundException("Server game not set");
        }

        try {
            $rconClass = $this->rconImplementations[$server['server_game']];
        } catch (\Exception $e) {
            throw new RconImplementationNotFoundException("No implementation for game: " . $server['server_game']);
        }

        $this->connection = new $rconClass($server);
    }

}