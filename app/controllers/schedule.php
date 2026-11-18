<?php

require dirname(__DIR__).'/bootstrap.php';
$error = '';
$user = $_SESSION['user'] ?? null;
$admin = ($user['role'] ?? '') === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'teacher';
        $table = $role === 'admin' ? 'admins' : 'teachers';
        $field = $role === 'admin' ? 'username' : 'email';
        $a = all($db, "SELECT * FROM $table WHERE $field=?", [trim($_POST['identity'] ?? '')]);
        if ($a && password_verify($_POST['password'] ?? '', $a[0]['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['role' => $role,'id' => $a[0]['id'],'name' => $role === 'admin' ? $a[0]['username'] : $a[0]['full_name']];
            go();
        }$error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    } elseif ($action === 'logout') {
        session_destroy();
        go();
    } elseif ($action === 'password' && $user) {
        $table = $admin ? 'admins' : 'teachers';
        $a = all($db, "SELECT password_hash FROM $table WHERE id=?", [$user['id']]);
        $new = $_POST['new_password'] ?? '';
        if (!$a || !password_verify($_POST['old_password'] ?? '', $a[0]['password_hash'])) {
            $error = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($new) < 8) {
            $error = 'Mật khẩu mới cần ít nhất 8 ký tự.';
        } else {
            $db->prepare("UPDATE $table SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT),$user['id']]);
            $_SESSION['notice'] = 'Đã đổi mật khẩu.';
            go();
        }
    } elseif (!$admin) {
        http_response_code(403);
        exit('Bạn không có quyền thực hiện thao tác này.');
    } elseif ($action === 'delete') {
        $db->prepare('DELETE FROM schedules WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        $_SESSION['notice'] = 'Đã xóa lịch.';
        go();
    } elseif ($action === 'exception') {
        $id = (int)($_POST['id'] ?? 0);
        $date = $_POST['occurrence_date'] ?? '';
        $note = trim($_POST['note'] ?? '');
        $s = all($db, 'SELECT * FROM schedules WHERE id=?', [$id]);
        $v = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$s || !$v || $v->format('Y-m-d') !== $date || $date < $s[0]['start_date'] || $date > $s[0]['end_date'] || !occurrence($date, $date, (int)$s[0]['day_of_week']) || !$note) {
            $error = 'Ngày nghỉ phải trùng một buổi học và có lý do.';
        } else {
            $db->prepare('INSERT INTO schedule_exceptions(schedule_id,occurrence_date,note) VALUES(?,?,?) ON DUPLICATE KEY UPDATE note=VALUES(note)')->execute([$id,$date,$note]);
            $_SESSION['notice'] = 'Đã đánh dấu buổi nghỉ.';
            go();
        }
    } elseif ($action === 'restore') {
        $db->prepare('DELETE FROM schedule_exceptions WHERE id=?')->execute([(int)($_POST['exception_id'] ?? 0)]);
        go();
    } elseif ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $teacher = (int)($_POST['teacher_id'] ?? 0);
        $branch = (int)($_POST['branch_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $term = (int)($_POST['term_id'] ?? 0);
        $class = trim($_POST['class_code'] ?? '');
        $day = (int)($_POST['day_of_week'] ?? 0);
        $start = (int)($_POST['start_period'] ?? 0);
        $end = (int)($_POST['end_period'] ?? 0);
        $from = $_POST['start_date'] ?? '';
        $to = $_POST['end_date'] ?? '';
        $room = all($db, 'SELECT * FROM rooms WHERE id=? AND branch_id=?', [$roomId,$branch]);
        $subject = all($db, 'SELECT * FROM subjects WHERE id=?', [$subjectId]);
        $termRow = all($db, 'SELECT * FROM terms WHERE id=?', [$term]);
        $teacherRow = all($db, 'SELECT id FROM teachers WHERE id=?', [$teacher]);
        $valid = static function ($d) {
            $v = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
            return $v && $v->format('Y-m-d') === $d;
        };
        if (!$room || !$subject || !$termRow || !$teacherRow || !$class || mb_strlen($class) > 80 || !isset($days[$day]) || $start < 1 || $end > 15 || $end < $start || !$valid($from) || !$valid($to) || $to < $from || $from < $termRow[0]['start_date'] || $to > $termRow[0]['end_date'] || !occurrence($from, $to, $day)) {
            $error = 'Kiểm tra học phần, giảng viên, phòng, lớp và ngày. Lịch phải nằm trong học kỳ và có ít nhất một buổi học.';
        } else {
            $existing = all($db, 'SELECT s.*,b.branch_name FROM schedules s JOIN branches b ON b.id=s.branch_id WHERE s.id<>? AND s.day_of_week=? AND s.start_date<=? AND s.end_date>=?', [$id,$day,$to,$from]);
            foreach ($existing as $s) {
                if (!occurrence(max($from, $s['start_date']), min($to, $s['end_date']), $day)) {
                    continue;
                }$overlap = $s['start_period'] <= $end && $s['end_period'] >= $start;
                if ($overlap && ($s['teacher_id'] == $teacher || ($s['branch_id'] == $branch && mb_strtolower(trim($s['room'])) === mb_strtolower(trim($room[0]['name']))) || ($s['term_id'] == $term && mb_strtolower(trim($s['class_code'] ?? '')) === mb_strtolower($class)))) {
                    $error = 'Trùng với '.$s['subject_name'].' ('.$days[$day].', tiết '.$s['start_period'].'–'.$s['end_period'].', '.$s['branch_name'].'). Kiểm tra giảng viên, phòng hoặc lớp.';
                    break;
                }if ($s['teacher_id'] == $teacher && $s['branch_id'] != $branch && !$overlap) {
                    $travel = all($db, 'SELECT minutes FROM travel_times WHERE branch_from=? AND branch_to=?', [$branch,$s['branch_id']]);
                    if (!$travel) {
                        $error = 'Chưa cấu hình thời gian di chuyển đến '.$s['branch_name'].'. Vào Danh mục → Thời gian di chuyển.';
                        break;
                    }$gap = max($start - $s['end_period'] - 1, $s['start_period'] - $end - 1) * 50;
                    if ($gap < (int)$travel[0]['minutes']) {
                        $error = 'Không đủ thời gian di chuyển đến '.$s['branch_name'].' (cần '.$travel[0]['minutes'].' phút, có khoảng '.$gap.' phút).';
                        break;
                    }
                }
            }if (!$error) {
                $params = [$teacher,$branch,$subject[0]['name'],$room[0]['name'],$day,$start,$end,$from,$to,$subjectId,$term,$roomId,$class];
                if ($id) {
                    $params[] = $id;
                    $db->prepare('UPDATE schedules SET teacher_id=?,branch_id=?,subject_name=?,room=?,day_of_week=?,start_period=?,end_period=?,start_date=?,end_date=?,subject_id=?,term_id=?,room_id=?,class_code=? WHERE id=?')->execute($params);
                } else {
                    $db->prepare('INSERT INTO schedules(teacher_id,branch_id,subject_name,room,day_of_week,start_period,end_period,start_date,end_date,subject_id,term_id,room_id,class_code) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);
                }$_SESSION['notice'] = 'Đã lưu lịch giảng dạy.';
                go();
            }
        }
    }
}
$branches = all($db, 'SELECT * FROM branches ORDER BY branch_name');
$teachers = all($db, 'SELECT id,full_name,expertise FROM teachers ORDER BY full_name');
$rooms = all($db, 'SELECT rooms.*,branches.branch_name FROM rooms JOIN branches ON branches.id=rooms.branch_id ORDER BY branches.branch_name,rooms.name');
$subjects = all($db, 'SELECT * FROM subjects ORDER BY name');
$terms = all($db, 'SELECT * FROM terms ORDER BY start_date DESC');
$edit = [];
if ($admin && isset($_GET['edit'])) {
    $edit = all($db, 'SELECT * FROM schedules WHERE id=?', [(int)$_GET['edit']])[0] ?? [];
}if ($error && ($_POST['action'] ?? '') === 'save') {
    $edit = $_POST;
}
$date = $_GET['date'] ?? date('Y-m-d');
$d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $d = new DateTimeImmutable('today');
}$week = $d->modify('monday this week');
$view = ($_GET['view'] ?? 'week') === 'month' ? 'month' : 'week';
$begin = $view === 'month' ? $d->modify('first day of this month') : $week;
$finish = $view === 'month' ? $d->modify('last day of this month') : $week->modify('+6 days');
$filters = ['s.start_date<=?','s.end_date>=?'];
$args = [$finish->format('Y-m-d'),$begin->format('Y-m-d')];
if (!$admin && $user) {
    $filters[] = 's.teacher_id=?';
    $args[] = $user['id'];
}if ($admin && !empty($_GET['teacher'])) {
    $filters[] = 's.teacher_id=?';
    $args[] = (int)$_GET['teacher'];
}if ($admin && !empty($_GET['branch'])) {
    $filters[] = 's.branch_id=?';
    $args[] = (int)$_GET['branch'];
}if (!empty($_GET['term'])) {
    $filters[] = 's.term_id=?';
    $args[] = (int)$_GET['term'];
}$schedules = $user ? all($db, 'SELECT s.*,t.full_name,b.branch_name,b.address FROM schedules s JOIN teachers t ON t.id=s.teacher_id JOIN branches b ON b.id=s.branch_id WHERE '.implode(' AND ', $filters).' ORDER BY s.day_of_week,s.start_period', $args) : [];
$schedules = array_values(array_filter($schedules, fn ($s) => occurrence(max($begin->format('Y-m-d'), $s['start_date']), min($finish->format('Y-m-d'), $s['end_date']), (int)$s['day_of_week'])));
$exceptions = $user ? all($db, 'SELECT * FROM schedule_exceptions WHERE occurrence_date BETWEEN ? AND ?', [$begin->format('Y-m-d'),$finish->format('Y-m-d')]) : [];
$exceptionMap = [];
foreach ($exceptions as $x) {
    $exceptionMap[$x['schedule_id'].'/'.$x['occurrence_date']] = $x;
}
function sel($a, $b): string
{
    return (string)$a === (string)$b ? 'selected' : '';
}function dateLink(DateTimeImmutable $d): string
{
    $q = $_GET;
    $q['date'] = $d->format('Y-m-d');
    return '?'.http_build_query($q);
}

require dirname(__DIR__).'/views/schedule/index.php';
