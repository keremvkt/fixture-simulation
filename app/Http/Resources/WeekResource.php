<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WeekResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'week' => $this['week'],
      'matches' => MatchResource::collection($this['matches']),
    ];
  }
}