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

// Publication years and boundaries use the same Prague timezone as displayed dates.
function publication_years(int $currentYear): array {
    $years=[$currentYear=>$currentYear];
    foreach(db()->query("SELECT DISTINCT published_at FROM posts WHERE status='published'") as $row){
        $year=(int)(new DateTimeImmutable($row['published_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Prague'))->format('Y');
        $years[$year]=$year;
    }
    rsort($years,SORT_NUMERIC);return $years;
}
function archive_year(mixed $input,array $years,int $currentYear): ?int {
    if($input==='all')return null;
    if(!is_string($input)||!preg_match('/^[0-9]{4}$/D',$input)||!in_array((int)$input,$years,true))return $currentYear;
    return (int)$input;
}
function archive_posts(?int $year): array {
    $where="status='published'";$params=[];
    if($year!==null){
        $start=new DateTimeImmutable(sprintf('%04d-01-01 00:00:00',$year),new DateTimeZone('Europe/Prague'));
        $where.=' AND published_at>=? AND published_at<?';
        $params=[$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$start->modify('+1 year')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
    }
    $q=db()->prepare("SELECT id,payload,published_at FROM posts WHERE $where ORDER BY published_at DESC,id DESC");
    $q->execute($params);return $q->fetchAll();
}
