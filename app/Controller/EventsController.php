<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository; // оставлен для совместимости, не обязателен
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use DomainException;

final class EventsController
{
    public function __construct(
        private EventRepository $events,
        private PDO $pdo
    ) {}

    private function tableCols(string $table): array {
        $st = $this->pdo->query("PRAGMA table_info($table)");
        $cols = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[$r['name']] = true;
        return $cols;
    }
    private function pick(array $cols, array $candidates): ?string {
        foreach ($candidates as $c) if (isset($cols[$c])) return $c;
        return null;
    }

    public function getAll(Request $request, Response $response): Response
    {
        $eCols = $this->tableCols('events');
        $cStart = $this->pick($eCols, ['start_time','startTime']);
        $cEnd   = $this->pick($eCols, ['end_time','endTime']);

        $select = "SELECT id, title, description, date, "
                . ($cStart ? "$cStart AS startTime" : "NULL AS startTime") . ", "
                . ($cEnd   ? "$cEnd   AS endTime"   : "NULL AS endTime") . ", "
                . (isset($eCols['status']) ? "status" : "'draft' AS status")
                . " FROM events ORDER BY date ASC, id ASC";
        $items = $this->pdo->query($select)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // pull stages
        $ids = array_column($items, 'id');
        $stagesMap = [];
        if ($ids) {
            $sCols = $this->tableCols('stages');
            $sStart = $this->pick($sCols, ['start_time','startTime']);
            $sEnd   = $this->pick($sCols, ['end_time','endTime']);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT event_id, title, description, "
                 . ($sStart ? "$sStart AS startTime" : "NULL AS startTime") . ", "
                 . ($sEnd   ? "$sEnd   AS endTime"   : "NULL AS endTime")
                 . " FROM stages WHERE event_id IN ($in) ORDER BY id ASC";
            $st = $this->pdo->prepare($sql);
            $st->execute($ids);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stagesMap[(int)$row['event_id']][] = [
                    'title'       => (string)($row['title'] ?? ''),
                    'description' => (string)($row['description'] ?? ''),
                    'startTime'   => $row['startTime'] ?? null,
                    'endTime'     => $row['endTime'] ?? null,
                ];
            }
        }
        foreach ($items as &$it) $it['stages'] = $stagesMap[(int)$it['id']] ?? [];

        $response->getBody()->write(json_encode(['items'=>$items], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type','application/json');
    }

    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $eCols = $this->tableCols('events');
        $cStart = $this->pick($eCols, ['start_time','startTime']);
        $cEnd   = $this->pick($eCols, ['end_time','endTime']);

        $sql = "SELECT id, title, description, date, "
             . ($cStart ? "$cStart AS startTime" : "NULL AS startTime") . ", "
             . ($cEnd   ? "$cEnd   AS endTime"   : "NULL AS endTime") . ", "
             . (isset($eCols['status']) ? "status" : "'draft' AS status")
             . " FROM events WHERE id = ?";
        $st = $this->pdo->prepare($sql); $st->execute([$id]);
        $event = $st->fetch(PDO::FETCH_ASSOC);
        if (!$event) return $response->withStatus(404)->withHeader('Content-Type','application/json')
            ->withBody((function(){ $s=fopen('php://temp','r+'); fwrite($s,'{"error":true,"message":"not_found"}'); rewind($s); return $s; })());

        $sCols = $this->tableCols('stages');
        $sStart = $this->pick($sCols, ['start_time','startTime']);
        $sEnd   = $this->pick($sCols, ['end_time','endTime']);
        $sqlS = "SELECT title, description, "
              . ($sStart ? "$sStart AS startTime" : "NULL AS startTime") . ", "
              . ($sEnd   ? "$sEnd   AS endTime"   : "NULL AS endTime")
              . " FROM stages WHERE event_id = ? ORDER BY id ASC";
        $st2 = $this->pdo->prepare($sqlS); $st2->execute([$id]);
        $event['stages'] = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $response->getBody()->write(json_encode($event, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type','application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $b = (array)$request->getParsedBody();
        $title = trim((string)($b['title'] ?? ''));
        $date  = (string)($b['date'] ?? '');
        if ($title === '' || $date === '') throw new DomainException('title_and_date_required', 422);

        $description = (string)($b['description'] ?? '');
        $startTime   = $b['startTime'] ?? null;
        $endTime     = $b['endTime'] ?? null;
        $status      = (string)($b['status'] ?? 'draft');
        $stagesIn    = is_array($b['stages'] ?? null) ? $b['stages'] : [];

        $eCols = $this->tableCols('events');
        $cStart = $this->pick($eCols, ['start_time','startTime']);
        $cEnd   = $this->pick($eCols, ['end_time','endTime']);

        $cols = ['title','description','date'];
        $vals = [':title',':description',':date'];
        $args = [':title'=>$title, ':description'=>$description, ':date'=>$date];

        if ($cStart) { $cols[] = $cStart; $vals[]=':start'; $args[':start']=$startTime; }
        if ($cEnd)   { $cols[] = $cEnd;   $vals[]=':end';   $args[':end']=$endTime; }
        if (isset($eCols['status'])) { $cols[]='status'; $vals[]=':status'; $args[':status']=$status; }

        $this->pdo->beginTransaction();
        try {
            $sql = 'INSERT INTO events ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')';
            $st = $this->pdo->prepare($sql); $st->execute($args);
            $eventId = (int)$this->pdo->lastInsertId();

            // stages (optional)
            if (!empty($stagesIn)) {
                $sCols = $this->tableCols('stages');
                $sStart = $this->pick($sCols, ['start_time','startTime']);
                $sEnd   = $this->pick($sCols, ['end_time','endTime']);

                $colsS = ['event_id','title','description'];
                $valsS = [':eid',':title',':desc'];
                if ($sStart) { $colsS[]=$sStart; $valsS[]=':s'; }
                if ($sEnd)   { $colsS[]=$sEnd;   $valsS[]=':e'; }

                $sqlS = 'INSERT INTO stages ('.implode(',',$colsS).') VALUES ('.implode(',',$valsS).')';
                $ins = $this->pdo->prepare($sqlS);

                foreach ($stagesIn as $s) {
                    $p = [
                        ':eid'   => $eventId,
                        ':title' => (string)($s['title'] ?? ''),
                        ':desc'  => (string)($s['description'] ?? ''),
                    ];
                    if ($sStart) $p[':s'] = $s['startTime'] ?? null;
                    if ($sEnd)   $p[':e'] = $s['endTime'] ?? null;
                    $ins->execute($p);
                }
            }

            $this->pdo->commit();
            $response->getBody()->write(json_encode(['id'=>$eventId], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $b = (array)$request->getParsedBody();
        $title = trim((string)($b['title'] ?? ''));
        $date  = (string)($b['date'] ?? '');
        if ($title === '' || $date === '') throw new DomainException('title_and_date_required', 422);

        $description = (string)($b['description'] ?? '');
        $startTime   = $b['startTime'] ?? null;
        $endTime     = $b['endTime'] ?? null;
        $status      = (string)($b['status'] ?? 'draft');
        $stagesIn    = is_array($b['stages'] ?? null) ? $b['stages'] : [];

        $eCols = $this->tableCols('events');
        $cStart = $this->pick($eCols, ['start_time','startTime']);
        $cEnd   = $this->pick($eCols, ['end_time','endTime']);

        $sets = ['title=:title','description=:description','date=:date'];
        $argsQ = [':id'=>$id, ':title'=>$title, ':description'=>$description, ':date'=>$date];
        if ($cStart) { $sets[]="$cStart=:start"; $argsQ[':start']=$startTime; }
        if ($cEnd)   { $sets[]="$cEnd=:end";     $argsQ[':end']=$endTime; }
        if (isset($eCols['status'])) { $sets[]="status=:status"; $argsQ[':status']=$status; }

        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE events SET '.implode(', ',$sets).' WHERE id=:id';
            $st = $this->pdo->prepare($sql); $st->execute($argsQ);

            // reset stages
            $this->pdo->prepare('DELETE FROM stages WHERE event_id = ?')->execute([$id]);

            if (!empty($stagesIn)) {
                $sCols = $this->tableCols('stages');
                $sStart = $this->pick($sCols, ['start_time','startTime']);
                $sEnd   = $this->pick($sCols, ['end_time','endTime']);

                $colsS = ['event_id','title','description'];
                $valsS = [':eid',':title',':desc'];
                if ($sStart) { $colsS[]=$sStart; $valsS[]=':s'; }
                if ($sEnd)   { $colsS[]=$sEnd;   $valsS[]=':e'; }

                $sqlS = 'INSERT INTO stages ('.implode(',',$colsS).') VALUES ('.implode(',',$valsS).')';
                $ins = $this->pdo->prepare($sqlS);

                foreach ($stagesIn as $s) {
                    $p = [
                        ':eid'   => $id,
                        ':title' => (string)($s['title'] ?? ''),
                        ':desc'  => (string)($s['description'] ?? ''),
                    ];
                    if ($sStart) $p[':s'] = $s['startTime'] ?? null;
                    if ($sEnd)   $p[':e'] = $s['endTime'] ?? null;
                    $ins->execute($p);
                }
            }

            $this->pdo->commit();
            $response->getBody()->write(json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM stages WHERE event_id = ?')->execute([$id]);
            $this->pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
            $this->pdo->commit();
            $response->getBody()->write(json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
{
    $id   = (int)$args['id'];
    $body = $request->getParsedBody();
    if (!is_array($body)) $body = [];

     $raw = $body['status'] ?? 'draft';
    $status = is_array($raw) ? (string)reset($raw) : (string)$raw;

    $stmt = $this->pdo->prepare('UPDATE events SET status = :s WHERE id = :i');
    $stmt->execute([':s' => $status, ':i' => $id]);

    $response->getBody()->write(json_encode(['ok' => true], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
}
}

