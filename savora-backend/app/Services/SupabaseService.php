<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SupabaseService
{
    private $supabaseUrl;
    private $supabaseKey;
    private $supabaseServiceKey;

    public function __construct()
    {
        $this->supabaseUrl = env('SUPABASE_URL');
        $this->supabaseKey = env('SUPABASE_KEY');
        $this->supabaseServiceKey = env('SUPABASE_SERVICE_KEY');
    }

    /**
     * Get base headers for Supabase REST API
     */
    private function getHeaders(bool $useServiceKey = false): array
    {
        $key = $useServiceKey ? $this->supabaseServiceKey : $this->supabaseKey;
        
        return [
            'apikey' => $key,
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ];
    }

    /**
     * Build filter string from filters array
     */
    private function buildFilters(array $filters): string
    {
        $query = '';
        foreach ($filters as $column => $value) {
            if (is_array($value)) {
                $operator = $value['operator'] ?? 'eq';

                if ($operator === 'in') {
                    $values = implode(',', array_map(function($v) {
                        return is_string($v) ? '"' . $v . '"' : $v;
                    }, $value['values']));
                    $query .= "&{$column}=in.({$values})";
                } else {
                    $val = $value['value'];
                    $query .= "&{$column}={$operator}.{$val}";
                }
            } else {
                $query .= "&{$column}=eq.{$value}";
            }
        }
        return $query;
    }

    /**
     * SELECT query
     */
    public function select(string $table, array $columns = ['*'], array $filters = [], array $options = [])
    {
        try {
            $columnsStr = implode(',', $columns);
            $url = "{$this->supabaseUrl}/rest/v1/{$table}?select={$columnsStr}";
            
            $url .= $this->buildFilters($filters);
            
            if (isset($options['order'])) {
                $url .= "&order={$options['order']}";
            }
            if (isset($options['limit'])) {
                $url .= "&limit={$options['limit']}";
            }
            if (isset($options['offset'])) {
                $url .= "&offset={$options['offset']}";
            }

            $response = Http::withHeaders($this->getHeaders())->get($url);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Supabase SELECT error: ' . $response->body());
                throw new Exception('Database query failed');
            }
        } catch (Exception $e) {
            Log::error('Supabase SELECT exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * INSERT query
     */
    public function insert(string $table, array $data, bool $useServiceKey = false)
    {
        try {
            $url = "{$this->supabaseUrl}/rest/v1/{$table}";

            $response = Http::withHeaders($this->getHeaders($useServiceKey))
                ->post($url, $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Supabase INSERT error: ' . $response->body());
                throw new Exception('Database insert failed: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Supabase INSERT exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * UPDATE query
     */
    public function update(string $table, array $data, array $filters, bool $useServiceKey = false)
    {
        try {
            $url = "{$this->supabaseUrl}/rest/v1/{$table}";

            $filterStr = $this->buildFilters($filters);
            if (!empty($filterStr)) {
                $url .= '?' . ltrim($filterStr, '&');
            }

            $response = Http::withHeaders($this->getHeaders($useServiceKey))
                ->patch($url, $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Supabase UPDATE error: ' . $response->body());
                throw new Exception('Database update failed');
            }
        } catch (Exception $e) {
            Log::error('Supabase UPDATE exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * DELETE query
     */
    public function delete(string $table, array $filters, bool $useServiceKey = false)
    {
        try {
            $url = "{$this->supabaseUrl}/rest/v1/{$table}";

            $filterStr = $this->buildFilters($filters);
            if (!empty($filterStr)) {
                $url .= '?' . ltrim($filterStr, '&');
            }

            $response = Http::withHeaders($this->getHeaders($useServiceKey))
                ->delete($url);

            if ($response->successful()) {
                return true;
            } else {
                Log::error('Supabase DELETE error: ' . $response->body());
                throw new Exception('Database delete failed');
            }
        } catch (Exception $e) {
            Log::error('Supabase DELETE exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * RPC (Remote Procedure Call)
     */
    public function rpc(string $functionName, array $params = [], bool $useServiceKey = false)
    {
        try {
            $url = "{$this->supabaseUrl}/rest/v1/rpc/{$functionName}";

            $response = Http::withHeaders($this->getHeaders($useServiceKey))
                ->post($url, $params);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Supabase RPC error: ' . $response->body());
                throw new Exception('RPC call failed');
            }
        } catch (Exception $e) {
            Log::error('Supabase RPC exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload file to Supabase Storage
     */
    public function uploadFile(string $bucket, string $path, $fileContent, string $contentType = 'image/jpeg')
    {
        try {
            $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";

            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
                'Content-Type' => $contentType,
            ])->send('POST', $url, [
                'body' => $fileContent
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Supabase Storage upload error: ' . $response->body());
                throw new Exception('File upload failed');
            }
        } catch (Exception $e) {
            Log::error('Supabase Storage exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get public URL for a file
     */
    public function getPublicUrl(string $bucket, string $path): string
    {
        return "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $bucket, string $path)
    {
        try {
            $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";

            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->delete($url);

            if ($response->successful()) {
                return true;
            } else {
                Log::error('Supabase Storage delete error: ' . $response->body());
                throw new Exception('File delete failed');
            }
        } catch (Exception $e) {
            Log::error('Supabase Storage delete exception: ' . $e->getMessage());
            throw $e;
        }
    }
}