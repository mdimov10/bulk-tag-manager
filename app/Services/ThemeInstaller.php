<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ThemeInstaller
{
    protected $shop;
    protected $themeId;
    protected $themeName;

    public function __construct($shop)
    {
        $this->shop = $shop;
    }

    public function install(): array
    {
        if (! $this->loadMainThemeInfo()) {
            Log::error('Could not find the main theme');
            return ['success' => false, 'updated_files' => 0, 'total_files' => 0];
        }

        try {
            $this->uploadSnippet($this->themeId);
        } catch (\Exception $exception) {
            Log::info('Could not upload snippet: ' . $exception->getMessage());
            return ['success' => false, 'updated_files' => 0, 'total_files' => 0];
        }

        // If already installed on another locale → only update price-eur.liquid
        if (count($this->shop->dualPricingLocales) > 1) {
            return ['success' => true];
        }

        $slug = \Str::slug($this->themeName);
        // Shrine & Shrine Pro Theme
        if (str_contains(strtolower($this->themeName), 'shrine-pro') ||
            str_contains(strtolower($this->themeName), 'shrine_pro') ||
            str_contains(strtolower($this->themeName), 'shrine-theme-pro') ||
            $slug == 'shrine-pro' || $slug == 'shrine-theme-pro') {
            $slug = 'shrine-pro';
        } elseif (str_contains(strtolower($this->themeName), 'shrine') || str_contains(strtolower($this->themeName), 'shrine-theme')) {
            $slug = 'shrine';
        }

        // Impact Theme
        if (str_contains(strtolower($this->themeName), 'impact')) {
            $this->handleImpactThemeInjection();

            return ['success' => true, 'updated_files' => 2, 'total_files' => 2];
        }

        Log::info('Theme installing slug: ' . $slug);
        Log::info('Theme installing name: ' . strtolower($this->themeName));

        $files = config("shopify-themes.{$slug}.files") ?? config("shopify-themes.dawn.files");

        if (! config("shopify-themes.{$slug}")) {
            Log::info("Fallback to Dawn theme config for theme '{$this->themeName}'");
        }

        if (! $files) {
            Log::warning("No dual pricing config found for theme '{$this->themeName}' (slug: {$slug})");
            return ['success' => false, 'updated_files' => 0, 'total_files' => 0];
        }

        $successfulUpdates = 0;

        foreach ($files as $filePath => $injections) {
            $asset = $this->getThemeFileContent($this->themeId, $filePath);
            if (! $asset) continue;

            $updatedContent = $asset;
            $fileWasUpdated = false;

            foreach ($injections as $injection) {
                $target = $injection['target'];
                $insert = $injection['inject'];

                preg_match('/<([\w\-]+)/', $target, $tagMatch);

                $tagName = $tagMatch[1] ?? null;

                if (!$tagName) {
                    Log::warning("Unable to determine tag name from target", ['target' => $target]);
                    continue;
                }

                if (str_contains($updatedContent, $target)) {
                    $updatedContent = preg_replace_callback(
                        '/(' . preg_quote($target, '/') . ')(.*?)(<\/' . $tagName . '>)/s',
                        function ($matches) use ($insert) {
                            return $matches[1] . $matches[2] . "\n" . $insert . $matches[3];
                        },
                        $updatedContent
                    );
                    $fileWasUpdated = true;
                } else {
                    Log::warning("Target string not found in {$filePath}", ['target' => $target]);
                }
            }

            if ($fileWasUpdated && $updatedContent !== $asset) {
                $this->upsertThemeFile($this->themeId, $filePath, $updatedContent);
                $successfulUpdates++;
            }
        }

        if ($successfulUpdates === 0) {
            return ['success' => false, 'updated_files' => 0, 'total_files' => count($files)];
        }

        return ['success' => true, 'updated_files' => $successfulUpdates, 'total_files' => count($files)];
    }

    protected function uploadSnippet($themeId): void
    {
        $baseContent = file_get_contents(resource_path('views/liquid/price-eur.liquid'));

        $locales = $this->shop->dualPricingLocales->pluck('locale')->toArray();
        if (empty($locales)) {
            $locales = ['bg'];
        }
        if (in_array('bg', $locales) && !in_array('bg-BG', $locales)) {
            $locales[] = 'bg-BG';
        }
        if (in_array('bg-BG', $locales) && !in_array('bg', $locales)) {
            $locales[] = 'bg';
        }

        $localesString = implode(',', $locales);

        $expiry = optional($this->shop->expires_at)->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d');

        // --- Inject locale + expiry logic ---
        $conditionalWrapperStart = <<<LIQUID
        {% assign allowed_locales_string = '{$localesString}' %}
        {% assign allowed_locales = allowed_locales_string | split: ',' %}
        {% assign expiry = '{$expiry}' %}
        {% assign today = 'now' | date: '%s' %}
        {% assign expiry_unix = expiry | date: '%s' %}
        {% if allowed_locales contains request.locale.iso_code and today < expiry_unix %}
        LIQUID;

        $conditionalWrapperEnd = "\n{% endif %}";

        $snippetContent = $conditionalWrapperStart . "\n" . $baseContent . $conditionalWrapperEnd;

        $mutation = <<<'GRAPHQL'
        mutation themeFilesUpsert($files: [OnlineStoreThemeFilesUpsertFileInput!]!, $themeId: ID!) {
            themeFilesUpsert(files: $files, themeId: $themeId) {
                upsertedThemeFiles {
                    filename
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

        $variables = [
            'themeId' => $themeId,
            'files' => [
                [
                    'filename' => 'snippets/price-eur.liquid',
                    'body' => [
                        'type' => 'TEXT',
                        'value' => $snippetContent,
                    ],
                ],
            ],
        ];

        $response = $this->shop->api()->graph($mutation, $variables);

        Log::info('Upload response', ['upload' => $response]);
    }


    protected function getThemeFileContent($themeId, $filePath)
    {
        $query = <<<'GRAPHQL'
        query GetThemeFileContent($themeId: ID!, $filenames: [String!]!) {
            theme(id: $themeId) {
                files(filenames: $filenames) {
                    nodes {
                        filename
                        body {
                            ... on OnlineStoreThemeFileBodyText {
                                content
                            }
                            ... on OnlineStoreThemeFileBodyBase64 {
                                contentBase64
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

        $variables = [
            'themeId' => $themeId,
            'filenames' => [$filePath],
        ];

        $response = $this->shop->api()->graph($query, $variables);

        $nodes = $response['body']['data']['theme']['files']['nodes'] ?? [];

        if (count($nodes) > 0 && isset($nodes[0]['body']['content'])) {
            return $nodes[0]['body']['content'];
        }

        return null;
    }


    protected function upsertThemeFile($themeId, $filename, $newContent): void
    {
        $mutation = <<<'GRAPHQL'
        mutation themeFilesUpsert($files: [OnlineStoreThemeFilesUpsertFileInput!]!, $themeId: ID!) {
            themeFilesUpsert(files: $files, themeId: $themeId) {
                upsertedThemeFiles {
                    filename
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

        $variables = [
            'themeId' => $themeId,
            'files' => [
                [
                    'filename' => $filename,
                    'body' => [
                        'type' => 'TEXT',
                        'value' => $newContent,
                    ],
                ],
            ],
        ];

        $response = $this->shop->api()->graph($mutation, $variables);

        Log::info('Upsert Theme File Response:', ['response' => $response]);
    }

    protected function loadMainThemeInfo(): bool
    {
        $query = <<<'GRAPHQL'
        {
          themes(first: 1, roles: [MAIN]) {
            edges {
              node {
                id
                name
              }
            }
          }
        }
        GRAPHQL;

        $response = $this->shop->api()->graph($query);
        $node = $response['body']['data']['themes']['edges'][0]['node'] ?? null;

        if (! $node) {
            return false;
        }

        $this->themeId = $node['id'];
        $this->themeName = $node['name'];

        return true;
    }

    public function remove()
    {
        if (! $this->loadMainThemeInfo()) {
            Log::error('Could not find the main theme for removal');
            return false;
        }

        if (str_contains(strtolower($this->themeName), 'impact')) {
            $this->removeImpactThemeInjection();

            return true;
        }

        $slug = \Str::slug($this->themeName);
        if (str_contains(strtolower($this->themeName), 'shrine-pro') ||
            str_contains(strtolower($this->themeName), 'shrine_pro') ||
            str_contains(strtolower($this->themeName), 'shrine-theme-pro') ||
            $slug == 'shrine-pro' || $slug == 'shrine-theme-pro') {
            $slug = 'shrine-pro';
        } elseif (str_contains(strtolower($this->themeName), 'shrine') || str_contains(strtolower($this->themeName), 'shrine-theme')) {
            $slug = 'shrine';
        }

        Log::info('Removing theme', ['slug' => $slug]);

        $files = config("shopify-themes.{$slug}.files") ?? config("shopify-themes.dawn.files");

        if (! $files) {
            Log::warning("No dual pricing config found for theme '{$this->themeName}' during removal");
            return false;
        }

        foreach ($files as $filePath => $injections) {
            $asset = $this->getThemeFileContent($this->themeId, $filePath);
            if (! $asset) continue;

            $updatedContent = $asset;

            foreach ($injections as $injection) {
                $insert = trim($injection['inject']);

                if (str_contains($updatedContent, $insert)) {
                    // Remove all occurrences of the exact injection code
                    $updatedContent = str_replace($insert, '', $updatedContent);
                    Log::info("Removed injection from {$filePath}", ['insert' => $insert]);
                }
            }

            if ($updatedContent !== $asset) {
                $this->upsertThemeFile($this->themeId, $filePath, $updatedContent);
            }
        }

        // Always regenerate the snippet with the updated list of locales (could be empty)
        try {
            $this->uploadSnippet($this->themeId);
        } catch (\Exception $exception) {
            Log::info('Could not update snippet during removal: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    public function refreshExpireDate(): bool
    {
        if (! $this->loadMainThemeInfo()) {
            Log::error('Could not find the main theme for refreshExpireDate');
            return false;
        }

        $filePath = 'snippets/price-eur.liquid';
        $content = $this->getThemeFileContent($this->themeId, $filePath);

        if (! $content) {
            Log::warning("No content found in {$filePath}.");
            return false;
        }

        $newDate = now()->addDays(30)->toDateString();

        $updatedContent = preg_replace(
            "/{% assign expiry = '(\d{4}-\d{2}-\d{2})' %}/",
            "{% assign expiry = '{$newDate}' %}",
            $content
        );

        if (! $updatedContent || $updatedContent === $content) {
            Log::info("No update needed for expiry in {$filePath}.");
            return false;
        }

        $this->upsertThemeFile($this->themeId, $filePath, $updatedContent);

        Log::info("✅ Expiry date refreshed in {$filePath} for shop {$this->shop->name}");

        return true;
    }

    protected function handleImpactThemeInjection(): void
    {
        $files = [
            'snippets/price-list.liquid' => [
                ['pattern' => '/({{\-?\s*variant\.price\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: variant.price %}"],
                ['pattern' => '/({{\-?\s*variant\.price\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: variant.price %}"],
                ['pattern' => '/({{\-?\s*product\.price\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: product.price %}"],
                ['pattern' => '/({{\-?\s*product\.price\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: product.price %}"],
                ['pattern' => '/({{\-?\s*product\.price_min\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: product.price_min %}"],
                ['pattern' => '/({{\-?\s*product\.price_min\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: product.price_min %}"],
                ['pattern' => '/({{\-?\s*line_item\.final_line_price\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: line_item.final_line_price %}"],
                ['pattern' => '/({{\-?\s*line_item\.final_line_price\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: line_item.final_line_price %}"],
                ['pattern' => '/({{\-?\s*line_item\.original_line_price\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: line_item.original_line_price %}"],
                ['pattern' => '/({{\-?\s*line_item\.original_line_price\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: line_item.original_line_price %}"],
            ],
            'sections/cart-drawer.liquid' => [
                ['pattern' => '/({{\-?\s*cart\.total_price\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: cart.total_price %}"],
            ],
            'sections/main-cart.liquid' => [
                ['pattern' => '/({{\-?\s*cart\.items_subtotal_price\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: cart.items_subtotal_price %}"],
                ['pattern' => '/({{\-?\s*cart\.total_price\s*\|\s*money_with_currency\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: cart.total_price %}"],
                ['pattern' => '/({{\-?\s*line_item\.final_line_price\s*\|\s*money\s*\-?}})/', 'replace' => "$1 {% render 'price-eur', price: line_item.final_line_price %}"],
            ],
        ];

        foreach ($files as $filePath => $rules) {
            $content = $this->getThemeFileContent($this->themeId, $filePath);
            if (!$content) continue;

            foreach ($rules as $rule) {
                // Prevent double-injection
                if (!str_contains($content, $rule['replace'])) {
                    $content = preg_replace($rule['pattern'], $rule['replace'], $content);
                }
            }

            $this->upsertThemeFile($this->themeId, $filePath, $content);
        }
    }

    protected function removeImpactThemeInjection(): void
    {
        $files = [
            'snippets/price-list.liquid' => [
                '/({{\-?\s*variant\.price\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: variant.price %}/' => '$1',
                '/({{\-?\s*variant\.price\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: variant.price %}/' => '$1',
                '/({{\-?\s*product\.price\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: product.price %}/' => '$1',
                '/({{\-?\s*product\.price\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: product.price %}/' => '$1',
                '/({{\-?\s*product\.price_min\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: product.price_min %}/' => '$1',
                '/({{\-?\s*product\.price_min\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: product.price_min %}/' => '$1',
                '/({{\-?\s*line_item\.final_line_price\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: line_item.final_line_price %}/' => '$1',
                '/({{\-?\s*line_item\.final_line_price\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: line_item.final_line_price %}/' => '$1',
                '/({{\-?\s*line_item\.original_line_price\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: line_item.original_line_price %}/' => '$1',
                '/({{\-?\s*line_item\.original_line_price\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: line_item.original_line_price %}/' => '$1',
            ],
            'sections/cart-drawer.liquid' => [
                '/({{\-?\s*cart\.total_price\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: cart.total_price %}/' => '$1',
            ],
            'sections/main-cart.liquid' => [
                '/({{\-?\s*cart\.items_subtotal_price\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: cart.items_subtotal_price %}/' => '$1',
                '/({{\-?\s*cart\.total_price\s*\|\s*money_with_currency\s*\-?}})\s*{% render \'price-eur\', price: cart.total_price %}/' => '$1',
                '/({{\-?\s*line_item\.final_line_price\s*\|\s*money\s*\-?}})\s*{% render \'price-eur\', price: line_item.final_line_price %}/' => '$1',
            ],
        ];

        foreach ($files as $filePath => $patterns) {
            $content = $this->getThemeFileContent($this->themeId, $filePath);
            if (!$content) continue;

            $updatedContent = $content;

            foreach ($patterns as $pattern => $replacement) {
                $updatedContent = preg_replace($pattern, $replacement, $updatedContent);
            }

            if ($updatedContent !== $content) {
                $this->upsertThemeFile($this->themeId, $filePath, $updatedContent);
                Log::info("Removed EUR injections from Impact theme file: {$filePath}");
            }
        }
    }
}
