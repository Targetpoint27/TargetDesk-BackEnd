<?php

namespace App\Http\Resources\Ringover;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 * schema="CallResource",
 * title="Call Resource",
 * description="Cleaned Ringover call data for the TargetDesk UI",
 * @OA\Property(property="id", type="integer", example=19479175),
 * @OA\Property(property="call_id", type="string", example="9058220947198957713"),
 * @OA\Property(property="direction", type="string", example="in"),
 * @OA\Property(property="is_answered", type="boolean", example=true),
 * @OA\Property(property="status", type="string", example="ANSWERED"),
 * @OA\Property(property="start_time", type="string", format="date-time"),
 * @OA\Property(property="duration", type="string", example="00:00:20"),
 * @OA\Property(property="from", type="string", example="33600000000"),
 * @OA\Property(property="to", type="string", example="33184800000"),
 * @OA\Property(property="recording_url", type="string", nullable=true),
 * @OA\Property(property="country", type="string", example="FR"),
 * @OA\Property(property="flag_url", type="string", example="https://flagcdn.com/w20/fr.png"),
 * @OA\Property(property="contact_name", type="string", example="John Doe"),
 * @OA\Property(property="note", type="string", example="Potential client")
 * )
 */
class CallResource extends JsonResource
{
    public function toArray($request): array
    {
        // Your existing transformation logic here...
        return [
            'id'            => $this['cdr_id'],
            'call_id'       => $this['call_id'],
            'direction'     => $this['direction'],
            'is_answered'   => $this['is_answered'],
            'status'        => $this['last_state'],
            'start_time'    => $this['start_time'],
            'duration'      => gmdate("H:i:s", $this['total_duration']),
            'from'          => $this['from_number'],
            'to'            => $this['to_number'],
            'recording_url' => $this['record'] ?? null,
            'country'       => $this['conference']['numbers'][0]['format']['country'] ?? 'Unknown',
            'flag_url'      => isset($this['conference']['numbers'][0]['format']['country']) 
                               ? "https://flagcdn.com/w20/" . strtolower($this['conference']['numbers'][0]['format']['country']) . ".png" 
                               : null,
            'contact_name'  => $this['contact']['concat_name'] ?? 'Inconnu',
            'note'          => $this['note'] ?? '',
        ];
    }
}