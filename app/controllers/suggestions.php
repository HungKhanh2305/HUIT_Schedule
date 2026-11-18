<?php

require dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();
$subject = (int)($_GET['subject_id'] ?? 0);
$branch = (int)($_GET['branch_id'] ?? 0);
$day = (int)($_GET['day_of_week'] ?? 0);
$start = (int)($_GET['start_period'] ?? 0);
$end = (int)($_GET['end_period'] ?? 0);
$from = $_GET['start_date'] ?? '';
$to = $_GET['end_date'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$valid = static function ($d) {
    $x = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
    return $x && $x->format('Y-m-d') === $d;
};
if (!$subject || !$branch || !isset($days[$day]) || $start < 1 || $end > 15 || $end < $start || !$valid($from) || !$valid($to) || $from > $to) {
    echo '[]';
    exit;
}
$teachers = all($db, 'SELECT t.id,t.full_name FROM teachers t JOIN teacher_subjects ts ON ts.teacher_id=t.id WHERE ts.subject_id=?', [$subject]);
$schedules = all($db, 'SELECT * FROM schedules WHERE id<>? AND day_of_week=? AND start_date<=? AND end_date>=?', [$id,$day,$to,$from]);
$result = [];
foreach ($teachers as $t) {
    $score = 0;
    $reasons = [];
    $busy = false;
    foreach ($schedules as $s) {
        if ($s['teacher_id'] != $t['id'] || !occurrence(max($from, $s['start_date']), min($to, $s['end_date']), $day)) {
            continue;
        }if ($s['start_period'] <= $end && $s['end_period'] >= $start) {
            $busy = true;
            break;
        }$gap = max($start - $s['end_period'] - 1, $s['start_period'] - $end - 1);
        if ($s['branch_id'] == $branch) {
            $score += 10;
            $reasons[] = 'cùng cơ sở';
        } else {
            $travel = all($db, 'SELECT minutes FROM travel_times WHERE branch_from=? AND branch_to=?', [$branch,$s['branch_id']]);
            if (!$travel || $gap * 50 < (int)$travel[0]['minutes']) {
                $busy = true;
                break;
            }$score -= 5;
            $reasons[] = 'đủ thời gian di chuyển';
        }$score -= min($gap, 5);
        $reasons[] = $gap === 0 ? 'liền tiết' : 'trống '.$gap.' tiết';
    }if (!$busy) {
        $t['score'] = $score;
        $t['reason'] = $reasons ? implode(', ', array_unique($reasons)) : 'chưa có lịch cùng ngày';
        $result[] = $t;
    }
}
usort($result, fn ($a, $b) => $b['score'] <=> $a['score']);
echo json_encode($result,JSON_UNESCAPED_UNICODE);
