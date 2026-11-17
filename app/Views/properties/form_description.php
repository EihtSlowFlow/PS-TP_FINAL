<?php
/**
 * "Descripción" card shared by the administration form (HU-07) and the owner form (HU-32).
 *
 * @var string                    $mode     'create' or 'edit'
 * @var array<string, mixed>|null $property Property being edited, or null
 */
$isEdit = $mode === 'edit' && $property !== null;
$errors = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$descriptionValue = (string) old('description', $isEdit ? (string) ($property['description'] ?? '') : '');
?>
                    <section class="panel-card form-section-card" aria-labelledby="section-property-description">
                        <div class="section-header">
                            <h2 id="section-property-description" class="section-title">Descripción</h2>
                            <p class="section-subtitle">Estado de conservación y comodidades disponibles.</p>
                        </div>

                        <div class="form-field">
                            <div class="form-label-row">
                                <label for="description" class="form-label">Estado y comodidades</label>
                                <span class="char-counter" id="description-counter" aria-live="polite">
                                    <?= esc((string) mb_strlen($descriptionValue)) ?> / 1.000 caracteres
                                </span>
                            </div>
                            <textarea
                                id="description"
                                name="description"
                                rows="6"
                                maxlength="1000"
                                class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>"
                                aria-invalid="<?= isset($errors['description']) ? 'true' : 'false' ?>"
                            ><?= esc($descriptionValue) ?></textarea>
                            <span class="field-help">Opcional. Máximo 1.000 caracteres.</span>
                            <?php if (isset($errors['description'])): ?>
                                <p class="field-error" role="alert"><?= esc((string) $errors['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </section>
