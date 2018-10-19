<?php
namespace Statamic\View\Antlers;

use Statamic\API\Path;
use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\API\Config;

class Directive
{
    /**
     * Handle an Antlers directive callback
     */
    public static function handle($method, $expression, $data)
    {
        $arguments = self::parseExpression($expression);

        return self::$method($arguments, $data);
    }

    /**
     * Parse an expression and turn an array of argument strings
     */
    protected static function parseExpression($expression)
    {
        $arguments = explode(',', $expression);

        foreach ($arguments as $key => $arg) {
            $arguments[$key] = substr(trim($arg), 1, -1);
        }

        return $arguments;
    }

    /**
     * Include another view
     *
     * @TODO This whole method is hijacked from the PartialTags method.
     * Too much duplictation of logic.
     */
    public static function include(array $views, $data)
    {
        $src = $views[0] ?? false;

        $partialPath = config('statamic.theming.dedicated_view_directories')
            ? resource_path("partials/{$src}.antlers")
            : resource_path("views/{$src}.antlers");

        if (! $partial = File::get($partialPath.'.html')) {
            if ($partial = File::get($partialPath.'.php')) {
                $php = true;
            }
        }

        // Allow front matter in these suckers
        $parsed = Parse::frontMatter($partial);
        $variables = array_get($parsed, 'data', []);
        $template = array_get($parsed, 'content');

        $variables = array_merge($data, $variables);

        return Parse::template($template, $variables, [], $php ?? false);
    }
}
