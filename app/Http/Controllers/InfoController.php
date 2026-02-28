<?php

namespace App\Http\Controllers;

use App\DTO\ClientInfoDTO;
use App\DTO\DatabaseInfoDTO;
use App\DTO\ServerInfoDTO;
use Illuminate\Support\Facades\DB;

class InfoController extends Controller
{
    public function serverInfo()
    {
        $dto = new ServerInfoDTO(
            phpversion(),
            $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            ini_get('memory_limit'),
            ini_get('max_execution_time'),
            ini_get('upload_max_filesize'),
            ini_get('post_max_size'),
            get_loaded_extensions()
        );

        return response()->json($dto->toArray());                
    }

    public function clientInfo()
    {
        $dto = new ClientInfoDTO(
            htmlspecialchars(request()->ip(),ENT_QUOTES, 'UTF-8'), 
            htmlspecialchars(request()->userAgent(),ENT_QUOTES, 'UTF-8'),
        );

        return response()->json($dto->toArray());
    }

  public function databaseInfo()
    {
        $connection = DB::connection();
        
        $dto = new DatabaseInfoDTO(
            $connection->getDriverName(),
            $connection->getDatabaseName(),
            $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION)
        );

        return response()->json($dto->toArray());
    }
}