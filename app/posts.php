<?php
declare(strict_types=1);
function published_post(int $id): array|false {
    $q=db()->prepare("SELECT id,payload,published_at FROM posts WHERE id=? AND status='published'");
    $q->execute([$id]);return $q->fetch();
}
function post_date(string $utc): string {
    return (new DateTimeImmutable($utc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Prague'))->format('j. n. Y');
}
function post_neighbour(array $post,bool $newer): array|false {
    $op=$newer?'>':'<';$order=$newer?'ASC':'DESC';
    $q=db()->prepare("SELECT id,payload FROM posts WHERE status='published' AND (published_at $op ? OR (published_at=? AND id $op ?)) ORDER BY published_at $order,id $order LIMIT 1");
    $q->execute([$post['published_at'],$post['published_at'],$post['id']]);return $q->fetch();
}
