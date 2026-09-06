<?php

namespace App\Services;

use PDO;
use Exception;

/**
 * RODAX — Serviço da API FIPE com Cache Mandatório no MySQL
 * 
 * Regra do RODAX:
 * - Toda consulta verifica primeiro o MySQL.
 * - Se houver cache válido (não expirado), retorna do MySQL com 0 chamadas externas.
 * - Se não houver cache ou estiver expirado, consulta a API FIPE via backend PHP.
 * - Salva a resposta no MySQL com timestamp e expiração.
 * - Se a API falhar, utiliza o cache anterior (se disponível) sem expor erros sensíveis.
 */
class FipeService
{
    private PDO $db;
    private string $baseUrl;
    private string $apiToken;
    private int $cacheTtlDays;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
        
        $this->baseUrl = env('FIPE_API_BASE_URL', 'https://parallelum.com.br/fipe/api/v1');
        $this->apiToken = env('FIPE_API_TOKEN', '');
        $this->cacheTtlDays = (int)env('FIPE_CACHE_TTL_DAYS', 30);
    }

    /**
     * Mapeia o tipo de veículo do RODAX para a API FIPE
     */
    private function mapVehicleType(string $typeSlug): string
    {
        return match (strtolower($typeSlug)) {
            'motos', 'moto' => 'motos',
            'caminhoes', 'caminhao', 'onibus', 'implementos-rodoviarios' => 'caminhoes',
            default => 'carros',
        };
    }

    /**
     * Obter Marcas FIPE (com Cache MySQL)
     */
    public function getBrands(string $typeSlug): array
    {
        $typeSlug = $this->mapVehicleType($typeSlug);
        
        // 1. Verificar cache local no MySQL
        $stmt = $this->db->prepare("
            SELECT fipe_brand_code AS codigo, name AS nome
            FROM fipe_brands
            WHERE vehicle_type_slug = :type
            ORDER BY name ASC
        ");
        $stmt->execute(['type' => $typeSlug]);
        $cachedBrands = $stmt->fetchAll();

        if (!empty($cachedBrands)) {
            return [
                'source' => 'cache',
                'data'   => $cachedBrands
            ];
        }

        // 2. Se não houver cache, consultar API FIPE
        $url = "{$this->baseUrl}/{$typeSlug}/marcas";
        $apiResult = $this->makeHttpRequest($url);

        if ($apiResult['success'] && is_array($apiResult['data'])) {
            // 3. Salvar marcas no MySQL
            $this->db->beginTransaction();
            try {
                $insertStmt = $this->db->prepare("
                    INSERT INTO fipe_brands (vehicle_type_slug, fipe_brand_code, name, cached_at)
                    VALUES (:type, :code, :name, NOW())
                    ON DUPLICATE KEY UPDATE name = VALUES(name), cached_at = NOW()
                ");

                foreach ($apiResult['data'] as $brand) {
                    $insertStmt->execute([
                        'type' => $typeSlug,
                        'code' => (string)($brand['codigo'] ?? ''),
                        'name' => (string)($brand['nome'] ?? '')
                    ]);
                }
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("Erro ao salvar marcas FIPE no cache: " . $e->getMessage());
            }

            return [
                'source' => 'api',
                'data'   => $apiResult['data']
            ];
        }

        // Fallback para falha na API
        return [
            'source' => 'error',
            'data'   => [],
            'message' => 'API FIPE indisponível no momento'
        ];
    }

    /**
     * Obter Modelos de uma Marca (com Cache MySQL)
     */
    public function getModels(string $typeSlug, string $brandCode): array
    {
        $typeSlug = $this->mapVehicleType($typeSlug);

        // 1. Verificar Cache MySQL
        $stmt = $this->db->prepare("
            SELECT fipe_model_code AS codigo, name AS nome
            FROM fipe_models
            WHERE vehicle_type_slug = :type AND fipe_brand_code = :brand_code
            ORDER BY name ASC
        ");
        $stmt->execute(['type' => $typeSlug, 'brand_code' => $brandCode]);
        $cachedModels = $stmt->fetchAll();

        if (!empty($cachedModels)) {
            return [
                'source' => 'cache',
                'data'   => $cachedModels
            ];
        }

        // 2. Consultar API FIPE
        $url = "{$this->baseUrl}/{$typeSlug}/marcas/{$brandCode}/modelos";
        $apiResult = $this->makeHttpRequest($url);

        if ($apiResult['success'] && isset($apiResult['data']['modelos'])) {
            $modelsList = $apiResult['data']['modelos'];

            // 3. Salvar no MySQL
            $this->db->beginTransaction();
            try {
                $insertStmt = $this->db->prepare("
                    INSERT INTO fipe_models (vehicle_type_slug, fipe_brand_code, fipe_model_code, name, cached_at)
                    VALUES (:type, :brand_code, :model_code, :name, NOW())
                    ON DUPLICATE KEY UPDATE name = VALUES(name), cached_at = NOW()
                ");

                foreach ($modelsList as $model) {
                    $insertStmt->execute([
                        'type'       => $typeSlug,
                        'brand_code' => $brandCode,
                        'model_code' => (string)($model['codigo'] ?? ''),
                        'name'       => (string)($model['nome'] ?? '')
                    ]);
                }
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("Erro ao salvar modelos FIPE no cache: " . $e->getMessage());
            }

            return [
                'source' => 'api',
                'data'   => $modelsList
            ];
        }

        return [
            'source' => 'error',
            'data'   => [],
            'message' => 'Não foi possível carregar os modelos FIPE'
        ];
    }

    /**
     * Obter Anos de um Modelo (com Cache MySQL)
     */
    public function getYears(string $typeSlug, string $brandCode, string $modelCode): array
    {
        $typeSlug = $this->mapVehicleType($typeSlug);

        // 1. Verificar Cache MySQL
        $stmt = $this->db->prepare("
            SELECT fipe_year_code AS codigo, name AS nome
            FROM fipe_years
            WHERE vehicle_type_slug = :type AND fipe_brand_code = :brand_code AND fipe_model_code = :model_code
            ORDER BY name DESC
        ");
        $stmt->execute([
            'type'       => $typeSlug,
            'brand_code' => $brandCode,
            'model_code' => $modelCode
        ]);
        $cachedYears = $stmt->fetchAll();

        if (!empty($cachedYears)) {
            return [
                'source' => 'cache',
                'data'   => $cachedYears
            ];
        }

        // 2. Consultar API FIPE
        $url = "{$this->baseUrl}/{$typeSlug}/marcas/{$brandCode}/modelos/{$modelCode}/anos";
        $apiResult = $this->makeHttpRequest($url);

        if ($apiResult['success'] && is_array($apiResult['data'])) {
            // 3. Salvar no MySQL
            $this->db->beginTransaction();
            try {
                $insertStmt = $this->db->prepare("
                    INSERT INTO fipe_years (vehicle_type_slug, fipe_brand_code, fipe_model_code, fipe_year_code, name, cached_at)
                    VALUES (:type, :brand_code, :model_code, :year_code, :name, NOW())
                    ON DUPLICATE KEY UPDATE name = VALUES(name), cached_at = NOW()
                ");

                foreach ($apiResult['data'] as $yearItem) {
                    $insertStmt->execute([
                        'type'       => $typeSlug,
                        'brand_code' => $brandCode,
                        'model_code' => $modelCode,
                        'year_code'  => (string)($yearItem['codigo'] ?? ''),
                        'name'       => (string)($yearItem['nome'] ?? '')
                    ]);
                }
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("Erro ao salvar anos FIPE no cache: " . $e->getMessage());
            }

            return [
                'source' => 'api',
                'data'   => $apiResult['data']
            ];
        }

        return [
            'source' => 'error',
            'data'   => [],
            'message' => 'Não foi possível carregar os anos FIPE'
        ];
    }

    /**
     * Obter Detalhes e Preço de Referência FIPE (com Cache MySQL e TTL)
     */
    public function getVehicleDetails(string $typeSlug, string $brandCode, string $modelCode, string $yearCode): array
    {
        $typeSlug = $this->mapVehicleType($typeSlug);
        $cacheKey = "fipe_details_{$typeSlug}_{$brandCode}_{$modelCode}_{$yearCode}";

        // 1. Verificar se existe cache válido (não expirado) no MySQL
        $stmt = $this->db->prepare("
            SELECT response_data, expires_at, cached_at
            FROM fipe_cache
            WHERE cache_key = :key AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['key' => $cacheKey]);
        $cachedRow = $stmt->fetch();

        if ($cachedRow) {
            $data = json_decode($cachedRow['response_data'], true);
            return [
                'source'     => 'cache',
                'data'       => $data,
                'cached_at'  => $cachedRow['cached_at'],
                'expires_at' => $cachedRow['expires_at']
            ];
        }

        // 2. Se não houver cache válido, consultar a API FIPE
        $url = "{$this->baseUrl}/{$typeSlug}/marcas/{$brandCode}/modelos/{$modelCode}/anos/{$yearCode}";
        $apiResult = $this->makeHttpRequest($url);

        if ($apiResult['success'] && is_array($apiResult['data'])) {
            $data = $apiResult['data'];
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->cacheTtlDays} days"));

            // 3. Salvar no Cache MySQL
            $saveStmt = $this->db->prepare("
                INSERT INTO fipe_cache (cache_key, response_data, cached_at, expires_at)
                VALUES (:key, :data, NOW(), :expires_at)
                ON DUPLICATE KEY UPDATE response_data = VALUES(response_data), cached_at = NOW(), expires_at = VALUES(expires_at)
            ");
            $saveStmt->execute([
                'key'        => $cacheKey,
                'data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
                'expires_at' => $expiresAt
            ]);

            // Salvar também na tabela estruturada fipe_vehicle_details se houver CodigoFipe
            if (!empty($data['CodigoFipe'])) {
                $cleanPrice = (float)str_replace(['R$', '.', ' '], '', str_replace(',', '.', $data['Valor'] ?? '0'));
                
                $detailsStmt = $this->db->prepare("
                    INSERT INTO fipe_vehicle_details (
                        fipe_code, brand, model, model_year, fuel, fipe_price,
                        reference_month, vehicle_type_slug, raw_json, cached_at, expires_at
                    ) VALUES (
                        :fipe_code, :brand, :model, :model_year, :fuel, :fipe_price,
                        :reference_month, :type_slug, :raw_json, NOW(), :expires_at
                    )
                ");
                $detailsStmt->execute([
                    'fipe_code'       => $data['CodigoFipe'],
                    'brand'           => $data['Marca'] ?? '',
                    'model'           => $data['Modelo'] ?? '',
                    'model_year'      => (int)($data['AnoModelo'] ?? 0),
                    'fuel'            => $data['Combustivel'] ?? '',
                    'fipe_price'      => $cleanPrice,
                    'reference_month' => $data['MesReferencia'] ?? '',
                    'type_slug'       => $typeSlug,
                    'raw_json'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'expires_at'      => $expiresAt
                ]);
            }

            return [
                'source'     => 'api',
                'data'       => $data,
                'cached_at'  => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ];
        }

        // 4. Se a API falhar, tentar utilizar o cache antigo expirado se disponível
        $expiredStmt = $this->db->prepare("
            SELECT response_data, cached_at, expires_at
            FROM fipe_cache
            WHERE cache_key = :key
            LIMIT 1
        ");
        $expiredStmt->execute(['key' => $cacheKey]);
        $fallbackRow = $expiredStmt->fetch();

        if ($fallbackRow) {
            return [
                'source'        => 'fallback_cache',
                'data'          => json_decode($fallbackRow['response_data'], true),
                'cached_at'     => $fallbackRow['cached_at'],
                'expires_at'    => $fallbackRow['expires_at'],
                'warning'       => 'API FIPE temporariamente indisponível. Exibindo dados em cache anterior.'
            ];
        }

        return [
            'source'  => 'error',
            'data'    => null,
            'message' => 'Falha ao consultar a API FIPE e nenhum cache anterior está disponível.'
        ];
    }

    /**
     * Método auxiliar para realizar requisições HTTP seguras via cURL
     */
    private function makeHttpRequest(string $url): array
    {
        $ch = curl_init();

        $headers = ['Accept: application/json'];
        if (!empty($this->apiToken) && $this->apiToken !== 'seu_token_aqui') {
            $headers[] = "Authorization: Bearer {$this->apiToken}";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'RODAX-Marketplace-PHP/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode < 200 || $httpCode >= 300) {
            error_log("RODAX FIPE cURL Error: Code {$httpCode}, URL: {$url}, Error: {$error}");
            return ['success' => false, 'code' => $httpCode, 'error' => $error];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'code' => $httpCode, 'error' => 'Resposta JSON inválida'];
        }

        return ['success' => true, 'code' => $httpCode, 'data' => $decoded];
    }
}
