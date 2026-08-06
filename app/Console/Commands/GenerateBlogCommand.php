<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class GenerateBlogCommand extends Command
{
    protected $signature = 'blog:generate';
    protected $description = 'Generate static blog HTML from Markdown posts';

    protected string $sourceDir;
    protected string $outputDir;
    protected string $layoutMaster;
    protected string $layoutPost;
    protected GithubFlavoredMarkdownConverter $markdown;

    public function handle(): int
    {
        $this->sourceDir = base_path('blog/source');
        $this->outputDir = public_path('blog');
        $this->markdown = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        $this->loadLayouts();

        File::deleteDirectory($this->outputDir);

        $posts = $this->parsePosts();
        $categories = $this->buildCategories($posts);

        $this->generatePostPages($posts, $categories);
        $this->generateIndexPage($posts, $categories);
        $this->generateCategoryPages($posts, $categories);
        $this->generateSearchIndex($posts);
        $this->copyAssets();

        $this->info('Blog generated: ' . count($posts) . ' posts, ' . count($categories) . ' categories → ' . $this->outputDir);

        return 0;
    }

    // ─── Layouts ─────────────────────────────────────────────────────

    protected function loadLayouts(): void
    {
        $this->layoutMaster = File::get($this->sourceDir . '/_layouts/master.blade.php');
        $this->layoutPost   = File::get($this->sourceDir . '/_layouts/post.blade.php');
    }

    // ─── Parse posts ─────────────────────────────────────────────────

    protected function parsePosts(): array
    {
        $posts = [];

        foreach (File::glob($this->sourceDir . '/_posts/*.md') as $file) {
            $raw = File::get($file);
            $parsed = $this->parseFrontMatter($raw);

            $slug = Str::slug(pathinfo($file, PATHINFO_FILENAME));
            $rawDate = $parsed['matter']['date'] ?? null;

            if (!$rawDate || $rawDate === '') {
                $dateObj = now();
            } elseif (is_int($rawDate)) {
                $dateObj = (new \DateTime())->setTimestamp($rawDate);
            } else {
                $dateObj = new \DateTime((string) $rawDate);
            }

            $parsedBody = $parsed['body'];
            $parsedBody = preg_replace('/^@extends\([^)]+\)\s*\n?/m', '', $parsedBody);
            $parsedBody = preg_replace('/^@section\([^)]+\)\s*\n?/m', '', $parsedBody);
            $parsedBody = preg_replace('/^\s*@endsection\s*\n?/m', '', $parsedBody);

            $htmlContent = (string) $this->markdown->convert($parsedBody);

            if (!empty($parsed['matter']['image'] ?? null)) {
                $htmlContent = preg_replace('/<img[^>]+>/', '', $htmlContent, 1);
            }

            $posts[] = [
                'slug'     => $slug,
                'folder'   => $slug,
                'filename' => $slug,
                'url'      => '/blog/' . $slug . '/',
                'title'    => $parsed['matter']['title'] ?? $slug,
                'category' => $parsed['matter']['category'] ?? 'General',
                'author'   => $parsed['matter']['author'] ?? 'Vigilante SEACE',
                'date'     => $dateObj,
                'excerpt'  => $parsed['matter']['excerpt'] ?? Str::limit(strip_tags($htmlContent), 160),
                'image'    => $parsed['matter']['image'] ?? null,
                'content'  => $htmlContent,
            ];
        }

        usort($posts, fn($a, $b) => $b['date'] <=> $a['date']);

        return $posts;
    }

    protected function parseFrontMatter(string $raw): array
    {
        $raw = ltrim($raw);
        if (!str_starts_with($raw, '---')) {
            return ['matter' => [], 'body' => $raw];
        }
        $parts = explode('---', $raw, 3);
        if (count($parts) < 3) {
            return ['matter' => [], 'body' => $raw];
        }
        return [
            'matter' => Yaml::parse(trim($parts[1])),
            'body'   => ltrim($parts[2]),
        ];
    }

    // ─── Categories ──────────────────────────────────────────────────

    protected function buildCategories(array $posts): array
    {
        $cats = [];
        foreach ($posts as $p) {
            $slug = Str::slug($p['category']);
            if (!isset($cats[$slug])) {
                $cats[$slug] = [
                    'name'  => $p['category'],
                    'slug'  => $slug,
                    'url'   => '/blog/categoria/' . $slug . '/',
                    'count' => 0,
                ];
            }
            $cats[$slug]['count']++;
        }
        ksort($cats);
        return array_values($cats);
    }

    // ─── Post pages ──────────────────────────────────────────────────

    protected function generatePostPages(array $posts, array $categories): void
    {
        foreach ($posts as $post) {
            $pageHtml = $this->renderPostLayout($post, $categories, $posts);
            $pageHtml = $this->finalReplace($pageHtml);

            $postDir = $this->outputDir . '/' . $post['folder'];
            $this->ensureDir($postDir);

            File::put($postDir . '/index.html', $pageHtml);
        }
    }

    protected function renderPostLayout(array $post, array $categories, array $allPosts): string
    {
        $title = e($post['title']);
        $excerpt = e((string) $post['excerpt']);
        $author = e((string) $post['author']);
        $authorInitial = strtoupper(substr($author, 0, 1));
        $category = e((string) $post['category']);
        $categoryUrl = '/blog/categoria/' . Str::slug($post['category']) . '/';
        $wordCount = str_word_count(strip_tags($post['content']));
        $readTime = max(1, (int) ceil($wordCount / 200));
        $imageUrl = $post['image'] ?? '';
        $url = 'https://vigilanteseace.pe' . $post['url'];
        $dateShort = $post['date']->format('d M Y');
        $dateLong = $post['date']->format('F d, Y');
        $shareText = urlencode($title . ' ' . $url);
        $urlEncoded = urlencode($url);
        $dateIso = $post['date']->format('c');
        $categoryUrlFull = 'https://vigilanteseace.pe' . $categoryUrl;

        $imageBlock = '';
        if (!empty($imageUrl)) {
            $imageBlock = '<figure class="m-0 mb-10 -mx-4 sm:-mx-6 lg:-mx-8 sm:rounded-2xl overflow-hidden shadow-lg"><div class="relative h-64 sm:h-96 w-full"><img src="' . $imageUrl . '" alt="' . $title . '" class="absolute inset-0 w-full h-full object-cover" loading="lazy"></div></figure>';
        }

        $relatedHtml = $this->renderRelatedPosts($post, $allPosts);

        return strtr($this->layoutPost, [
            '__TITLE__' => $title,
            '__EXCERPT__' => $excerpt,
            '__AUTHOR__' => $author,
            '__INITIAL__' => $authorInitial,
            '__CATEGORY__' => $category,
            '__CATEGORY_URL__' => $categoryUrl,
            '__DATE_SHORT__' => $dateShort,
            '__DATE_LONG__' => $dateLong,
            '__READTIME__' => (string) $readTime,
            '__SHARE_TEXT__' => $shareText,
            '__URL_ENCODED__' => $urlEncoded,
            '__IMAGE_URL__' => $imageUrl,
            '__DATE_ISO__' => $dateIso,
            '__CANONICAL_URL__' => $url,
            '__CATEGORY_URL_FULL__' => $categoryUrlFull,
            '__IMAGE_BLOCK__' => $imageBlock,
            '__CONTENT__' => $post['content'],
            '__RELATED__' => $relatedHtml,
            '__YEAR__' => date('Y'),
        ]);
    }

    // ─── Index page ──────────────────────────────────────────────────

    protected function generateIndexPage(array $posts, array $categories): void
    {
        $perPage = 6;
        $total = count($posts);
        $totalPages = max(1, (int) ceil($total / $perPage));

        for ($page = 1; $page <= $totalPages; $page++) {
            $items = array_slice($posts, ($page - 1) * $perPage, $perPage);
            $pageHtml = $this->renderIndex($items, $categories, $page, $totalPages);
            $pageHtml = $this->finalReplace($pageHtml);

            if ($page === 1) {
                $this->ensureDir($this->outputDir);
                File::put($this->outputDir . '/index.html', $pageHtml);
            } else {
                $pageDir = $this->outputDir . '/page/' . $page;
                $this->ensureDir($pageDir);
                File::put($pageDir . '/index.html', $pageHtml);
            }
        }
    }

    protected function renderIndex(array $posts, array $categories, int $currentPage, int $totalPages): string
    {
        $cardsHtml = '';
        foreach ($posts as $post) {
            $initial = strtoupper(substr($post['author'], 0, 1));
            $catUrl = '/blog/categoria/' . Str::slug($post['category']) . '/';
            $imgTag = '';
            if (!empty($post['image'])) {
                $imgTag = '<div class="relative h-52 w-full overflow-hidden"><img src="' . $post['image'] . '" alt="' . e($post['title']) . '" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"></div>';
            } else {
                $imgTag = '<div class="h-40 bg-gradient-to-br from-primary-500/20 via-primary-400/10 to-secondary-400/20 flex items-center justify-center"><svg class="w-10 h-10 text-primary-300/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg></div>';
            }
            $cardsHtml .= <<<CARD
                <a href="{$post['url']}" class="group bg-white rounded-2xl shadow-card border border-neutral-100 overflow-hidden hover:border-primary-300 hover:shadow-md transition-all flex flex-col">
                    {$imgTag}
                    <div class="p-5 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 mb-2">
                            <a href="{$catUrl}" onclick="event.stopPropagation()" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary-50 text-primary-600 border border-primary-100 hover:bg-primary-100 transition-colors">
                                {$post['category']}
                            </a>
                            <span class="text-xs text-neutral-400">{$post['date']->format('d M Y')}</span>
                        </div>
                        <h2 class="text-base font-bold text-neutral-900 group-hover:text-primary-600 transition-colors mb-1.5 line-clamp-2 leading-snug">
                            {$post['title']}
                        </h2>
                        <p class="text-xs text-neutral-500 line-clamp-2 leading-relaxed flex-1">
                            {$post['excerpt']}
                        </p>
                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-neutral-100">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-primary-500 to-primary-400 flex items-center justify-center text-white text-[9px] font-bold shadow-sm">
                                {$initial}
                            </div>
                            <span class="text-[11px] text-neutral-500">{$post['author']}</span>
                            <span class="ml-auto text-[11px] font-medium text-primary-500 group-hover:translate-x-1 transition-transform inline-flex items-center gap-0.5">
                                Leer <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </div>
                </a>
            CARD;
        }

        $paginationHtml = '';
        if ($totalPages > 1) {
            $paginationHtml .= '<div class="flex items-center justify-center gap-1.5">';
            if ($currentPage > 1) {
                $prevUrl = $currentPage === 2 ? '/blog/' : "/blog/page/" . ($currentPage - 1) . '/';
                $paginationHtml .= '<a href="' . $prevUrl . '" class="w-8 h-8 flex items-center justify-center rounded-lg text-neutral-400 hover:text-primary-600 hover:bg-primary-50 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>';
            }
            for ($p = 1; $p <= $totalPages; $p++) {
                $pageUrl = $p === 1 ? '/blog/' : "/blog/page/$p/";
                $active = $p === $currentPage ? 'bg-primary-500 text-white font-bold' : 'text-neutral-500 hover:bg-neutral-100 font-medium';
                $paginationHtml .= '<a href="' . $pageUrl . '" class="w-8 h-8 text-xs rounded-lg flex items-center justify-center transition-colors ' . $active . '">' . $p . '</a>';
            }
            if ($currentPage < $totalPages) {
                $paginationHtml .= '<a href="/blog/page/' . ($currentPage + 1) . '/" class="w-8 h-8 flex items-center justify-center rounded-lg text-neutral-400 hover:text-primary-600 hover:bg-primary-50 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
            }
            $paginationHtml .= '</div>';
        }

        $catsSidebar = $this->renderSidebar($categories, null, $posts);

        $body = <<<BODY
        <div class="bg-gradient-to-br from-primary-500 to-primary-600 text-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <h1 class="text-3xl sm:text-4xl font-extrabold mb-3">Blog de Vigilante SEACE</h1>
                <p class="text-base sm:text-lg text-primary-100/90 max-w-2xl">Guías, noticias y análisis sobre contrataciones del Estado peruano. Aprendé a usar el SEACE, interpretar TDRs y detectar direccionamiento.</p>
            </div>
        </div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="flex-1 min-w-0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {$cardsHtml}
                    </div>
                    <div class="mt-8">
                        {$paginationHtml}
                    </div>
                </div>
                {$catsSidebar}
            </div>
        </div>
        BODY;

        return strtr($this->layoutMaster, [
            '@yield(\'title\', \'Blog — Vigilante SEACE\')' => '📰 Blog de Licitaciones SEACE — Guías, Noticias y Análisis | Vigilante SEACE',
            '@yield(\'description\', \'Blog de Vigilante SEACE. Guías, noticias y análisis de contratación pública.\')' => 'Guías actualizadas sobre el SEACE, contrataciones del Estado peruano, análisis de TDR con IA, Ley 32069 y cómo vender al Estado. ' . date('Y') . '.',
            '@yield(\'head\')' => '',
            '@yield(\'content\')' => $body,
        ]);
    }

    // ─── Category pages ──────────────────────────────────────────────

    protected function generateCategoryPages(array $posts, array $categories): void
    {
        foreach ($categories as $cat) {
            $catPosts = array_values(array_filter($posts, fn($p) => Str::slug($p['category']) === $cat['slug']));
            $html = $this->renderCategory($catPosts, $cat, $categories);
            $html = $this->finalReplace($html);

            $catDir = $this->outputDir . '/categoria/' . $cat['slug'];
            $this->ensureDir($catDir);
            File::put($catDir . '/index.html', $html);
        }
    }

    protected function renderCategory(array $posts, array $cat, array $categories): string
    {
        $cardsHtml = '';
        foreach ($posts as $post) {
            $initial = strtoupper(substr($post['author'], 0, 1));
            $cardsHtml .= <<<CARD
                <a href="{$post['url']}" class="group bg-white rounded-2xl shadow-soft border border-neutral-200 overflow-hidden hover:border-primary-300 hover:shadow-md transition-all flex flex-col">
                    <div class="p-5 flex-1 flex flex-col">
                        <span class="text-xs text-neutral-400 mb-2">{$post['date']->format('d M Y')}</span>
                        <h2 class="text-base font-bold text-neutral-900 group-hover:text-primary-600 transition-colors mb-1.5 line-clamp-2 leading-snug">
                            {$post['title']}
                        </h2>
                        <p class="text-xs text-neutral-500 line-clamp-2 leading-relaxed flex-1">
                            {$post['excerpt']}
                        </p>
                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-neutral-100">
                            <div class="w-6 h-6 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600 text-[9px] font-bold">
                                {$initial}
                            </div>
                            <span class="text-[11px] text-neutral-500">{$post['author']}</span>
                            <span class="ml-auto text-[11px] font-medium text-primary-500 inline-flex items-center gap-0.5">
                                Leer &rarr;
                            </span>
                        </div>
                    </div>
                </a>
            CARD;
        }

        $catsSidebar = $this->renderSidebar($categories, $cat['slug'], $posts);

        $body = <<<BODY
        <div class="bg-gradient-to-br from-primary-500 to-primary-600 text-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <p class="text-sm text-primary-200 mb-1"><a href="/blog/" class="hover:text-white transition-colors">&larr; Blog</a></p>
                <h1 class="text-3xl sm:text-4xl font-extrabold mb-3">Categoría: {$cat['name']}</h1>
                <p class="text-base text-primary-100/90">{$cat['count']} post(s) en esta categoría</p>
            </div>
        </div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="flex-1 min-w-0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {$cardsHtml}
                    </div>
                </div>
                {$catsSidebar}
            </div>
        </div>
        BODY;

        return strtr($this->layoutMaster, [
            '@yield(\'title\', \'Blog — Vigilante SEACE\')' => "{$cat['name']} — Blog de Licitaciones SEACE | Vigilante SEACE",
            '@yield(\'description\', \'Blog de Vigilante SEACE. Guías, noticias y análisis de contratación pública.\')' => "Artículos sobre {$cat['name']} en el blog de Vigilante SEACE. Guías, noticias y análisis de contrataciones del Estado peruano. " . date('Y') . ".",
            '@yield(\'head\')' => '',
            '@yield(\'content\')' => $body,
        ]);
    }

    // ─── Sidebar ─────────────────────────────────────────────────────

    protected function renderSidebar(array $categories, ?string $activeSlug, array $allPosts = []): string
    {
        $html = '<aside class="lg:w-72 shrink-0"><div class="lg:sticky lg:top-20 space-y-5">';

        // Search
        $html .= '<div class="relative">';
        $html .= '<input id="blog-search-input" type="text" oninput="searchPosts()" onfocus="searchPosts()" placeholder="Buscar artículos..." autocomplete="off" class="w-full pl-9 pr-4 py-2.5 bg-white border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">';
        $html .= '<svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';
        $html .= '<div id="blog-search-results" class="hidden absolute z-50 top-full mt-1 left-0 right-0 bg-white border border-neutral-200 rounded-xl shadow-lg overflow-hidden divide-y divide-neutral-50"></div>';
        $html .= '</div>';

        // Categories
        $totalCount = array_sum(array_column($categories, 'count'));
        $html .= '<div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-4">';
        $html .= '<h3 class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Categorías</h3>';
        $html .= '<ul class="space-y-0.5">';
        $html .= '<li><a href="/blog/" class="flex items-center justify-between px-3 py-2 rounded-lg text-sm ' . ($activeSlug === null ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-neutral-600 hover:bg-neutral-50') . ' transition-colors"><span>Todos</span><span class="text-xs text-neutral-400">' . $totalCount . '</span></a></li>';
        foreach ($categories as $cat) {
            $isActive = $activeSlug === $cat['slug'];
            $class = $isActive ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-neutral-600 hover:bg-neutral-50';
            $html .= '<li><a href="' . $cat['url'] . '" class="flex items-center justify-between px-3 py-2 rounded-lg text-sm ' . $class . ' transition-colors"><span>' . e($cat['name']) . '</span><span class="text-xs text-neutral-400">' . $cat['count'] . '</span></a></li>';
        }
        $html .= '</ul></div>';

        // Popular posts
        if (count($allPosts) >= 2) {
            $popular = array_slice($allPosts, 0, 3);
            $html .= '<div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-4">';
            $html .= '<h3 class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Más leídos</h3>';
            $html .= '<div class="space-y-3">';
            foreach ($popular as $i => $pp) {
                $html .= '<a href="' . $pp['url'] . '" class="flex items-start gap-3 group"><span class="text-lg font-extrabold text-neutral-200 group-hover:text-primary-300 leading-none shrink-0">' . ($i + 1) . '</span><div class="min-w-0"><p class="text-xs font-medium text-neutral-700 group-hover:text-primary-600 transition-colors line-clamp-2 leading-snug">' . e($pp['title']) . '</p><p class="text-[10px] text-neutral-400 mt-0.5">' . $pp['date']->format('d M') . '</p></div></a>';
            }
            $html .= '</div></div>';
        }

        // CTA
        $html .= '<div class="bg-gradient-to-br from-primary-500 to-primary-600 text-white rounded-2xl p-5 shadow-lg">';
        $html .= '<div class="flex items-center gap-2 mb-3"><div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div><p class="text-sm font-bold">Vigilante SEACE</p></div>';
        $html .= '<p class="text-xs text-primary-100 leading-relaxed mb-4">Monitorea licitaciones, analiza TDRs con IA y recibe alertas automáticas.</p>';
        $html .= '<a href="/register" class="block w-full text-center py-2.5 bg-white text-primary-600 rounded-xl text-sm font-bold hover:bg-primary-50 transition-colors shadow-sm">Probar Gratis Ahora</a>';
        $html .= '</div>';

        $html .= '</div></aside>';
        return $html;
    }

    // ─── Search index ───────────────────────────────────────────────

    protected function generateSearchIndex(array $posts): void
    {
        $items = [];
        foreach ($posts as $p) {
            $items[] = [
                'title' => $p['title'],
                'excerpt' => strip_tags($p['excerpt']),
                'url' => $p['url'],
                'category' => $p['category'],
                'date' => $p['date']->format('d M Y'),
            ];
        }

        $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        File::put($this->outputDir . '/search-index.js', 'window.BLOG_POSTS = ' . $json . ';');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    protected function renderRelatedPosts(array $currentPost, array $allPosts): string
    {
        $related = array_values(array_filter($allPosts, fn($p) =>
            $p['slug'] !== $currentPost['slug'] && $p['category'] === $currentPost['category']
        ));

        if (empty($related)) {
            $related = array_values(array_filter($allPosts, fn($p) => $p['slug'] !== $currentPost['slug']));
        }
        $related = array_slice($related, 0, 3);

        if (empty($related)) return '';

        $html = '<div class="mt-10 pt-8 border-t border-neutral-200"><h3 class="text-lg font-bold text-neutral-900 mb-5">Artículos relacionados</h3><div class="grid grid-cols-1 sm:grid-cols-3 gap-4">';

        foreach ($related as $rp) {
            $initial = strtoupper(substr($rp['author'], 0, 1));
            $html .= '<a href="' . $rp['url'] . '" class="group bg-white rounded-xl shadow-card border border-neutral-100 overflow-hidden hover:border-primary-300 transition-all flex flex-col">';
            $html .= '<div class="p-4 flex-1 flex flex-col">';
            $html .= '<span class="text-[10px] font-bold uppercase tracking-wider text-primary-500 mb-1">' . e($rp['category']) . '</span>';
            $html .= '<h4 class="text-sm font-bold text-neutral-900 group-hover:text-primary-600 transition-colors line-clamp-2 leading-snug mb-1">' . e($rp['title']) . '</h4>';
            $html .= '<p class="text-[11px] text-neutral-400 mt-auto pt-2">' . $rp['date']->format('d M Y') . ' · ' . ceil(str_word_count(strip_tags($rp['content'])) / 200) . ' min</p>';
            $html .= '</div></a>';
        }

        $html .= '</div></div>';
        return $html;
    }

    protected function ensureDir(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function finalReplace(string $html): string
    {
        return str_replace("{{ date('Y') }}", date('Y'), $html);
    }

    protected function copyAssets(): void
    {
        $assetDir = $this->sourceDir . '/assets';
        if (File::isDirectory($assetDir)) {
            File::copyDirectory($assetDir, $this->outputDir . '/assets');
        }
        File::put($this->outputDir . '/.htaccess', "<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteCond %{REQUEST_FILENAME} !-d\n    RewriteCond %{REQUEST_FILENAME} !-f\n    RewriteRule ^ index.html [L]\n</IfModule>\n");
    }
}
