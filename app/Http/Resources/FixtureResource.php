<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\WeekResource;

class FixtureResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'fixtureId' => $this['fixtureId'],
      'weeks' => WeekResource::collection($this['weeks']),
    ];
  }
}