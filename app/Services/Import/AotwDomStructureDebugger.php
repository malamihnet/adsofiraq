<?php

namespace App\Services\Import;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Read-only DOM inspection for AOTW campaign pages (admin debug).
 */
class AotwDomStructureDebugger
{
    public function inspect(Crawler $crawler, string $sourceUrl): array
    {
        $main = $this->findMain($crawler);

        $result = [
            'main_found' => $main->count() > 0,
            'main_selector' => $main->count() > 0 ? $this->mainSelectorUsed($crawler) : null,
            'main_child_count' => 0,
            'images_in_main' => [],
            'container_classes' => [],
            'active_storage_urls' => [],
            'media_block_candidates' => [],
        ];

        $crawler->filter('meta[property="og:image"], meta[name="twitter:image"]')->each(function (Crawler $node) use (&$result) {
            $url = trim($node->attr('content') ?? '');

            if ($url !== '' && str_contains($url, 'active_storage')) {
                $result['active_storage_urls'][] = $url;
            }
        });

        if (! $main->count()) {
            return $result;
        }

        $mainNode = $main->getNode(0);

        if ($mainNode instanceof \DOMElement) {
            $result['main_child_count'] = $this->countElementChildren($mainNode);
        }

        $imgCount = 0;

        $main->filter('img[src*="active_storage"], img[src*="/rails/active_storage"]')->each(function (Crawler $img) use (&$result) {
            $src = trim($img->attr('src') ?? '');

            if ($src !== '') {
                $result['active_storage_urls'][] = $src;
            }
        });

        $imgCount = 0;

        $main->filter('img')->each(function (Crawler $img) use (&$result, &$imgCount) {
            if ($imgCount >= 40) {
                return;
            }

            $src = trim($img->attr('src') ?? '');

            if ($src === '') {
                return;
            }

            $parent = $img->getNode(0)?->parentNode;
            $parentClass = $parent instanceof \DOMElement ? $parent->getAttribute('class') : '';
            $grandParent = $parent?->parentNode;
            $grandClass = $grandParent instanceof \DOMElement ? $grandParent->getAttribute('class') : '';

            $result['images_in_main'][] = [
                'url' => $src,
                'img_class' => $img->attr('class') ?? '',
                'width' => $img->attr('width') ?? '',
                'height' => $img->attr('height') ?? '',
                'parent_class' => $parentClass,
                'grandparent_class' => $grandClass,
            ];
            $imgCount++;
        });

        $containerCount = 0;

        $main->filter('div')->each(function (Crawler $div) use (&$result, &$containerCount) {
            if ($containerCount >= 20) {
                return;
            }

            $class = trim($div->attr('class') ?? '');

            if ($class === '') {
                return;
            }

            $result['container_classes'][] = [
                'tag' => 'div',
                'class' => $class,
                'id' => $div->attr('id') ?? '',
            ];
            $containerCount++;
        });

        $main->filter('div')->each(function (Crawler $div) use (&$result) {
            if (! $this->hasClasses($div, ['bg-white', 'my-3'])) {
                return;
            }

            $result['media_block_candidates'][] = [
                'class' => $div->attr('class') ?? '',
                'id' => $div->attr('id') ?? '',
                'has_max_h_screen_img' => $div->filter('img.max-h-screen, img[class*="max-h-screen"]')->count() > 0,
                'has_video' => $div->filter('iframe, video')->count() > 0,
            ];
        });

        return $result;
    }

    protected function findMain(Crawler $crawler): Crawler
    {
        foreach (['#main', '[id="main"]', '[id=\'main\']', 'main'] as $selector) {
            $main = $crawler->filter($selector);

            if ($main->count() > 0) {
                return $main->first();
            }
        }

        return new Crawler;
    }

    protected function mainSelectorUsed(Crawler $crawler): ?string
    {
        foreach (['#main', '[id="main"]', '[id=\'main\']', 'main'] as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                return $selector;
            }
        }

        return null;
    }

    protected function countElementChildren(\DOMElement $element): int
    {
        $count = 0;

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  list<string>  $required
     */
    protected function hasClasses(Crawler $node, array $required): bool
    {
        $class = $node->attr('class') ?? '';
        $tokens = preg_split('/\s+/', trim($class), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($required as $token) {
            if (! in_array($token, $tokens, true)) {
                return false;
            }
        }

        return true;
    }
}
