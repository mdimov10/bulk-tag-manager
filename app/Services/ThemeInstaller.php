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

                preg_match('/<(\w+)/', $target, $tagMatch);
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

        $slug = \Str::slug($this->themeName);
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

    protected function injectScriptIntoLayout($themeId)
    {
        if (! $this->shop->show_checkout_notice) return;

        $filePath = 'layout/theme.liquid';
        $themeContent = $this->getThemeFileContent($themeId, $filePath);

        if (! $themeContent) {
            Log::warning("Layout file '{$filePath}' not found.");
            return;
        }

        $scriptTag = <<<LIQUID
        {% if request.path contains '/checkouts' %}
          <script src="{{ 'checkout-eur-notice.js' | asset_url }}" defer></script>
        {% endif %}

        LIQUID;

        if (str_contains($themeContent, $scriptTag)) {
            Log::info("Script already injected in {$filePath}");
            return;
        }

        $updatedContent = str_replace('</body>', $scriptTag . "\n</body>", $themeContent);

        if ($updatedContent === $themeContent) {
            Log::warning("Failed to inject script — </body> not found in {$filePath}");
            return;
        }

        $this->upsertThemeFile($themeId, $filePath, $updatedContent);
    }

    private function registerHook() {
        $callbackUrl = config('app.url') . '/webhook/app-uninstalled';

        $mutation = <<<GRAPHQL
        mutation webhookCreate(\$callbackUrl: URL!) {
          webhookSubscriptionCreate(
            topic: APP_UNINSTALLED,
            webhookSubscription: {
              callbackUrl: \$callbackUrl,
              format: JSON
            }
          ) {
            webhookSubscription {
              id
            }
            userErrors {
              field
              message
            }
          }
        }
        GRAPHQL;

        $variables = ['callbackUrl' => $callbackUrl];

        $response = $this->shop->api()->graph($mutation, $variables);
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
}
