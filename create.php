<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="container section-padding">
    <div class="form-container">
        <h1 class="auth-title">Anunciar Veículo no RODAX</h1>
        <p class="auth-subtitle">Preencha as informações gerais e os detalhes específicos da categoria do seu veículo.</p>

        <form action="<?= baseUrl('anunciar') ?>" method="POST" class="rodax-form margin-top-md" id="vehicleForm">
            <?= csrf_field() ?>

            <!-- SELEÇÃO DE CATEGORIA PRINCIPAL -->
            <div class="form-card">
                <h2>1. Escolha a Categoria do Veículo</h2>
                <div class="form-group margin-top-sm">
                    <label for="vehicle_type_id">Categoria *</label>
                    <select name="vehicle_type_id" id="vehicle_type_id" class="form-control" required onchange="renderCategoryFields(this.value)">
                        <option value="">-- Selecione a Categoria --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-slug="<?= e($cat['slug']) ?>">
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- INFORMAÇÕES GERAIS -->
            <div class="form-card margin-top-md">
                <h2>2. Informações Gerais do Anúncio</h2>
                
                <div class="form-group margin-top-sm">
                    <label for="title">Título do Anúncio *</label>
                    <input type="text" name="title" id="title" class="form-control" required placeholder="Ex: Volkswagen Golf 1.4 TSI Highline 2017 Completo">
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="brand">Marca *</label>
                        <input type="text" name="brand" id="brand" class="form-control" required placeholder="Ex: Volkswagen, Honda, Volvo...">
                    </div>
                    <div class="form-group">
                        <label for="model">Modelo *</label>
                        <input type="text" name="model" id="model" class="form-control" required placeholder="Ex: Golf, Civic, FH 540...">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="version">Versão</label>
                        <input type="text" name="version" id="version" class="form-control" placeholder="Ex: 1.4 TSI Highline Automatico">
                    </div>
                    <div class="form-group">
                        <label for="color">Cor *</label>
                        <input type="text" name="color" id="color" class="form-control" required placeholder="Ex: Branco, Preto, Prata...">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="year_manufacture">Ano Fabricação *</label>
                        <input type="number" name="year_manufacture" id="year_manufacture" class="form-control" required min="1950" max="2027" placeholder="Ex: 2016">
                    </div>
                    <div class="form-group">
                        <label for="year_model">Ano Modelo *</label>
                        <input type="number" name="year_model" id="year_model" class="form-control" required min="1950" max="2027" placeholder="Ex: 2017">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="mileage">Quilometragem (km) *</label>
                        <input type="number" name="mileage" id="mileage" class="form-control" required min="0" placeholder="Ex: 65000">
                    </div>
                    <div class="form-group">
                        <label for="price">Preço Pedido (R$) *</label>
                        <input type="text" name="price" id="price" class="form-control" required placeholder="R$ 79.900,00">
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="fuel_type">Combustível *</label>
                        <select name="fuel_type" id="fuel_type" class="form-control" required>
                            <option value="Flex">Flex (Gasolina/Álcool)</option>
                            <option value="Gasolina">Gasolina</option>
                            <option value="Ethanol">Álcool / Etanol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="Elétrico">Elétrico</option>
                            <option value="GNV">GNV</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transmission">Transmissão</label>
                        <select name="transmission" id="transmission" class="form-control">
                            <option value="Manual">Manual</option>
                            <option value="Automática">Automática</option>
                            <option value="CVT">CVT</option>
                            <option value="Automatizada">Automatizada</option>
                        </select>
                    </div>
                </div>

                <div class="form-row margin-top-sm">
                    <div class="form-group">
                        <label for="state">Estado (UF) *</label>
                        <select name="state" id="state" class="form-control" required>
                            <option value="SP">São Paulo (SP)</option>
                            <option value="RJ">Rio de Janeiro (RJ)</option>
                            <option value="MG">Minas Gerais (MG)</option>
                            <option value="PR">Paraná (PR)</option>
                            <option value="RS">Rio Grande do Sul (RS)</option>
                            <option value="SC">Santa Catarina (SC)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city">Cidade *</label>
                        <input type="text" name="city" id="city" class="form-control" required placeholder="Sua cidade">
                    </div>
                </div>
            </div>

            <!-- SEÇÃO DE CAMPOS ESPECÍFICOS DA CATEGORIA (DINÂMICO) -->
            <div class="form-card margin-top-md" id="categoryFieldsCard">
                <h2>3. Especificações da Categoria</h2>
                <p class="text-muted" id="categoryFieldsNotice">Selecione uma categoria acima para visualizar os campos específicos.</p>

                <!-- CAMPOS PARA CARROS -->
                <div id="fields_carros" class="cat-fields-group" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="body_style">Carroceria</label>
                            <select name="body_style" class="form-control">
                                <option value="Hatch">Hatch</option>
                                <option value="Sedan">Sedan</option>
                                <option value="SUV">SUV</option>
                                <option value="Picape">Picape</option>
                                <option value="Perua">Perua</option>
                                <option value="Conversível">Conversível</option>
                                <option value="Coupé">Coupé</option>
                                <option value="Minivan">Minivan</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="doors_count">Número de Portas</label>
                            <input type="number" name="doors_count" class="form-control" placeholder="4">
                        </div>
                        <div class="form-group">
                            <label for="seats_count">Número de Lugares</label>
                            <input type="number" name="seats_count" class="form-control" placeholder="5">
                        </div>
                    </div>
                </div>

                <!-- CAMPOS PARA MOTOS -->
                <div id="fields_motos" class="cat-fields-group" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="engine_capacity_cc">Cilindrada (cc)</label>
                            <input type="number" name="engine_capacity_cc" class="form-control" placeholder="Ex: 600">
                        </div>
                        <div class="form-group">
                            <label for="motorcycle_category">Categoria da Moto</label>
                            <select name="motorcycle_category" class="form-control">
                                <option value="Street">Street</option>
                                <option value="Naked">Naked</option>
                                <option value="Sport">Sport</option>
                                <option value="Trail">Trail</option>
                                <option value="Custom">Custom</option>
                                <option value="Scooter">Scooter</option>
                                <option value="Touring">Touring</option>
                                <option value="Off-road">Off-road</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="starter_type">Tipo de Partida</label>
                            <select name="starter_type" class="form-control">
                                <option value="Elétrica">Elétrica</option>
                                <option value="Pedal">Pedal</option>
                                <option value="Elétrica e Pedal">Elétrica e Pedal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- CAMPOS PARA CAMINHÕES -->
                <div id="fields_caminhoes" class="cat-fields-group" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="traction">Tração</label>
                            <select name="traction" class="form-control">
                                <option value="4x2">4x2</option>
                                <option value="6x2">6x2</option>
                                <option value="6x4">6x4</option>
                                <option value="8x2">8x2</option>
                                <option value="8x4">8x4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cabin_type">Tipo de Cabine</label>
                            <select name="cabin_type" class="form-control">
                                <option value="Simples">Simples</option>
                                <option value="Estendida">Estendida</option>
                                <option value="Leito">Leito</option>
                                <option value="Teto Alto">Teto Alto</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="load_capacity_kg">Capacidade de Carga (kg)</label>
                            <input type="number" name="load_capacity_kg" class="form-control" placeholder="Ex: 15000">
                        </div>
                    </div>
                </div>

                <!-- CAMPOS PARA IMPLEMENTOS RODOVIÁRIOS -->
                <div id="fields_implementos-rodoviarios" class="cat-fields-group" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="implement_type">Tipo de Implemento</label>
                            <select name="implement_type" class="form-control">
                                <option value="Reboque">Reboque</option>
                                <option value="Semirreboque">Semirreboque</option>
                                <option value="Baú">Baú</option>
                                <option value="Sider">Sider</option>
                                <option value="Graneleiro">Graneleiro</option>
                                <option value="Tanque">Tanque</option>
                                <option value="Basculante">Basculante</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="implement_manufacturer">Fabricante</label>
                            <input type="text" name="implement_manufacturer" class="form-control" placeholder="Ex: Randon, Facchini...">
                        </div>
                        <div class="form-group">
                            <label for="implement_length_m">Comprimento (metros)</label>
                            <input type="number" step="0.1" name="implement_length_m" class="form-control" placeholder="Ex: 14.5">
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESCRIÇÃO -->
            <div class="form-card margin-top-md">
                <h2>4. Descrição Detalhada</h2>
                <div class="form-group margin-top-sm">
                    <textarea name="description" id="description" rows="5" class="form-control" placeholder="Descreva os diferenciais, estado de conservação, revisões efetuadas e histórico do veículo..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-accent btn-block btn-lg margin-top-md">Publicar Anúncio no RODAX</button>
        </form>
    </div>
</div>

<script>
function renderCategoryFields(typeId) {
    const select = document.getElementById('vehicle_type_id');
    const selectedOption = select.options[select.selectedIndex];
    const slug = selectedOption ? selectedOption.getAttribute('data-slug') : '';

    // Esconder todos os grupos de campos
    document.querySelectorAll('.cat-fields-group').forEach(group => group.style.display = 'none');
    document.getElementById('categoryFieldsNotice').style.display = 'none';

    // Exibir campos específicos da categoria selecionada
    if (slug) {
        const targetGroup = document.getElementById('fields_' + slug);
        if (targetGroup) {
            targetGroup.style.display = 'block';
        } else {
            document.getElementById('categoryFieldsNotice').style.display = 'block';
            document.getElementById('categoryFieldsNotice').innerText = 'Categoria selecionada sem especificações adicionais necessárias.';
        }
    } else {
        document.getElementById('categoryFieldsNotice').style.display = 'block';
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
