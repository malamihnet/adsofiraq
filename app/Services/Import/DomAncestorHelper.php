<?php

namespace App\Services\Import;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Symfony DomCrawler::ancestors()->filter() searches descendants of each ancestor,
 * not the ancestor chain itself. Use these helpers for true parent-walk checks.
 */
final class DomAncestorHelper
{
    /**
     * @return \Generator<int, \DOMElement>
     */
    public static function walkElements(\DOMNode $node): \Generator
    {
        $current = $node->parentNode;

        while ($current instanceof \DOMElement) {
            yield $current;
            $current = $current->parentNode;
        }
    }

    public static function hasMatchingAncestor(\DOMNode $node, callable $predicate): bool
    {
        foreach (self::walkElements($node) as $ancestor) {
            if ($predicate($ancestor)) {
                return true;
            }
        }

        return false;
    }

    public static function hasAncestorTag(\DOMNode $node, string $tag): bool
    {
        $tag = strtolower($tag);

        return self::hasMatchingAncestor(
            $node,
            fn (\DOMElement $element) => strtolower($element->tagName) === $tag,
        );
    }

    public static function blockIsInRelatedCampaignsRegion(Crawler $block): bool
    {
        $node = $block->getNode(0);

        if ($node === null) {
            return false;
        }

        return self::hasMatchingAncestor($node, function (\DOMElement $ancestor) {
            $id = $ancestor->getAttribute('id') ?? '';

            if ($id === 'related' || str_starts_with($id, 'related_') || str_starts_with($id, 'campaign_card_')) {
                return true;
            }

            $onclick = $ancestor->getAttribute('onclick') ?? '';

            if (str_contains($onclick, 'location.href') && str_contains($onclick, '/campaigns/')) {
                return true;
            }

            $class = $ancestor->getAttribute('class') ?? '';

            return str_contains($class, 'shadow-lg') && str_contains($onclick, '/campaigns/');
        });
    }

    public static function isInsideCampaignLink(\DOMNode $node): bool
    {
        return self::hasMatchingAncestor($node, function (\DOMElement $ancestor) {
            if (strtolower($ancestor->tagName) !== 'a') {
                return false;
            }

            return str_contains($ancestor->getAttribute('href') ?? '', '/campaigns/');
        });
    }

    public static function isInsideCampaignHeader(\DOMNode $node): bool
    {
        return self::hasMatchingAncestor(
            $node,
            fn (\DOMElement $ancestor) => str_starts_with($ancestor->getAttribute('id') ?? '', 'campaign_header'),
        );
    }

    public static function isInsideFooter(\DOMNode $node): bool
    {
        return self::hasAncestorTag($node, 'footer');
    }

    public static function isInsidePicture(\DOMNode $node): bool
    {
        return self::hasAncestorTag($node, 'picture');
    }
}
