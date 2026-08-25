<?php

use App\Helpers\DocumentHelper;

describe('DocumentHelper', function () {
    it('should return just numbers characters', function () {
        expect(DocumentHelper::remove_pontuation('123'))->toBe('123');
        expect(DocumentHelper::remove_pontuation('qwe'))->toBe('');
        expect(DocumentHelper::remove_pontuation('123.456.789'))->toBe('123456789');
        expect(DocumentHelper::remove_pontuation('46.194.381/0001-98'))->toBe('46194381000198');
        expect(DocumentHelper::remove_pontuation('46194381000198'))->toBe('46194381000198');
    });
});
