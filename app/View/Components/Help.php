<?php

namespace App\View\Components;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Symfony\Component\DomCrawler\Crawler;

class Help extends Component
{
    public string $helpDir;
    public bool $showPageLinks;
    public bool $showQuickLinks;
    public bool $showBreadcrumbs;
    public ?string $page;
    public ?string $content;
    public array $headings;
    public ?string $currentPage;
    public $availablePages;
    public string $class;
    public string $sidebarClass;
    public string $contentClass;
    public bool $showPagesList;
    public array $breadcrumbs;

    public function __construct(
        string $helpDir = 'views/help',
        bool $showPageLinks = true,
        bool $showQuickLinks = true,
        bool $showBreadcrumbs = false,
        ?string $page = null,
        ?string $content = null,
        array $headings = [],
        ?string $currentPage = null,
        $availablePages = [],
        string $class = '',
        string $sidebarClass = 'border-l border-slate-200 w-64 hidden lg:block overflow-y-auto py-4 px-1 rounded',
        string $contentClass = 'mt-2 w-full text-left prose-sm prose-slate dark:prose-invert dark:bg-gray-900 dark:prose-h2:text-blue-200 dark:prose-h3:text-slate-400 prose-h2:text-blue-900'
    ) {
        $this->helpDir = $helpDir;
        $this->showPageLinks = $showPageLinks;
        $this->showQuickLinks = $showQuickLinks;
        $this->showBreadcrumbs = $showBreadcrumbs;
        $this->page = $page;
        $this->content = $content;
        $this->headings = $headings;
        $this->currentPage = $currentPage;
        $this->availablePages = $availablePages;
        $this->class = $class;
        $this->sidebarClass = $sidebarClass;
        $this->contentClass = $contentClass;

        $this->processPage();
        $this->discoverPages();
        
        if ($this->showBreadcrumbs) {
            $this->generateBreadcrumbs();
        }
        
        $this->showPagesList = is_null($this->content);
    }

    protected function processPage(): void
    {
        if (!$this->page || $this->content) {
            return;
        }

        // For nested paths, use the page parameter as-is (already sanitized by controller)
        // For simple slugs, apply additional sanitization as fallback
        if (!str_contains($this->page, '/')) {
            $this->page = Str::slug($this->page) ?: 'index';
        }

        $markdownPath = resource_path("{$this->helpDir}/{$this->page}.md");

        // Check if this is a directory browse request
        $directoryPath = resource_path("{$this->helpDir}/{$this->page}");
        $isDirectoryBrowse = File::isDirectory($directoryPath) && !File::exists($markdownPath);

        if ($isDirectoryBrowse) {
            // This is a directory browse request - don't load content, show directory listing
            $this->content = null;
            $this->currentPage = $this->page;
            return;
        }

        // If the direct file doesn't exist, try looking for an index file in the directory
        if (!File::exists($markdownPath)) {
            $directoryIndexPath = resource_path("{$this->helpDir}/{$this->page}/index.md");
            if (File::exists($directoryIndexPath)) {
                $markdownPath = $directoryIndexPath;
            }
        }

        if (File::exists($markdownPath)) {
            // Load and process markdown
            $markdown = File::get($markdownPath);
            $this->content = Str::markdown($markdown);

            // Generate headings and add IDs
            [$this->content, $this->headings] = $this->processHeadings($this->content);

            $this->currentPage = $this->page;
        } else {
            // Page not found
            abort(404, "Help page '{$this->page}' not found");
        }
    }

    protected function processHeadings(string $content): array
    {
        $dom = new Crawler($content);
        $headings = [];

        $dom->filter('h1, h2, h3, h4')->each(function ($node) use (&$headings) {
            $text = $node->text();
            $slug = Str::slug($text);
            $tag = $node->nodeName();

            // Add slugified id to the dom element
            $node->getNode(0)->setAttribute('id', $slug);

            // add heading to the headings array
            $headings[] = [
                'tag' => $tag,
                'text' => $text,
                'id' => $slug,
            ];
        });

        return [$dom->html(), $headings];
    }

    protected function discoverPages(): void
    {
        if (!empty($this->availablePages)) {
            return;
        }

        $helpPath = resource_path($this->helpDir);

        if (!File::isDirectory($helpPath)) {
            $this->availablePages = collect();
            return;
        }

        $pages = [];

        // Recursively scan all markdown files
        $markdownFiles = File::allFiles($helpPath);

        foreach ($markdownFiles as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $relativePath = $file->getRelativePathname();
            $slug = str_replace('.md', '', $relativePath);
            $slug = str_replace('\\', '/', $slug); // Normalize directory separators

            // Skip if this is the current page to avoid duplication
            if ($slug === $this->currentPage) {
                continue;
            }

            $pages[] = $this->createPageData($file, $slug);
        }

        $this->availablePages = $this->groupAndSortPages($pages);
    }

    protected function createPageData($file, string $slug): array
    {
        // Generate title from filename
        $filename = $file->getFilenameWithoutExtension();
        $title = Str::title(str_replace('-', ' ', $filename));
        $isFeaturePage = $file->getRelativePath() !== '';
        $description = $this->extractPageDescription($file->getPathname());

        // Special handling for index files
        if ($filename === 'index') {
            if ($file->getRelativePath()) {
                // For subdirectory index files, use "Overview" or the directory name + "Overview"
                $directoryName = basename($file->getRelativePath());
                $title = Str::title(str_replace('-', ' ', $directoryName)) . ' Overview';
            } else {
                // For root index file
                $title = 'Starter Kit';
            }
        } elseif ($slug === 'help-docs') {
            $title = 'Help System';
        }

        // Determine folder and category
        $folder = '';
        $category = 'General Documentation';

        if ($file->getRelativePath()) {
            $directory = str_replace('\\', '/', $file->getRelativePath());
            $folder = $directory;
            $category = Str::title(str_replace('-', ' ', str_replace('/', ' / ', $directory)));
        }

        return [
            'slug' => $slug,
            'title' => $title,
            'folder' => $folder,
            'category' => $category,
            'description' => $description,
            'kind' => $isFeaturePage ? 'Feature' : 'Guide',
        ];
    }

    protected function extractPageDescription(string $path): string
    {
        $markdown = trim(File::get($path));
        $paragraphs = preg_split("/\R{2,}/", $markdown) ?: [];

        foreach ($paragraphs as $paragraph) {
            $line = trim($paragraph);

            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '- ') || str_starts_with($line, '```')) {
                continue;
            }

            return Str::limit(preg_replace('/\s+/', ' ', $line) ?? '', 140);
        }

        return 'Open this page for details.';
    }

    protected function groupAndSortPages(array $pages)
    {
        return collect($pages)
            ->groupBy('category')
            ->map(function ($categoryPages) {
                return $categoryPages->sortBy(function ($page) {
                    // Index files (overview pages) come first, then alphabetical by title
                    $isIndex = str_ends_with($page['slug'], '/index') || $page['slug'] === 'index';
                    return ($isIndex ? '0-' : '1-') . $page['title'];
                });
            })
            ->sortKeysUsing(function ($a, $b) {
                // Always put "General Documentation" first
                if ($a === 'General Documentation') return -1;
                if ($b === 'General Documentation') return 1;
                // Sort other categories alphabetically
                return strcmp($a, $b);
            });
    }

    protected function generateBreadcrumbs(): void
    {
        $crumbs = ['Home' => '/', 'Help' => route('help')];

        if ($this->page) {
            // Build nested breadcrumbs for subdirectory pages
            if (str_contains($this->page, '/')) {
                $segments = explode('/', $this->page);
                $path = '';

                foreach ($segments as $index => $segment) {
                    $path .= ($path ? '/' : '') . $segment;
                    $title = Str::title(str_replace('-', ' ', $segment));

                    if ($index === count($segments) - 1) {
                        // Last segment (current page)
                        $crumbs[$title] = null;
                    } else {
                        // Check if intermediate segment has a file or index file
                        $segmentFile = resource_path("views/help/{$path}.md");
                        $segmentIndexFile = resource_path("views/help/{$path}/index.md");

                        if (File::exists($segmentFile) || File::exists($segmentIndexFile)) {
                            // Create link if file exists
                            $crumbs[$title] = route('help', ['page' => $path]);
                        } else {
                            // No link if no file exists
                            $crumbs[$title] = null;
                        }
                    }
                }
            } else {
                $crumbs[Str::title(str_replace('-', ' ', $this->page))] = null;
            }
        } else {
            // For help index page, Help has no link (current page)
            $crumbs['Help'] = null;
        }

        $this->breadcrumbs = $crumbs;
    }

    public function render()
    {
        return view('components.help');
    }
}
