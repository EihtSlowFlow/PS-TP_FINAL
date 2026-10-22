<?php
require_once 'abm.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sprint 2 - ABM de Usuarios</title>
    <!-- Incluyendo tipografía moderna desde Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Sistema ABM de Usuarios <span class="badge">Sprint 2</span></h1>
        </header>

        <main>
            <?php if ($mensaje): ?>
            <div class="success-msg">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <section>
            <h2>Agregar nuevo usuario</h2>
            <form action="index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej. Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="juan@ejemplo.com">
                </div>
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">Guardar Registro</button>
                </div>
            </form>
        </section>

            <section>
                <h2>Listado de Usuarios</h2>
                <?php if (!empty($usuarios)): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th class="th-actions">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr id="row-<?= htmlspecialchars($usuario['id']) ?>">
                                        <td>#<?= htmlspecialchars($usuario['id']) ?></td>
                                        <td class="fw-500">
                                            <span class="view-mode"><?= htmlspecialchars($usuario['nombre']) ?></span>
                                            <input type="text" class="edit-mode form-control" name="nombre" form="edit-form-<?= $usuario['id'] ?>" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                        </td>
                                        <td class="text-muted">
                                            <span class="view-mode"><?= htmlspecialchars($usuario['email'] ?? 'Sin email') ?></span>
                                            <input type="email" class="edit-mode form-control" name="email" form="edit-form-<?= $usuario['id'] ?>" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                                        </td>
                                        <td>
                                            <!-- Modo Lectura -->
                                            <div class="actions view-mode">
                                                <button type="button" class="btn btn-edit" data-action="edit" data-id="<?= $usuario['id'] ?>">Editar</button>
                                                
                                                <form action="index.php" method="POST" class="form-delete">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">
                                                    <button type="submit" class="btn btn-delete">Eliminar</button>
                                                </form>
                                            </div>
                                            <!-- Modo Edición -->
                                            <div class="actions edit-mode">
                                                <form action="index.php" method="POST" id="edit-form-<?= $usuario['id'] ?>" class="form-inline-edit">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                    <input type="hidden" name="accion" value="actualizar">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.8rem;">Guardar</button>
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-action="cancel-edit" data-id="<?= $usuario['id'] ?>" style="padding: 0.4rem 0.8rem;">Cancelar</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <?php if (!$error): ?>
                        <div class="empty-state">
                            <p>No hay usuarios registrados actualmente. ¡Comienza agregando uno arriba!</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
