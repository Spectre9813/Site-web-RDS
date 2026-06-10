<?php
// .back/util/TwigFactory.php

declare(strict_types=1);

use App\Util;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

class TwigFactory
{
    /** @var Environment|null */
    private static ?Environment $twig = null;

    public static function getTwig(): Environment
    {
        if (self::$twig === null) {
            $templateDir = __DIR__ . '/../templates';

            if (!is_dir($templateDir)) {
                die("FATAL: Twig templates directory not found at: " . realpath(__DIR__ . '/..') . "/templates.");
            }

            $loader = new FilesystemLoader($templateDir);

            self::$twig = new Environment($loader, [
                'cache' => APP_ENV === 'production' ? __DIR__ . '/../cache' : false,
                'debug' => APP_ENV !== 'production',
                'auto_reload' => true,
                'strict_variables' => true,
            ]);

            self::$twig->addExtension(new DebugExtension());

            // ? Each global registered exactly once, using constants from config.php
            self::$twig->addGlobal('cdn_url', CDN_URL);
            self::$twig->addGlobal('site_url', defined('SITE_URL') ? SITE_URL : 'https://prod.stageflow.fr');
            self::$twig->addGlobal('session', $_SESSION);

            // ? Single source of truth for user ? always from Util, not raw $_SESSION
            self::$twig->addGlobal('user', Util::getUser() ?? null);

            // Token CSRF disponible globalement (deux alias pour cohErence avec les templates existants)
            $csrfToken = Util::getCSRFToken();
            self::$twig->addGlobal('csrf_token', $csrfToken);
            self::$twig->addGlobal('csrf', $csrfToken);
        }

        return self::$twig;
    }

    /**
     * Helper method to render a template directly
     */
    public static function render(string $template, array $data = []): void
    {
        echo self::getTwig()->render($template, $data);
    }
}
