<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= lang('Errors.pageNotFound') ?></title>

    <!-- Same identity as the rest of the app: colours and fonts come from the shared tokens (issue #33). -->
    <link rel="stylesheet" href="<?= esc(base_url('css/tokens.css')) ?>">
    <style>
        body {
            height: 100%;
            margin: 0;
            background: var(--bg-app);
            font-family: var(--font-body);
            color: var(--text-muted);
            font-weight: 400;
        }
        h1 {
            font-family: var(--font-display);
            font-weight: 600;
            letter-spacing: normal;
            font-size: 3rem;
            margin-top: 0;
            margin-bottom: 0;
            color: var(--heading);
        }
        .wrap {
            max-width: 1024px;
            margin: 5rem auto;
            padding: 2rem;
            background: var(--surface);
            text-align: center;
            border: 1px solid var(--border-default);
            border-bottom: 2px solid var(--edge);
            border-radius: var(--radius-md);
            position: relative;
        }
        p {
            margin-top: 1.5rem;
        }
        a:active,
        a:link,
        a:visited {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>404</h1>

        <p>
            <?php if (ENVIRONMENT !== 'production') : ?>
                <?= nl2br(esc($message)) ?>
            <?php else : ?>
                <?= lang('Errors.sorryCannotFind') ?>
            <?php endif; ?>
        </p>
    </div>
</body>
</html>
