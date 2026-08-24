<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DuplicateCandidate;
use App\Support\Money;

class DuplicateDetectionService
{
    /**
     * @return array{score: float, match: ?Document, signals: array<string, mixed>}
     */
    public function exactFileMatch(Document $document): array
    {
        $match = Document::query()
            ->forWorkspace($document->workspace_id)
            ->where('id', '!=', $document->id)
            ->where('sha256', $document->sha256)
            ->whereNotIn('status', [DocumentStatus::Failed->value, DocumentStatus::Archived->value])
            ->latest()
            ->first();

        if ($match) {
            return [
                'score' => 100.0,
                'match' => $match,
                'signals' => ['exact_sha256' => true],
            ];
        }

        return ['score' => 0, 'match' => null, 'signals' => []];
    }

    /**
     * @return array{score: float, match: ?Document, signals: array<string, mixed>}
     */
    public function perceptualMatch(Document $document): array
    {
        if (! $document->perceptual_hash) {
            return ['score' => 0, 'match' => null, 'signals' => []];
        }

        $threshold = (int) config('ainota.duplicate.perceptual_hamming_threshold');
        $candidates = Document::query()
            ->forWorkspace($document->workspace_id)
            ->where('id', '!=', $document->id)
            ->whereNotNull('perceptual_hash')
            ->whereNotIn('status', [DocumentStatus::Failed->value, DocumentStatus::Archived->value])
            ->latest()
            ->limit(200)
            ->get();

        $best = null;
        $bestDistance = 99;

        foreach ($candidates as $candidate) {
            $distance = 0;
            $left = $document->perceptual_hash;
            $right = (string) $candidate->perceptual_hash;
            $len = min(strlen($left), strlen($right));
            $distance = abs(strlen($left) - strlen($right));
            for ($i = 0; $i < $len; $i++) {
                if ($left[$i] !== $right[$i]) {
                    $distance++;
                }
            }

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        if ($best && $bestDistance <= $threshold) {
            $score = max(0, (float) config('ainota.duplicate.weights.perceptual_image') * (1 - ($bestDistance / max(1, $threshold))));

            return [
                'score' => round($score, 2),
                'match' => $best,
                'signals' => ['perceptual_hamming' => $bestDistance],
            ];
        }

        return ['score' => 0, 'match' => null, 'signals' => []];
    }

    /**
     * @return array{score: float, match: ?Document, signals: array<string, mixed>}
     */
    public function contentMatch(Document $document): array
    {
        if (! $document->content_fingerprint) {
            return ['score' => 0, 'match' => null, 'signals' => []];
        }

        $exact = Document::query()
            ->forWorkspace($document->workspace_id)
            ->where('id', '!=', $document->id)
            ->where('content_fingerprint', $document->content_fingerprint)
            ->latest()
            ->first();

        if ($exact) {
            return [
                'score' => 95,
                'match' => $exact,
                'signals' => ['content_fingerprint' => true],
            ];
        }

        $weights = config('ainota.duplicate.weights');
        $candidates = Document::query()
            ->forWorkspace($document->workspace_id)
            ->where('id', '!=', $document->id)
            ->whereNotNull('transaction_date')
            ->latest()
            ->limit(100)
            ->get();

        $bestScore = 0.0;
        $best = null;
        $bestSignals = [];

        foreach ($candidates as $candidate) {
            $score = 0.0;
            $signals = [];

            if ($document->transaction_date && $candidate->transaction_date?->eq($document->transaction_date)) {
                $score += (float) $weights['transaction_date_exact'];
                $signals['date'] = true;
            }

            if ($document->grand_total !== null && Money::equal($document->grand_total, $candidate->grand_total, (float) config('ainota.duplicate.amount_tolerance'))) {
                $score += (float) $weights['amount_exact'];
                $signals['amount'] = true;
            }

            if ($document->vendor_id && $document->vendor_id === $candidate->vendor_id) {
                $score += (float) $weights['vendor_high_similarity'];
                $signals['vendor'] = true;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
                $bestSignals = $signals;
            }
        }

        return [
            'score' => round($bestScore, 2),
            'match' => $bestScore >= (float) config('ainota.duplicate.thresholds.possible') ? $best : null,
            'signals' => $bestSignals,
        ];
    }

    public function rememberCandidate(Document $document, Document $match, float $score, array $signals): DuplicateCandidate
    {
        return DuplicateCandidate::query()->create([
            'workspace_id' => $document->workspace_id,
            'document_id' => $document->id,
            'matched_document_id' => $match->id,
            'score' => $score,
            'signals' => $signals,
        ]);
    }

    public function fingerprintFromExtraction(array $normalized): ?string
    {
        $vendor = Money::normalizeName((string) data_get($normalized, 'merchant.name', ''));
        $date = (string) data_get($normalized, 'transaction_date', '');
        $number = (string) data_get($normalized, 'document_number', '');
        $amount = (string) data_get($normalized, 'grand_total', '');

        if ($vendor === '' && $number === '') {
            return null;
        }

        return strtoupper($vendor.'|'.$date.'|'.$number.'|'.$amount);
    }
}
