<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="container section-padding">
    <div class="breadcrumb">
        <a href="<?= baseUrl('painel') ?>">Painel</a> &rsaquo;
        <span>Gerenciar Fotos — <?= e($vehicle['title']) ?></span>
    </div>

    <div class="dashboard-card margin-top-md">
        <h2>Fotos do Anúncio: <?= e($vehicle['title']) ?></h2>
        <p class="text-muted">Adicione até 15 fotos de alta qualidade. A primeira foto é a foto principal exibida nas buscas do RODAX.</p>

        <!-- FORMULÁRIO DE UPLOAD -->
        <form action="<?= baseUrl('veiculos/fotos/upload') ?>" method="POST" enctype="multipart/form-data" class="margin-top-md dropzone-form">
            <?= csrf_field() ?>
            <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">

            <div class="form-group">
                <label for="images">Selecionar Fotos (JPG, PNG, WEBP — Máx 5MB por foto)</label>
                <input type="file" name="images[]" id="images" class="form-control" multiple accept="image/jpeg,image/png,image/webp" required>
            </div>

            <button type="submit" class="btn btn-accent margin-top-sm">📤 Enviar Fotos</button>
        </form>

        <!-- GRID DE IMAGENS CADASTRADAS -->
        <h3 class="margin-top-md">Fotos Cadastradas (<?= count($images) ?> / 15)</h3>

        <?php if (!empty($images)): ?>
            <div class="images-manager-grid margin-top-sm">
                <?php foreach ($images as $img): ?>
                    <div class="image-manage-card <?= !empty($img['is_main']) ? 'is-main-border' : '' ?>">
                        <img src="<?= baseUrl(e($img['image_path'])) ?>" alt="Foto Veículo" class="manage-thumb">
                        
                        <?php if (!empty($img['is_main'])): ?>
                            <span class="main-badge">★ Foto Principal</span>
                        <?php endif; ?>

                        <div class="manage-actions margin-top-xs">
                            <?php if (empty($img['is_main'])): ?>
                                <form action="<?= baseUrl('veiculos/fotos/principal') ?>" method="POST" inline>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                    <button type="submit" class="btn-action info">★ Tornar Principal</button>
                                </form>
                            <?php endif; ?>

                            <form action="<?= baseUrl('veiculos/fotos/excluir') ?>" method="POST" inline onsubmit="return confirm('Deseja excluir esta foto?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                                <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                <button type="submit" class="btn-action danger">🗑️ Excluir</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state margin-top-md">
                <p>Nenhuma foto enviada para este anúncio. Adicione fotos para atrair mais compradores!</p>
            </div>
        <?php endif; ?>

        <div class="margin-top-md border-top padding-top-sm">
            <a href="<?= baseUrl('painel') ?>" class="btn btn-secondary">&larr; Voltar para Meus Anúncios</a>
            <a href="<?= baseUrl('veiculos/' . e($vehicle['slug'])) ?>" class="btn btn-primary" target="_blank">👁️ Ver Anúncio Público</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
