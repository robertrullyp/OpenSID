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
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

defined('BASEPATH') || exit('No direct script access allowed');

class Sitemap extends CI_Controller
{
    public function index()
    {
        $perSitemap = 40000;
        $page       = (int) ($this->input->get('page') ?? 0);

        $baseQuery      = Artikel::without(['author', 'category', 'comments'])->active();
        $articlesQuery  = (clone $baseQuery)->sitemap()->orderBy('tgl_upload', 'desc');
        $totalArticles  = (clone $baseQuery)->count();
        $totalPages     = (int) ceil($totalArticles / $perSitemap);
        $data['sitemapUrl'] = site_url($this->uri->uri_string());

        if ($page > 0) {
            if ($totalPages === 0 || $page > $totalPages) {
                show_404();
            }

            $data['artikel'] = $this->prepareEntries($articlesQuery->forPage($page, $perSitemap)->get());
            $data['isIndex'] = false;
        } elseif ($totalPages > 1) {
            $data['isIndex']     = true;
            $data['pages']       = $totalPages;
            $data['generatedAt'] = Carbon::now()->toAtomString();
        } else {
            $data['artikel'] = $this->prepareEntries($articlesQuery->get());
            $data['isIndex'] = false;
        }

        $content = View::make('sitemap', $data)->render();
        header('Content-Type: text/xml; charset=UTF-8');
        echo $content;
    }

    private function prepareEntries($articles)
    {
        return $articles->map(static function ($article) {
            return [
                'loc'     => $article->url_slug,
                'lastmod' => Carbon::parse($article->getRawOriginal('tgl_upload'))->toAtomString(),
            ];
        });
    }
}
