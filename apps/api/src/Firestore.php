<?php
declare(strict_types=1);

namespace ReFood;

// Acceso a Cloud Firestore por su API REST, sin librerías.
// Siempre se usa el token del usuario, así las reglas de seguridad se aplican también aquí.
final class Firestore
{
    private string $root; // projects/<id>/databases/(default)/documents
    private string $base; // URL completa hasta /documents

    public function __construct()
    {
        $project = Config::required('firebase_project_id');
        $this->root = 'projects/' . $project . '/databases/(default)/documents';
        $this->base = Config::firestoreUrl() . '/' . $this->root;
    }

    // ---------- Lecturas ----------

    public function get(string $collection, string $id, ?string $token): ?array
    {
        try {
            $raw = Remote::request('GET', $this->base . '/' . self::path($collection, $id), null, $token);
        } catch (ApiException $e) {
            if ($e->httpStatus === 404) {
                return null;
            }
            throw $e;
        }
        return self::document($raw);
    }

    /**
     * Consulta con filtros de igualdad, por ejemplo ['status' => 'PUBLISHED'].
     * Sin orderBy para no necesitar índices compuestos: el orden se hace en PHP.
     */
    public function query(string $collection, array $equals, ?string $token, int $limit = 200): array
    {
        $filters = [];
        foreach ($equals as $field => $value) {
            $filters[] = ['fieldFilter' => [
                'field' => ['fieldPath' => $field],
                'op' => 'EQUAL',
                'value' => self::value($value),
            ]];
        }
        $query = [
            'from' => [['collectionId' => $collection]],
            'limit' => max(1, min($limit, 500)),
        ];
        if (count($filters) === 1) {
            $query['where'] = $filters[0];
        } elseif (count($filters) > 1) {
            $query['where'] = ['compositeFilter' => ['op' => 'AND', 'filters' => $filters]];
        }
        $rows = Remote::request('POST', $this->base . ':runQuery', ['structuredQuery' => $query], $token);
        $documents = [];
        foreach ($rows as $row) {
            if (isset($row['document'])) {
                $documents[] = self::document($row['document']);
            }
        }
        return $documents;
    }

    // ---------- Escrituras sencillas ----------

    public function create(string $collection, string $id, array $data, string $token): array
    {
        $url = $this->base . '/' . $collection . '?documentId=' . rawurlencode(Validator::documentId($id));
        return self::document(Remote::request('POST', $url, ['fields' => self::fields($data)], $token));
    }

    // Cambia solo los campos de $data. Falla si el documento no existe.
    public function update(string $collection, string $id, array $data, string $token): array
    {
        $params = ['currentDocument.exists=true'];
        foreach (array_keys($data) as $field) {
            $params[] = 'updateMask.fieldPaths=' . rawurlencode((string) $field);
        }
        $url = $this->base . '/' . self::path($collection, $id) . '?' . implode('&', $params);
        return self::document(Remote::request('PATCH', $url, ['fields' => self::fields($data)], $token));
    }

    public function delete(string $collection, string $id, string $token): void
    {
        Remote::request('DELETE', $this->base . '/' . self::path($collection, $id), null, $token);
    }

    // ---------- Escrituras atómicas (todas o ninguna) ----------

    /** Ejecuta varias escrituras juntas con documents:commit. */
    public function commit(array $writes, string $token): void
    {
        Remote::request('POST', $this->base . ':commit', ['writes' => $writes], $token);
    }

    // Crea un documento completo y falla si ya existe (así se evita la doble reserva).
    public function createWrite(string $collection, string $id, array $data): array
    {
        return [
            'update' => ['name' => $this->name($collection, $id), 'fields' => self::fields($data)],
            'currentDocument' => ['exists' => false],
        ];
    }

    // Cambia solo los campos de $data en un documento que ya existe.
    public function updateWrite(string $collection, string $id, array $data): array
    {
        return [
            'update' => ['name' => $this->name($collection, $id), 'fields' => self::fields($data)],
            'updateMask' => ['fieldPaths' => array_map('strval', array_keys($data))],
            'currentDocument' => ['exists' => true],
        ];
    }

    public function deleteWrite(string $collection, string $id): array
    {
        return [
            'delete' => $this->name($collection, $id),
            'currentDocument' => ['exists' => true],
        ];
    }

    // Suma valores (por ejemplo kilos) sin leer primero. Si no existe, lo crea.
    public function incrementWrite(string $collection, string $id, array $set, array $increments): array
    {
        $transforms = [];
        foreach ($increments as $field => $amount) {
            $transforms[] = ['fieldPath' => $field, 'increment' => self::value($amount)];
        }
        return [
            'update' => ['name' => $this->name($collection, $id), 'fields' => self::fields($set)],
            'updateMask' => ['fieldPaths' => array_map('strval', array_keys($set))],
            'updateTransforms' => $transforms,
        ];
    }

    // ---------- Conversión entre PHP y el formato de Firestore ----------

    private function name(string $collection, string $id): string
    {
        return $this->root . '/' . self::path($collection, $id);
    }

    private static function path(string $collection, string $id): string
    {
        return $collection . '/' . Validator::documentId($id);
    }

    public static function value(mixed $value): array
    {
        return match (true) {
            $value === null => ['nullValue' => null],
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_string($value) => ['stringValue' => $value],
            is_array($value) && array_is_list($value) => ['arrayValue' => $value === []
                ? new \stdClass()
                : ['values' => array_map(self::value(...), $value)]],
            is_array($value) => ['mapValue' => ['fields' => self::fields($value)]],
            default => throw new ApiException('Tipo de dato no soportado.', 500),
        };
    }

    public static function fields(array $data): array|\stdClass
    {
        if ($data === []) {
            return new \stdClass();
        }
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[(string) $key] = self::value($value);
        }
        return $fields;
    }

    public static function decode(array $value): mixed
    {
        return match (true) {
            array_key_exists('stringValue', $value) => $value['stringValue'],
            array_key_exists('integerValue', $value) => (int) $value['integerValue'],
            array_key_exists('doubleValue', $value) => (float) $value['doubleValue'],
            array_key_exists('booleanValue', $value) => (bool) $value['booleanValue'],
            array_key_exists('timestampValue', $value) => $value['timestampValue'],
            array_key_exists('arrayValue', $value) => array_map(
                self::decode(...),
                $value['arrayValue']['values'] ?? []
            ),
            array_key_exists('mapValue', $value) => self::decodeFields($value['mapValue']['fields'] ?? []),
            default => null,
        };
    }

    public static function decodeFields(array $fields): array
    {
        $data = [];
        foreach ($fields as $key => $value) {
            $data[$key] = self::decode($value);
        }
        return $data;
    }

    private static function document(array $raw): array
    {
        $data = self::decodeFields($raw['fields'] ?? []);
        $data['id'] = basename((string) ($raw['name'] ?? ''));
        return $data;
    }
}
