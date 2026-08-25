
<?php

use App\Actions\Treatment\TreatDocument;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Helpers\DocumentHelper;
use App\Models\Seller;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TreatDocument', function () {
    it('throw an error when pass an empty document', function () {
        expect(fn () => app(TreatDocument::class)->execute(new Seller, 'document', '', null))
            ->toThrow(InvalidFieldException::class);
    });

    it('throw an error when pass an invalid document', function () {
        expect(fn () => app(TreatDocument::class)->execute(new Supplier, 'document', '111111111', null))
            ->toThrow(InvalidFieldException::class);
    });

    it('should remove pontuation and return a clean document', function () {
        $document = fake()->cnpj(true);

        $result = app(TreatDocument::class)->execute(new Seller, 'document', $document, null);

        expect($result)->toBe(DocumentHelper::remove_pontuation($document));
    });

    it('should return a clean document when it does not have pontuation', function () {
        $document = fake()->cpf(false);

        $result = app(TreatDocument::class)->execute(new Seller, 'document', $document, null);

        expect($result)->toBe($document);
    });

    it('throw an error when the document already exists', function () {
        $seller = Seller::factory()->create();

        expect(fn () => app(TreatDocument::class)->execute(new Seller, 'document', $seller->document, null))
            ->toThrow(DuplicateFieldException::class);
    });

    it('should not throw when passing the same document as before, ignoring its own id', function () {
        $seller = Seller::factory()->create();

        $result = app(TreatDocument::class)->execute(new Seller, 'document', $seller->document, $seller->id);

        expect($result)->toBe($seller->document);
    });

    it('throw an error when the document already exists within the same organization', function () {
        $seller = Seller::factory()->create();

        expect(fn () => app(TreatDocument::class)->execute(
            new Seller,
            'document',
            $seller->document,
            null,
            $seller->organization_id
        ))->toThrow(DuplicateFieldException::class);
    });
});
