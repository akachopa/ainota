<?php

namespace App\Ai\Contracts;

use App\Ai\DTOs\ExtractionResult;
use App\Models\Document;

interface DocumentExtractorInterface
{
    public function extract(Document $document): ExtractionResult;
}
