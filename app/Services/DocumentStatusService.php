<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Events\DocumentStatusChanged;
use App\Models\Document;
use App\Models\User;
use InvalidArgumentException;

class DocumentStatusService
{
    public function __construct(private ActivityLogger $logger) {}

    public function transition(
        Document $document,
        DocumentStatus $to,
        ?User $user = null,
        array $attributes = [],
        ?int $progress = null,
    ): Document {
        $from = $document->status;

        if ($from !== $to && ! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException("Tidak dapat mengubah status {$from->value} menjadi {$to->value}.");
        }

        $document->fill($attributes);
        $document->status = $to;

        if ($progress !== null) {
            $document->progress = $progress;
        }

        $document->save();

        $this->logger->log(
            action: 'document.status_changed',
            workspace: $document->workspace,
            user: $user,
            subject: $document,
            before: ['status' => $from->value],
            after: ['status' => $to->value],
        );

        DocumentStatusChanged::dispatch($document->fresh());

        return $document;
    }
}
