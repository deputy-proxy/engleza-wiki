<?php

namespace App\Contracts;

interface EditorialReviewable
{
    /** @return array<int, string> */
    public function editorialReviewableFields(): array;
}
