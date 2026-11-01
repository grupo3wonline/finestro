<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\PlanPricingInterface;
use App\Interfaces\Services\StructuredDataServiceInterface;

/**
 * schema.org payloads for the public pages. Only facts the app can back up:
 * no ratings, reviews or user counts. Offer prices come from PlanPricing.
 */
class StructuredDataService implements StructuredDataServiceInterface
{
    private const string CONTEXT = 'https://schema.org';

    public function __construct(
        private readonly PlanPricingInterface $pricing,
    ) {}

    public function organization(): array
    {
        $developer = $this->developer();
        $whatsapp = 'https://wa.me/'.config('marketing.contact.whatsapp_number');

        return [
            '@context' => self::CONTEXT,
            '@type' => 'Organization',
            '@id' => $this->homeUrl().'#organization',
            'name' => 'Finestro',
            'url' => $this->homeUrl(),
            'logo' => asset('icon-512.png'),
            'description' => 'Plataforma web de gestão financeira pessoal para organizar carteiras, transações, metas, orçamentos e calculadoras financeiras.',
            'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
            'parentOrganization' => $developer,
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'telephone' => '+55-41-99692-4829',
                    'contactType' => 'customer support',
                    'availableLanguage' => 'pt-BR',
                    'url' => $whatsapp,
                ],
                [
                    '@type' => 'ContactPoint',
                    'email' => (string) config('marketing.contact.email'),
                    'contactType' => 'customer support',
                    'availableLanguage' => 'pt-BR',
                ],
            ],
            'sameAs' => [(string) config('marketing.cta.url')],
        ];
    }

    public function website(): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'WebSite',
            'name' => 'Finestro',
            'url' => $this->homeUrl(),
            'inLanguage' => 'pt-BR',
            'publisher' => ['@id' => $this->homeUrl().'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('faq').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function softwareApplication(): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'SoftwareApplication',
            'name' => 'Finestro',
            'url' => $this->homeUrl(),
            'description' => 'Gestão financeira pessoal: carteiras, receitas e despesas, metas, orçamentos, calculadoras, simuladores, relatórios e insights com IA.',
            'applicationCategory' => 'FinanceApplication',
            'operatingSystem' => 'Web',
            'inLanguage' => 'pt-BR',
            'publisher' => $this->developer(),
            'author' => $this->developer(),
            'offers' => [
                [
                    '@type' => 'Offer',
                    'name' => 'Básico',
                    'price' => '0',
                    'priceCurrency' => 'BRL',
                    'url' => route('register'),
                ],
                [
                    '@type' => 'Offer',
                    'name' => $this->pricing->planName(),
                    'price' => (string) $this->pricing->currentPrice(),
                    'priceCurrency' => 'BRL',
                    'url' => route('register'),
                ],
            ],
        ];
    }

    public function faqPage(array $items): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ], $items),
        ];
    }

    public function howTo(array $steps): array
    {
        $position = 0;

        return [
            '@context' => self::CONTEXT,
            '@type' => 'HowTo',
            'name' => 'Como começar a usar o Finestro',
            'inLanguage' => 'pt-BR',
            'step' => array_map(static function (array $step) use (&$position): array {
                return [
                    '@type' => 'HowToStep',
                    'position' => ++$position,
                    'name' => $step['title'],
                    'text' => $step['text'],
                ];
            }, $steps),
        ];
    }

    public function breadcrumbList(array $items): array
    {
        $elements = [];

        foreach ($items as $index => $item) {
            $element = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
            ];

            if (! empty($item['url'])) {
                $element['item'] = $item['url'];
            }

            $elements[] = $element;
        }

        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function developer(): array
    {
        return [
            '@type' => 'Organization',
            'name' => 'Grupo 3W Online',
            'url' => (string) config('marketing.cta.url'),
        ];
    }

    private function homeUrl(): string
    {
        return route('home');
    }
}
