<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'home' => $this['home'],
      'away' => $this['away'],
      'result' => $this['result'] ?? null
    ];
  }
}