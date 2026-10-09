<?php
// path: core/View.php

namespace App\Core;

class View
{
    /**
     * Render a view inside a parent layout (default 'layouts/admin').
     */
    public static function render(string $viewPath, array $data = [], ?string $layout = 'layouts/admin'): void
    {
        extract($data);

        $baseDir  = dirname(__DIR__) . '/app/Views/';
        $viewFile = $baseDir . ltrim($viewPath, '/') . '.php';

        // Check view file existence with lowercase fallback for Linux compatibility
        if (!file_exists($viewFile)) {
            $altView = dirname(__DIR__) . '/app/views/' . ltrim($viewPath, '/') . '.php';
            if (file_exists($altView)) {
                $viewFile = $altView;
            } else {
                throw new \Exception("View [{$viewPath}] not found at {$viewFile}");
            }
        }

        // Capture view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render inside layout if specified
        if ($layout) {
            $normalizedLayout = ltrim($layout, '/');
            $candidates = [
                $baseDir . $normalizedLayout . '.php',
                $baseDir . str_replace('layouts/', 'layout/', $normalizedLayout) . '.php',
                $baseDir . str_replace('layout/', 'layouts/', $normalizedLayout) . '.php',
                dirname(__DIR__) . '/app/views/' . $normalizedLayout . '.php',
                dirname(__DIR__) . '/app/views/' . str_replace('layouts/', 'layout/', $normalizedLayout) . '.php',
                $baseDir . 'layouts/' . basename($normalizedLayout) . '.php',
                $baseDir . 'layout/' . basename($normalizedLayout) . '.php',
            ];

            foreach ($candidates as $cand) {
                if (file_exists($cand)) {
                    require $cand;
                    return;
                }
            }
        }

        echo $content;
    }
}
