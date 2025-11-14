<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Get all supported languages
     */
    public function index(): JsonResponse
    {
        $languages = Config::get('languages.supported', []);
        $currentLocale = App::getLocale();

        $formattedLanguages = [];
        foreach ($languages as $code => $name) {
            $formattedLanguages[] = [
                'code' => $code,
                'name' => $name,
                'native_name' => $this->getNativeLanguageName($code),
                'is_current' => $code === $currentLocale,
                'is_rtl' => in_array($code, Config::get('languages.rtl', [])),
                'currency' => Config::get("languages.currency_mapping.{$code}", 'USD'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'languages' => $formattedLanguages,
                'current_locale' => $currentLocale,
                'fallback_locale' => Config::get('languages.fallback', 'en'),
            ],
        ]);
    }

    /**
     * Switch to a different language
     */
    public function switch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:' . implode(',', array_keys(Config::get('languages.supported', []))),
        ]);

        $locale = $validated['locale'];
        App::setLocale($locale);
        Session::put('locale', $locale);

        return response()->json([
            'success' => true,
            'message' => __('Language switched successfully'),
            'data' => [
                'locale' => $locale,
                'language_name' => Config::get("languages.supported.{$locale}", $locale),
                'currency' => Config::get("languages.currency_mapping.{$locale}", 'USD'),
            ],
        ]);
    }

    /**
     * Get current language information
     */
    public function current(): JsonResponse
    {
        $currentLocale = App::getLocale();
        $supportedLanguages = Config::get('languages.supported', []);

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $currentLocale,
                'language_name' => $supportedLanguages[$currentLocale] ?? 'Unknown',
                'native_name' => $this->getNativeLanguageName($currentLocale),
                'is_rtl' => in_array($currentLocale, Config::get('languages.rtl', [])),
                'currency' => Config::get("languages.currency_mapping.{$currentLocale}", 'USD'),
            ],
        ]);
    }

    /**
     * Get translations for JavaScript
     */
    public function translations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:' . implode(',', array_keys(Config::get('languages.supported', []))),
        ]);

        $locale = $validated['locale'];

        // Load translation files
        $translations = [
            // Common translations
            'common' => $this->getTranslationsForFile('common', $locale),
            'validation' => $this->getTranslationsForFile('validation', $locale),
            'auth' => $this->getTranslationsForFile('auth', $locale),
            'ecommerce' => $this->getTranslationsForFile('ecommerce', $locale),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $locale,
                'translations' => $translations,
            ],
        ]);
    }

    /**
     * Get native language name
     */
    private function getNativeLanguageName(string $code): string
    {
        $nativeNames = [
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ru' => 'Русский',
            'zh' => '中文',
            'ja' => '日本語',
            'ar' => 'العربية',
        ];

        return $nativeNames[$code] ?? $code;
    }

    /**
     * Get translations for a specific file
     */
    private function getTranslationsForFile(string $file, string $locale): array
    {
        $path = resource_path("lang/{$locale}/{$file}.php");

        if (!file_exists($path)) {
            // Fallback to English
            $path = resource_path("lang/en/{$file}.php");
        }

        if (file_exists($path)) {
            return require $path;
        }

        return [];
    }
}
