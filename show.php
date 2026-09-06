<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="container padding-y-lg">
    <!-- NAVEGAÇÃO BREADCRUMB -->
    <nav class="breadcrumb-nav margin-bottom-sm">
        <a href="<?= baseUrl('/') ?>" class="breadcrumb-link">Home</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <a href="<?= baseUrl('veiculos?categoria=' . e($vehicle['category_slug'])) ?>" class="breadcrumb-link"><?= e($vehicle['category_name']) ?></a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <span class="breadcrumb-current"><?= e($vehicle['brand']) ?> <?= e($vehicle['model']) ?></span>
    </nav>

    <!-- TÍTULO E SUBTÍTULO DO ANÚNCIO -->
    <div class="vehicle-header-card card-box padding-md">
        <div class="header-tag-row flex-header">
            <span class="badge-navy"><?= e($vehicle['category_name']) ?></span>
            <span class="font-xs text-muted"> Anúncio #<?= (int)$vehicle['id'] ?> • 👁️ <?= (int)$vehicle['views_count'] ?> visualizações</span>
        </div>
        <h1 class="vehicle-title margin-top-xs"><?= e($vehicle['title']) ?></h1>
        <p class="vehicle-subtitle text-muted">
            <?= !empty($vehicle['version']) ? e($vehicle['version']) . ' • ' : '' ?>📍 <?= e($vehicle['city']) ?> - <?= e($vehicle['state']) ?>
        </p>
    </div>

    <!-- LAYOUT PRINCIPAL DE 2 COLUNAS (ETAPA 5) -->
    <div class="vehicle-details-layout margin-top-md">
        <!-- ÁREA PRINCIPAL DA ESQUERDA: GALERIA, ESPECIFICAÇÕES E DESCRIÇÃO -->
        <div class="vehicle-main-col">
            <!-- GALERIA DE FOTOS INTERATIVA -->
            <div class="gallery-card card-box">
                <?php if (!empty($images)): ?>
                    <div class="main-image-display">
                        <img src="<?= baseUrl(e($images[0]['image_path'])) ?>" alt="<?= e($vehicle['title']) ?>" id="currentMainImg" class="main-img-el">
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="gallery-thumbnails margin-top-xs">
                            <?php foreach ($images as $idx => $img): ?>
                                <img src="<?= baseUrl(e($img['image_path'])) ?>" alt="Foto <?= $idx + 1 ?>" class="thumb-item <?= !empty($img['is_main']) ? 'active' : '' ?>" onclick="switchGalleryImg(this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-image-placeholder large">
                        <span class="placeholder-logo">RODAX</span>
                        <span class="placeholder-text">Foto não disponível para este anúncio</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CARDS DE ESPECIFICAÇÕES RÁPIDAS DA FICHA -->
            <div class="quick-specs-grid margin-top-md">
                <div class="qspec-card card-box">
                    <span class="qspec-icon">📅</span>
                    <span class="qspec-label">Ano Modelo</span>
                    <span class="qspec-val"><?= e($vehicle['year_manufacture']) ?> / <?= e($vehicle['year_model']) ?></span>
                </div>
                <div class="qspec-card card-box">
                    <span class="qspec-icon">🛣️</span>
                    <span class="qspec-label">Quilometragem</span>
                    <span class="qspec-val"><?= formatKm($vehicle['mileage']) ?></span>
                </div>
                <div class="qspec-card card-box">
                    <span class="qspec-icon">🎨</span>
                    <span class="qspec-label">Cor</span>
                    <span class="qspec-val"><?= e($vehicle['color']) ?></span>
                </div>
                <div class="qspec-card card-box">
                    <span class="qspec-icon">⛽</span>
                    <span class="qspec-label">Combustível</span>
                    <span class="qspec-val"><?= e($vehicle['fuel_type']) ?></span>
                </div>
            </div>

            <!-- FICHA TÉCNICA COMPLETA -->
            <div class="details-section card-box margin-top-md">
                <h2 class="font-lg fw-bold text-navy border-bottom padding-bottom-xs">📋 Ficha Técnica & Especificações</h2>
                
                <div class="specs-table margin-top-sm">
                    <div class="spec-row"><span class="spec-name">Marca</span> <span class="spec-val"><?= e($vehicle['brand']) ?></span></div>
                    <div class="spec-row"><span class="spec-name">Modelo</span> <span class="spec-val"><?= e($vehicle['model']) ?></span></div>
                    <?php if (!empty($vehicle['version'])): ?>
                        <div class="spec-row"><span class="spec-name">Versão</span> <span class="spec-val"><?= e($vehicle['version']) ?></span></div>
                    <?php endif; ?>
                    <div class="spec-row"><span class="spec-name">Ano Fabricação / Modelo</span> <span class="spec-val"><?= e($vehicle['year_manufacture']) ?> / <?= e($vehicle['year_model']) ?></span></div>
                    <div class="spec-row"><span class="spec-name">Quilometragem</span> <span class="spec-val"><?= formatKm($vehicle['mileage']) ?></span></div>
                    <div class="spec-row"><span class="spec-name">Cor</span> <span class="spec-val"><?= e($vehicle['color']) ?></span></div>
                    <div class="spec-row"><span class="spec-name">Combustível</span> <span class="spec-val"><?= e($vehicle['fuel_type']) ?></span></div>

                    <?php if (!empty($vehicle['transmission'])): ?>
                        <div class="spec-row"><span class="spec-name">Transmissão / Câmbio</span> <span class="spec-val"><?= e($vehicle['transmission']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['engine'])): ?>
                        <div class="spec-row"><span class="spec-name">Motorização</span> <span class="spec-val"><?= e($vehicle['engine']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['power_hp'])): ?>
                        <div class="spec-row"><span class="spec-name">Potência</span> <span class="spec-val"><?= e($vehicle['power_hp']) ?> cv</span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['torque_kgfm'])): ?>
                        <div class="spec-row"><span class="spec-name">Torque</span> <span class="spec-val"><?= e($vehicle['torque_kgfm']) ?> kgfm</span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['steering'])): ?>
                        <div class="spec-row"><span class="spec-name">Direção</span> <span class="spec-val"><?= e($vehicle['steering']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['traction'])): ?>
                        <div class="spec-row"><span class="spec-name">Tração</span> <span class="spec-val"><?= e($vehicle['traction']) ?></span></div>
                    <?php endif; ?>

                    <!-- CAMPOS ESPECÍFICOS DA CATEGORIA -->
                    <?php if (!empty($vehicle['body_style'])): ?>
                        <div class="spec-row"><span class="spec-name">Carroceria</span> <span class="spec-val"><?= e($vehicle['body_style']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['doors_count'])): ?>
                        <div class="spec-row"><span class="spec-name">Número de Portas</span> <span class="spec-val"><?= e($vehicle['doors_count']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['seats_count'])): ?>
                        <div class="spec-row"><span class="spec-name">Número de Lugares</span> <span class="spec-val"><?= e($vehicle['seats_count']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['engine_capacity_cc'])): ?>
                        <div class="spec-row"><span class="spec-name">Cilindrada</span> <span class="spec-val"><?= e($vehicle['engine_capacity_cc']) ?> cc</span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['starter_type'])): ?>
                        <div class="spec-row"><span class="spec-name">Tipo de Partida</span> <span class="spec-val"><?= e($vehicle['starter_type']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['axles_count'])): ?>
                        <div class="spec-row"><span class="spec-name">Quantidade de Eixos</span> <span class="spec-val"><?= e($vehicle['axles_count']) ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['pbt_kg'])): ?>
                        <div class="spec-row"><span class="spec-name">PBT (Peso Bruto Total)</span> <span class="spec-val"><?= number_format($vehicle['pbt_kg'], 0, '', '.') ?> kg</span></div>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['load_capacity_kg'])): ?>
                        <div class="spec-row"><span class="spec-name">Capacidade de Carga</span> <span class="spec-val"><?= number_format($vehicle['load_capacity_kg'], 0, '', '.') ?> kg</span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- OPCIONAIS E ITENS DE SÉRIE -->
            <?php if (!empty($features)): ?>
                <div class="details-section card-box margin-top-md">
                    <h2 class="font-lg fw-bold text-navy border-bottom padding-bottom-xs">✨ Opcionais e Itens de Série</h2>
                    <div class="features-grid margin-top-sm">
                        <?php foreach ($features as $feat): ?>
                            <div class="feature-item">✓ <?= e($feat['name']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DESCRIÇÃO DO VENDEDOR -->
            <div class="details-section card-box margin-top-md">
                <h2 class="font-lg fw-bold text-navy border-bottom padding-bottom-xs">📝 Descrição do Vendedor</h2>
                <div class="description-content margin-top-sm">
                    <?= nl2br(e($vehicle['description'])) ?>
                </div>
            </div>
        </div>

        <!-- SIDEBAR DIREITA: PREÇO, TABELA FIPE E CONTATO (STICKY CARD ETAPA 5) -->
        <aside class="vehicle-sidebar-col">
            <div class="price-sticky-card card-box">
                <span class="price-tag-label font-xs fw-bold text-muted">PREÇO ANUNCIADO</span>
                <div class="seller-asking-price text-blue font-3xl fw-extrabold"><?= formatMoney($vehicle['price']) ?></div>

                <!-- BOX DE REFERÊNCIA DA TABELA FIPE -->
                <?php if (!empty($vehicle['fipe_reference_price']) || !empty($fipeData)): ?>
                    <?php 
                        $fipeVal = (float)($vehicle['fipe_reference_price'] ?? 0);
                        $diff = $fipeVal - (float)$vehicle['price'];
                    ?>
                    <div class="fipe-official-card margin-top-sm">
                        <div class="fipe-card-header">
                            <span class="fipe-badge">TABELA FIPE</span>
                            <span class="fipe-code-badge font-xs">Código: <?= e($vehicle['fipe_code'] ?? $fipeData['CodigoFipe'] ?? 'N/A') ?></span>
                        </div>
                        <div class="fipe-price-value">
                            <?= formatMoney($vehicle['fipe_reference_price'] ?? str_replace(['R$', '.', ' '], '', str_replace(',', '.', $fipeData['Valor'] ?? '0'))) ?>
                        </div>
                        <?php if ($diff > 0): ?>
                            <div class="fipe-opportunity-badge margin-top-xs">
                                🔥 <strong><?= formatMoney($diff) ?> abaixo da Tabela FIPE!</strong> (Excelente oportunidade)
                            </div>
                        <?php endif; ?>
                        <p class="fipe-disclaimer-text margin-top-xs">
                            * Preço médio de referência Tabela FIPE (<?= e($fipeData['MesReferencia'] ?? 'Mês vigente') ?>).
                        </p>
                    </div>
                <?php endif; ?>

                <!-- CARD DO VENDEDOR DA ANÚNCIO -->
                <div class="seller-profile-box margin-top-md border-top padding-top-sm">
                    <h3 class="font-sm fw-bold text-navy margin-bottom-xs">Anunciante</h3>
                    <div class="seller-name-row flex-header">
                        <strong class="text-navy font-md"><?= e($vehicle['seller_name']) ?></strong>
                        <span class="badge badge-outline-sm"><?= ucfirst($vehicle['seller_user_type']) ?></span>
                    </div>
                    <p class="seller-info font-xs text-muted margin-top-xs">📍 <?= e($vehicle['city']) ?> - <?= e($vehicle['state']) ?></p>
                    <p class="seller-info font-xs text-muted">📅 Membro RODAX desde <?= date('m/Y', strtotime($vehicle['seller_since'])) ?></p>

                    <!-- AÇÕES DE CONTATO -->
                    <a href="<?= baseUrl('mensagens/nova/' . $vehicle['id']) ?>" class="btn btn-accent btn-block btn-lg margin-top-md">
                        💬 Entrar em Contato
                    </a>

                    <?php 
                    $isFav = false;
                    if (!empty($_SESSION['user_id'])) {
                        $favModel = new \App\Models\Favorite();
                        $isFav = $favModel->isFavorited((int)$_SESSION['user_id'], (int)$vehicle['id']);
                    }
                    ?>
                    <form action="<?= baseUrl('favoritos/toggle') ?>" method="POST" class="margin-top-xs">
                        <?= csrf_field() ?>
                        <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                        <button type="submit" class="btn <?= $isFav ? 'btn-accent' : 'btn-secondary' ?> btn-block">
                            <?= $isFav ? '❤️ Salvo nos Favoritos' : '🤍 Salvar nos Favoritos' ?>
                        </button>
                    </form>

                    <button type="button" onclick="openReportModal()" class="btn-link-danger btn-block text-center margin-top-xs">
                        ⚠️ Denunciar este Anúncio
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- MODAL DE DENÚNCIA -->
<div id="reportModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card card-box">
        <div class="modal-header border-bottom padding-bottom-xs">
            <h3 class="font-md fw-bold text-navy">⚠️ Denunciar Anúncio</h3>
            <button type="button" class="modal-close-btn" onclick="closeReportModal()">&times;</button>
        </div>
        <form action="<?= baseUrl('denunciar') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">

            <div class="form-group margin-top-sm">
                <label for="report_reason" class="form-label font-xs fw-bold text-muted">MOTIVO DA DENÚNCIA *</label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">-- Selecione o Motivo --</option>
                    <?php foreach (\App\Models\Report::$allowedReasons as $reasonOption): ?>
                        <option value="<?= e($reasonOption) ?>"><?= e($reasonOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group margin-top-sm">
                <label for="report_description" class="form-label font-xs fw-bold text-muted">DESCRIÇÃO DETALHADA (OPCIONAL)</label>
                <textarea name="description" id="report_description" class="form-control" rows="4" placeholder="Descreva brevemente o motivo da denúncia para nos ajudar na análise..."></textarea>
            </div>

            <div class="modal-actions margin-top-md">
                <button type="button" class="btn btn-secondary" onclick="closeReportModal()">Cancelar</button>
                <button type="submit" class="btn btn-danger">Enviar Denúncia</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchGalleryImg(el) {
    document.getElementById('currentMainImg').src = el.src;
    document.querySelectorAll('.thumb-item').forEach(thumb => thumb.classList.remove('active'));
    el.classList.add('active');
}

function openReportModal() {
    document.getElementById('reportModal').style.display = 'flex';
}

function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
