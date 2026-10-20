<?php

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\User;

/**
 * Editors (articles.edit-any) manage every article. Authors (articles.create)
 * manage only their own unpublished work and submit it for review.
 * Super Administrators pass every check through Gate::before.
 */
class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['articles.view', 'articles.create', 'articles.edit-any']);
    }

    public function viewAll(User $user): bool
    {
        return $user->canAny(['articles.view', 'articles.edit-any']);
    }

    public function create(User $user): bool
    {
        return $user->can('articles.create') || $user->can('articles.edit-any');
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->can('articles.edit-any')) {
            return true;
        }

        return $user->can('articles.create')
            && $this->owns($user, $article)
            && ! in_array($article->status, [PublicationStatus::Published, PublicationStatus::Scheduled], true);
    }

    public function delete(User $user, Article $article): bool
    {
        if ($user->can('articles.delete')) {
            return true;
        }

        return $user->can('articles.create')
            && $this->owns($user, $article)
            && $article->status === PublicationStatus::Draft;
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->can('articles.publish');
    }

    public function submit(User $user, Article $article): bool
    {
        return $this->update($user, $article);
    }

    public function changeAuthor(User $user): bool
    {
        return $user->can('articles.edit-any');
    }

    protected function owns(User $user, Article $article): bool
    {
        return $article->author_id === $user->id || $article->created_by === $user->id;
    }
}
