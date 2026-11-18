<?php
/**
 * "Fotos" card shared by the administration form (HU-07) and the owner form (HU-32): the photos already loaded, with a
 * box to remove each one, and the field to add new ones. The first photo is the cover shown on the landing and the
 * catalog. public/js/property-images.js previews the chosen photos and shrinks the big ones before sending them.
 *
 * @var string                          $mode   'create' or 'edit'
 * @var list<array<string, mixed>>|null $images Photos already loaded (edit only)
 */
use App\Libraries\PropertyImageProcessor;

$images = $images ?? [];
$errors = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$maxImages  = PropertyImageProcessor::MAX_IMAGES_PER_PROPERTY;
$freeSlots  = max(0, $maxImages - count($images));
$hadErrors  = $errors !== [];
?>
                    <section class="panel-card form-section-card" aria-labelledby="section-property-images">
                        <div class="section-header">
                            <h2 id="section-property-images" class="section-title">Fotos</h2>
                            <p class="section-subtitle">Hasta <?= esc((string) $maxImages) ?> fotos. La primera es la portada que se ve en el inicio y en el catálogo.</p>
                        </div>

                        <?php if ($images !== []): ?>
                            <ul class="property-image-list" aria-label="Fotos cargadas">
                                <?php foreach ($images as $index => $image): ?>
                                    <?php $imageId = (int) $image['id']; ?>
                                    <li class="property-image-item">
                                        <img src="<?= esc(site_url('inmuebles/fotos/' . $imageId)) ?>" alt="Foto <?= esc((string) ($index + 1)) ?> del inmueble" loading="lazy" width="160" height="120">
                                        <?php if ($index === 0): ?>
                                            <span class="badge-pill badge-neutral property-image-cover">Portada</span>
                                        <?php endif; ?>
                                        <label class="property-image-remove">
                                            <input type="checkbox" name="remove_images[]" value="<?= esc((string) $imageId) ?>">
                                            <span>Quitar</span>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="form-field">
                            <label for="property-images" class="form-label"><?= $images === [] ? 'Agregar fotos' : 'Agregar más fotos' ?></label>
                            <input
                                type="file"
                                id="property-images"
                                name="images[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                class="form-control<?= isset($errors['images']) ? ' is-invalid' : '' ?>"
                                aria-invalid="<?= isset($errors['images']) ? 'true' : 'false' ?>"
                                aria-describedby="property-images-help"
                                data-max-files="<?= esc((string) $freeSlots) ?>"
                            >
                            <span class="field-help" id="property-images-help">
                                JPG, PNG o WebP. Podés elegir varias a la vez; las fotos grandes se achican solas antes de enviarse.
                                <?php if ($images !== []): ?>Para cambiar la portada, quitá la actual: pasa a serlo la siguiente.<?php endif; ?>
                            </span>
                            <?php if (isset($errors['images'])): ?>
                                <p class="field-error" role="alert"><?= esc((string) $errors['images']) ?></p>
                            <?php elseif ($hadErrors): ?>
                                <p class="field-help" role="status">Si habías elegido fotos nuevas, volvé a elegirlas: el navegador no las conserva al mostrar los errores.</p>
                            <?php endif; ?>
                            <ul class="property-image-preview" id="property-images-preview" aria-live="polite" hidden></ul>
                        </div>
                    </section>
