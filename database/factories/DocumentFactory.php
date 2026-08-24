<?php

namespace Database\Factories;

use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $id = (string) Str::uuid();

        return [
            'workspace_id' => Workspace::factory(),
            'uploaded_by' => User::factory(),
            'source' => DocumentSource::App,
            'status' => DocumentStatus::Queued,
            'original_filename' => 'nota.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => "workspaces/test/documents/{$id}/v1/original.jpg",
            'sha256' => hash('sha256', (string) fake()->unique()->uuid()),
            'current_version' => 1,
            'page_count' => 1,
            'progress' => 0,
            'flags' => [],
        ];
    }
}
