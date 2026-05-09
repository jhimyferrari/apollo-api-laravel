<?php

use App\Helpers\DocumentHelper;

describe('DocumentHelper', function () {
    it('should return just numbers characters', function () {
        expect(DocumentHelper::formatCpfAndCnpj('123'))->toBe('123');
        expect(DocumentHelper::formatCpfAndCnpj('qwe'))->toBe('');
        expect(DocumentHelper::formatCpfAndCnpj('123.456.789'))->toBe('123456789');
        expect(DocumentHelper::formatCpfAndCnpj('46.194.381/0001-98'))->toBe('46194381000198');
        expect(DocumentHelper::formatCpfAndCnpj('46194381000198'))->toBe('46194381000198');
    });
});
