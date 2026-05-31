<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_completed' => $this->is_completed,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),


            'category' => new CategoryResource($this->whenLoaded('category')),
            'subtasks' => SubtaskResource::collection($this->whenLoaded('subtasks')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];

    }
}
