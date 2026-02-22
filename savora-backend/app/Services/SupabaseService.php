<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SupabaseService
{
    private string $supabaseUrl;
    private string $supabaseKey;
    private string $supabaseServiceKey;

    public function __construct()
    {
        $this->supabaseUrl        = env('SUPABASE_URL');
        $this->supabaseKey        = env('SUPABASE_KEY');
        $this->supabaseServiceKey = env('SUPABASE_SERVICE_KEY');
    }

    // ─────────────────────────────────────────────
    // HEADERS
    // Default: service key (bypass RLS) untuk server-side requests
    // Set $useServiceKey = false hanya jika perlu enforce RLS per-user
    // ─────────────────────────────────────────────
    private function getHeaders(bool $useServiceKey = true): array
    {
        $key = $useServiceKey ? $this->supabaseServiceKey : $this->supabaseKey;

        return [
            'apikey'        => $key,
            'Authorization' => 'Bearer ' . $key,
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation',
        ];
    }

    // ─────────────────────────────────────────────
    // BUILD FILTER STRING
    // ─────────────────────────────────────────────
    private function buildFilters(array $filters): string
    {
        $query = '';

        foreach ($filters as $column => $value) {
            if (is_array($value)) {
                $operator = $value['operator'] ?? 'eq';

                if ($operator === 'in') {
                    $values = implode(',', array_map(function ($v) {
                        return is_string($v) ? '"' . $v . '"' : $v;
                    }, $value['values']));
                    $query .= "&{$column}=in.({$values})";
                } elseif ($operator === 'like') {
                    $val    = $value['value'];
                    $query .= "&{$column}=like.{$val}";
                } elseif ($operator === 'ilike') {
                    $val    = $value['value'];
                    $query .= "&{$column}=ilike.{$val}";
                } elseif ($operator === 'is') {
                    $val    = $value['value'];
                    $query .= "&{$column}=is.{$val}";
                } else {
                    $val    = $value['value'];
                    $query .= "&{$column}={$operator}.{$val}";
                }
            } else {
                $query .= "&{$column}=eq.{$value}";
            }
        }

        return $query;
    }

    // ─────────────────────────────────────────────
    // SELECT
    // ─────────────────────────────────────────────
    public function select(
        string $table,
        array $columns = ['*'],
        array $filters = [],
        array $options = [],
        bool $useServiceKey = true
    ): array {
        try {
            $columnsStr = implode(',', $columns);
            $url        = "{$this->supabaseUrl}/rest/v1/{$table}?select={$columnsStr}";

            $url .= $this->buildFilters($filters);

            if (isset($options['order']))  $url .= "&order={$options['order']}";
            if (isset($options['limit']))  $url .= "&limit={$options['limit']}";
            if (isset($options['offset'])) $url .= "&offset={$options['offset']}";

            $response = Http::withHeaders($this->getHeaders($useServiceKey))->get($url);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Supabase SELECT error [{$table}]: " . $response->body());
            throw new Exception('Database query failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase SELECT exception [{$table}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // COUNT
    // ─────────────────────────────────────────────
    public function count(
        string $table,
        array $filters = [],
        bool $useServiceKey = true
    ): int {
        try {
            $url = "{$this->supabaseUrl}/rest/v1/{$table}?select=id";
            $url .= $this->buildFilters($filters);

            $response = Http::withHeaders(array_merge(
                $this->getHeaders($useServiceKey),
                ['Prefer' => 'count=exact']
            ))->head($url);

            if ($response->successful()) {
                return (int) ($response->header('Content-Range')
                    ? explode('/', $response->header('Content-Range'))[1] ?? 0
                    : 0);
            }

            Log::error("Supabase COUNT error [{$table}]: " . $response->body());
            throw new Exception('Database count failed');

        } catch (Exception $e) {
            Log::error("Supabase COUNT exception [{$table}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // INSERT
    // ─────────────────────────────────────────────
    public function insert(
        string $table,
        array $data,
        bool $useServiceKey = true
    ): array {
        try {
            $url      = "{$this->supabaseUrl}/rest/v1/{$table}";
            $response = Http::withHeaders($this->getHeaders($useServiceKey))->post($url, $data);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Supabase INSERT error [{$table}]: " . $response->body());
            throw new Exception('Database insert failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase INSERT exception [{$table}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // UPSERT
    // ─────────────────────────────────────────────
    public function upsert(
        string $table,
        array $data,
        string $onConflict = 'id',
        bool $useServiceKey = true
    ): array {
        try {
            $url = "{$this->supabaseUrl}/rest/v1/{$table}";

            $response = Http::withHeaders(array_merge(
                $this->getHeaders($useServiceKey),
                ['Prefer' => "resolution=merge-duplicates,return=representation"]
            ))->post($url . "?on_conflict={$onConflict}", $data);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Supabase UPSERT error [{$table}]: " . $response->body());
            throw new Exception('Database upsert failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase UPSERT exception [{$table}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────
    public function update(
        string $table,
        array $data,
        array $filters,
        bool $useServiceKey = true
    ): array {
        try {
            $url       = "{$this->supabaseUrl}/rest/v1/{$table}";
            $filterStr = $this->buildFilters($filters);

            if (!empty($filterStr)) {
                $url .= '?' . ltrim($filterStr, '&');
            }

            $response = Http::withHeaders($this->getHeaders($useServiceKey))->patch($url, $data);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Supabase UPDATE error [{$table}]: " . $response->body());
            throw new Exception('Database update failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase UPDATE exception [{$table}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────
    public function delete(
        string $table,
        array $filters,
        bool $useServiceKey = true
    ): bool {
        try {
            $url       = "{$this->supabaseUrl}/rest/v1/{$table}";
            $filterStr = $this->buildFilters($filters);

            if (!empty($filterStr)) {
                $url .= '?' . ltrim($filterStr, '&');
            }

            $response = Http::withHeaders($this->getHeaders($useServiceKey))->delete($url);

            if ($response->successful()) {
                return true;
            }

            Log::error("Supabase DELETE error [{$table}]: " . $response->body());
            throw new Exception('Database delete failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase DELETE exception [{$table}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // RPC (Remote Procedure Call)
    // ─────────────────────────────────────────────
    public function rpc(
        string $functionName,
        array $params = [],
        bool $useServiceKey = true
    ): mixed {
        try {
            $url      = "{$this->supabaseUrl}/rest/v1/rpc/{$functionName}";
            $response = Http::withHeaders($this->getHeaders($useServiceKey))->post($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Supabase RPC error [{$functionName}]: " . $response->body());
            throw new Exception('RPC call failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase RPC exception [{$functionName}]: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // STORAGE — UPLOAD FILE
    // ─────────────────────────────────────────────
    public function uploadFile(
        string $bucket,
        string $path,
        mixed $fileContent,
        string $contentType = 'image/jpeg'
    ): array {
        try {
            $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";

            $response = Http::withHeaders([
                'apikey'        => $this->supabaseServiceKey,
                'Authorization' => 'Bearer ' . $this->supabaseServiceKey,
                'Content-Type'  => $contentType,
            ])->send('POST', $url, ['body' => $fileContent]);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Supabase Storage upload error [{$bucket}/{$path}]: " . $response->body());
            throw new Exception('File upload failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase Storage upload exception: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // STORAGE — GET PUBLIC URL
    // ─────────────────────────────────────────────
    public function getPublicUrl(string $bucket, string $path): string
    {
        return "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
    }

    // ─────────────────────────────────────────────
    // STORAGE — DELETE FILE
    // ─────────────────────────────────────────────
    public function deleteFile(string $bucket, string $path): bool
    {
        try {
            $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";

            $response = Http::withHeaders([
                'apikey'        => $this->supabaseServiceKey,
                'Authorization' => 'Bearer ' . $this->supabaseServiceKey,
            ])->delete($url);

            if ($response->successful()) {
                return true;
            }

            Log::error("Supabase Storage delete error [{$bucket}/{$path}]: " . $response->body());
            throw new Exception('File delete failed: ' . $response->body());

        } catch (Exception $e) {
            Log::error("Supabase Storage delete exception: " . $e->getMessage());
            throw $e;
        }
    }
}