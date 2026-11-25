<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class IndexQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    abstract protected function sortableColumns(): array;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function baseRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'string', Rule::in($this->sortableColumns())],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function searchTerm(): ?string
    {
        $raw = $this->query('search');
        if (! is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);

        return $trimmed === '' ? null : $trimmed;
    }

    public function searchPositiveInt(): ?int
    {
        $term = $this->searchTerm();
        if ($term === null) {
            return null;
        }

        $candidate = ltrim($term, '#');
        if ($candidate === '' || ! ctype_digit($candidate)) {
            return null;
        }

        $parsed = filter_var($candidate, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $parsed === false ? null : $parsed;
    }

    public function likePattern(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    public function sortBy(): ?string
    {
        $sortBy = $this->query('sort_by');

        return is_string($sortBy) && $sortBy !== '' ? $sortBy : null;
    }

    public function sortDirection(): string
    {
        return $this->query('sort_direction') === 'desc' ? 'desc' : 'asc';
    }

    public function perPage(): int
    {
        $perPage = $this->query('per_page');
        if ($perPage === null || $perPage === '') {
            return 15;
        }

        return (int) $perPage;
    }

    /**
     * Aplica ordenação determinística em colunas diretas do model quando `sort_by` é informado.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function applyOrderBy(Builder $query): void
    {
        $sortBy = $this->sortBy();
        if ($sortBy === null) {
            return;
        }

        $query->orderBy($sortBy, $this->sortDirection())->orderBy('id');
    }

    /**
     * Aplica busca por nome, descrição e número sequencial + ordenação para recursos simples (Marcas e Categorias).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function applyNamedResourceIndex(Builder $query): void
    {
        $term = $this->searchTerm();
        if ($term !== null) {
            $number = $this->searchPositiveInt();
            if (str_starts_with($term, '#') && $number !== null) {
                $query->where('number', $number);
            } else {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $q) use ($pattern, $number): void {
                    $q->where('name', 'ilike', $pattern)
                        ->orWhere('description', 'ilike', $pattern);

                    if ($number !== null) {
                        $q->orWhere('number', $number);
                    }
                });
            }
        }

        $this->applyOrderBy($query);
    }

    /**
     * Aplica filtro de status, busca cadastral e ordenação para parceiros (Clientes, Vendedores e Fornecedores).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function applyPartnerIndex(Builder $query): void
    {
        $status = $this->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $term = $this->searchTerm();
        if ($term !== null) {
            $number = $this->searchPositiveInt();
            if (str_starts_with($term, '#') && $number !== null) {
                $query->where('number', $number);
            } else {
                $pattern = $this->likePattern($term);
                $cleanDoc = preg_replace('/[^A-Za-z0-9]/', '', $term) ?? '';
                $digitsOnly = preg_replace('/\D/', '', $term) ?? '';
                $cleanDocPattern = $cleanDoc !== '' ? $this->likePattern($cleanDoc) : null;
                $digitsPattern = $digitsOnly !== '' ? $this->likePattern($digitsOnly) : null;

                $query->where(function (Builder $q) use ($pattern, $number, $cleanDocPattern, $digitsPattern): void {
                    $q->where('legal_name', 'ilike', $pattern)
                        ->orWhere('trade_name', 'ilike', $pattern)
                        ->orWhere('email', 'ilike', $pattern)
                        ->orWhere('phone', 'ilike', $pattern)
                        ->orWhere('document', 'ilike', $pattern);

                    if ($cleanDocPattern !== null) {
                        $q->orWhere('document', 'ilike', $cleanDocPattern);
                    }

                    if ($digitsPattern !== null) {
                        $q->orWhere('phone', 'like', $digitsPattern);
                    }

                    if ($number !== null) {
                        $q->orWhere('number', $number);
                    }
                });
            }
        }

        $this->applyOrderBy($query);
    }
}
