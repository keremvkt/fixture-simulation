<?php

namespace App\Http\Controllers;

use App\Http\Resources\FixtureResource;
use App\Http\Resources\PlayWeekStatusResource;
use App\Http\Resources\WeekResource;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Jobs\SimulateWeek;
use App\Http\Requests\CreateFixtureRequest;

class FixtureController extends Controller
{
    public function create(CreateFixtureRequest $request)
    {
        $id = Str::uuid();
        $teams = $request->input('teams');
        $weeks = $this->generateFixture($teams);
        $weekCount = count($weeks);

        // Store fixture metadata
        Redis::set("fixture:$id", json_encode([
            'weekCount' => $weekCount,
            'currentWeek' => 0
        ]));

        // Store each week's matches separately
        foreach ($weeks as $i => $week) {
            Redis::set("fixture:$id-week-" . ($i + 1), json_encode($week));
        }

        return new FixtureResource([
            'fixtureId' => $id,
            'weeks' => $weeks
        ]);
    }

    public function playWeek(string $id)
    {
        $metaKey = "fixture:$id";
        $meta = json_decode(Redis::get($metaKey), true);

        if (!$meta) {
            return response()->json(['status' => 'NOT_FOUND'], 404);
        }

        $currentWeek = $meta['currentWeek'] + 1;

        if ($currentWeek > $meta['weekCount']) {
            Redis::del($metaKey);
            return new PlayWeekStatusResource(['status' => 'DONE']);
        }

        dispatch(new SimulateWeek($id, $currentWeek));
        $meta['currentWeek'] = $currentWeek;
        Redis::set($metaKey, json_encode($meta));

        return new PlayWeekStatusResource(['status' => 'PROCESSING']);
    }
    public function generateFixture(array $teams): array
    {
        // Validate teams
        if (empty($teams) || count($teams) < 2) {
            return [
                'error' => 'At least 2 teams are required to generate a fixture'
            ];
        }

        // Create a copy of teams array
        $teamsCopy = $teams;

        // If we have an odd number of teams, add a dummy team (0)
        // The dummy team represents a "bye" week for the team paired with it
        $hasDummyTeam = false;
        if (count($teamsCopy) % 2 !== 0) {
            array_push($teamsCopy, 0); // 0 represents a bye
            $hasDummyTeam = true;
        }

        $teamCount = count($teamsCopy);
        $rounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;
        $fixture = [];

        // First half of the season
        for ($round = 0; $round < $rounds; $round++) {
            $weekMatches = [];

            for ($i = 0; $i < $matchesPerRound; $i++) {
                $home = $teamsCopy[$i];
                $away = $teamsCopy[$teamCount - 1 - $i];

                // Skip matches with the dummy team (0)
                if ($home != 0 && $away != 0) {
                    $weekMatches[] = [
                        'home' => $home,
                        'away' => $away
                    ];
                }
            }

            if (!empty($weekMatches)) {
                $fixture[] = [
                    'week' => $round + 1,
                    'matches' => $weekMatches
                ];
            }

            // Rotate teams: fix the first team and rotate the rest
            $this->rotateTeams($teamsCopy);
        }

        // Second half of the season (return matches)
        $secondHalfFixture = [];
        $weekOffset = count($fixture);

        foreach ($fixture as $round) {
            $returnMatches = [];

            foreach ($round['matches'] as $match) {
                $returnMatches[] = [
                    'home' => $match['away'],
                    'away' => $match['home']
                ];
            }

            if (!empty($returnMatches)) {
                $secondHalfFixture[] = [
                    'week' => $round['week'] + $weekOffset,
                    'matches' => $returnMatches
                ];
            }
        }

        // Combine both fixtures
        $completeFixture = array_merge($fixture, $secondHalfFixture);

        return $completeFixture;
    }

    /**
     * Rotate teams array for the next round (keeping first element fixed)
     *
     * @param array $teams
     * @return void
     */
    private function rotateTeams(array &$teams): void
    {
        if (count($teams) < 3)
            return;

        $lastElement = $teams[count($teams) - 1];

        // Move all elements except first and last
        for ($i = count($teams) - 1; $i > 1; $i--) {
            $teams[$i] = $teams[$i - 1];
        }

        // Place the last element in the second position
        $teams[1] = $lastElement;
    }

    public function getPlayedWeeks(string $id)
    {
        $pattern = "fixture:$id-week-*-played";
        $keys = Redis::keys($pattern);

        $results = [];
        foreach ($keys as $fullKey) {
            $json = Redis::get($fullKey);
            if ($json) {
                $results[] = json_decode($json, true);
                Redis::del($fullKey);
            }
        }

        return WeekResource::collection($results);
    }
}
