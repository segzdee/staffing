<?php

namespace App\Http\Controllers\Concerns;

/**
 * Trait for standardizing flash message keys across controllers.
 * 
 * Standard keys:
 * - 'success' for successful operations
 * - 'error' for errors
 * - 'warning' for warnings
 * - 'info' for informational messages
 */
trait StandardizedFlashMessages
{
    /**
     * Flash a success message.
     */
    protected function flashSuccess(string $message)
    {
        return redirect()->back()->with('success', $message);
    }

    /**
     * Flash an error message.
     */
    protected function flashError(string $message)
    {
        return redirect()->back()->with('error', $message);
    }

    /**
     * Flash a warning message.
     */
    protected function flashWarning(string $message)
    {
        return redirect()->back()->with('warning', $message);
    }

    /**
     * Flash an info message.
     */
    protected function flashInfo(string $message)
    {
        return redirect()->back()->with('info', $message);
    }

    /**
     * Flash success and redirect to a specific route.
     */
    protected function redirectWithSuccess(string $route, string $message, ...$parameters)
    {
        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Flash error and redirect to a specific route.
     */
    protected function redirectWithError(string $route, string $message, ...$parameters)
    {
        return redirect()->route($route, $parameters)->with('error', $message);
    }
}

