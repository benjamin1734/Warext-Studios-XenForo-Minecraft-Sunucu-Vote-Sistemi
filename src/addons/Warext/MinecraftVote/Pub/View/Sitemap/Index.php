<?php

namespace Warext\MinecraftVote\Pub\View\Sitemap;

use XF\Mvc\View;

class Index extends View
{
    public function renderRaw()
    {
        $this->response->contentType('application/xml', 'utf-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->params['entries'] ?? [] as $entry)
        {
            $loc = htmlspecialchars((string)($entry['loc'] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            if ($loc === '')
            {
                continue;
            }

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . $loc . "</loc>\n";
            if (!empty($entry['lastmod']))
            {
                $xml .= '    <lastmod>' . gmdate('c', (int)$entry['lastmod']) . "</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";
        return $xml;
    }
}
