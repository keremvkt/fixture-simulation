<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this['id'],
      'name' => $this['name'],
      'league' => $this['league'],
      'attack' => $this['attack'],
      'midfield' => $this['midfield'],
      'defence' => $this['defence'],
    ];
  }
}