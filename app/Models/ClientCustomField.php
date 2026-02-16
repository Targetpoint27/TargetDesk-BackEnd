<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="ClientCustomField",
 *     title="Champ personnalisé client",
 *     description="Champ personnalisé dynamique pour un client",
 *     @OA\Property(property="id", type="integer", description="ID du champ"),
 *     @OA\Property(property="client_id", type="integer", description="ID du client"),
 *     @OA\Property(property="field_key", type="string", description="Clé du champ", maxLength=100),
 *     @OA\Property(property="field_value", type="string", description="Valeur du champ"),
 *     @OA\Property(property="field_type", type="string", enum={"text", "number", "email", "phone", "url", "textarea", "select", "date", "boolean"}, description="Type du champ"),
 *     @OA\Property(property="field_label", type="string", description="Libellé du champ", maxLength=200),
 *     @OA\Property(property="field_description", type="string", description="Description du champ"),
 *     @OA\Property(property="field_options", type="object", description="Options du champ (select, validation, etc.)"),
 *     @OA\Property(property="is_required", type="boolean", description="Champ requis"),
 *     @OA\Property(property="is_active", type="boolean", description="Champ actif"),
 *     @OA\Property(property="display_order", type="integer", description="Ordre d'affichage"),
 *     @OA\Property(property="created_by", type="integer", description="ID de l'utilisateur qui a créé"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de modification"),
 * )
 */
class ClientCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'field_key',
        'field_value',
        'field_type',
        'field_label',
        'field_description',
        'field_options',
        'is_required',
        'is_active',
        'display_order',
        'created_by'
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = [
        'formatted_value',
        'validation_rules'
    ];

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getFormattedValueAttribute(): mixed
    {
        return $this->formatValue($this->field_value, $this->field_type);
    }

    public function getValidationRulesAttribute(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($this->field_type) {
            case 'email':
                $rules[] = 'email';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'phone':
                $rules[] = 'string';
                $rules[] = 'max:20';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'select':
                if (!empty($this->field_options['options'])) {
                    $options = array_keys($this->field_options['options']);
                    $rules[] = 'in:' . implode(',', $options);
                }
                break;
            case 'textarea':
            case 'text':
            default:
                $rules[] = 'string';
                if (!empty($this->field_options['max_length'])) {
                    $rules[] = 'max:' . $this->field_options['max_length'];
                } else {
                    $rules[] = 'max:1000';
                }
                break;
        }

        // Règles personnalisées depuis field_options
        if (!empty($this->field_options['custom_rules'])) {
            $rules = array_merge($rules, $this->field_options['custom_rules']);
        }

        return $rules;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('field_key', $key);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('field_type', $type);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('field_key');
    }

    // Méthodes utilitaires
    public function formatValue($value, string $type): mixed
    {
        if (is_null($value)) {
            return null;
        }

        switch ($type) {
            case 'number':
                return is_numeric($value) ? (float) $value : $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'date':
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return $value;
                }
            case 'select':
                if (!empty($this->field_options['options'][$value])) {
                    return [
                        'value' => $value,
                        'label' => $this->field_options['options'][$value]
                    ];
                }
                return $value;
            default:
                return (string) $value;
        }
    }

    public function setValue($value): void
    {
        $this->field_value = $value;
    }

    public function getValue(): mixed
    {
        return $this->formatted_value;
    }

    // Méthodes statiques
    public static function getAvailableTypes(): array
    {
        return [
            'text' => 'Texte simple',
            'number' => 'Nombre',
            'email' => 'Email',
            'phone' => 'Téléphone',
            'url' => 'URL',
            'textarea' => 'Texte long',
            'select' => 'Liste déroulante',
            'date' => 'Date',
            'boolean' => 'Oui/Non'
        ];
    }

    public static function createField(int $clientId, string $key, $value, string $type = 'text', array $options = []): self
    {
        return self::create([
            'client_id' => $clientId,
            'field_key' => $key,
            'field_value' => $value,
            'field_type' => $type,
            'field_label' => $options['label'] ?? ucfirst($key),
            'field_description' => $options['description'] ?? null,
            'field_options' => $options['field_options'] ?? null,
            'is_required' => $options['is_required'] ?? false,
            'is_active' => $options['is_active'] ?? true,
            'display_order' => $options['display_order'] ?? 0,
            'created_by' => auth()->id()
        ]);
    }
}
