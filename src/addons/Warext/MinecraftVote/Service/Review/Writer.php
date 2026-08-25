<?php

namespace Warext\MinecraftVote\Service\Review;

use Warext\MinecraftVote\Entity\Review;
use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

class Writer extends AbstractService
{
    protected Server $server;
    protected User $user;

    public function __construct(App $app, Server $server, User $user)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->user = $user;
    }

    public function save(array $input): Review
    {
        if (!$this->user->user_id)
        {
            throw new PrintableException('Değerlendirme yapabilmek için giriş yapmanız gerekiyor.');
        }

        if ($this->server->state !== 'active')
        {
            throw new PrintableException('Bu sunucu şu anda değerlendirme kabul etmiyor.');
        }

        if ($this->server->owner_user_id === $this->user->user_id)
        {
            throw new PrintableException('Kendi sunucunuzu değerlendiremezsiniz.');
        }

        $rating = (int)($input['rating'] ?? 0);
        if ($rating < 1 || $rating > 5)
        {
            throw new PrintableException('Genel puan 1-5 arasında olmalıdır.');
        }

        $message = trim((string)($input['message'] ?? ''));
        if ($message !== '' && mb_strlen($message) < 10)
        {
            throw new PrintableException('Yorum yazacaksanız en az 10 karakter olmalıdır.');
        }
        if (mb_strlen($message) > 2000)
        {
            throw new PrintableException('Değerlendirme yorumu en fazla 2000 karakter olabilir.');
        }

        $repo = $this->repository('Warext\MinecraftVote:Review');
        $review = $repo->getUserReview($this->server->server_id, $this->user->user_id);

        if (!$review)
        {
            $review = $this->em()->create('Warext\MinecraftVote:Review');
            $review->server_id = $this->server->server_id;
            $review->user_id = $this->user->user_id;
        }

        $review->rating = $rating;
        $review->gameplay_rating = $this->normalizeOptionalRating($input['gameplay_rating'] ?? 0);
        $review->staff_rating = $this->normalizeOptionalRating($input['staff_rating'] ?? 0);
        $review->performance_rating = $this->normalizeOptionalRating($input['performance_rating'] ?? 0);
        $review->community_rating = $this->normalizeOptionalRating($input['community_rating'] ?? 0);
        $review->originality_rating = $this->normalizeOptionalRating($input['originality_rating'] ?? 0);
        $review->message = $message;
        $review->is_verified_player = $this->hasVerifiedMinecraftAccount();
        $review->state = 'visible';
        $review->save();

        $repo->rebuildServerRating($this->server);

        return $review;
    }

    public function delete(): bool
    {
        $review = $this->repository('Warext\MinecraftVote:Review')
            ->getUserReview($this->server->server_id, $this->user->user_id);
        if (!$review)
        {
            return false;
        }

        $review->delete();
        $this->repository('Warext\MinecraftVote:Review')->rebuildServerRating($this->server);
        return true;
    }

    protected function normalizeOptionalRating(mixed $value): int
    {
        $rating = (int)$value;
        return ($rating >= 1 && $rating <= 5) ? $rating : 0;
    }

    protected function hasVerifiedMinecraftAccount(): bool
    {
        return (bool)$this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('user_id', $this->user->user_id)
            ->where('verification_state', 'verified')
            ->fetchOne();
    }
}
