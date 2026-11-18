<?php

use App\Controllers\PropertyController;
use App\Database\Seeds\RoleSeeder;
use App\Libraries\PropertyImageProcessor;
use App\Models\PropertyImageModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Libraries\PropertyReviewFixtures;
use Tests\Support\Libraries\TestPropertyImageStorage;

/**
 * Photos of the properties: the owner (and the administration) load them from the property form, they are re-encoded
 * and kept outside public/, and they are shown on the landing, the catalog and the sheet once the property is public.
 *
 * @internal
 */
final class PropertyImagesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PropertyReviewFixtures;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private TestPropertyImageStorage $storage;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION              = [];
        $this->fixtureSequence = 0;
        $this->tempFiles       = [];
        $this->storage         = new TestPropertyImageStorage();
        $this->storage->clear();
        Services::injectMock('propertyImageStorage', $this->storage);
        $this->attachImages([]);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('propertyImageStorage');
        $this->storage->clear();
        $this->attachImages([]);
        $_SESSION = [];

        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function testOwnerPhotosStayPrivateUntilTheAdministrationApprovesTheProperty(): void
    {
        $ownerId = $this->makeUser('owner');
        $otherId = $this->makeUser('owner');
        $adminId = $this->makeUser('admin');

        $this->attachImages([$this->makeJpeg(2400, 1200, 'living.jpg'), $this->makeJpeg(800, 600, 'cocina.jpg')]);
        $this->postWithSession($this->sessionOf($ownerId, 'owner'), 'propietario/inmuebles', $this->validPropertyPayload())
            ->assertRedirectTo('/propietario/inmuebles');
        $this->attachImages([]);

        $propertyId = (int) $this->db->table('properties')->get()->getRowArray()['id'];
        $images     = model(PropertyImageModel::class)->forProperty($propertyId);

        $this->assertCount(2, $images);
        $this->assertSame(['living.jpg', 'cocina.jpg'], array_column($images, 'original_name'));
        $this->assertSame([0, 1], array_map('intval', array_column($images, 'position')));

        // Stored re-encoded as JPEG, outside public/, at most 1600 px per side.
        $stored = $this->db->table('property_images')->where('id', $images[0]['id'])->get()->getRowArray();
        $path   = $this->storage->path((string) $stored['stored_name']);
        $this->assertFileExists($path);
        $this->assertSame('image/jpeg', $stored['mime_type']);
        $this->assertSame([1600, 800], array_slice(getimagesize($path) ?: [], 0, 2));
        $this->assertStringNotContainsString('public', $this->storage->directory());

        // Pending: only its owner and the administration see the photo.
        $photo = 'inmuebles/fotos/' . $images[0]['id'];
        $this->assertAnswers404(fn () => $this->visitorGet($photo), 'A visitor must not see the photo of a pending property');
        $this->assertAnswers404(fn () => $this->getAs($otherId, 'owner', $photo), 'Another owner must not see it');

        $own = $this->getAs($ownerId, 'owner', $photo);
        $own->assertStatus(200);
        $own->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('private', $own->response()->getHeaderLine('Cache-Control'));
        $this->getAs($adminId, 'admin', $photo)->assertStatus(200);

        // Approved: everybody sees it, and it appears on the landing, the catalog and the sheet.
        $this->db->table('properties')->where('id', $propertyId)->update(['approval_status' => 'approved']);

        $public = $this->visitorGet($photo);
        $public->assertStatus(200);
        $this->assertStringContainsString('public', $public->response()->getHeaderLine('Cache-Control'));

        $coverSrc = 'src="' . site_url($photo) . '"';
        $this->visitorGet('/')->assertSee($coverSrc);
        $this->visitorGet('catalogo-inmuebles')->assertSee($coverSrc);

        $sheet = $this->visitorGet('catalogo-inmuebles/' . $propertyId);
        $sheet->assertSee('class="property-gallery count-2"');
        $sheet->assertSee($coverSrc);
        $sheet->assertSee('src="' . site_url('inmuebles/fotos/' . $images[1]['id']) . '"');
    }

    public function testAnInvalidPhotoRejectsTheWholeFormAndNothingIsSaved(): void
    {
        $session = $this->sessionOf($this->makeUser('owner'), 'owner');

        $this->attachImages([$this->makeJpeg(400, 300, 'buena.jpg'), $this->makeFile('no es una imagen', 'trucho.jpg')]);
        $this->postWithSession($session, 'propietario/inmuebles', $this->validPropertyPayload())
            ->assertRedirectTo('/propietario/inmuebles/nuevo');

        $this->assertStringContainsString('«trucho.jpg» no es una foto válida', (string) (session()->getFlashdata('errors')['images'] ?? ''));
        $this->assertSame(0, $this->db->table('properties')->countAllResults());
        $this->assertSame(0, $this->db->table('property_images')->countAllResults());
        $this->assertSame([], glob($this->storage->directory() . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    public function testAPropertyCannotHaveMoreThanEightPhotos(): void
    {
        $session = $this->sessionOf($this->makeUser('owner'), 'owner');
        $files   = [];

        for ($i = 1; $i <= PropertyImageProcessor::MAX_IMAGES_PER_PROPERTY + 1; $i++) {
            $files[] = $this->makeJpeg(200, 150, "foto{$i}.jpg");
        }

        $this->attachImages($files);
        $this->postWithSession($session, 'propietario/inmuebles', $this->validPropertyPayload())
            ->assertRedirectTo('/propietario/inmuebles/nuevo');

        $this->assertSame(PropertyImageProcessor::TOO_MANY_MESSAGE, session()->getFlashdata('errors')['images'] ?? null);
        $this->assertSame(0, $this->db->table('properties')->countAllResults());
    }

    public function testTheAdministrationRemovesAndAddsPhotosOnlyOfTheEditedProperty(): void
    {
        $adminId  = $this->makeUser('admin');
        $ownerId  = $this->makeUser('owner');
        $edited   = $this->makePropertyIn('approved', $ownerId, $adminId);
        $other    = $this->makePropertyIn('approved', $ownerId, $adminId, ['address' => 'Otra 1']);
        $keep     = $this->storeImage($edited, 0);
        $drop     = $this->storeImage($edited, 1);
        $foreign  = $this->storeImage($other, 0);
        $dropName = (string) $this->db->table('property_images')->where('id', $drop)->get()->getRowArray()['stored_name'];

        $this->attachImages([$this->makeJpeg(300, 200, 'nueva.jpg')]);
        $this->postWithSession($this->sessionOf($adminId, 'admin'), 'admin/inmuebles/editar/' . $edited, $this->validPropertyPayload([
            'owner_id'      => (string) $ownerId,
            'status'        => 'available',
            'remove_images' => [(string) $drop, (string) $foreign],
        ]))->assertRedirectTo('/admin/inmuebles');

        $this->assertSame(PropertyController::PROPERTY_UPDATED_MESSAGE, session()->getFlashdata('success'));

        $images = model(PropertyImageModel::class)->forProperty($edited);
        $this->assertSame($keep, (int) $images[0]['id']);
        $this->assertSame(['nueva.jpg'], [$images[1]['original_name']]);
        $this->assertGreaterThan((int) $images[0]['position'], (int) $images[1]['position'], 'A new photo goes after the existing ones');
        $this->assertCount(2, $images);
        $this->assertFileDoesNotExist($this->storage->path($dropName));

        $this->assertCount(1, model(PropertyImageModel::class)->forProperty($other), 'Photos of another property are not touched');
    }

    public function testPhotosOfPropertiesOutsideTheCatalogAreNotPublic(): void
    {
        $adminId = $this->makeUser('admin');
        $ownerId = $this->makeUser('owner');

        $cases = [
            'alquilado'     => $this->makePropertyIn('approved', $ownerId, $adminId, ['status' => 'rented']),
            'rechazado'     => $this->makePropertyIn('rejected', $ownerId, $adminId, ['rejection_reason' => 'Faltan datos']),
            'dado de baja'  => $this->makePropertyIn('approved', $ownerId, $adminId, ['deleted_at' => '2026-10-01 10:00:00']),
            'dueño de baja' => $this->makePropertyIn('approved', $this->makeUser('owner', '2026-10-01 10:00:00'), $adminId),
        ];

        foreach ($cases as $label => $propertyId) {
            $imageId = $this->storeImage($propertyId, 0);
            $this->assertAnswers404(fn () => $this->visitorGet('inmuebles/fotos/' . $imageId), "Photo of a property {$label}");
        }

        $this->assertAnswers404(fn () => $this->visitorGet('inmuebles/fotos/999999'), 'Unknown photo');
    }

    public function testACardWithoutPhotosShowsThePlaceholderAndAPngBecomesAJpegOnWhite(): void
    {
        $adminId = $this->makeUser('admin');
        $this->makePropertyIn('approved', $this->makeUser('owner'), $adminId, ['address' => 'Sin Fotos 10']);

        $landing = $this->visitorGet('/');
        $landing->assertSee('Sin Fotos 10');
        $landing->assertSee('Sin fotos todavía');

        // A transparent PNG is laid on white instead of turning black.
        $png = imagecreatetruecolor(40, 40);
        imagesavealpha($png, true);
        imagefill($png, 0, 0, (int) imagecolorallocatealpha($png, 0, 0, 0, 127));
        $path = $this->tempPath('transparente.png');
        imagepng($png, $path);

        $prepared = PropertyImageProcessor::prepare($path, 'image/png');
        $this->assertNotNull($prepared);
        $this->assertSame('image/jpeg', $prepared['mime_type']);

        $jpeg  = imagecreatefromstring($prepared['contents']);
        $color = imagecolorsforindex($jpeg, imagecolorat($jpeg, 5, 5));
        $this->assertGreaterThan(240, $color['red']);
        $this->assertGreaterThan(240, $color['green']);
        $this->assertGreaterThan(240, $color['blue']);
    }

    /**
     * Puts the given temporary files in `$_FILES['images']`, as a multiple file input sends them.
     *
     * @param list<array{path: string, name: string, type: string}> $files
     */
    private function attachImages(array $files): void
    {
        $_FILES = $files === [] ? [] : ['images' => [
            'name'     => array_column($files, 'name'),
            'type'     => array_column($files, 'type'),
            'tmp_name' => array_column($files, 'path'),
            'error'    => array_fill(0, count($files), UPLOAD_ERR_OK),
            'size'     => array_map(static fn (array $file): int => (int) filesize($file['path']), $files),
        ]];
        service('superglobals')->setFilesArray($_FILES);
    }

    /**
     * @return array{path: string, name: string, type: string}
     */
    private function makeJpeg(int $width, int $height, string $name): array
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 120, 160, 200));
        $path = $this->tempPath($name);
        imagejpeg($image, $path, 85);

        return ['path' => $path, 'name' => $name, 'type' => 'image/jpeg'];
    }

    /**
     * @return array{path: string, name: string, type: string}
     */
    private function makeFile(string $contents, string $name): array
    {
        $path = $this->tempPath($name);
        file_put_contents($path, $contents);

        return ['path' => $path, 'name' => $name, 'type' => 'image/jpeg'];
    }

    private function tempPath(string $name): string
    {
        $path              = tempnam(sys_get_temp_dir(), 'hu-img-') . '-' . $name;
        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * Stores a photo straight in the storage and the table (bypassing the form), returning its id.
     */
    private function storeImage(int $propertyId, int $position): int
    {
        $image = imagecreatetruecolor(64, 48);
        ob_start();
        imagejpeg($image);
        $contents = (string) ob_get_clean();

        $storedName = PropertyImageProcessor::generateStoredName('jpg');
        $this->storage->write($storedName, $contents);

        $this->db->table('property_images')->insert([
            'property_id'   => $propertyId,
            'original_name' => "foto{$position}.jpg",
            'stored_name'   => $storedName,
            'mime_type'     => 'image/jpeg',
            'size_bytes'    => strlen($contents),
            'position'      => $position,
            'created_at'    => '2026-11-01 10:00:00',
            'updated_at'    => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function visitorGet(string $path): TestResponse
    {
        $_SESSION = [];

        return $this->withSession([])->get($path);
    }

    private function getAs(int $userId, string $role, string $path): TestResponse
    {
        $_SESSION = [];

        return $this->withSession($this->sessionOf($userId, $role))->get($path);
    }
}
