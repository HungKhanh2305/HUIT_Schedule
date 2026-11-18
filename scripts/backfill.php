<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit;
}
require dirname(__DIR__).'/app/bootstrap.php';
$db->beginTransaction();
try {
    $rows = all($db, 'SELECT * FROM schedules ORDER BY id');
    foreach ($rows as $s) {
        $room = all($db, 'SELECT id FROM rooms WHERE branch_id=? AND name=?', [$s['branch_id'],$s['room']]);
        if (!$room) {
            $db->prepare('INSERT INTO rooms(branch_id,name) VALUES(?,?)')->execute([$s['branch_id'],$s['room']]);
            $room = [['id' => $db->lastInsertId()]];
        }
        $subject = all($db, 'SELECT id FROM subjects WHERE name=? ORDER BY id LIMIT 1', [$s['subject_name']]);
        if (!$subject) {
            $code = 'MH-CU-'.$s['id'];
            $db->prepare('INSERT INTO subjects(code,name) VALUES(?,?)')->execute([$code,$s['subject_name']]);
            $subject = [['id' => $db->lastInsertId()]];
        }
        $db->prepare('INSERT IGNORE INTO teacher_subjects(teacher_id,subject_id) VALUES(?,?)')->execute([$s['teacher_id'],$subject[0]['id']]);
        $db->prepare('UPDATE schedules SET room_id=COALESCE(room_id,?),subject_id=COALESCE(subject_id,?) WHERE id=?')->execute([$room[0]['id'],$subject[0]['id'],$s['id']]);
    }
    $missing = all($db, 'SELECT MIN(start_date) AS first_date,MAX(end_date) AS last_date FROM schedules WHERE term_id IS NULL');
    if ($missing[0]['first_date']) {
        $db->prepare('INSERT INTO terms(name,start_date,end_date) VALUES(?,?,?)')->execute(['Lịch đã có',$missing[0]['first_date'],$missing[0]['last_date']]);
        $term = $db->lastInsertId();
        $db->prepare('UPDATE schedules SET term_id=?,class_code=COALESCE(class_code,CONCAT(\'LICH-CU-\',id)) WHERE term_id IS NULL')->execute([$term]);
    }
    $db->commit();
    echo "Đã bổ sung danh mục từ lịch hiện có. Hãy kiểm tra và chỉnh sửa mã lớp, học kỳ, chuyên môn nếu cần.\n";
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
