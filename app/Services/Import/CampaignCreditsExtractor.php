<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class CampaignCreditsExtractor
{
    /** @var list<string> */
    protected array $sectionHeadings = [
        'description',
        'credits',
        'credit',
        'categories',
        'category',
        'related campaigns',
        'related',
        'share',
    ];

    public function extract(Crawler $crawler, string $html, string $sourceUrl, bool $isAotw = false): ?string
    {
        $blocks = [];

        if ($isAotw) {
            $main = $crawler->filter('#main');
            $scope = $main->count() ? $main : $crawler;

            $aotwBlock = $this->extractAotwCreditsBlock($scope);

            if ($aotwBlock !== null) {
                $blocks[] = $aotwBlock;
            }
        } else {
            $headingBlock = $this->extractFromCreditsHeadings($crawler);

            if ($headingBlock !== null) {
                $blocks[] = $headingBlock;
            }
        }

        if ($blocks === []) {
            $fallback = $this->extractFromDefinitionLists($crawler);

            if ($fallback !== null) {
                $blocks[] = $fallback;
            }

            $tableBlock = $this->extractFromTables($crawler);

            if ($tableBlock !== null) {
                $blocks[] = $tableBlock;
            }
        }

        $jsonLd = $this->extractFromJsonLd($html);

        if ($jsonLd !== null) {
            $blocks[] = $jsonLd;
        }

        $formatted = self::sanitizeCredits(implode("\n", array_filter($blocks)));

        if ($formatted === null && $isAotw) {
            Log::warning('Importer: credits not found for {url}', ['url' => $sourceUrl]);
        }

        return $formatted;
    }

    public static function sanitizeCredits(?string $credits): ?string
    {
        if ($credits === null) {
            return null;
        }

        $credits = str_replace(["\r\n", "\r"], "\n", $credits);
        $credits = preg_replace("/[ \t]+/u", ' ', $credits) ?? $credits;

        $lines = explode("\n", $credits);
        $result = [];
        $hadBlank = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                if ($result !== [] && ! $hadBlank) {
                    $result[] = '';
                    $hadBlank = true;
                }

                continue;
            }

            $hadBlank = false;
            $result[] = $line;
        }

        $text = implode("\n", $result);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    protected function extractAotwCreditsBlock(Crawler $scope): ?string
    {
        $parts = [];

        $scope->filter('p')->each(function (Crawler $node) use (&$parts) {
            if (! $this->isCreditsHeading($this->plainText($node->text()))) {
                return;
            }

            $sibling = $node->getNode(0)?->nextSibling;

            while ($sibling !== null) {
                if ($sibling->nodeType !== XML_ELEMENT_NODE) {
                    $sibling = $sibling->nextSibling;

                    continue;
                }

                /** @var \DOMElement $element */
                $element = $sibling;

                if ($element->nodeName === 'p' && $this->isSectionHeading($this->plainText($element->textContent))) {
                    break;
                }

                if (in_array($element->nodeName, ['div', 'section', 'article'], true)) {
                    $text = $this->htmlToPlainText($element->ownerDocument->saveHTML($element) ?: '');

                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }

                if ($element->nodeName === 'p' && ! $this->isSectionHeading($this->plainText($element->textContent))) {
                    $text = $this->htmlToPlainText($element->ownerDocument->saveHTML($element) ?: '');

                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }

                $sibling = $sibling->nextSibling;
            }
        });

        if ($parts === []) {
            return null;
        }

        return implode("\n", $parts);
    }

    protected function extractFromCreditsHeadings(Crawler $crawler): ?string
    {
        $parts = [];

        $crawler->filter('h1, h2, h3, h4, h5, h6, p, span, strong')->each(function (Crawler $node) use (&$parts) {
            if (! $this->isCreditsHeading($this->plainText($node->text()))) {
                return;
            }

            $parent = $node->ancestors()->filter('div, section, article')->first();

            if ($parent->count()) {
                $text = $this->htmlToPlainText($parent->html('') ?? '');

                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        });

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    protected function extractFromDefinitionLists(Crawler $crawler): ?string
    {
        $lines = [];

        $crawler->filter('dl')->each(function (Crawler $dl) use (&$lines) {
            if (! $this->containerNearCreditsHeading($dl)) {
                return;
            }

            $dl->filter('dt')->each(function (Crawler $dt) use ($dl, &$lines) {
                $label = $this->plainText($dt->text());
                $dd = $dt->nextAll()->filter('dd')->first();
                $value = $dd->count() ? $this->plainText($dd->text()) : null;

                if ($label !== null && $value !== null && $value !== '') {
                    $lines[] = $label.': '.$value;
                }
            });
        });

        return $lines !== [] ? implode("\n", $lines) : null;
    }

    protected function extractFromTables(Crawler $crawler): ?string
    {
        $lines = [];

        $crawler->filter('table')->each(function (Crawler $table) use (&$lines) {
            if (! $this->containerNearCreditsHeading($table)) {
                return;
            }

            $table->filter('tr')->each(function (Crawler $row) use (&$lines) {
                $cells = $row->filter('th, td');

                if ($cells->count() < 2) {
                    return;
                }

                $label = $this->plainText($cells->eq(0)->text());
                $value = $this->plainText($cells->eq(1)->text());

                if ($label !== null && $value !== null && $value !== '') {
                    $lines[] = $label.': '.$value;
                }
            });
        });

        return $lines !== [] ? implode("\n", $lines) : null;
    }

    protected function extractFromJsonLd(string $html): ?string
    {
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true);

            if (! is_array($decoded)) {
                continue;
            }

            $credits = $decoded['credits'] ?? $decoded['creditText'] ?? null;

            if (is_string($credits) && trim($credits) !== '') {
                return self::sanitizeCredits($credits);
            }
        }

        return null;
    }

    protected function htmlToPlainText(string $html): string
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $lines = [];

        foreach (preg_split('/\n/', $text) ?: [] as $line) {
            $line = trim(preg_replace('/[ \t]+/u', ' ', $line) ?? '');

            if ($line === '' || $this->isBoilerplateLine($line)) {
                continue;
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    protected function containerNearCreditsHeading(Crawler $node): bool
    {
        $parent = $node->ancestors()->first();

        if (! $parent->count()) {
            return false;
        }

        return str_contains(strtolower($parent->text('')), 'credit');
    }

    protected function isCreditsHeading(?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        return in_array(strtolower($text), ['credits', 'credit', 'creative credits', 'production credits'], true);
    }

    protected function isSectionHeading(?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        return in_array(strtolower($text), $this->sectionHeadings, true)
            || str_starts_with(strtolower($text), 'this  professional campaign');
    }

    protected function isBoilerplateLine(string $line): bool
    {
        $lower = strtolower($line);

        return str_starts_with($lower, 'this  professional campaign')
            || str_starts_with($lower, 'check out this work')
            || str_contains($lower, 'was published in')
            || str_contains($lower, 'media asset')
            || str_contains($lower, 'was submitted')
            || str_contains($lower, 'by ad agencies:')
            || str_contains($lower, 'marketing agency');
    }

    protected function plainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $value !== '' ? $value : null;
    }
}
