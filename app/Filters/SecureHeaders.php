<?php

namespace App\Filters;

use CodeIgniter\Filters\SecureHeaders as BaseSecureHeaders;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Applies default security headers without weakening headers set by a controller.
 */
class SecureHeaders extends BaseSecureHeaders
{
    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        foreach ($this->headers as $header => $value) {
            if (! $response->hasHeader($header)) {
                $response->setHeader($header, $value);
            }
        }

        return $response;
    }
}
