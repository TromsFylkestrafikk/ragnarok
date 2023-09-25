<?php

namespace App\Http\Middleware;

use App\Models\Sink;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Ragnarok\Sink\Facades\SinkRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Synchronize available sinks (through discovery) with stored sink models.
 */
class SinkModels
{
    const CAHCE_KEY = 'ragnarok:sinks-synced';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->syncSinkModels();
        return $next($request);
    }

    /**
     * Create and remove Sink models based on available sink runners.
     */
    public function syncSinkModels(): void
    {
        $avail = SinkRegistrar::getSinkClasses();
        $cached = collect(Cache::get(self::CAHCE_KEY));
        if (!$avail->diffKeys($cached)->count()) {
            return;
        }

        // From here, ignore cache and fetch live sinks from db.
        $sinks = Sink::all()->keyBy('id');
        $sinks->diffKeys($avail)->each(fn (Sink $sink) => $sink->delete());
        $avail->diffKeys($sinks)->each(fn ($className, $id) => Sink::create([
            'id' => $id,
            'title' => $className::$title,
            'impl_class' => $className,
        ]));
        Cache::put(self::CAHCE_KEY, $avail);
    }
}
