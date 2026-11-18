<?php

namespace App\Models\Concerns;

use App\Contracts\EditorialReviewable;
use App\Models\ContentTranslation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasEditorialReview
{
    protected static function bootHasEditorialReview(): void
    {
        static::updating(function (Model $model): void {
            if (! $model instanceof EditorialReviewable) {
                return;
            }

            $fields = $model->editorialReviewableFields();

            if ($fields !== [] && $model->isDirty($fields)) {
                $model->forceFill([
                    'editorial_status' => 'pending',
                    'editorial_reviewed_by' => null,
                    'editorial_reviewed_at' => null,
                ]);

                if ($model instanceof ContentTranslation && $model->isDirty(['value', 'source_hash'])) {
                    $model->setAttribute('status', 'draft');
                }
            }
        });
    }

    /** @return array<int, string> */
    public function editorialReviewableFields(): array
    {
        return [];
    }

    /** @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeEditorialVisible(Builder $query): Builder
    {
        return $query->whereIn('editorial_status', ['approved', 'noindex']);
    }

    public function isEditorialApproved(): bool
    {
        return $this->getAttribute('editorial_status') === 'approved';
    }

    public function isEditorialVisible(): bool
    {
        return in_array($this->getAttribute('editorial_status'), ['approved', 'noindex'], true);
    }

    /** @return BelongsTo<User, $this> */
    public function editorialReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editorial_reviewed_by');
    }

    public function invalidateEditorialReview(): void
    {
        $attributes = [
            'editorial_status' => 'pending',
            'editorial_reviewed_by' => null,
            'editorial_reviewed_at' => null,
        ];
        $this->newQuery()->whereKey($this->getKey())->whereIn('editorial_status', ['approved', 'noindex'])->update($attributes);
        $this->forceFill($attributes);
    }

    public function approveEditorialReview(User $reviewer, ?string $notes = null): void
    {
        $attributes = [
            'editorial_status' => 'approved',
            'editorial_reviewed_by' => $reviewer->getKey(),
            'editorial_reviewed_at' => now(),
            'editorial_notes' => $notes,
        ];
        $this->newQuery()->whereKey($this->getKey())->update($attributes);
        $this->forceFill($attributes);
    }

    public function setEditorialDecision(string $decision, User $reviewer, ?string $notes = null): void
    {
        if (! in_array($decision, ['revise', 'unpublish', 'noindex', 'remove'], true)) {
            throw new \InvalidArgumentException('Unsupported editorial decision.');
        }

        if ($decision === 'noindex' && ! in_array('robots_index', $this->getFillable(), true)) {
            throw new \InvalidArgumentException('The noindex decision is only supported for resources with indexing controls.');
        }

        $attributes = [
            'editorial_status' => $decision,
            'editorial_reviewed_by' => $reviewer->getKey(),
            'editorial_reviewed_at' => now(),
            'editorial_notes' => $notes,
        ];

        if (in_array($decision, ['unpublish', 'remove'], true) && in_array('status', $this->getFillable(), true)) {
            $attributes['status'] = $decision === 'remove' ? 'archived' : 'draft';
        }

        if ($decision === 'noindex' && in_array('robots_index', $this->getFillable(), true)) {
            $attributes['robots_index'] = false;
        }

        if ($this instanceof ContentTranslation && $decision !== 'noindex') {
            $attributes['status'] = 'draft';
        }

        $this->newQuery()->whereKey($this->getKey())->update($attributes);
        $this->forceFill($attributes);
    }
}
