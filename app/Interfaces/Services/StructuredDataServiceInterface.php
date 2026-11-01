<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

/**
 * Builds schema.org JSON-LD payloads as plain PHP arrays, so they are
 * always valid JSON once encoded (no hand-built strings in Blade).
 */
interface StructuredDataServiceInterface
{
    /**
     * Organization "Finestro" with Grupo 3W Online as publisher/developer.
     *
     * @return array<string, mixed>
     */
    public function organization(): array;

    /**
     * WebSite with a SearchAction targeting the public help search.
     *
     * @return array<string, mixed>
     */
    public function website(): array;

    /**
     * SoftwareApplication with one Offer per plan (prices from PlanPricing).
     *
     * @return array<string, mixed>
     */
    public function softwareApplication(): array;

    /**
     * @param  list<array{question: string, answer: string}>  $items
     * @return array<string, mixed>
     */
    public function faqPage(array $items): array;

    /**
     * @param  list<array{title: string, text: string}>  $steps
     * @return array<string, mixed>
     */
    public function howTo(array $steps): array;

    /**
     * @param  list<array{label: string, url?: string|null}>  $items  Ordered trail, root first
     * @return array<string, mixed>
     */
    public function breadcrumbList(array $items): array;
}
