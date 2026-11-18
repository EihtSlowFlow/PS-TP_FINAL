<?php

namespace App\Controllers;

use App\Models\PropertyImageModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves the photos of the properties from the private storage.
 *
 * A photo of a property everybody can see (available, approved, of an active owner: the public catalog rule) is
 * served to anyone and may be cached. Any other photo (a property still pending, rejected, rented or taken down) is
 * only served to its owner and to the administration; for everybody else it does not exist (404).
 */
class PropertyImageController extends BaseController
{
    public function show(int $imageId): ResponseInterface
    {
        $image = model(PropertyImageModel::class)->findForAccess($imageId);

        if ($image === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $isPublic = PropertyImageModel::isPublic($image);

        if (! $isPublic && ! $this->canSeePrivatePhoto((int) $image['owner_id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path     = service('propertyImageStorage')->path((string) $image['stored_name']);
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', (string) $image['mime_type'])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'inline')
            ->setHeader('Cache-Control', $isPublic ? 'public, max-age=86400' : 'private, no-store')
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setBody($contents);
    }

    private function canSeePrivatePhoto(int $ownerId): bool
    {
        $session = session();

        if (! $session->get('is_logged_in')) {
            return false;
        }

        $role = (string) $session->get('user_role');

        return in_array($role, [UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE], true)
            || ($role === UserModel::ROLE_OWNER && (int) $session->get('user_id') === $ownerId);
    }
}
