<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Priority order: URL parameter > Session > Cookie > Browser > Default
        $locale = null;

        // 1. Check URL parameter
        if ($request->has('lang')) {
            $locale = $request->get('lang');
        }

        // 2. Check session
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
        }

        // 3. Check cookie
        if (!$locale && $request->cookie('locale')) {
            $locale = $request->cookie('locale');
        }

        // 4. Auto-detect from browser if enabled
        if (!$locale && Config::get('languages.auto_detect', true)) {
            $locale = $this->detectBrowserLanguage($request);
        }

        // 5. Use default
        if (!$locale) {
            $locale = Config::get('languages.default', 'en');
        }

        // Validate locale is supported
        $supportedLocales = array_keys(Config::get('languages.supported', []));
        if (!in_array($locale, $supportedLocales)) {
            $locale = Config::get('languages.fallback', 'en');
        }

        // Set the locale
        App::setLocale($locale);

        // Store in session for future requests
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Detect browser language from Accept-Language header
     */
    private function detectBrowserLanguage(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', $lang);
            $languages[] = trim($parts[0]);
        }

        $supportedLocales = array_keys(Config::get('languages.supported', []));

        foreach ($languages as $lang) {
            // Check exact match
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }

            // Check language part only (e.g., 'en' from 'en-US')
            $langPart = substr($lang, 0, 2);
            if (in_array($langPart, $supportedLocales)) {
                return $langPart;
            }
        }

        return null;
    }
}
