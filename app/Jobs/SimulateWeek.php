<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class SimulateWeek implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public string $fixtureId, public int $weekNum)
    {
    }

    public function handle(): void
    {
        $weekKey = "fixture:{$this->fixtureId}-week-{$this->weekNum}";
        $weekData = json_decode(Redis::get($weekKey), true);

        foreach ($weekData['matches'] as &$match) {
            $home = collect(config('teams'))->firstWhere('id', $match['home']);
            $away = collect(config('teams'))->firstWhere('id', $match['away']);
            $match['result'] = $this->simulate($home, $away);
        }

        Redis::set("$weekKey-played", json_encode($weekData));
        Redis::del($weekKey);
    }

    private function simulate($home, $away): array
    {
        $homeScore = rand(0, 3) + round((($home['attack'] + $home['midfield'] * 0.5) - ($away['defence'] + $away['midfield'] * 0.3)) / 20);
        $awayScore = rand(0, 2) + round((($away['attack'] + $away['midfield'] * 0.5) - ($home['defence'] + $home['midfield'] * 0.3)) / 25);
        return ['home' => max(0, $homeScore), 'away' => max(0, $awayScore)];
    }
}