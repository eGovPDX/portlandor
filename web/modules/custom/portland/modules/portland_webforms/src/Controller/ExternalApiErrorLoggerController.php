<?php

namespace Drupal\portland_webforms\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

class ExternalApiErrorLoggerController extends ControllerBase
{
    public function log(Request $request)
    {
        $allowed_tlds = ['portland.gov', 'lndo.site'];

        $origin = $request->headers->get('Origin') ?? $request->headers->get('Referer');
        $origin_host = parse_url($origin, PHP_URL_HOST);

        // Check if the host ends with any allowed TLD
        $is_allowed = FALSE;
        foreach ($allowed_tlds as $tld) {
            if ($origin_host && str_ends_with($origin_host, $tld)) {
                $is_allowed = TRUE;
                break;
            }
        }

        if (!$is_allowed) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), TRUE);
        if (!empty($data['message'])) {
            \Drupal::logger('external_api')->error('API Error: @message in @file at line @line. Stack: @stack', [
                '@message' => $data['message'],
                '@file' => $data['file'] ?? 'unknown',
                '@line' => $data['line'] ?? 'n/a',
                '@stack' => $data['stack'] ?? '',
            ]);
        }

        return new JsonResponse(['status' => 'logged']);
    }

    /**
     * Simulated 429 response for testing. Use path /log-client-error/test-429.
     */
    public function http429()
    {
        return new Response('Simulated 429 Too Many Requests error', 429);
    }
}
