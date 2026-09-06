<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="container section-padding">
    <div class="form-container">
        <h1 class="auth-title">Editar Anúncio</h1>
        <p class="auth-subtitle">Atualize as informações do seu veículo <strong><?= e($vehicle['title']) ?></strong></p>

        <form action="<?= baseUrl('veiculos/editar/' . $vehicle['id']) ?>" method="POST" class="rodax-form margin-top-md">
            <?= csrf_field() ?>

            <div class="form-card">
                <h2>Informações Principais</h2>
                <div class="form-group margin-top-sm">
                    <label for="title">Título do Anúncio *</label>
                    <input type="text" name="title" id="title" class="form-control" required value="<?= e($vehicle['title']) ?>">
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="brand">Marca *</label>
                        <input type="text" name="brand" id="brand" class="form-control" required value="<?= e($vehicle['brand']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="model">Modelo *</label>
                        <input type="text" name="model" id="model" class="form-control" required value="<?= e($vehicle['model']) ?>">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="version">Versão</label>
                        <input type="text" name="version" id="version" class="form-control" value="<?= e($vehicle['version']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="color">Cor *</label>
                        <input type="text" name="color" id="color" class="form-control" required value="<?= e($vehicle['color']) ?>">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="year_manufacture">Ano Fabricação *</label>
                        <input type="number" name="year_manufacture" id="year_manufacture" class="form-control" required value="<?= e($vehicle['year_manufacture']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="year_model">Ano Modelo *</label>
                        <input type="number" name="year_model" id="year_model" class="form-control" required value="<?= e($vehicle['year_model']) ?>">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="mileage">Quilometragem (km) *</label>
                        <input type="number" name="mileage" id="mileage" class="form-control" required value="<?= e($vehicle['mileage']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="price">Preço Pedido (R$) *</label>
                        <input type="text" name="price" id="price" class="form-control" required value="<?= formatMoney($vehicle['price']) ?>">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="fuel_type">Combustível *</label>
                        <select name="fuel_type" id="fuel_type" class="form-control" required>
                            <option value="Flex" <?= $vehicle['fuel_type'] === 'Flex' ? 'selected' : '' ?>>Flex</option>
                            <option value="Gasolina" <?= $vehicle['fuel_type'] === 'Gasolina' ? 'selected' : '' ?>>Gasolina</option>
                            <option value="Ethanol" <?= $vehicle['fuel_type'] === 'Ethanol' ? 'selected' : '' ?>>Álcool / Etanol</option>
                            <option value="Diesel" <?= $vehicle['fuel_type'] === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="Híbrido" <?= $vehicle['fuel_type'] === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                            <option value="Elétrico" <?= $vehicle['fuel_type'] === 'Elétrico' ? 'selected' : '' ?>>Elétrico</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="transmission">Transmissão</label>
                        <select name="transmission" id="transmission" class="form-control">
                            <option value="Manual" <?= $vehicle['transmission'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="Automática" <?= $vehicle['transmission'] === 'Automática' ? 'selected' : '' ?>>Automática</option>
                            <option value="CVT" <?= $vehicle['transmission'] === 'CVT' ? 'selected' : '' ?>>CVT</option>
                        </select>
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="state">Estado (UF) *</label>
                        <input type="text" name="state" id="state" class="form-control" required maxlength="2" value="<?= e($vehicle['state']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">Cidade *</label>
                        <input type="text" name="city" id="city" class="form-control" required value="<?= e($vehicle['city']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-card margin-top-md">
                <h2>Descrição do Anúncio</h2>
                <div class="form-group margin-top-sm">
                    <textarea name="description" id="description" rows="6" class="form-control"><?= e($vehicle['description']) ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg margin-top-md">Salvar Alterações</button>
            <a href="<?= baseUrl('painel') ?>" class="btn btn-secondary btn-block margin-top-xs">Cancelar</a>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
