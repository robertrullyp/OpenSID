<?xml version="1.0" encoding="UTF-8"?>
@if ($isIndex ?? false)
    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        @for ($page = 1; $page <= $pages; $page++)
            <sitemap>
                <loc>{{ $sitemapUrl }}?page={{ $page }}</loc>
                <lastmod>{{ $generatedAt ?? now()->toAtomString() }}</lastmod>
            </sitemap>
        @endfor
    </sitemapindex>
@else
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        @foreach (($entries ?? collect()) as $item)
            <url>
                <loc>{{ $item['loc'] }}</loc>
                <lastmod>{{ $item['lastmod'] }}</lastmod>
                <priority>0.8</priority>
                <changefreq>weekly</changefreq>
            </url>
        @endforeach
    </urlset>
@endif
