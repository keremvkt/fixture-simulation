<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use Illuminate\Routing\Controller;

class TeamController extends Controller
{
    public function index()
    {
        return TeamResource::collection(config('teams'));
    }
}
