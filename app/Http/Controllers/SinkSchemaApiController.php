<?php

namespace App\Http\Controllers;

use App\Models\Sink;
use App\Facades\Ragnarok;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PDO;

class SinkSchemaApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return mixed[]
     */
    public function index(Sink $sink): array
    {
        return [
            'status' => true,
            'schemas' => Ragnarok::getSinkHandler($sink->id)->src->destinationTables()
        ];
    }

    /**
     * Get complete-ish schema listing of resource.
     *
     * @return array
     */
    public function show(Sink $sink, string $table): array
    {
        $pdo = DB::getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'mysql') {
            return [
                'status' => false,
                'schema' => null,
            ];
        }
        $query = $pdo->query(sprintf('show full columns from %s', $table));
        $query->execute();
        $result = new Collection($query->fetchAll());
        $keys = array_flip(['Field', 'Type', 'Key', 'Default', 'Comment']);
        return [
            'status' => true,
            'schema' => $result->map(fn ($item) => array_intersect_key(
                $item,
                $keys
            )),
        ];
    }
}
