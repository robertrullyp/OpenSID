<?php

/*
 *
 * File ini bagian dari:
 *
 * OpenSID
 *
 * Sistem informasi desa sumber terbuka untuk memajukan desa
 *
 * Aplikasi dan source code ini dirilis berdasarkan lisensi GPL V3
 *
 * Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * Hak Cipta 2016 - 2025 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:
 *
 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.
 *
 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package   OpenSID
 * @author    Tim Pengembang OpenDesa
 * @copyright Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright Hak Cipta 2016 - 2025 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license   http://www.gnu.org/licenses/gpl.html GPL V3
 * @link      https://github.com/OpenSID/OpenSID
 *
 */

use App\Models\Artikel;
use App\Models\Menu;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

defined('BASEPATH') || exit('No direct script access allowed');

class Sitemap extends CI_Controller
{
    public function index()
    {
        $perSitemap    = 40000;
        $page          = (int) max($this->input->get('page') ?? 0, 0);
        $staticEntries = $this->collectPublicPages();

        $baseQuery     = Artikel::without(['author', 'category', 'comments'])->active();
        $articlesQuery = (clone $baseQuery)->sitemap()->orderBy('tgl_upload', 'desc');
        $totalArticles = (clone $baseQuery)->count();
        $totalEntries  = $staticEntries->count() + $totalArticles;
        $totalPages    = $totalEntries > 0 ? (int) ceil($totalEntries / $perSitemap) : 1;
        $data['sitemapUrl'] = site_url($this->uri->uri_string());

        if ($page > 0) {
            if ($totalPages === 0 || $page > $totalPages) {
                show_404();
            }

            $data['entries'] = $this->getPageEntries($staticEntries, $articlesQuery, $page, $perSitemap);
            $data['isIndex'] = false;
        } elseif ($totalPages > 1) {
            $data['isIndex']     = true;
            $data['pages']       = $totalPages;
            $data['generatedAt'] = Carbon::now()->toAtomString();
        } else {
            $data['entries'] = $staticEntries->merge($this->prepareEntries($articlesQuery->get()));
            $data['isIndex'] = false;
        }

        $content = View::make('sitemap', $data)->render();
        header('Content-Type: text/xml; charset=UTF-8');
        echo $content;
    }

    private function prepareEntries($articles): Collection
    {
        return $articles->map(function ($article) {
            $rawDate = $article->getRawOriginal('tgl_upload');

            if ($article->tgl_upload instanceof CarbonInterface) {
                $lastmod = $article->tgl_upload;
            } elseif (! empty($rawDate)) {
                try {
                    $lastmod = Carbon::parse($rawDate);
                } catch (\Exception $e) {
                    $lastmod = Carbon::now();
                }
            } else {
                $lastmod = Carbon::now();
            }

            return [
                'loc'     => $article->url_slug,
                'lastmod' => $lastmod->toAtomString(),
            ];
        });
    }

    private function collectPublicPages(): Collection
    {
        $entries = collect([$this->makeEntry(site_url())]);
        $menus   = Menu::select(['link', 'link_tipe'])->whereEnabled(Menu::UNLOCK)->get();

        foreach ($menus as $menu) {
            $rawLink = trim($menu->link_url ?? '');

            if ($rawLink === '' || $rawLink === '#') {
                continue;
            }

            $url = filter_var($rawLink, FILTER_VALIDATE_URL)
                ? $rawLink
                : site_url(ltrim($rawLink, '/'));

            if (! $this->isInternalUrl($url)) {
                continue;
            }

            $entries->push($this->makeEntry($url));
        }

        return $entries->unique('loc')->values();
    }

    private function getPageEntries(Collection $staticEntries, Builder $articlesQuery, int $page, int $perPage): Collection
    {
        $totalStatic = $staticEntries->count();
        $offset      = ($page - 1) * $perPage;
        $remaining   = $perPage;
        $entries     = collect();

        if ($offset < $totalStatic) {
            $staticChunk = $staticEntries->slice($offset, $remaining);
            $entries     = $entries->merge($staticChunk);
            $remaining   -= $staticChunk->count();
            $articleOffset = 0;
        } else {
            $articleOffset = $offset - $totalStatic;
        }

        if ($remaining > 0) {
            $articles = $this->prepareEntries((clone $articlesQuery)->skip($articleOffset)->take($remaining)->get());
            $entries  = $entries->merge($articles);
        }

        return $entries->values();
    }

    private function makeEntry(string $loc, ?CarbonInterface $lastmod = null): array
    {
        return [
            'loc'     => $loc,
            'lastmod' => ($lastmod ?? Carbon::now())->toAtomString(),
        ];
    }

    private function isInternalUrl(string $url): bool
    {
        $host     = parse_url($url, PHP_URL_HOST);
        $siteHost = parse_url(site_url(), PHP_URL_HOST);

        return $host === null || $host === $siteHost;
    }
}
