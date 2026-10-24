<?php

namespace App\DTO;

use JsonSerializable;

/**
 * DTO untuk menampung struktur data hasil inferensi Fuzzy Logic.
 *
 * Catatan Arsitektur Phase 14:
 * Struktur ini disiapkan untuk menerima hasil inferensi jika dikirimkan oleh firmware
 * ESP32 atau sumber telemetri lainnya. Mesin inferensi AI final belum diaktifkan
 * sampai rule-base dan membership function disepakati bersama Tim Elektro.
 */
class FuzzyInferenceResult implements JsonSerializable
{
    public function __construct(
        public readonly ?string $inferenceTimestamp = null,
        public readonly array $inputValues = [],
        public readonly string $method = 'Mamdani',
        public readonly ?float $score = null,
        public readonly ?string $conditionCategory = null,
        public readonly ?string $conclusion = null,
        public readonly string|array|null $recommendation = null,
        public readonly string $inferenceSource = 'unspecified',
        public readonly string $ruleVersion = 'v1.0-provisional',
        public readonly string $status = 'AVAILABLE',
        public readonly ?array $membershipSnapshot = null,
        public readonly ?array $ruleActivationSnapshot = null
    ) {}

    public static function fromModel(\App\Models\FuzzyInferenceResult $model): self
    {
        return new self(
            inferenceTimestamp: $model->inferred_at?->toDateTimeString(),
            inputValues: $model->input_snapshot ?? [],
            method: 'Mamdani',
            score: $model->fuzzy_score,
            conditionCategory: $model->fuzzy_category,
            conclusion: $model->conclusion,
            recommendation: $model->recommendation,
            inferenceSource: $model->inference_source,
            ruleVersion: $model->rule_version,
            status: $model->inference_status,
            membershipSnapshot: $model->membership_snapshot,
            ruleActivationSnapshot: $model->rule_activation_snapshot
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            inferenceTimestamp: $data['inference_timestamp'] ?? $data['timestamp'] ?? null,
            inputValues: $data['input_values'] ?? $data['input_vector'] ?? [],
            method: $data['method'] ?? 'Mamdani',
            score: isset($data['score']) ? (float) $data['score'] : (isset($data['comfort_score']) ? (float) $data['comfort_score'] : null),
            conditionCategory: $data['condition_category'] ?? $data['condition'] ?? null,
            conclusion: $data['conclusion'] ?? null,
            recommendation: $data['recommendation'] ?? $data['recommended_action'] ?? null,
            inferenceSource: $data['inference_source'] ?? $data['source_of_inference'] ?? 'unspecified',
            ruleVersion: $data['rule_version'] ?? 'v1.0-provisional',
            status: $data['status'] ?? $data['inference_status'] ?? 'AVAILABLE',
            membershipSnapshot: $data['membership_snapshot'] ?? null,
            ruleActivationSnapshot: $data['rule_activation_snapshot'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'inference_timestamp'     => $this->inferenceTimestamp,
            'input_values'            => $this->inputValues,
            'method'                  => $this->method,
            'score'                   => $this->score,
            'condition_category'      => $this->conditionCategory,
            'conclusion'              => $this->conclusion,
            'recommendation'          => $this->recommendation,
            'inference_source'        => $this->inferenceSource,
            'rule_version'            => $this->ruleVersion,
            'status'                  => $this->status,
            'membership_snapshot'     => $this->membershipSnapshot,
            'rule_activation_snapshot' => $this->ruleActivationSnapshot,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
